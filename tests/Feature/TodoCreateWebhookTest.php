<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TodoCreateWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProject(): array
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        return [$user, $project];
    }

    public function test_todo_is_created_when_n8n_webhook_returns_503(): void
    {
        [$user, $project] = $this->createUserWithProject();

        $webhookUrl = 'https://n8njb-6378e565ae08.herokuapp.com/webhook/new-task';
        config(['services.n8n.new_task_webhook_url' => $webhookUrl]);

        Http::fake([
            $webhookUrl => Http::response('Service Unavailable', 503),
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson('/api/todos', [
                'project_id' => $project->id,
                'title' => 'Task despite n8n 503',
                'priority' => 'Medium',
                'status' => 'todo',
                'type' => 'Task',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Task despite n8n 503');

        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Task despite n8n 503',
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        Http::assertSent(fn ($request) => $request->url() === $webhookUrl);
    }

    public function test_todo_is_created_when_n8n_webhook_times_out(): void
    {
        [$user, $project] = $this->createUserWithProject();

        $webhookUrl = 'https://n8njb-6378e565ae08.herokuapp.com/webhook/new-task';
        config(['services.n8n.new_task_webhook_url' => $webhookUrl]);

        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $response = $this
            ->actingAs($user)
            ->postJson('/api/todos', [
                'project_id' => $project->id,
                'title' => 'Task despite n8n timeout',
                'priority' => 'Medium',
                'status' => 'todo',
                'type' => 'Task',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Task despite n8n timeout');

        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Task despite n8n timeout',
            'user_id' => $user->id,
        ]);
    }

    public function test_todo_is_created_when_n8n_webhook_is_not_configured(): void
    {
        [$user, $project] = $this->createUserWithProject();

        config(['services.n8n.new_task_webhook_url' => null]);
        Http::fake();

        $this
            ->actingAs($user)
            ->postJson('/api/todos', [
                'project_id' => $project->id,
                'title' => 'Task without webhook',
                'priority' => 'Medium',
                'status' => 'todo',
                'type' => 'Task',
            ])
            ->assertCreated();

        Http::assertNothingSent();
    }
}
