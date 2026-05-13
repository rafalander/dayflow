<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VacationRequest;
use App\Support\UserHierarchy;

class VacationRequestPolicy
{
    public function update(User $user, VacationRequest $vacation): bool
    {
        if ($user->id === $vacation->user_id) {
            return true;
        }

        return UserHierarchy::isAdmin($user)
            && UserHierarchy::level($user) > UserHierarchy::level($vacation->user);
    }

    public function delete(User $user, VacationRequest $vacation): bool
    {
        if ($user->id === $vacation->user_id) {
            return true;
        }

        return UserHierarchy::isAdmin($user)
            && UserHierarchy::level($user) > UserHierarchy::level($vacation->user);
    }

    public function approve(User $user, VacationRequest $vacation): bool
    {
        if ($user->id === $vacation->user_id && UserHierarchy::isAdmin($user)) {
            return true;
        }

        if ($user->id === $vacation->approver_id) {
            return true;
        }

        if (UserHierarchy::isAdmin($user)
            && UserHierarchy::level($user) > UserHierarchy::level($vacation->user)) {
            return true;
        }

        return $this->isHierarchyAbove($user, $vacation->user);
    }

    private function isHierarchyAbove(User $checker, User $target): bool
    {
        if (UserHierarchy::level($checker) > UserHierarchy::level($target)) {
            return true;
        }

        $current = $target;
        $maxDepth = 10;

        while ($current->manager_id && $maxDepth > 0) {
            if ($current->manager_id === $checker->id) {
                return true;
            }
            $current = $current->manager;
            $maxDepth--;
        }

        return false;
    }
}
