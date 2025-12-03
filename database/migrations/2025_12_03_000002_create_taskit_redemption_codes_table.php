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
        // In production, this table may already exist (created manually in Supabase),
        // so guard against duplicate-table errors during deploy.
        if (Schema::hasTable('taskit_redemption_codes')) {
            return;
        }

        Schema::create('taskit_redemption_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('ltd_tier_id')
                ->constrained('taskit_ltd_tiers')
                ->onDelete('cascade');
            $table->boolean('redeemed')->default(false);
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('taskit_users')
                ->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taskit_redemption_codes');
    }
};


