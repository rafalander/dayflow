<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
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
            ],
            'status' => 'success',
        ]);
    }
}
