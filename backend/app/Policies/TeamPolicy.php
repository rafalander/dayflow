<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Support\UserHierarchy;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }

    public function view(User $user, Team $team): bool
    {
        return UserHierarchy::isAdmin($user) && UserHierarchy::canView($user, $team->lead);
    }

    public function create(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }

    public function update(User $user, Team $team): bool
    {
        return $this->view($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->view($user, $team);
    }
}
