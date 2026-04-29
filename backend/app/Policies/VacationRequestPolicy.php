<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VacationRequest;

class VacationRequestPolicy
{
    /**
     * Check if user can update vacation request
     */
    public function update(User $user, VacationRequest $vacation): bool
    {
        return $user->id === $vacation->user_id || $user->isAdmin();
    }

    /**
     * Check if user can delete vacation request
     */
    public function delete(User $user, VacationRequest $vacation): bool
    {
        return $user->id === $vacation->user_id || $user->isAdmin();
    }

    /**
     * Check if user can approve vacation request
     */
    public function approve(User $user, VacationRequest $vacation): bool
    {
        // Only approver or higher can approve
        return $user->id === $vacation->approver_id || 
               $user->isAdmin() || 
               $this->isHierarchyAbove($user, $vacation->user);
    }

    private function isHierarchyAbove(User $checker, User $target): bool
    {
        // Check if checker has a higher role weight
        if ($checker->role?->weight > $target->role?->weight) {
            return true;
        }

        // Check if checker is in the hierarchy above target
        $current = $target;
        while ($current->manager_id) {
            if ($current->manager_id === $checker->id) {
                return true;
            }
            $current = $current->manager;
        }

        return false;
    }
}
