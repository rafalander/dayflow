<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 50)->default('slack');
            $table->string('type', 100);
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->boolean('sent_successfully')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
