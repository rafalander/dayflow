<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $slug = config('dayflow.system_cargo_slugs.superadmin');
        $cargoId = Cargo::where('slug', $slug)->value('id');

        if (! $cargoId) {
            throw new \RuntimeException("Cargo de sistema não encontrado (slug: {$slug}). Execute as migrações.");
        }

        User::updateOrCreate(
            ['email' => config('dayflow.superadmin.email')],
            [
                'name' => 'Super Admin',
                'google_id' => 'superadmin-dayflow-internal',
                'password' => Hash::make((string) config('dayflow.superadmin.password')),
                'avatar' => null,
                'cargo_id' => $cargoId,
                'is_active' => true,
            ]
        );
    }
}
