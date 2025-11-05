<?php

namespace Database\Seeders;

use App\Models\CreditRule;
use Illuminate\Database\Seeder;

class CreditRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'event' => CreditRule::EVENT_POSITIVE_REVIEW,
                'amount' => 50,
                'description' => 'Reward for receiving a positive review (4-5 stars)',
                'is_active' => true,
                'metadata' => [
                    'category' => 'reputation',
                    'min_rating' => 4,
                ],
            ],
            [
                'event' => CreditRule::EVENT_SERVICE_COMPLETED,
                'amount' => 100,
                'description' => 'Reward for successfully completing a service',
                'is_active' => true,
                'metadata' => [
                    'category' => 'engagement',
                ],
            ],
            [
                'event' => CreditRule::EVENT_QUICK_RESPONSE,
                'amount' => 25,
                'description' => 'Reward for responding to messages within 1 hour',
                'is_active' => true,
                'metadata' => [
                    'category' => 'engagement',
                    'max_response_time_minutes' => 60,
                ],
            ],
            [
                'event' => CreditRule::EVENT_ACCOUNT_VERIFIED,
                'amount' => 200,
                'description' => 'One-time reward for verifying account (email, phone, documents)',
                'is_active' => true,
                'metadata' => [
                    'category' => 'onboarding',
                    'one_time' => true,
                ],
            ],
            [
                'event' => CreditRule::EVENT_PROFILE_COMPLETED,
                'amount' => 150,
                'description' => 'One-time reward for completing 100% of profile information',
                'is_active' => true,
                'metadata' => [
                    'category' => 'onboarding',
                    'one_time' => true,
                    'required_completion' => 100,
                ],
            ],
            [
                'event' => CreditRule::EVENT_FIRST_SERVICE,
                'amount' => 300,
                'description' => 'One-time reward for completing first service on the platform',
                'is_active' => true,
                'metadata' => [
                    'category' => 'milestone',
                    'one_time' => true,
                ],
            ],
            [
                'event' => CreditRule::EVENT_REFERRAL,
                'amount' => 500,
                'description' => 'Reward for successfully referring a new user who completes their first service',
                'is_active' => true,
                'metadata' => [
                    'category' => 'growth',
                    'requires_completion' => true,
                ],
            ],
        ];

        foreach ($rules as $rule) {
            CreditRule::updateOrCreate(
                ['event' => $rule['event']],
                $rule
            );
        }

        $this->command->info('Credit rules seeded successfully!');
    }
}
