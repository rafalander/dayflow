<?php

namespace App\Policies;

use App\Models\User;
use App\Support\UserHierarchy;

class UserPolicy
{
    /**
     * Lista global de utilizadores — apenas administradores (role admin).
     */
    public function viewAny(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }

    /**
     * Ver detalhes de outro utilizador (hierarquia ou próprio perfil).
     */
    public function view(User $user, User $target): bool
    {
        return UserHierarchy::canView($user, $target);
    }

    /**
     * Criar utilizador — apenas admin.
     */
    public function create(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }

    /**
     * Atualizar utilizador — próprio perfil ou admin acima na hierarquia.
     */
    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }

        return UserHierarchy::canManage($user, $target);
    }

    /**
     * Operações administrativas amplas (settings, etc.).
     */
    public function admin(User $user): bool
    {
        return UserHierarchy::isAdmin($user);
    }
}
