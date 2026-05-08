<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Support\ApiQueryCacheGens;
use App\Support\UserHierarchy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CargoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cargo::class);

        $ttl = (int) config('dayflow.api_read_cache.cargos_index', 600);
        $gen = ApiQueryCacheGens::cargos();

        $cargos = Cache::remember(
            "api.cargos.index.{$gen}",
            $ttl,
            fn () => Cargo::query()->orderByDesc('level')->orderBy('name')->get()
        );

        return response()->json([
            'data' => $cargos,
            'status' => 'success',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Cargo::class);

        $auth = $request->user();
        $auth->loadMissing('cargo');
        $maxLevel = max(1, UserHierarchy::level($auth) - 1);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'role' => 'required|in:admin,user',
            'level' => ['required', 'integer', 'min:1', 'max:'.$maxLevel],
        ]);

        $cargo = Cargo::create([
            'name' => $validated['name'],
            'slug' => Cargo::uniqueSlugFromName($validated['name']),
            'description' => $validated['description'] ?? null,
            'role' => $validated['role'],
            'level' => $validated['level'],
        ]);

        ApiQueryCacheGens::bumpCargos();

        return response()->json([
            'data' => $cargo,
            'message' => 'Cargo criado',
            'status' => 'success',
        ], 201);
    }

    public function update(Request $request, Cargo $cargo): JsonResponse
    {
        $this->authorize('update', $cargo);

        $auth = $request->user();
        $auth->loadMissing('cargo');
        $maxLevel = max(1, UserHierarchy::level($auth) - 1);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'description' => 'nullable|string|max:2000',
            'role' => 'sometimes|in:admin,user',
            'level' => ['sometimes', 'integer', 'min:1', 'max:'.$maxLevel],
        ]);

        if (isset($validated['role']) || isset($validated['level'])) {
            if ($cargo->level >= UserHierarchy::level($auth)) {
                throw ValidationException::withMessages([
                    'level' => ['Não pode alterar um cargo do seu nível ou acima.'],
                ]);
            }
        }

        $cargo->update($validated);

        ApiQueryCacheGens::bumpCargos();

        return response()->json([
            'data' => $cargo->fresh(),
            'message' => 'Cargo atualizado',
            'status' => 'success',
        ]);
    }

    public function destroy(Cargo $cargo): JsonResponse
    {
        $this->authorize('delete', $cargo);

        if ($cargo->users()->exists()) {
            throw ValidationException::withMessages([
                'cargo' => ['Existem utilizadores com este cargo.'],
            ]);
        }

        $auth = request()->user();
        $auth?->loadMissing('cargo');

        if ($cargo->level >= UserHierarchy::level($auth)) {
            throw ValidationException::withMessages([
                'cargo' => ['Não pode remover este cargo.'],
            ]);
        }

        $cargo->delete();

        ApiQueryCacheGens::bumpCargos();

        return response()->json([
            'message' => 'Cargo removido',
            'status' => 'success',
        ]);
    }
}
