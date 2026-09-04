<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('company_id')
                ->constrained('taskit_clients')
                ->nullOnDelete();

            $table->index(['company_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'client_id']);
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
