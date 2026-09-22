<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('taskit_companies')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('taskit_users')->nullOnDelete();
            $table->string('name');
            $table->string('trigger_type', 50);
            $table->json('trigger_config')->nullable();
            $table->string('action_type', 50);
            $table->json('action_config')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'enabled']);
            $table->index(['company_id', 'trigger_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_automations');
    }
};
