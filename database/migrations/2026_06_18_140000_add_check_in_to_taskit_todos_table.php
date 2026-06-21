<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('longitude');
            $table->decimal('checked_in_latitude', 10, 7)->nullable()->after('checked_in_at');
            $table->decimal('checked_in_longitude', 10, 7)->nullable()->after('checked_in_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_in_latitude', 'checked_in_longitude']);
        });
    }
};
