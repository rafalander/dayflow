<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('positions')) {
            return;
        }

        if (! Schema::hasColumn('positions', 'role')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->string('role', 10)->default('user')->after('slug');
                $table->unsignedInteger('level')->default(40)->after('role');
            });
        }

        if (Schema::hasColumn('positions', 'role_id')) {
            if (DB::table('roles')->exists()) {
                DB::statement('
                    UPDATE positions p
                    INNER JOIN roles r ON p.role_id = r.id
                    SET p.role = CASE WHEN r.is_admin = 1 THEN "admin" ELSE "user" END,
                        p.level = r.weight
                    WHERE p.role_id IS NOT NULL
                ');
            }

            Schema::table('positions', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('positions') || ! Schema::hasTable('roles')) {
            return;
        }

        if (! Schema::hasColumn('positions', 'role_id') && Schema::hasColumn('positions', 'role')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('description')->constrained('roles')->cascadeOnDelete();
            });

            $roles = DB::table('roles')->pluck('id', 'slug');

            DB::table('positions')->orderBy('id')->each(function ($p) use ($roles) {
                $slug = ($p->role === 'admin') ? 'admin' : 'user';
                $rid = $roles[$slug] ?? null;
                if ($rid) {
                    DB::table('positions')->where('id', $p->id)->update(['role_id' => $rid]);
                }
            });

            Schema::table('positions', function (Blueprint $table) {
                $table->dropColumn(['role', 'level']);
            });
        }
    }
};
