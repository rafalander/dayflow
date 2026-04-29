<?php

namespace App\Support;

use App\Models\User;

/**
 * Regras de hierarquia: nível numérico maior = mais autoridade.
 * Só utilizadores com role "admin" podem gerir outros; é obrigatório level do gestor > level do alvo.
 */
final class UserHierarchy
{
    public static function isAdmin(User $user): bool
    {
        return $user->role === 'admin';
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

        return $auth->level > $target->level;
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

        return $auth->level > $target->level;
    }

    /** Novo utilizador deve ficar estritamente abaixo do criador. */
    public static function canAssignLevel(User $auth, int $newLevel): bool
    {
        if (! self::isAdmin($auth)) {
            return false;
        }

        return $newLevel < $auth->level;
    }
}
