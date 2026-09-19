<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoMoveProjectTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(User $owner, string $name, string $key): array
    {
        $project = Project::create([
            'name' => $name,
            'key' => $key,
            'color' => '#3B82F6',
            'owner_id' => $owner->id,
        ]);
        $group = ProjectGroup::createDefaultForProject($project);

        return [$project, $group];
    }

    private function makeTodo(User $user, Project $project, ProjectGroup $group, array $overrides = []): Todo
    {
        return Todo::create(array_merge([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_group_id' => $group->id,
            'title' => 'Paint the fence',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
        ], $overrides));
    }

    public function test_todo_can_be_moved_to_another_project(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target, $targetGroup] = $this->makeProject($user, 'House', 'HOU');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $response = $this
            ->actingAs($user)
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Paint the fence',
                'priority' => 'Medium',
                'status' => 'todo',
                'project_id' => $target->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.project_id', $target->id)
            ->assertJsonPath('data.project_group_id', $targetGroup->id)
            ->assertJsonPath('data.project.id', $target->id);

        $this->assertDatabaseHas('taskit_todos', [
            'id' => $todo->id,
            'project_id' => $target->id,
            'project_group_id' => $targetGroup->id,
        ]);
    }

    public function test_moving_a_todo_moves_its_subtasks_too(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target, $targetGroup] = $this->makeProject($user, 'House', 'HOU');
        $parent = $this->makeTodo($user, $source, $sourceGroup);
        $subtask = $this->makeTodo($user, $source, $sourceGroup, [
            'title' => 'Buy paint',
            'parent_task_id' => $parent->id,
        ]);

        $this
            ->actingAs($user)
            ->putJson("/api/todos/{$parent->id}", [
                'title' => 'Paint the fence',
                'priority' => 'Medium',
                'status' => 'todo',
                'project_id' => $target->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('taskit_todos', [
            'id' => $subtask->id,
            'project_id' => $target->id,
            'project_group_id' => $targetGroup->id,
        ]);
    }

    public function test_sending_the_same_project_id_is_a_no_op(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $this
            ->actingAs($user)
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Paint the fence (edited)',
                'priority' => 'High',
                'status' => 'todo',
                'project_id' => $source->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Paint the fence (edited)')
            ->assertJsonPath('data.project_id', $source->id)
            ->assertJsonPath('data.project_group_id', $sourceGroup->id);
    }

    public function test_subtask_cannot_be_moved_independently(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target] = $this->makeProject($user, 'House', 'HOU');
        $parent = $this->makeTodo($user, $source, $sourceGroup);
        $subtask = $this->makeTodo($user, $source, $sourceGroup, [
            'title' => 'Buy paint',
            'parent_task_id' => $parent->id,
        ]);

        $this
            ->actingAs($user)
            ->putJson("/api/todos/{$subtask->id}", [
                'title' => 'Buy paint',
                'priority' => 'Medium',
                'status' => 'todo',
                'project_id' => $target->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('taskit_todos', [
            'id' => $subtask->id,
            'project_id' => $source->id,
        ]);
    }

    public function test_todo_cannot_be_moved_to_an_inaccessible_project(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$foreign] = $this->makeProject($stranger, 'Secret', 'SEC');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $this
            ->actingAs($user)
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Paint the fence',
                'priority' => 'Medium',
                'status' => 'todo',
                'project_id' => $foreign->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('taskit_todos', [
            'id' => $todo->id,
            'project_id' => $source->id,
        ]);
    }

    public function test_board_from_old_project_is_rejected_when_moving(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target] = $this->makeProject($user, 'House', 'HOU');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $this
            ->actingAs($user)
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Paint the fence',
                'priority' => 'Medium',
                'status' => 'todo',
                'project_id' => $target->id,
                'project_group_id' => $sourceGroup->id,
            ])
            ->assertStatus(422);
    }
}
