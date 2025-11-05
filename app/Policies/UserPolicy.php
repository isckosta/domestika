<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Own profile or has permission
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.view');
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Own profile or has permission
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.update');
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermissionTo('users.delete');
    }
}

