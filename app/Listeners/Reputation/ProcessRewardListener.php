<?php

namespace App\Listeners\Reputation;

use App\Events\Reputation\UserRewardEvent;
use App\Services\ReputationRewardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessRewardListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected ReputationRewardService $rewardService;

    /**
     * Create the event listener.
     */
    public function __construct(ReputationRewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * Handle the event.
     */
    public function handle(UserRewardEvent $event): void
    {
        $this->rewardService->processReward(
            $event->user,
            $event->event,
            $event->metadata
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserRewardEvent $event, \Throwable $exception): void
    {
        \Log::error('Failed to process reward event', [
            'user_id' => $event->user->id,
            'event' => $event->event,
            'error' => $exception->getMessage(),
        ]);
    }
}
