<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->integer('weight')->default(0)->comment('Higher = more authority');
            $table->string('color', 7)->default('#6366F1');
            $table->json('permissions')->nullable()->comment('JSON array of permission strings');
            $table->text('description')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->index('weight');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
