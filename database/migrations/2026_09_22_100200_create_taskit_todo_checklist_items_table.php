<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_todo_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained('taskit_todos')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('completed')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('taskit_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['todo_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_todo_checklist_items');
    }
};
