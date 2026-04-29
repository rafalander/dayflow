<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('dayflow.superadmin.email')],
            [
                'name' => 'Super Admin',
                'google_id' => 'superadmin-dayflow-internal',
                'password' => config('dayflow.superadmin.password'),
                'avatar' => null,
                'role' => User::ROLE_ADMIN,
                'level' => config('dayflow.superadmin_level', 1000),
                'is_active' => true,
            ]
        );
    }
}
