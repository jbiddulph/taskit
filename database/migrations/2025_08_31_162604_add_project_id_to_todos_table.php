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
        Schema::table('todos', function (Blueprint $table) {
            $table->foreignId('project_id')->after('user_id')->constrained()->onDelete('cascade');
            $table->index(['user_id', 'project_id', 'status']);
            $table->index(['project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['user_id', 'project_id', 'status']);
            $table->dropIndex(['project_id', 'status']);
            $table->dropColumn('project_id');
        });
    }
};
