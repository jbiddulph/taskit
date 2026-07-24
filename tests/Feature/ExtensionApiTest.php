<?php

namespace Tests\Feature;

use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExtensionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_extension_me_requires_token(): void
    {
        $this->getJson('/api/extension/me')->assertUnauthorized();
    }

    public function test_extension_can_list_projects_with_token(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);

        Sanctum::actingAs($user, ['extension']);

        $this->getJson('/api/extension/projects')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $project->id, 'name' => 'Website']);
    }

    public function test_extension_can_create_todo_with_token(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        Sanctum::actingAs($user, ['extension']);

        $this->postJson('/api/extension/todos', [
            'project_id' => $project->id,
            'title' => 'Follow up from browser',
            'priority' => 'Medium',
            'status' => 'todo',
            'type' => 'Task',
            'description' => 'Captured from: https://example.com',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Follow up from browser');

        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Follow up from browser',
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_extension_can_create_todo_with_bearer_token_header(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        $plainTextToken = $user->createToken('Chrome Extension', ['extension'])->plainTextToken;

        $this->withToken($plainTextToken)
            ->postJson('/api/extension/todos', [
                'project_id' => $project->id,
                'title' => 'Bearer token task',
                'priority' => 'High',
                'status' => 'todo',
                'type' => 'Task',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Bearer token task');
    }

    public function test_existing_session_todo_create_still_works(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        $this->actingAs($user)
            ->postJson('/api/todos', [
                'project_id' => $project->id,
                'title' => 'Session task still works',
                'priority' => 'Medium',
                'status' => 'todo',
                'type' => 'Task',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Session task still works');
    }

    public function test_user_can_create_and_revoke_api_token_from_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/api-tokens', ['name' => 'Chrome Extension'])
            ->assertRedirect(route('api-tokens.edit'));

        $this->assertDatabaseHas('taskit_personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Chrome Extension',
        ]);

        $tokenId = PersonalAccessToken::query()->where('tokenable_id', $user->id)->value('id');

        $this->actingAs($user)
            ->delete("/settings/api-tokens/{$tokenId}")
            ->assertRedirect(route('api-tokens.edit'));

        $this->assertDatabaseMissing('taskit_personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }
}
