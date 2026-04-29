<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compatibilidade: bases que ainda têm `cargos` ou `job_titles` em vez de `positions`.
 * Instalações novas já criam `positions` — esta migração não altera nada nesses casos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('positions')) {
            return;
        }

        if (! Schema::hasTable('job_titles') && ! Schema::hasTable('cargos')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cargo_id']);
        });

        if (Schema::hasTable('job_titles')) {
            Schema::rename('job_titles', 'positions');
        } else {
            Schema::rename('cargos', 'positions');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('cargo_id')
                ->references('id')
                ->on('positions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('positions')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cargo_id']);
        });

        Schema::rename('positions', 'job_titles');

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('cargo_id')
                ->references('id')
                ->on('job_titles')
                ->nullOnDelete();
        });
    }
};
