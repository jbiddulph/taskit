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
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->enum('type', ['Bug', 'Feature', 'Task', 'Story', 'Epic'])->nullable();
            $table->json('tags')->nullable();
            $table->string('assignee')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('story_points')->nullable();
            $table->enum('status', ['todo', 'in-progress', 'done'])->default('todo');
            $table->timestamps();
            
            $table->index(['user_id', 'project_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['assignee', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
