<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoCopyProjectTest extends TestCase
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
            'description' => '<p>Two coats</p>',
            'priority' => 'High',
            'type' => 'Task',
            'tags' => ['outdoor', 'weekend'],
            'assignee' => 'Sam',
            'status' => 'in-progress',
        ], $overrides));
    }

    public function test_todo_can_be_copied_to_another_project(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target, $targetGroup] = $this->makeProject($user, 'House', 'HOU');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $response = $this
            ->actingAs($user)
            ->postJson("/api/todos/{$todo->id}/copy", [
                'project_id' => $target->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.project_id', $target->id)
            ->assertJsonPath('data.project_group_id', $targetGroup->id)
            ->assertJsonPath('data.title', 'Paint the fence')
            ->assertJsonPath('data.description', '<p>Two coats</p>')
            ->assertJsonPath('data.priority', 'High')
            ->assertJsonPath('data.tags', ['outdoor', 'weekend'])
            ->assertJsonPath('data.assignee', 'Sam')
            ->assertJsonPath('data.status', 'todo');

        $copyId = $response->json('data.id');
        $this->assertNotEquals($todo->id, $copyId);

        // Original is untouched.
        $this->assertDatabaseHas('taskit_todos', [
            'id' => $todo->id,
            'project_id' => $source->id,
            'status' => 'in-progress',
        ]);
        $this->assertSame(2, Todo::count());
    }

    public function test_copying_a_todo_copies_its_subtasks(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target, $targetGroup] = $this->makeProject($user, 'House', 'HOU');
        $parent = $this->makeTodo($user, $source, $sourceGroup);
        $this->makeTodo($user, $source, $sourceGroup, [
            'title' => 'Buy paint',
            'parent_task_id' => $parent->id,
            'status' => 'done',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson("/api/todos/{$parent->id}/copy", [
                'project_id' => $target->id,
            ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'data.subtasks')
            ->assertJsonPath('data.subtasks.0.title', 'Buy paint')
            ->assertJsonPath('data.subtasks.0.status', 'todo');

        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Buy paint',
            'parent_task_id' => $response->json('data.id'),
            'project_id' => $target->id,
            'project_group_id' => $targetGroup->id,
        ]);
        $this->assertSame(4, Todo::count());
    }

    public function test_todo_can_be_copied_into_a_specific_board_of_the_target_project(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target] = $this->makeProject($user, 'House', 'HOU');
        $otherBoard = ProjectGroup::create([
            'project_id' => $target->id,
            'name' => 'Backlog',
            'viewing_order' => 2,
            'is_default' => false,
        ]);
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $this
            ->actingAs($user)
            ->postJson("/api/todos/{$todo->id}/copy", [
                'project_id' => $target->id,
                'project_group_id' => $otherBoard->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.project_group_id', $otherBoard->id);
    }

    public function test_board_from_a_different_project_is_rejected(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$target] = $this->makeProject($user, 'House', 'HOU');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $this
            ->actingAs($user)
            ->postJson("/api/todos/{$todo->id}/copy", [
                'project_id' => $target->id,
                'project_group_id' => $sourceGroup->id,
            ])
            ->assertStatus(422);

        $this->assertSame(1, Todo::count());
    }

    public function test_todo_cannot_be_copied_to_an_inaccessible_project(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        [$foreign] = $this->makeProject($stranger, 'Secret', 'SEC');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $this
            ->actingAs($user)
            ->postJson("/api/todos/{$todo->id}/copy", [
                'project_id' => $foreign->id,
            ])
            ->assertForbidden();

        $this->assertSame(1, Todo::count());
    }

    public function test_project_id_is_required(): void
    {
        $user = User::factory()->create();
        [$source, $sourceGroup] = $this->makeProject($user, 'Garden', 'GAR');
        $todo = $this->makeTodo($user, $source, $sourceGroup);

        $this
            ->actingAs($user)
            ->postJson("/api/todos/{$todo->id}/copy", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }
}
