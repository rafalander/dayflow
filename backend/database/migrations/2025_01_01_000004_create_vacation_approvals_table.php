<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacation_request_id')->constrained('vacation_requests')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['approved', 'rejected']);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('vacation_request_id');
            $table->index('approver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_approvals');
    }
};
