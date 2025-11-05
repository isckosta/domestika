<?php

namespace App\Events\Reputation;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRewardEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $event;
    public ?array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $event, ?array $metadata = null)
    {
        $this->user = $user;
        $this->event = $event;
        $this->metadata = $metadata;
    }
}
