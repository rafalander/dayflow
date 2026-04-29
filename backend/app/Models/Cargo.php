<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    protected $table = 'positions';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'role',
        'level',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'cargo_id');
    }
}
