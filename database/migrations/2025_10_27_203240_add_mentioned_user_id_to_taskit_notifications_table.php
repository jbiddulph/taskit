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
        Schema::table('taskit_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('mentioned_user_id')->nullable()->after('user_id');
            $table->foreign('mentioned_user_id')->references('id')->on('taskit_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taskit_notifications', function (Blueprint $table) {
            $table->dropForeign(['mentioned_user_id']);
            $table->dropColumn('mentioned_user_id');
        });
    }
};
