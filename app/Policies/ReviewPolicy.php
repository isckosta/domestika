<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine if the user can view any reviews.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reviews.view');
    }

    /**
     * Determine if the user can view the review.
     */
    public function view(User $user, Review $review): bool
    {
        return $user->hasPermissionTo('reviews.view');
    }

    /**
     * Determine if the user can create reviews.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('reviews.create');
    }

    /**
     * Determine if the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        // Can only update own review
        if ($review->reviewer_id === $user->id) {
            return $user->hasPermissionTo('reviews.update');
        }

        return false;
    }

    /**
     * Determine if the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        // Own review or moderator/admin
        if ($review->reviewer_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('reviews.delete') || $user->hasPermissionTo('reviews.moderate');
    }

    /**
     * Determine if the user can moderate reviews.
     */
    public function moderate(User $user): bool
    {
        return $user->hasPermissionTo('reviews.moderate');
    }
}

