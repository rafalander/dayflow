<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->string('absence_type', 64)->default('vacation')->after('user_id');
            $table->index('absence_type');
        });
    }

    public function down(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->dropIndex(['absence_type']);
            $table->dropColumn('absence_type');
        });
    }
};
