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
        Schema::create('taskit_activities', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // todo_created, todo_updated, todo_deleted, todo_commented, etc.
            $table->unsignedBigInteger('actor_id'); // User who performed the action
            $table->string('actor_name'); // Cached name for performance
            $table->string('actor_email')->nullable(); // Cached email for performance
            $table->unsignedBigInteger('target_id')->nullable(); // ID of the target (todo, project, etc.)
            $table->string('target_name')->nullable(); // Name of the target (todo title, project name, etc.)
            $table->text('description'); // Human-readable description
            $table->json('metadata')->nullable(); // Additional data (old values, new values, etc.)
            $table->unsignedBigInteger('project_id')->nullable(); // Project context
            $table->unsignedBigInteger('company_id'); // Company context
            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('target_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taskit_activities');
    }
};