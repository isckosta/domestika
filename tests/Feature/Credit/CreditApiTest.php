<?php

namespace Tests\Feature\Credit;

use App\Models\User;
use App\Models\CreditRule;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreditApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected string $userToken;
    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'user', 'guard_name' => 'api']);
        Role::create(['name' => 'admin', 'guard_name' => 'api']);

        // Create regular user
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->userToken = JWTAuth::fromUser($this->user);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->adminToken = JWTAuth::fromUser($this->admin);

        // Seed credit rules
        $this->seed(\Database\Seeders\CreditRuleSeeder::class);
    }

    public function test_user_can_get_balance(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/v1/credits/balance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'balance',
                    'user_id',
                ],
            ]);
    }

    public function test_admin_can_add_credits(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/v1/credits/add', [
            'user_id' => $this->user->id,
            'amount' => 100,
            'reason' => 'Test reward',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 100);
    }

    public function test_regular_user_cannot_add_credits(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/v1/credits/add', [
            'user_id' => $this->admin->id,
            'amount' => 100,
            'reason' => 'Test reward',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_deduct_own_credits(): void
    {
        $creditService = app(CreditService::class);
        $creditService->addCredits($this->user, 200, 'Initial balance');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/v1/credits/deduct', [
            'amount' => 50,
            'reason' => 'Test deduction',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 150);
    }

    public function test_user_can_transfer_credits(): void
    {
        $creditService = app(CreditService::class);
        $creditService->addCredits($this->user, 200, 'Initial balance');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/v1/credits/transfer', [
            'to_user_id' => $this->admin->id,
            'amount' => 75,
            'reason' => 'Test transfer',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 125);
    }

    public function test_user_can_view_transaction_history(): void
    {
        $creditService = app(CreditService::class);
        $creditService->addCredits($this->user, 100, 'Reward 1');
        $creditService->addCredits($this->user, 50, 'Reward 2');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/v1/credits/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transactions',
                    'count',
                ],
            ])
            ->assertJsonPath('data.count', 2);
    }

    public function test_validation_fails_for_invalid_amount(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/v1/credits/add', [
            'user_id' => $this->user->id,
            'amount' => -50, // Invalid negative amount
            'reason' => 'Test',
        ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_access_credit_endpoints(): void
    {
        $response = $this->getJson('/api/v1/credits/balance');
        $response->assertStatus(401);
    }
}
