<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    /**
     * Determine if the user can view any chat messages.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('chat.view');
    }

    /**
     * Determine if the user can view the chat message.
     */
    public function view(User $user, ChatMessage $chatMessage): bool
    {
        // Can view if user is sender or receiver
        return $chatMessage->sender_id === $user->id || $chatMessage->receiver_id === $user->id;
    }

    /**
     * Determine if the user can create chat messages.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('chat.send');
    }

    /**
     * Determine if the user can update the chat message.
     */
    public function update(User $user, ChatMessage $chatMessage): bool
    {
        // Can only update own messages
        return $chatMessage->sender_id === $user->id;
    }

    /**
     * Determine if the user can delete the chat message.
     */
    public function delete(User $user, ChatMessage $chatMessage): bool
    {
        // Can delete own messages or has delete permission
        if ($chatMessage->sender_id === $user->id || $chatMessage->receiver_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('chat.delete');
    }
}

