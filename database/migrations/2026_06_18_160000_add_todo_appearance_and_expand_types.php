<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE taskit_todos ALTER COLUMN type TYPE VARCHAR(50) USING type::text');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE taskit_todos MODIFY type VARCHAR(50) NULL');
        }

        Schema::table('taskit_todos', function (Blueprint $table) {
            if (! Schema::hasColumn('taskit_todos', 'card_icon')) {
                $table->string('card_icon', 50)->nullable()->after('type');
            }
            if (! Schema::hasColumn('taskit_todos', 'outline_color')) {
                $table->string('outline_color', 7)->nullable()->after('card_icon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            if (Schema::hasColumn('taskit_todos', 'outline_color')) {
                $table->dropColumn('outline_color');
            }
            if (Schema::hasColumn('taskit_todos', 'card_icon')) {
                $table->dropColumn('card_icon');
            }
        });
    }
};
