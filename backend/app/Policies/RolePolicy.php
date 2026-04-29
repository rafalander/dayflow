<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;

class RolePolicy
{
    /**
     * Check if user can create role
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Check if user can update role
     */
    public function update(User $user, Role $role): bool
    {
        return $user->isAdmin();
    }

    /**
     * Check if user can delete role
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->isAdmin() && $role->slug !== 'admin';
    }
}
