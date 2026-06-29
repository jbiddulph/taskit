<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_operational_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('taskit_companies')->cascadeOnDelete();
            $table->foreignId('operational_object_id')->constrained('taskit_operational_objects')->cascadeOnDelete();
            $table->foreignId('compliance_requirement_id')->nullable()->constrained('taskit_compliance_requirements')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('taskit_users')->nullOnDelete();
            $table->string('title');
            $table->string('document_type', 100)->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->date('expires_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('extracted_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['operational_object_id', 'expires_at']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_operational_documents');
    }
};
