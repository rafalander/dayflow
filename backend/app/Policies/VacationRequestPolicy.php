<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VacationRequest;

class VacationRequestPolicy
{
    public function update(User $user, VacationRequest $vacation): bool
    {
        if ($user->id === $vacation->user_id) {
            return true;
        }

        return $user->role === 'admin' && $user->level > $vacation->user->level;
    }

    public function delete(User $user, VacationRequest $vacation): bool
    {
        if ($user->id === $vacation->user_id) {
            return true;
        }

        return $user->role === 'admin' && $user->level > $vacation->user->level;
    }

    public function approve(User $user, VacationRequest $vacation): bool
    {
        if ($user->id === $vacation->approver_id) {
            return true;
        }

        if ($user->role === 'admin' && $user->level > $vacation->user->level) {
            return true;
        }

        return $this->isHierarchyAbove($user, $vacation->user);
    }

    private function isHierarchyAbove(User $checker, User $target): bool
    {
        if ($checker->level > $target->level) {
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
