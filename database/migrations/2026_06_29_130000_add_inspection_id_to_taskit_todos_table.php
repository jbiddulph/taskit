<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->foreignId('inspection_id')
                ->nullable()
                ->after('compliance_requirement_id')
                ->constrained('taskit_inspections')
                ->nullOnDelete();

            $table->index('inspection_id');
        });
    }

    public function down(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->dropForeign(['inspection_id']);
            $table->dropColumn('inspection_id');
        });
    }
};
