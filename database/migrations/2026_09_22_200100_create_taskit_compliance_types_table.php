<?php

use App\Support\CertificateTypes;
use App\Support\ComplianceTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_compliance_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('default_validity_months')->default(12);
            $table->string('category', 50)->default('compliance');
            $table->text('description')->nullable();
            $table->unsignedInteger('lead_time_days')->default(30);
            $table->string('frequency', 50)->default('annual');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $rows = [];

        foreach (CertificateTypes::list() as $slug => $meta) {
            $frequency = $meta['frequency'] ?? 'annual';
            $days = ComplianceTemplates::frequencyDays($frequency);
            $months = max(1, (int) round($days / 30.44));

            $rows[] = [
                'slug' => $slug,
                'name' => $meta['label'] ?? $slug,
                'default_validity_months' => $months,
                'category' => $meta['task_type'] ?? 'compliance',
                'description' => $meta['short'] ?? null,
                'lead_time_days' => $meta['lead_days'] ?? 30,
                'frequency' => $frequency,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('taskit_compliance_types')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_compliance_types');
    }
};
