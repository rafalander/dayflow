<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->index(['status', 'start_date', 'end_date'], 'vacation_requests_calendar_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->dropIndex('vacation_requests_calendar_idx');
        });
    }
};
