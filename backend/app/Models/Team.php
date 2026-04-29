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
                'children' => $children->map(fn (User $c) => $node($c))->values()->all(),
            ];
        };

        return $node($lead);
    }
}
