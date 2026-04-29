<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 10)->default('user')->after('email');
                $table->unsignedInteger('level')->default(20)->after('role');
            });
        }

        if (Schema::hasColumn('users', 'role_id')) {
            if (DB::table('roles')->exists()) {
                DB::statement('
                    UPDATE users u
                    INNER JOIN roles r ON u.role_id = r.id
                    SET u.role = CASE WHEN r.is_admin = 1 THEN "admin" ELSE "user" END,
                        u.level = r.weight
                    WHERE u.role_id IS NOT NULL
                ');
            }

            DB::table('users')->where(function ($q) {
                $q->whereNull('role')->orWhere('role', '');
            })->update([
                'role' => 'user',
                'level' => 20,
            ]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        if (! Schema::hasColumn('users', 'role_id') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('custom_avatar')->constrained('roles')->nullOnDelete();
            });

            $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
            $userId = DB::table('roles')->where('slug', 'user')->value('id');

            DB::statement('UPDATE users SET role_id = CASE WHEN role = "admin" THEN ? ELSE ? END WHERE role IS NOT NULL', [
                $adminId,
                $userId,
            ]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['role', 'level']);
            });
        }
    }
};
