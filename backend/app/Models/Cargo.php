<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    public static function uniqueSlugFromName(string $name): string
    {
        $base = Str::slug($name) ?: 'cargo';
        $slug = $base;
        $n = 0;
        while (static::query()->where('slug', $slug)->exists()) {
            $n++;
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
