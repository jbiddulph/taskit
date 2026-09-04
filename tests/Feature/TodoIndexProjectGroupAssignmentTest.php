<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoIndexProjectGroupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_todos_does_not_assign_another_projects_group_to_ungrouped_tasks(): void
    {
        $user = User::factory()->create();
        $currentProject = $this->makeProject($user, 'Current', 'CUR');
        $targetProject = $this->makeProject($user, 'Target', 'TGT');
        $staleGroup = ProjectGroup::createDefaultForProject($currentProject);
        $targetGroup = ProjectGroup::createDefaultForProject($targetProject);

        $ungrouped = Todo::create([
            'user_id' => $user->id,
            'project_id' => $targetProject->id,
            'project_group_id' => null,
            'title' => 'Compliance reminder',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
        ]);

        $this->actingAs($user)
            ->getJson('/api/todos?'.http_build_query([
                'project_id' => $targetProject->id,
                'project_group_id' => $staleGroup->id,
                'fresh' => 1,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('taskit_todos', [
            'id' => $ungrouped->id,
            'project_id' => $targetProject->id,
            'project_group_id' => $targetGroup->id,
        ]);
        $this->assertDatabaseMissing('taskit_todos', [
            'id' => $ungrouped->id,
            'project_group_id' => $staleGroup->id,
        ]);
    }

    public function test_listing_todos_still_assigns_ungrouped_tasks_to_the_requested_project_group(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProject($user, 'Board', 'BRD');
        ProjectGroup::createDefaultForProject($project);
        $extraGroup = ProjectGroup::create([
            'project_id' => $project->id,
            'name' => 'Extra board',
            'color' => '#10B981',
            'viewing_order' => 2,
            'is_default' => false,
        ]);

        $ungrouped = Todo::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_group_id' => null,
            'title' => 'Needs a board',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
        ]);

        $this->actingAs($user)
            ->getJson('/api/todos?'.http_build_query([
                'project_id' => $project->id,
                'project_group_id' => $extraGroup->id,
                'fresh' => 1,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('taskit_todos', [
            'id' => $ungrouped->id,
            'project_group_id' => $extraGroup->id,
        ]);
    }

    protected function makeProject(User $user, string $name, string $key): Project
    {
        return Project::create([
            'name' => $name,
            'key' => $key,
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'is_active' => true,
        ]);
    }
}
