<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Cargo;
use App\Models\User;
use App\Support\ApiQueryCacheGens;
use App\Support\UserHierarchy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $auth = $request->user();
        $auth->loadMissing('cargo');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'cargo_id' => 'required|exists:positions,id',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
            'birth_date' => 'sometimes|nullable|date|before_or_equal:today',
        ]);

        $cargo = Cargo::findOrFail($validated['cargo_id']);

        if ($cargo->level >= UserHierarchy::level($auth)) {
            throw ValidationException::withMessages([
                'cargo_id' => ['Este cargo tem nível igual ou superior ao seu.'],
            ]);
        }

        if (! UserHierarchy::canAssignLevel($auth, $cargo->level)) {
            throw ValidationException::withMessages([
                'cargo_id' => ['Nível do cargo inválido para o seu perfil.'],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'google_id' => 'manual-'.Str::uuid()->toString(),
            'cargo_id' => $cargo->id,
            'manager_id' => $validated['manager_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'birth_date' => $validated['birth_date'] ?? null,
        ]);

        $user->load('manager', 'cargo');

        AuditLog::log(
            'user_created',
            User::class,
            $user->id,
            null,
            [
                'email' => $user->email,
                'cargo_id' => $user->cargo_id,
                'birth_date' => $user->birth_date,
            ]
        );

        ApiQueryCacheGens::bumpUserDirectory();

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
        $auth->loadMissing('cargo');
        $authLevel = UserHierarchy::level($auth);

        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        if ($this->shouldCacheUserDirectory($request, $page, $perPage)) {
            $ttl = (int) config('dayflow.api_read_cache.users_directory', 240);
            $gen = ApiQueryCacheGens::userDirectory();
            $users = Cache::remember(
                "api.users.directory.{$gen}.{$auth->id}",
                $ttl,
                fn () => $this->buildUserIndexQuery($request, $auth, $authLevel)->paginate($perPage)
            );

            return response()->json([
                'data' => $users,
                'status' => 'success',
            ]);
        }

        $users = $this->buildUserIndexQuery($request, $auth, $authLevel)->paginate($perPage);

        return response()->json([
            'data' => $users,
            'status' => 'success',
        ]);
    }

    private function shouldCacheUserDirectory(Request $request, int $page, int $perPage): bool
    {
        return $page === 1
            && $perPage === 500
            && ! $request->filled('search')
            && ! $request->filled('role')
            && ! $request->filled('manager_id')
            && ! $request->has('is_active');
    }

    private function buildUserIndexQuery(Request $request, User $auth, int $authLevel): Builder
    {
        $query = User::query()
            ->with('manager', 'cargo')
            ->join('positions', 'users.cargo_id', '=', 'positions.id');

        $query->where(function ($q) use ($authLevel, $auth) {
            $q->where('positions.level', '<', $authLevel)
                ->orWhere('users.id', $auth->id);
        });

        if ($request->filled('role')) {
            $query->where('positions.role', $request->role);
        }

        if ($request->filled('manager_id')) {
            $query->where('users.manager_id', $request->manager_id);
        }

        if ($request->has('is_active')) {
            $query->where('users.is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('positions.level')
            ->select('users.*');
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
        $auth->loadMissing('cargo');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'sometimes|nullable|string|min:8',
            'cargo_id' => 'sometimes|exists:positions,id',
            'manager_id' => 'sometimes|nullable|exists:users,id|different:id',
            'is_active' => 'sometimes|boolean',
            'custom_avatar' => 'sometimes|nullable|url',
            'birth_date' => 'sometimes|nullable|date|before_or_equal:today',
        ]);

        if ($auth->id === $user->id) {
            unset($validated['cargo_id'], $validated['is_active']);
        }

        if ($request->filled('password')) {
            $validated['password'] = $request->password;
        } else {
            unset($validated['password']);
        }

        if ($auth->id !== $user->id && array_key_exists('cargo_id', $validated)) {
            $cid = $validated['cargo_id'];
            $cargo = Cargo::findOrFail($cid);
            if ($cargo->level >= UserHierarchy::level($auth)) {
                throw ValidationException::withMessages([
                    'cargo_id' => ['Este cargo tem nível igual ou superior ao seu.'],
                ]);
            }
            if (! UserHierarchy::canAssignLevel($auth, $cargo->level)) {
                throw ValidationException::withMessages([
                    'cargo_id' => ['Nível do cargo inválido.'],
                ]);
            }
        }

        $before = $user->only(['name', 'email', 'is_active', 'cargo_id', 'manager_id', 'birth_date']);

        $user->update($validated);
        $user->load('manager', 'cargo');

        AuditLog::log('user_updated', User::class, $user->id, $before, $user->only([
            'name', 'email', 'is_active', 'cargo_id', 'manager_id', 'birth_date',
        ]));

        ApiQueryCacheGens::bumpUserDirectory();

        return response()->json([
            'data' => $user,
            'message' => 'Usuário atualizado',
            'status' => 'success',
        ]);
    }

    public function organizationTree(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $rootUsers = User::whereNull('manager_id')
            ->with(['subordinates', 'cargo'])
            ->get();

        return response()->json([
            'data' => $rootUsers,
            'status' => 'success',
        ]);
    }

    public function subordinates(User $user): JsonResponse
    {
        $subordinates = $user->subordinates()->with(['subordinates', 'cargo'])->get();

        return response()->json([
            'data' => $subordinates,
            'status' => 'success',
        ]);
    }
}
