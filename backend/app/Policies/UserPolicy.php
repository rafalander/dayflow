<?php

namespace App\Policies;

use App\Models\User;
use App\Support\UserHierarchy;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }

    public function view(User $user, User $target): bool
    {
        return UserHierarchy::canView($user, $target);
    }

    public function create(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }

    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }

        return UserHierarchy::canManage($user, $target);
    }

    public function admin(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }
}
