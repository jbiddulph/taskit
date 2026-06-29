<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_compliance_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('taskit_companies')->cascadeOnDelete();
            $table->foreignId('operational_object_id')->constrained('taskit_operational_objects')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('taskit_projects')->nullOnDelete();
            $table->string('requirement_type', 100);
            $table->string('label');
            $table->string('frequency', 50)->default('annual');
            $table->unsignedSmallInteger('lead_time_days')->default(30);
            $table->date('next_due_date')->nullable();
            $table->date('last_completed_at')->nullable();
            $table->string('assignee')->nullable();
            $table->string('status', 30)->default('missing');
            $table->text('notes')->nullable();
            $table->boolean('auto_create_tasks')->default(true);
            $table->timestamps();

            $table->unique(['operational_object_id', 'requirement_type'], 'compliance_object_type_unique');
            $table->index(['company_id', 'status']);
            $table->index(['next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_compliance_requirements');
    }
};
