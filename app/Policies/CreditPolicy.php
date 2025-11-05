<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserCredit;

class CreditPolicy
{
    /**
     * Determine if the user can view their own balance.
     */
    public function viewBalance(User $user, ?UserCredit $userCredit = null): bool
    {
        return true; // Any authenticated user can view their own balance
    }

    /**
     * Determine if the user can add credits (Admin only).
     */
    public function addCredits(User $user, ?UserCredit $userCredit = null): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can deduct their own credits.
     */
    public function deductCredits(User $user, ?UserCredit $userCredit = null): bool
    {
        return true; // Any authenticated user can use their credits
    }

    /**
     * Determine if the user can transfer credits.
     */
    public function transferCredits(User $user, ?UserCredit $userCredit = null): bool
    {
        return true; // Any authenticated user can transfer credits
    }

    /**
     * Determine if the user can view transaction history.
     */
    public function viewTransactions(User $user, ?UserCredit $userCredit = null): bool
    {
        return true; // Any authenticated user can view their own transactions
    }

    /**
     * Determine if the user can manage credit rules (Admin only).
     */
    public function manageCreditRules(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
