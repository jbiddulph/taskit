<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_project_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('taskit_projects')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('viewing_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'viewing_order']);
        });

        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->foreignId('project_group_id')
                ->nullable()
                ->after('project_id')
                ->constrained('taskit_project_groups')
                ->nullOnDelete();
        });

        $projectIds = DB::table('taskit_projects')->pluck('id');

        foreach ($projectIds as $projectId) {
            $groupId = DB::table('taskit_project_groups')->insertGetId([
                'project_id' => $projectId,
                'name' => 'Main board',
                'description' => null,
                'color' => null,
                'viewing_order' => 1,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('taskit_todos')
                ->where('project_id', $projectId)
                ->update(['project_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_group_id');
        });

        Schema::dropIfExists('taskit_project_groups');
    }
};
