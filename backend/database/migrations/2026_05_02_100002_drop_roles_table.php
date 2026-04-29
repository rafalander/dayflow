<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        // Recriação mínima para rollback — use RoleSeeder completo em ambientes dev se necessário
        //
    }
};
