<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->enum('subscription_type', ['FREE', 'MIDI', 'MAXI'])->default('FREE')->after('code');
        });

        // Update existing companies to FREE
        DB::table('taskit_companies')->update(['subscription_type' => 'FREE']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->dropColumn('subscription_type');
        });
    }
};