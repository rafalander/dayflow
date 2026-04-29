<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Cargo;
use App\Models\User;
use App\Support\UserHierarchy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $auth = $request->user();
        $maxLevel = max(1, $auth->level - 1);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'cargo_id' => 'nullable|exists:positions,id',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if (! empty($validated['cargo_id'])) {
            $cargo = Cargo::findOrFail($validated['cargo_id']);
            if ($cargo->level >= $auth->level) {
                throw ValidationException::withMessages([
                    'cargo_id' => ['Este cargo tem nível igual ou superior ao seu.'],
                ]);
            }
            $validated['role'] = $cargo->role;
            $validated['level'] = $cargo->level;
        } else {
            $more = $request->validate([
                'role' => 'required|in:admin,user',
                'level' => ['required', 'integer', 'min:1', 'max:'.$maxLevel],
            ]);
            $validated = array_merge($validated, $more);
        }

        if (! UserHierarchy::canAssignLevel($auth, $validated['level'])) {
            throw ValidationException::withMessages([
                'level' => ['Nível inválido para o seu perfil.'],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'google_id' => 'manual-'.Str::uuid()->toString(),
            'role' => $validated['role'],
            'level' => $validated['level'],
            'cargo_id' => $validated['cargo_id'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->load('manager', 'cargo');

        AuditLog::log(
            'user_created',
            User::class,
            $user->id,
            null,
            [
                'email' => $user->email,
                'role' => $user->role,
                'level' => $user->level,
            ]
        );

        return response()->json([
            'data' => $user,
            'message' => 'Usuário criado',
            'status' => 'success',
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $auth = $request->user();

        $query = User::with('manager', 'cargo');

        $query->where(function ($q) use ($auth) {
            $q->where('level', '<', $auth->level)
                ->orWhere('id', $auth->id);
        });

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('level')->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $users,
            'status' => 'success',
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load('manager', 'subordinates', 'vacationRequests', 'cargo');

        return response()->json([
            'data' => $user,
            'status' => 'success',
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $auth = $request->user();
        $maxLevel = max(1, $auth->level - 1);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|in:admin,user',
            'level' => ['sometimes', 'integer', 'min:1', 'max:'.$maxLevel],
            'cargo_id' => 'sometimes|nullable|exists:positions,id',
            'manager_id' => 'sometimes|nullable|exists:users,id|different:id',
            'is_active' => 'sometimes|boolean',
            'custom_avatar' => 'sometimes|nullable|url',
        ]);

        if ($auth->id === $user->id) {
            unset($validated['role'], $validated['level'], $validated['is_active']);
        }

        if ($request->filled('password')) {
            $validated['password'] = $request->password;
        } else {
            unset($validated['password']);
        }

        if ($auth->id !== $user->id && array_key_exists('cargo_id', $validated)) {
            $cid = $validated['cargo_id'];
            if ($cid) {
                $cargo = Cargo::findOrFail($cid);
                if ($cargo->level >= $auth->level) {
                    throw ValidationException::withMessages([
                        'cargo_id' => ['Este cargo tem nível igual ou superior ao seu.'],
                    ]);
                }
                $validated['role'] = $cargo->role;
                $validated['level'] = $cargo->level;
            }
        }

        if ($auth->id !== $user->id && isset($validated['level'])) {
            if ($validated['level'] >= $auth->level) {
                throw ValidationException::withMessages([
                    'level' => ['Não pode atribuir nível igual ou superior ao seu.'],
                ]);
            }
            if (! UserHierarchy::canAssignLevel($auth, $validated['level'])) {
                throw ValidationException::withMessages([
                    'level' => ['Nível inválido.'],
                ]);
            }
        }

        $before = $user->only(['name', 'email', 'role', 'level', 'is_active', 'cargo_id', 'manager_id']);

        $user->update($validated);
        $user->load('manager', 'cargo');

        AuditLog::log('user_updated', User::class, $user->id, $before, $user->only([
            'name', 'email', 'role', 'level', 'is_active', 'cargo_id', 'manager_id',
        ]));

        return response()->json([
            'data' => $user,
            'message' => 'Usuário atualizado',
            'status' => 'success',
        ]);
    }

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

    public function subordinates(User $user): JsonResponse
    {
        $subordinates = $user->subordinates()->with('subordinates')->get();

        return response()->json([
            'data' => $subordinates,
            'status' => 'success',
        ]);
    }
}
