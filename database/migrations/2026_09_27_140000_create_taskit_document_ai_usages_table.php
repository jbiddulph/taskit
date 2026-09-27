<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_document_ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('taskit_companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('taskit_users')->nullOnDelete();
            $table->string('kind', 40); // extraction | portfolio_ask
            $table->nullableMorphs('reference');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_document_ai_usages');
    }
};
