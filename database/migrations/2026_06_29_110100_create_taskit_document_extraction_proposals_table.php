<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_document_extraction_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('taskit_users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('taskit_companies')->nullOnDelete();
            $table->foreignId('operational_document_id')->constrained('taskit_operational_documents')->cascadeOnDelete();
            $table->foreignId('operational_object_id')->constrained('taskit_operational_objects')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->json('extracted_data');
            $table->text('summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_document_extraction_proposals');
    }
};
