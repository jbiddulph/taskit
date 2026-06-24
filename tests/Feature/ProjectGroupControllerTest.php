<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_non_default_project_group_deletes_its_todos(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        $defaultGroup = ProjectGroup::createDefaultForProject($project);
        $extraGroup = ProjectGroup::create([
            'project_id' => $project->id,
            'name' => 'Extra board',
            'color' => '#10B981',
            'viewing_order' => 2,
            'is_default' => false,
        ]);
        $defaultTodo = Todo::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_group_id' => $defaultGroup->id,
            'title' => 'Keep me',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
        ]);
        $deletedTodo = Todo::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_group_id' => $extraGroup->id,
            'title' => 'Delete me',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson("/api/project-groups/{$extraGroup->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Group and 1 todos deleted successfully',
            ]);

        $this->assertDatabaseMissing('taskit_project_groups', ['id' => $extraGroup->id]);
        $this->assertDatabaseMissing('taskit_todos', ['id' => $deletedTodo->id]);
        $this->assertDatabaseHas('taskit_project_groups', ['id' => $defaultGroup->id]);
        $this->assertDatabaseHas('taskit_todos', ['id' => $defaultTodo->id]);
    }

    public function test_default_project_group_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        $defaultGroup = ProjectGroup::createDefaultForProject($project);

        $response = $this
            ->actingAs($user)
            ->deleteJson("/api/project-groups/{$defaultGroup->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The default board cannot be deleted.',
            ]);

        $this->assertDatabaseHas('taskit_project_groups', ['id' => $defaultGroup->id]);
    }
}
