<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CargoController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Cargo::class);

        $cargos = Cargo::query()->orderByDesc('level')->orderBy('name')->get();

        return response()->json([
            'data' => $cargos,
            'status' => 'success',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Cargo::class);

        $auth = $request->user();
        $maxLevel = max(1, $auth->level - 1);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'role' => 'required|in:admin,user',
            'level' => ['required', 'integer', 'min:1', 'max:'.$maxLevel],
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        $cargo = Cargo::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'role' => $validated['role'],
            'level' => $validated['level'],
        ]);

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
        $maxLevel = max(1, $auth->level - 1);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'description' => 'sometimes|nullable|string',
            'role' => 'sometimes|in:admin,user',
            'level' => ['sometimes', 'integer', 'min:1', 'max:'.$maxLevel],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = $this->uniqueSlug(Str::slug($validated['name']), $cargo->id);
        }

        $cargo->update($validated);

        if (isset($validated['role']) || isset($validated['level'])) {
            $cargo->users()->update([
                'role' => $cargo->role,
                'level' => $cargo->level,
            ]);
        }

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
            return response()->json([
                'message' => 'Não é possível excluir um cargo com usuários vinculados',
                'status' => 'error',
            ], 422);
        }

        $cargo->delete();

        return response()->json([
            'message' => 'Cargo removido',
            'status' => 'success',
        ]);
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base !== '' ? $base : 'cargo';
        $candidate = $slug;
        $n = 0;

        while (
            Cargo::where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $slug.'-'.(++$n);
        }

        return $candidate;
    }
}
