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
        Schema::create('taskit_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('key')->unique(); // Project key like "PROJ", "FEAT", etc.
            $table->string('color', 7)->default('#3B82F6'); // Hex color for project
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_id')->constrained('taskit_users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['owner_id', 'is_active']);
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taskit_projects');
    }
};
