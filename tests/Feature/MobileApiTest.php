<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_requires_credentials(): void
    {
        $this->postJson('/api/mobile/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_mobile_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'field@zaptask.co.uk',
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => 'field@zaptask.co.uk',
            'password' => 'wrong-password',
            'device_name' => 'iPhone',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_mobile_login_returns_token_and_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Field Agent',
            'email' => 'field@zaptask.co.uk',
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => 'field@zaptask.co.uk',
            'password' => 'password',
            'device_name' => 'iPhone 16',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'field@zaptask.co.uk')
            ->assertJsonPath('data.user.name', 'Field Agent')
            ->assertJsonStructure([
                'token',
                'data' => [
                    'user' => ['id', 'name', 'email', 'company_id'],
                    'company',
                    'task_types',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('taskit_personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'iPhone 16',
        ]);
    }

    public function test_mobile_me_requires_token(): void
    {
        $this->getJson('/api/mobile/me')->assertUnauthorized();
    }

    public function test_mobile_me_returns_profile_with_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Field Agent',
            'email' => 'field@zaptask.co.uk',
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'field@zaptask.co.uk');
    }

    public function test_mobile_can_list_today_summary_and_todos_with_bearer_token(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Viewings',
            'key' => 'VIEW',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        Todo::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Inspect 12 High Street',
            'priority' => 'High',
            'status' => 'todo',
            'type' => 'Viewing',
            'due_date' => now()->toDateString(),
        ]);

        $plainTextToken = $user->createToken('iPhone', ['mobile'])->plainTextToken;

        $this->withToken($plainTextToken)
            ->getJson('/api/mobile/today/summary')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['overdue', 'due_today', 'viewings', 'spoken_summary']]);

        $this->withToken($plainTextToken)
            ->getJson('/api/mobile/todos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['title' => 'Inspect 12 High Street']);

        $this->withToken($plainTextToken)
            ->getJson('/api/mobile/projects')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Viewings']);
    }

    public function test_mobile_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('iPhone', ['mobile'])->plainTextToken;

        $this->withToken($plainTextToken)
            ->postJson('/api/mobile/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('taskit_personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'iPhone',
        ]);
    }

    public function test_mobile_can_create_and_update_todo_with_put_and_tags(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Viewings',
            'key' => 'VIEW',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        $plainTextToken = $user->createToken('iPhone', ['mobile'])->plainTextToken;

        $create = $this->withToken($plainTextToken)
            ->postJson('/api/mobile/todos', [
                'project_id' => $project->id,
                'title' => 'Inspect flat',
                'tags' => 'urgent, viewing',
                'location_name' => '12 High Street',
                'latitude' => 50.8225,
                'longitude' => -0.1372,
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Inspect flat')
            ->assertJsonPath('data.priority', 'Medium')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.tags.0', 'urgent')
            ->assertJsonPath('data.tags.1', 'viewing')
            ->assertJsonPath('data.location_name', '12 High Street');

        $todoId = $create->json('data.id');

        $this->withToken($plainTextToken)
            ->putJson("/api/mobile/todos/{$todoId}", [
                'title' => 'Inspect flat — updated',
                'description' => 'Bring keys',
                'tags' => ['keys', 'urgent'],
                'priority' => 'High',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Inspect flat — updated')
            ->assertJsonPath('data.description', 'Bring keys')
            ->assertJsonPath('data.priority', 'High')
            ->assertJsonPath('data.tags.0', 'keys');

        $this->withToken($plainTextToken)
            ->patchJson("/api/mobile/todos/{$todoId}/status", [
                'status' => 'in-progress',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in-progress');
    }

    public function test_mobile_exposes_voice_and_mapbox_routes(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('iPhone', ['mobile'])->plainTextToken;

        $this->withToken($plainTextToken)
            ->getJson('/api/mobile/mapbox/status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['configured']]);

        $this->withToken($plainTextToken)
            ->postJson('/api/mobile/voice-commands/process', [])
            ->assertStatus(422);
    }
}
