<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        User::updateOrCreate(
            ['email' => config('dayflow.superadmin.email')],
            [
                'name' => 'Super Admin',
                'google_id' => 'superadmin-dayflow-internal',
                'password' => config('dayflow.superadmin.password'),
                'avatar' => null,
                'role_id' => $adminRole?->id,
                'is_active' => true,
            ]
        );
    }
}
