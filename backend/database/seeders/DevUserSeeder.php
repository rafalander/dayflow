<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('dayflow.dev_password_login')) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'dev@uello.com.br'],
            [
                'name' => 'Dev Local',
                'google_id' => 'local-dev-dayflow',
                'password' => 'password',
                'avatar' => null,
                'role' => User::ROLE_ADMIN,
                'level' => 500,
                'is_active' => true,
            ]
        );
    }
}
