<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->text('about_text')->nullable()->after('industry');
            $table->string('homepage_tagline', 255)->nullable()->after('about_text');
            $table->string('homepage_background_url', 2048)->nullable()->after('homepage_tagline');
            $table->json('homepage_background_attribution')->nullable()->after('homepage_background_url');
            $table->string('homepage_background_industry', 64)->nullable()->after('homepage_background_attribution');
        });
    }

    public function down(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->dropColumn([
                'about_text',
                'homepage_tagline',
                'homepage_background_url',
                'homepage_background_attribution',
                'homepage_background_industry',
            ]);
        });
    }
};
