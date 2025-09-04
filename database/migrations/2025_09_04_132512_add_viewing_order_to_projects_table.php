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
        Schema::table('taskit_projects', function (Blueprint $table) {
            $table->integer('viewing_order')->default(0)->after('color');
        });

        // Initialize viewing order for existing projects
        \App\Models\Project::initializeViewingOrder();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taskit_projects', function (Blueprint $table) {
            $table->dropColumn('viewing_order');
        });
    }
};
