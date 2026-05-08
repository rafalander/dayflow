<?php

namespace App\Support;

use App\Models\User;

final class UserHierarchy
{
    public static function level(User $user): int
    {
        $user->loadMissing('cargo');

        return (int) ($user->cargo?->level ?? 0);
    }

    public static function isAdmin(User $user): bool
    {
        $user->loadMissing('cargo');

        return ($user->cargo?->role ?? null) === 'admin';
    }

    public static function canView(User $auth, User $target): bool
    {
        if ($auth->id === $target->id) {
            return true;
        }

        if (! self::isAdmin($auth)) {
            return false;
        }

        return self::level($auth) > self::level($target);
    }

    public static function canManage(User $auth, User $target): bool
    {
        if ($auth->id === $target->id) {
            return false;
        }

        if (! self::isAdmin($auth)) {
            return false;
        }

        return self::level($auth) > self::level($target);
    }

    public static function canAssignLevel(User $auth, int $newCargoLevel): bool
    {
        if (! self::isAdmin($auth)) {
            return false;
        }

        return $newCargoLevel < self::level($auth);
    }
}
