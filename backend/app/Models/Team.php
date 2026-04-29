<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
        'lead_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    /**
     * Nested tree under the team lead (manager_id links within the team).
     *
     * @return array<string, mixed>|null
     */
    public function buildHierarchyTree(): ?array
    {
        $lead = $this->lead()->with('cargo')->first();
        if (! $lead) {
            return null;
        }

        $members = User::where('team_id', $this->id)->with('cargo')->get()->keyBy('id');

        if (! $members->has($lead->id)) {
            $members->put($lead->id, $lead);
        }

        $node = function (User $user) use (&$node, $members): array {
            $children = $members
                ->filter(fn (User $u) => (int) $u->manager_id === (int) $user->id)
                ->sortBy('name')
                ->values();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'display_avatar' => $user->display_avatar,
                'cargo' => $user->cargo,
                'is_lead' => (int) $user->id === (int) $this->lead_id,
                'orphan_hierarchy' => false,
                'children' => $children->map(fn (User $c) => $node($c))->values()->all(),
            ];
        };

        $tree = $node($lead);

        $collectIds = function (array $n) use (&$collectIds): array {
            $ids = [$n['id']];
            foreach ($n['children'] ?? [] as $c) {
                $ids = array_merge($ids, $collectIds($c));
            }

            return $ids;
        };

        $idsInTree = $collectIds($tree);

        $orphans = $members
            ->filter(fn (User $u) => (int) $u->id !== (int) $lead->id && ! in_array($u->id, $idsInTree, true))
            ->sortBy('name')
            ->values();

        foreach ($orphans as $u) {
            $tree['children'][] = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'display_avatar' => $u->display_avatar,
                'cargo' => $u->cargo,
                'is_lead' => false,
                'orphan_hierarchy' => true,
                'children' => [],
            ];
        }

        return $tree;
    }
}
