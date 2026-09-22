<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('taskit_company_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('taskit_companies')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('taskit_applications')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('subscription_plan')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'application_id']);
        });

        $now = now();
        DB::table('taskit_applications')->insert([
            ['name' => 'ZapTask', 'slug' => 'zaptask', 'description' => 'Core task management', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ZapTask Property', 'slug' => 'property', 'description' => 'Property compliance', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ZapTask Fleet', 'slug' => 'fleet', 'description' => 'Fleet and vehicle compliance', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ZapTask Estate', 'slug' => 'estate', 'description' => 'Estate agent operations', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ZapTask Personal', 'slug' => 'personal', 'description' => 'Personal AI assistant', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ZapTask Facilities', 'slug' => 'facilities', 'description' => 'Facilities management', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_company_applications');
        Schema::dropIfExists('taskit_applications');
    }
};
