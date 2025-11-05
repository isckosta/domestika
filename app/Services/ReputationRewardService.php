<?php

namespace App\Services;

use App\Models\User;
use App\Models\CreditRule;
use Illuminate\Support\Facades\Log;

class ReputationRewardService
{
    protected CreditService $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

    /**
     * Process reward for an event.
     *
     * @param User $user
     * @param string $event
     * @param array|null $metadata
     * @return bool
     */
    public function processReward(User $user, string $event, ?array $metadata = null): bool
    {
        try {
            // Find the credit rule for this event
            $rule = CreditRule::findByEvent($event);

            if (!$rule) {
                Log::warning('No credit rule found for event', [
                    'event' => $event,
                    'user_id' => $user->id,
                ]);
                return false;
            }

            // Process based on amount (positive = credit, negative = debit)
            if ($rule->amount > 0) {
                $this->creditService->addCredits(
                    $user,
                    $rule->amount,
                    "Reward: {$rule->description}",
                    null,
                    array_merge($metadata ?? [], [
                        'event' => $event,
                        'rule_id' => $rule->id,
                    ])
                );
            } elseif ($rule->amount < 0) {
                $this->creditService->deductCredits(
                    $user,
                    abs($rule->amount),
                    "Penalty: {$rule->description}",
                    null,
                    array_merge($metadata ?? [], [
                        'event' => $event,
                        'rule_id' => $rule->id,
                    ])
                );
            }

            Log::info('Reputation reward processed', [
                'user_id' => $user->id,
                'event' => $event,
                'amount' => $rule->amount,
                'rule_id' => $rule->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to process reputation reward', [
                'user_id' => $user->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Reward for positive review.
     */
    public function rewardPositiveReview(User $user, ?array $metadata = null): bool
    {
        return $this->processReward($user, CreditRule::EVENT_POSITIVE_REVIEW, $metadata);
    }

    /**
     * Reward for service completion.
     */
    public function rewardServiceCompleted(User $user, ?array $metadata = null): bool
    {
        return $this->processReward($user, CreditRule::EVENT_SERVICE_COMPLETED, $metadata);
    }

    /**
     * Reward for quick response.
     */
    public function rewardQuickResponse(User $user, ?array $metadata = null): bool
    {
        return $this->processReward($user, CreditRule::EVENT_QUICK_RESPONSE, $metadata);
    }

    /**
     * Reward for account verification.
     */
    public function rewardAccountVerified(User $user, ?array $metadata = null): bool
    {
        return $this->processReward($user, CreditRule::EVENT_ACCOUNT_VERIFIED, $metadata);
    }

    /**
     * Reward for profile completion.
     */
    public function rewardProfileCompleted(User $user, ?array $metadata = null): bool
    {
        return $this->processReward($user, CreditRule::EVENT_PROFILE_COMPLETED, $metadata);
    }

    /**
     * Reward for first service.
     */
    public function rewardFirstService(User $user, ?array $metadata = null): bool
    {
        return $this->processReward($user, CreditRule::EVENT_FIRST_SERVICE, $metadata);
    }

    /**
     * Reward for referral.
     */
    public function rewardReferral(User $user, ?array $metadata = null): bool
    {
        return $this->processReward($user, CreditRule::EVENT_REFERRAL, $metadata);
    }

    /**
     * Get all active reward rules.
     */
    public function getActiveRules(): \Illuminate\Database\Eloquent\Collection
    {
        return CreditRule::getActiveRules();
    }
}
