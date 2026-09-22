<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('company_id')
                ->constrained('taskit_workspaces')
                ->nullOnDelete();
            $table->string('category', 100)->nullable()->after('type');
            $table->json('metadata')->nullable()->after('source');
            $table->json('recurrence')->nullable()->after('metadata');
            $table->timestamp('completed_at')->nullable()->after('due_date');

            $table->index(['company_id', 'workspace_id']);
            $table->index(['company_id', 'category']);
        });

        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('company_id')
                ->constrained('taskit_workspaces')
                ->nullOnDelete();
            $table->string('status', 50)->default('active')->after('is_active');

            $table->index(['company_id', 'workspace_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('taskit_projects', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('company_id')
                ->constrained('taskit_workspaces')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('taskit_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropColumn('status');
        });

        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropColumn(['category', 'metadata', 'recurrence', 'completed_at']);
        });
    }
};
