<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            SuperadminSeeder::class,
        ]);

        if (config('dayflow.dev_password_login')) {
            $this->call(DevUserSeeder::class);
        }
    }
}
