<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'custom_avatar',
        'cargo_id',
        'manager_id',
        'team_id',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'google_id',
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['display_avatar'];

    // ─── Relationships ───────────────────────────

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function vacationRequests(): HasMany
    {
        return $this->hasMany(VacationRequest::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(VacationApproval::class, 'approver_id');
    }

    // ─── Accessors ───────────────────────────────

    /**
     * Display avatar: prefer custom_avatar, fallback to Google avatar.
     */
    public function getDisplayAvatarAttribute(): ?string
    {
        if ($this->custom_avatar) {
            return asset('storage/'.$this->custom_avatar);
        }

        return $this->avatar;
    }

    // ─── Authorization Helpers ───────────────────

    /** Administrador = cargo com role "admin" na tabela positions. */
    public function isAdmin(): bool
    {
        return \App\Support\UserHierarchy::isAdmin($this);
    }

    /**
     * Compatibilidade: permissões amplas seguem isAdmin().
     */
    public function hasPermission(string $permission): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if this user is a manager of the given user.
     */
    public function isManagerOf(User $user): bool
    {
        $current = $user;
        $maxDepth = 10;

        while ($current->manager_id && $maxDepth > 0) {
            if ($current->manager_id === $this->id) {
                return true;
            }
            $current = $current->manager;
            $maxDepth--;
        }

        return false;
    }

    /**
     * Get all subordinates recursively (flatten tree).
     */
    public function getAllSubordinateIds(): array
    {
        $ids = [];
        $this->collectSubordinateIds($ids);

        return $ids;
    }

    private function collectSubordinateIds(array &$ids): void
    {
        foreach ($this->subordinates as $sub) {
            $ids[] = $sub->id;
            $sub->collectSubordinateIds($ids);
        }
    }

    /**
     * Get team members: users sharing the same manager.
     */
    public function getTeamMembers()
    {
        if (! $this->manager_id) {
            return collect();
        }

        return User::where('manager_id', $this->manager_id)
            ->where('id', '!=', $this->id)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Scope: only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
