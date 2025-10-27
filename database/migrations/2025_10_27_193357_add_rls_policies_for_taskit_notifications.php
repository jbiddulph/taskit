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
        // Note: RLS policies should be configured directly in Supabase
        // This migration is a placeholder for documentation
        // To enable notifications:
        // 1. Go to Supabase Dashboard > Database > Replication
        // 2. Make sure 'taskit_notifications' is enabled for replication
        // 3. Add RLS policy: Allow SELECT where user_id matches authenticated user
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op as RLS is managed in Supabase
    }
};
