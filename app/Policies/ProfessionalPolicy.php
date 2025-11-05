<?php

namespace App\Policies;

use App\Models\Professional;
use App\Models\User;

class ProfessionalPolicy
{
    /**
     * Determine if the user can view any professionals.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('professionals.view');
    }

    /**
     * Determine if the user can view the professional.
     */
    public function view(User $user, Professional $professional): bool
    {
        // Own profile or has permission
        if ($professional->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('professionals.view');
    }

    /**
     * Determine if the user can create professionals.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('professionals.create');
    }

    /**
     * Determine if the user can update the professional.
     */
    public function update(User $user, Professional $professional): bool
    {
        // Own profile or has manage permission
        if ($professional->user_id === $user->id) {
            return $user->hasPermissionTo('professionals.update');
        }

        return $user->hasPermissionTo('professionals.manage');
    }

    /**
     * Determine if the user can delete the professional.
     */
    public function delete(User $user, Professional $professional): bool
    {
        // Own profile or admin
        if ($professional->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('professionals.delete');
    }

    /**
     * Determine if the user can manage professionals (admin/company).
     */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('professionals.manage');
    }
}

