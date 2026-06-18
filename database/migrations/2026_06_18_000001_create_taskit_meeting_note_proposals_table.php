<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_meeting_note_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('taskit_users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('taskit_companies')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('meeting_summary')->nullable();
            $table->longText('transcript')->nullable();
            $table->json('action_items');
            $table->json('metadata')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('taskit_projects')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_meeting_note_proposals');
    }
};
