<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->foreignId('operational_object_id')
                ->nullable()
                ->after('company_id')
                ->constrained('taskit_operational_objects')
                ->nullOnDelete();
            $table->foreignId('compliance_requirement_id')
                ->nullable()
                ->after('operational_object_id')
                ->constrained('taskit_compliance_requirements')
                ->nullOnDelete();
            $table->string('source', 50)->nullable()->after('compliance_requirement_id');

            $table->index('operational_object_id');
            $table->index('compliance_requirement_id');
        });
    }

    public function down(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->dropForeign(['operational_object_id']);
            $table->dropForeign(['compliance_requirement_id']);
            $table->dropColumn(['operational_object_id', 'compliance_requirement_id', 'source']);
        });
    }
};
