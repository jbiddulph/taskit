<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoCustomTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_todo_can_be_updated_with_custom_other_type(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        $group = ProjectGroup::createDefaultForProject($project);
        $todo = Todo::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_group_id' => $group->id,
            'title' => 'Mow the lawn',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
        ]);

        $response = $this
            ->actingAs($user)
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Mow the lawn',
                'description' => null,
                'priority' => 'Medium',
                'type' => 'gardening',
                'tags' => [],
                'assignee' => null,
                'due_date' => null,
                'story_points' => null,
                'status' => 'todo',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'gardening');

        $this->assertDatabaseHas('taskit_todos', [
            'id' => $todo->id,
            'type' => 'gardening',
        ]);
    }

    public function test_todo_can_be_created_with_custom_other_type(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        $response = $this
            ->actingAs($user)
            ->postJson('/api/todos', [
                'project_id' => $project->id,
                'title' => 'Trim hedges',
                'description' => null,
                'priority' => 'Medium',
                'type' => 'gardening',
                'tags' => [],
                'assignee' => null,
                'due_date' => null,
                'story_points' => null,
                'status' => 'todo',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'gardening');

        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Trim hedges',
            'type' => 'gardening',
        ]);
    }

    public function test_literal_other_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        $group = ProjectGroup::createDefaultForProject($project);
        $todo = Todo::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_group_id' => $group->id,
            'title' => 'Example',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
        ]);

        $response = $this
            ->actingAs($user)
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Example',
                'priority' => 'Medium',
                'type' => 'Other',
                'status' => 'todo',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
