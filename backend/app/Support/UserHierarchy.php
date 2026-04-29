<?php

namespace App\Support;

use App\Models\User;

/**
 * Hierarquia só via cargo (positions.role / positions.level).
 * Nível maior = mais autoridade. Admins (cargo.role = admin) gerem quem tem nível inferior.
 */
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

    /** Ver/detalhes de outro utilizador (não inclui edição). */
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

    /** Criar/editar/desativar outro utilizador. */
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

    /** Novo utilizador deve ficar estritamente abaixo do criador (nível do cargo). */
    public static function canAssignLevel(User $auth, int $newCargoLevel): bool
    {
        if (! self::isAdmin($auth)) {
            return false;
        }

        return $newCargoLevel < self::level($auth);
    }
}
