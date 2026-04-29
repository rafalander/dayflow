<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * List all users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('role', 'manager');

        // Filter by role
        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Filter by manager
        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $users,
            'status' => 'success',
        ]);
    }

    /**
     * Get single user
     */
    public function show(User $user): JsonResponse
    {
        $user->load('role', 'manager', 'subordinates', 'vacationRequests');

        return response()->json([
            'data' => $user,
            'status' => 'success',
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role_id' => 'sometimes|exists:roles,id',
            'manager_id' => 'sometimes|nullable|exists:users,id|different:id',
            'is_active' => 'sometimes|boolean',
            'custom_avatar' => 'sometimes|nullable|url',
        ]);

        $user->update($validated);

        return response()->json([
            'data' => $user,
            'message' => 'User updated successfully',
            'status' => 'success',
        ]);
    }

    /**
     * Get organization tree
     */
    public function organizationTree(): JsonResponse
    {
        $rootUsers = User::whereNull('manager_id')
            ->with('subordinates')
            ->get();

        return response()->json([
            'data' => $rootUsers,
            'status' => 'success',
        ]);
    }

    /**
     * Get user subordinates recursively
     */
    public function subordinates(User $user): JsonResponse
    {
        $subordinates = $user->subordinates()->with('subordinates')->get();

        return response()->json([
            'data' => $subordinates,
            'status' => 'success',
        ]);
    }
}
