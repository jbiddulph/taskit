<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->string('property_type', 50)->nullable()->after('type');
            $table->unsignedTinyInteger('bedrooms')->nullable()->after('property_type');
            $table->string('tenure', 50)->nullable()->after('bedrooms');
            $table->string('occupancy_status', 50)->default('occupied')->after('tenure');
        });

        Schema::table('taskit_compliance_requirements', function (Blueprint $table) {
            $table->date('issued_at')->nullable()->after('last_completed_at');
            $table->string('provider')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('taskit_compliance_requirements', function (Blueprint $table) {
            $table->dropColumn(['issued_at', 'provider']);
        });

        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->dropColumn(['property_type', 'bedrooms', 'tenure', 'occupancy_status']);
        });
    }
};
