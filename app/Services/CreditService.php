<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCredit;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreditService
{
    /**
     * Get user's current credit balance.
     */
    public function getBalance(User $user): int
    {
        $creditAccount = $this->getOrCreateCreditAccount($user);
        return $creditAccount->balance;
    }

    /**
     * Add credits to user account.
     *
     * @throws \Exception
     */
    public function addCredits(
        User $user,
        int $amount,
        string $reason,
        ?string $referenceId = null,
        ?array $metadata = null
    ): CreditTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        $transaction = DB::transaction(function () use ($user, $amount, $reason, $referenceId, $metadata) {
            // Lock credit account for update to prevent race conditions
            $creditAccount = UserCredit::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$creditAccount) {
                $creditAccount = $this->createCreditAccount($user);
            }

            // Increment balance
            $creditAccount->incrementBalance($amount);

            // Create transaction record
            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => CreditTransaction::TYPE_CREDIT,
                'reason' => $reason,
                'reference_id' => $referenceId ?? Str::uuid()->toString(),
                'metadata' => $metadata,
            ]);

            return $transaction;
        });

        // Log after transaction completes (don't fail transaction if log fails)
        try {
            Log::info('Credits added', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $this->getBalance($user),
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            // Silently fail if log fails - transaction already committed
        }

        return $transaction;
    }

    /**
     * Deduct credits from user account.
     *
     * @throws \Exception
     */
    public function deductCredits(
        User $user,
        int $amount,
        string $reason,
        ?string $referenceId = null,
        ?array $metadata = null
    ): CreditTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        $transaction = DB::transaction(function () use ($user, $amount, $reason, $referenceId, $metadata) {
            // Lock credit account for update to prevent race conditions
            $creditAccount = UserCredit::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$creditAccount) {
                throw new \Exception('User has no credit account');
            }

            // Check sufficient balance
            if (!$creditAccount->hasSufficientBalance($amount)) {
                throw new \Exception('Insufficient credit balance');
            }

            // Decrement balance
            $creditAccount->decrementBalance($amount);

            // Create transaction record (negative amount)
            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => CreditTransaction::TYPE_DEBIT,
                'reason' => $reason,
                'reference_id' => $referenceId ?? Str::uuid()->toString(),
                'metadata' => $metadata,
            ]);

            return $transaction;
        });

        // Log after transaction completes
        try {
            Log::info('Credits deducted', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $this->getBalance($user),
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            // Silently fail if log fails
        }

        return $transaction;
    }

    /**
     * Transfer credits between users.
     *
     * @throws \Exception
     */
    public function transferCredits(
        User $from,
        User $to,
        int $amount,
        string $reason,
        ?string $referenceId = null,
        ?array $metadata = null
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Cannot transfer credits to yourself');
        }

        $result = DB::transaction(function () use ($from, $to, $amount, $reason, $referenceId, $metadata) {
            // Lock both accounts (ordered by ID to prevent deadlocks)
            $accounts = UserCredit::whereIn('user_id', [$from->id, $to->id])
                ->orderBy('user_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('user_id');

            $fromAccount = $accounts->get($from->id);
            $toAccount = $accounts->get($to->id);

            if (!$fromAccount) {
                throw new \Exception('Sender has no credit account');
            }

            if (!$toAccount) {
                $toAccount = $this->createCreditAccount($to);
            }

            // Check sufficient balance
            if (!$fromAccount->hasSufficientBalance($amount)) {
                throw new \Exception('Insufficient credit balance for transfer');
            }

            // Deduct from sender
            $fromAccount->decrementBalance($amount);

            // Add to receiver
            $toAccount->incrementBalance($amount);

            $transferReferenceId = $referenceId ?? Str::uuid()->toString();

            // Create transaction records
            $transactionOut = CreditTransaction::create([
                'user_id' => $from->id,
                'amount' => -$amount,
                'type' => CreditTransaction::TYPE_TRANSFER_OUT,
                'reason' => $reason,
                'reference_id' => $transferReferenceId,
                'related_user_id' => $to->id,
                'metadata' => $metadata,
            ]);

            $transactionIn = CreditTransaction::create([
                'user_id' => $to->id,
                'amount' => $amount,
                'type' => CreditTransaction::TYPE_TRANSFER_IN,
                'reason' => $reason,
                'reference_id' => $transferReferenceId,
                'related_user_id' => $from->id,
                'metadata' => $metadata,
            ]);

            return [
                'transaction_out' => $transactionOut,
                'transaction_in' => $transactionIn,
            ];
        });

        // Log after transaction completes
        try {
            Log::info('Credits transferred', [
                'from_user_id' => $from->id,
                'to_user_id' => $to->id,
                'amount' => $amount,
                'reason' => $reason,
                'from_balance' => $this->getBalance($from),
                'to_balance' => $this->getBalance($to),
            ]);
        } catch (\Exception $e) {
            // Silently fail if log fails
        }

        return $result;
    }

    /**
     * Get user's transaction history.
     */
    public function getTransactionHistory(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return CreditTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Recalculate user balance from transaction history.
     * Used for integrity checks.
     */
    public function recalculateBalance(User $user): int
    {
        return CreditTransaction::where('user_id', $user->id)
            ->sum('amount');
    }

    /**
     * Get or create credit account for user.
     */
    protected function getOrCreateCreditAccount(User $user): UserCredit
    {
        return UserCredit::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
    }

    /**
     * Create credit account for user.
     */
    protected function createCreditAccount(User $user): UserCredit
    {
        return UserCredit::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);
    }
}
