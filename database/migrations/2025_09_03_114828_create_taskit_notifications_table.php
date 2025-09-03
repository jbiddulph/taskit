<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('taskit_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('taskit_users')->onDelete('cascade');
            $table->string('type')->default('info'); // info, warning, error, success
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data like project_id, todo_id, etc.
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taskit_notifications');
    }
};