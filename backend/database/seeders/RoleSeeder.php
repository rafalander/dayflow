<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'weight' => 100,
                'description' => 'Full system access',
                'is_admin' => true,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'weight' => 70,
                'description' => 'Can manage teams and approve vacations',
                'is_admin' => false,
            ],
            [
                'name' => 'Coordinator',
                'slug' => 'coordinator',
                'weight' => 50,
                'description' => 'Can coordinate projects and manage subordinates',
                'is_admin' => false,
            ],
            [
                'name' => 'Tech Lead',
                'slug' => 'tech_lead',
                'weight' => 30,
                'description' => 'Can lead technical teams',
                'is_admin' => false,
            ],
            [
                'name' => 'Developer',
                'slug' => 'developer',
                'weight' => 10,
                'description' => 'Standard developer role',
                'is_admin' => false,
            ],
            [
                'name' => 'Analyst',
                'slug' => 'analyst',
                'weight' => 10,
                'description' => 'Business analyst role',
                'is_admin' => false,
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'weight' => 5,
                'description' => 'Standard user role',
                'is_admin' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
