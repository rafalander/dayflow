<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Support\UserHierarchy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()
            ->with('lead')
            ->withCount('members')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $teams,
            'status' => 'success',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Team::class);

        $auth = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'lead_id' => 'required|exists:users,id',
        ]);

        $lead = User::findOrFail($validated['lead_id']);

        if (! UserHierarchy::canView($auth, $lead)) {
            throw ValidationException::withMessages([
                'lead_id' => ['Não pode definir este utilizador como gestor do time.'],
            ]);
        }

        if (! $lead->is_active) {
            throw ValidationException::withMessages([
                'lead_id' => ['O gestor deve estar ativo.'],
            ]);
        }

        $team = Team::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#6366f1',
            'lead_id' => $lead->id,
        ]);

        $lead->update(['team_id' => $team->id]);

        $team->load('lead');
        $team->loadCount('members');

        return response()->json([
            'data' => $team,
            'message' => 'Time criado',
            'status' => 'success',
        ], 201);
    }

    public function show(Request $request, Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $team->load('lead');
        $team->loadCount('members');

        $tree = $team->buildHierarchyTree();

        $memberIds = User::where('team_id', $team->id)->orderBy('name')->pluck('id')->values()->all();

        return response()->json([
            'data' => [
                'team' => $team,
                'hierarchy' => $tree,
                'member_ids' => $memberIds,
            ],
            'status' => 'success',
        ]);
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $team->update(array_filter($validated, fn ($v) => $v !== null));

        $team->load('lead');
        $team->loadCount('members');

        return response()->json([
            'data' => $team,
            'message' => 'Time atualizado',
            'status' => 'success',
        ]);
    }

    public function destroy(Request $request, Team $team): JsonResponse
    {
        $this->authorize('delete', $team);

        User::where('team_id', $team->id)->update(['team_id' => null]);

        $team->delete();

        return response()->json([
            'message' => 'Time removido',
            'status' => 'success',
        ]);
    }

    /**
     * Define membros do time (team_id). O gestor (lead) é sempre incluído.
     */
    public function syncMembers(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $ids = collect($validated['user_ids'])->unique()->values();

        if (! $ids->contains(fn ($id) => (int) $id === (int) $team->lead_id)) {
            $ids->push($team->lead_id);
        }

        foreach ($ids as $uid) {
            $target = User::findOrFail($uid);
            $this->authorize('update', $target);
        }

        User::where('team_id', $team->id)
            ->whereNotIn('id', $ids->all())
            ->update(['team_id' => null]);

        foreach ($ids as $uid) {
            User::whereKey($uid)->update(['team_id' => $team->id]);
        }

        $team->load('lead');
        $team->loadCount('members');

        $memberIds = User::where('team_id', $team->id)->orderBy('name')->pluck('id')->values()->all();

        return response()->json([
            'data' => [
                'team' => $team,
                'hierarchy' => $team->buildHierarchyTree(),
                'member_ids' => $memberIds,
            ],
            'message' => 'Membros atualizados',
            'status' => 'success',
        ]);
    }
}
