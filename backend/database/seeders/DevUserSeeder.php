<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('dayflow.dev_password_login')) {
            return;
        }

        $slug = config('dayflow.system_cargo_slugs.dev_admin');
        $cargoId = Cargo::where('slug', $slug)->value('id');

        if (! $cargoId) {
            throw new \RuntimeException("Cargo dev não encontrado (slug: {$slug}). Execute as migrações.");
        }

        User::updateOrCreate(
            ['email' => 'dev@uello.com.br'],
            [
                'name' => 'Dev Local',
                'google_id' => 'local-dev-dayflow',
                'password' => Hash::make('password'),
                'avatar' => null,
                'cargo_id' => $cargoId,
                'is_active' => true,
            ]
        );
    }
}
