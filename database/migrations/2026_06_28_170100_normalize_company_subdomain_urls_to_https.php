<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('taskit_companies')
            ->whereNotNull('subdomain_url')
            ->where('subdomain_url', 'like', 'http://%')
            ->update([
                'subdomain_url' => DB::raw("REPLACE(subdomain_url, 'http://', 'https://')"),
            ]);
    }

    public function down(): void
    {
        // No rollback needed.
    }
};
