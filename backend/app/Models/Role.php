<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'weight',
        'color',
        'permissions',
        'description',
        'is_admin',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_admin' => 'boolean',
        'weight' => 'integer',
    ];

    /**
     * Users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if this role outranks another role.
     */
    public function outranks(Role $other): bool
    {
        return $this->weight > $other->weight;
    }

    /**
     * Scope: order by weight descending (highest authority first).
     */
    public function scopeByAuthority($query)
    {
        return $query->orderByDesc('weight');
    }
}
