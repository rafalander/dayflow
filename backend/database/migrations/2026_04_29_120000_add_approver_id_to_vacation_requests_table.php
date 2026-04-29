<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->foreignId('approver_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->dropForeign(['approver_id']);
            $table->dropColumn('approver_id');
        });
    }
};
