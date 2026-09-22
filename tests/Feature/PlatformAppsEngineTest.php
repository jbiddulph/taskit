<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Automation;
use App\Models\Company;
use App\Models\PlatformApplication;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAppsEngineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Company, 2: Project}
     */
    protected function createCompanySetup(string $subscription = 'MAXI'): array
    {
        $company = Company::create([
            'name' => 'Builder Co',
            'code' => 'BLD'.random_int(1000, 9999),
            'subscription_type' => $subscription,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Alex Manager',
        ]);

        $project = Project::create([
            'name' => 'Property Ops',
            'key' => 'PROP',
            'color' => '#0EA5E9',
            'owner_id' => $user->id,
            'company_id' => $company->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        return [$user, $company, $project];
    }

    public function test_platform_ai_propose_and_confirm_via_api_key(): void
    {
        [$user, $company, $project] = $this->createCompanySetup();
        $result = ApiKey::generate($company, 'Property Ops', $user);
        $plainKey = $result['plain_text_key'];

        $propose = $this->withToken($plainKey)
            ->postJson('/api/v1/ai', [
                'message' => 'Remind Alex to renew gas safety tomorrow',
                'context' => 'property_ops',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.intent', 'create_task')
            ->assertJsonPath('data.requires_confirmation', true);

        $task = $propose->json('data.task');
        $this->assertNotEmpty($task['title'] ?? null);

        $this->withToken($plainKey)
            ->postJson('/api/v1/ai', [
                'confirm' => true,
                'project_id' => $project->id,
                'message' => 'Remind Alex to renew gas safety tomorrow',
                'task' => [
                    'title' => $task['title'],
                    'assigned_to' => $task['assigned_to'] ?? null,
                    'due_date' => $task['due_date'] ?? now()->addDay()->toDateString(),
                    'category' => 'compliance',
                    'priority' => 'high',
                    'description' => $task['description'] ?? null,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.task.category', 'compliance');

        $this->assertDatabaseHas('taskit_todos', [
            'company_id' => $company->id,
            'source' => 'ai',
            'category' => 'compliance',
        ]);
    }

    public function test_platform_ai_denied_without_ai_write_permission(): void
    {
        [$user, $company] = $this->createCompanySetup();
        $result = ApiKey::generate($company, 'No AI', $user, ['tasks.read', 'tasks.write']);

        $this->withToken($result['plain_text_key'])
            ->postJson('/api/v1/ai', [
                'message' => 'Create a task for tomorrow',
            ])
            ->assertForbidden();
    }

    public function test_task_completed_automation_creates_follow_up(): void
    {
        [$user, $company, $project] = $this->createCompanySetup();

        Automation::create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'name' => 'After inspection',
            'trigger_type' => Automation::TRIGGER_TASK_COMPLETED,
            'trigger_config' => [],
            'action_type' => Automation::ACTION_CREATE_TASK,
            'action_config' => [
                'title' => 'File report for {task_title}',
                'project_id' => $project->id,
                'priority' => 'Medium',
            ],
            'enabled' => true,
        ]);

        $todo = Todo::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'project_id' => $project->id,
            'title' => 'Gas safety inspection',
            'priority' => 'High',
            'status' => 'todo',
            'type' => 'Task',
            'source' => 'api',
        ]);

        $todo->update(['status' => 'done', 'completed_at' => now()]);

        $this->assertDatabaseHas('taskit_todos', [
            'company_id' => $company->id,
            'title' => 'File report for Gas safety inspection',
            'source' => 'automation',
        ]);
    }

    public function test_task_created_automation_fires_but_automation_source_does_not_loop(): void
    {
        [$user, $company, $project] = $this->createCompanySetup();

        Automation::create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'name' => 'On create notify chain',
            'trigger_type' => Automation::TRIGGER_TASK_CREATED,
            'trigger_config' => [],
            'action_type' => Automation::ACTION_CREATE_TASK,
            'action_config' => [
                'title' => 'Follow-up for {task_title}',
                'project_id' => $project->id,
            ],
            'enabled' => true,
        ]);

        Todo::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'project_id' => $project->id,
            'title' => 'Original work',
            'priority' => 'Medium',
            'status' => 'todo',
            'type' => 'Task',
            'source' => 'api',
        ]);

        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Follow-up for Original work',
            'source' => 'automation',
        ]);

        // Automation-created task must not re-trigger infinite create_task chain
        $this->assertSame(
            1,
            Todo::query()->forCompany($company->id)->where('source', 'automation')->count()
        );
    }

    public function test_company_can_enable_platform_application_via_settings(): void
    {
        [$user, $company] = $this->createCompanySetup();

        $this->assertNotNull(PlatformApplication::query()->where('slug', 'property')->first());

        $this->actingAs($user)
            ->put('/settings/platform-applications/property', ['enabled' => true])
            ->assertRedirect();

        $this->assertTrue($company->fresh()->hasPlatformApplication('property'));

        $this->actingAs($user)
            ->get('/settings/platform-applications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/PlatformApplications')
                ->has('applications')
            );
    }
}
