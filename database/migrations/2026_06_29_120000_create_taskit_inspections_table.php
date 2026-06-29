<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('taskit_companies')->cascadeOnDelete();
            $table->foreignId('operational_object_id')->constrained('taskit_operational_objects')->cascadeOnDelete();
            $table->foreignId('compliance_requirement_id')->nullable()->constrained('taskit_compliance_requirements')->nullOnDelete();
            $table->foreignId('inspector_user_id')->constrained('taskit_users')->cascadeOnDelete();
            $table->string('template_key', 100);
            $table->string('label');
            $table->string('status', 30)->default('draft');
            $table->json('responses')->nullable();
            $table->text('summary')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['operational_object_id', 'status']);
            $table->index(['company_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_inspections');
    }
};
