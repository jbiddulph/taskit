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
            $table->string('stripe_customer_id')->nullable()->after('subscription_type');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('stripe_subscription_id');
            $table->enum('subscription_status', ['active', 'canceled', 'past_due', 'incomplete', 'trialing'])->default('active')->after('subscription_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id', 
                'subscription_ends_at',
                'subscription_status'
            ]);
        });
    }
};