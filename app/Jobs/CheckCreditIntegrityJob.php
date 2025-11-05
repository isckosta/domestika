<?php

namespace App\Jobs;

use App\Models\UserCredit;
use App\Models\CreditTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckCreditIntegrityJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting credit integrity check');

        $discrepancies = 0;
        $corrected = 0;
        $errors = 0;

        // Get all user credit accounts
        UserCredit::chunk(100, function ($creditAccounts) use (&$discrepancies, &$corrected, &$errors) {
            foreach ($creditAccounts as $creditAccount) {
                try {
                    // Calculate expected balance from transaction history
                    $calculatedBalance = CreditTransaction::where('user_id', $creditAccount->user_id)
                        ->sum('amount');

                    // Check if there's a discrepancy
                    if ($creditAccount->balance !== $calculatedBalance) {
                        $discrepancies++;

                        Log::warning('Credit balance discrepancy detected', [
                            'user_id' => $creditAccount->user_id,
                            'stored_balance' => $creditAccount->balance,
                            'calculated_balance' => $calculatedBalance,
                            'difference' => $calculatedBalance - $creditAccount->balance,
                        ]);

                        // Correct the balance
                        DB::transaction(function () use ($creditAccount, $calculatedBalance) {
                            $oldBalance = $creditAccount->balance;
                            $creditAccount->balance = $calculatedBalance;
                            $creditAccount->save();

                            // Create adjustment transaction record
                            CreditTransaction::create([
                                'user_id' => $creditAccount->user_id,
                                'amount' => $calculatedBalance - $oldBalance,
                                'type' => CreditTransaction::TYPE_CREDIT,
                                'reason' => 'Automatic balance adjustment - Integrity check',
                                'metadata' => [
                                    'old_balance' => $oldBalance,
                                    'new_balance' => $calculatedBalance,
                                    'adjusted_by' => 'CheckCreditIntegrityJob',
                                    'adjusted_at' => now()->toIso8601String(),
                                ],
                            ]);
                        });

                        $corrected++;

                        Log::info('Credit balance corrected', [
                            'user_id' => $creditAccount->user_id,
                            'old_balance' => $creditAccount->balance,
                            'new_balance' => $calculatedBalance,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Error checking credit integrity', [
                        'user_id' => $creditAccount->user_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        Log::info('Credit integrity check completed', [
            'total_checked' => UserCredit::count(),
            'discrepancies_found' => $discrepancies,
            'balances_corrected' => $corrected,
            'errors' => $errors,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Credit integrity check job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
