<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * List all roles
     */
    public function index(): JsonResponse
    {
        $roles = Role::orderBy('weight', 'asc')->get();

        return response()->json([
            'data' => $roles,
            'status' => 'success',
        ]);
    }

    /**
     * Create new role
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles|max:255',
            'slug' => 'required|string|unique:roles|max:255',
            'weight' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create($validated);

        return response()->json([
            'data' => $role,
            'message' => 'Role created successfully',
            'status' => 'success',
        ], 201);
    }

    /**
     * Update role
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $role->id . '|max:255',
            'weight' => 'sometimes|integer|min:0',
            'description' => 'sometimes|nullable|string',
            'permissions' => 'sometimes|nullable|array',
        ]);

        $role->update($validated);

        return response()->json([
            'data' => $role,
            'message' => 'Role updated successfully',
            'status' => 'success',
        ]);
    }

    /**
     * Delete role
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete role with associated users',
                'status' => 'error',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
            'status' => 'success',
        ]);
    }
}
