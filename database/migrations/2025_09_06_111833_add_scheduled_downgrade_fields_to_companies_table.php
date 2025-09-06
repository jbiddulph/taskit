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
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->string('scheduled_subscription_type')->nullable()->after('subscription_type');
            $table->timestamp('scheduled_change_date')->nullable()->after('subscription_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->dropColumn(['scheduled_subscription_type', 'scheduled_change_date']);
        });
    }
};