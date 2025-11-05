<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserCredit;
use App\Models\CreditTransaction;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreditService $creditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creditService = app(CreditService::class);
    }

    public function test_can_get_user_balance(): void
    {
        $user = User::factory()->create();

        $balance = $this->creditService->getBalance($user);

        $this->assertEquals(0, $balance);
    }

    public function test_can_add_credits_to_user(): void
    {
        $user = User::factory()->create();

        $transaction = $this->creditService->addCredits($user, 100, 'Test reward');

        $this->assertEquals(100, $this->creditService->getBalance($user));
        $this->assertEquals(100, $transaction->amount);
        $this->assertEquals(CreditTransaction::TYPE_CREDIT, $transaction->type);
    }

    public function test_can_deduct_credits_from_user(): void
    {
        $user = User::factory()->create();
        $this->creditService->addCredits($user, 200, 'Initial balance');

        $transaction = $this->creditService->deductCredits($user, 50, 'Test deduction');

        $this->assertEquals(150, $this->creditService->getBalance($user));
        $this->assertEquals(-50, $transaction->amount);
        $this->assertEquals(CreditTransaction::TYPE_DEBIT, $transaction->type);
    }

    public function test_cannot_deduct_more_than_balance(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient credit balance');

        $user = User::factory()->create();
        $this->creditService->addCredits($user, 50, 'Initial balance');

        $this->creditService->deductCredits($user, 100, 'Test deduction');
    }

    public function test_can_transfer_credits_between_users(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->creditService->addCredits($userA, 200, 'Initial balance');

        $transactions = $this->creditService->transferCredits($userA, $userB, 75, 'Test transfer');

        $this->assertEquals(125, $this->creditService->getBalance($userA));
        $this->assertEquals(75, $this->creditService->getBalance($userB));
        $this->assertArrayHasKey('transaction_out', $transactions);
        $this->assertArrayHasKey('transaction_in', $transactions);
    }

    public function test_cannot_transfer_credits_to_self(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer credits to yourself');

        $user = User::factory()->create();
        $this->creditService->addCredits($user, 100, 'Initial balance');

        $this->creditService->transferCredits($user, $user, 50, 'Test transfer');
    }

    public function test_recalculate_balance_returns_correct_sum(): void
    {
        $user = User::factory()->create();

        $this->creditService->addCredits($user, 100, 'First');
        $this->creditService->addCredits($user, 50, 'Second');
        $this->creditService->deductCredits($user, 25, 'Third');

        $calculatedBalance = $this->creditService->recalculateBalance($user);

        $this->assertEquals(125, $calculatedBalance);
    }

    public function test_transaction_hash_is_generated(): void
    {
        $user = User::factory()->create();

        $transaction = $this->creditService->addCredits($user, 100, 'Test');

        $this->assertNotEmpty($transaction->transaction_hash);
        $this->assertEquals(64, strlen($transaction->transaction_hash));
    }
}
