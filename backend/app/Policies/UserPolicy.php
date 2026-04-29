<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Check if user can update another user
     */
    public function update(User $user, User $target): bool
    {
        // Users can only update themselves, admins can update anyone
        return $user->id === $target->id || $user->isAdmin();
    }

    /**
     * Check if user has admin access
     */
    public function admin(User $user): bool
    {
        return $user->isAdmin();
    }
}
