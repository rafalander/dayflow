<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $ttl = (int) config('dayflow.api_read_cache.roles', 86400);

        $data = Cache::remember(
            'api.roles',
            $ttl,
            static fn () => [
                [
                    'id' => 1,
                    'slug' => 'admin',
                    'name' => 'Administrador',
                    'weight' => 100,
                    'is_admin' => true,
                    'color' => '#6366F1',
                    'permissions' => null,
                    'description' => 'Acesso administrativo — hierarquia definida pelo cargo (positions.level)',
                ],
                [
                    'id' => 2,
                    'slug' => 'user',
                    'name' => 'Usuário',
                    'weight' => 20,
                    'is_admin' => false,
                    'color' => '#6366F1',
                    'permissions' => null,
                    'description' => 'Utilizador padrão',
                ],
            ]
        );

        return response()->json([
            'data' => $data,
            'status' => 'success',
        ]);
    }
}
