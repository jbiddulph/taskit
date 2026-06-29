<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_operational_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('taskit_companies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('taskit_operational_objects')->nullOnDelete();
            $table->string('type', 50);
            $table->string('name');
            $table->string('reference')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('taskit_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
            $table->index(['parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_operational_objects');
    }
};
