<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('positions') || ! Schema::hasTable('users')) {
            return;
        }

        $now = now();

        $defs = [
            ['name' => 'Super Admin', 'slug' => 'dayflow-sys-superadmin', 'description' => 'Cargo de sistema — topo da hierarquia', 'role' => 'admin', 'level' => 1000],
            ['name' => 'Colaborador', 'slug' => 'dayflow-sys-colaborador', 'description' => 'Cargo de sistema — utilizador padrão', 'role' => 'user', 'level' => 20],
        ];

        foreach ($defs as $row) {
            DB::table('positions')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $ids = DB::table('positions')->whereIn('slug', array_column($defs, 'slug'))->pluck('id', 'slug');

        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        $superEmail = (string) config('dayflow.superadmin.email');
        if ($superEmail !== '' && isset($ids['dayflow-sys-superadmin'])) {
            DB::table('users')->where('email', $superEmail)->update(['cargo_id' => $ids['dayflow-sys-superadmin']]);
        }

        if (isset($ids['dayflow-sys-dev-admin'])) {
            DB::table('users')->where('email', 'dev@uello.com.br')->update(['cargo_id' => $ids['dayflow-sys-dev-admin']]);
        }

        if (isset($ids['dayflow-sys-superadmin'])) {
            DB::table('users')
                ->where('role', 'admin')
                ->where('level', '>=', 900)
                ->whereNull('cargo_id')
                ->update(['cargo_id' => $ids['dayflow-sys-superadmin']]);
        }

        if (isset($ids['dayflow-sys-dev-admin'])) {
            DB::table('users')
                ->where('role', 'admin')
                ->whereBetween('level', [100, 899])
                ->whereNull('cargo_id')
                ->update(['cargo_id' => $ids['dayflow-sys-dev-admin']]);
        }

        if (isset($ids['dayflow-sys-colaborador'])) {
            DB::table('users')->whereNull('cargo_id')->update(['cargo_id' => $ids['dayflow-sys-colaborador']]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'level']);
        });

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cargo_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY cargo_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN cargo_id SET NOT NULL');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('cargo_id')->references('id')->on('positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('Esta migração não pode ser revertida com segurança.');
    }
};
