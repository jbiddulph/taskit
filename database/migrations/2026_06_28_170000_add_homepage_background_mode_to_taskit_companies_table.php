<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->string('homepage_background_mode', 20)->default('industry')->after('homepage_background_industry');
            $table->string('homepage_background_unsplash_id', 64)->nullable()->after('homepage_background_mode');
        });
    }

    public function down(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->dropColumn(['homepage_background_mode', 'homepage_background_unsplash_id']);
        });
    }
};
