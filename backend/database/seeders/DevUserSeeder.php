<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('dayflow.dev_password_login')) {
            return;
        }

        $adminRole = Role::where('slug', 'admin')->first();

        User::updateOrCreate(
            ['email' => 'dev@uello.com.br'],
            [
                'name' => 'Dev Local',
                'google_id' => 'local-dev-dayflow',
                'password' => 'password',
                'avatar' => null,
                'role_id' => $adminRole?->id,
                'is_active' => true,
            ]
        );
    }
}
