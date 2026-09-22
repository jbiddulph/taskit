<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Automation;
use App\Models\Company;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AiTaskCreationService;
use App\Services\AutomationRunnerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformV1ApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Company, 2: Project}
     */
    protected function createCompanySetup(string $subscription = 'MAXI'): array
    {
        $company = Company::create([
            'name' => 'Acme Ltd',
            'code' => 'ACME'.random_int(1000, 9999),
            'subscription_type' => $subscription,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Sarah Smith',
        ]);

        $project = Project::create([
            'name' => 'Operations',
            'key' => 'OPS',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
        ]);
        ProjectGroup::createDefaultForProject($project);

        return [$user, $company, $project];
    }

    public function test_api_key_can_create_and_list_tasks_scoped_to_company(): void
    {
        [$user, $company, $project] = $this->createCompanySetup();
        $other = Company::create([
            'name' => 'Other Co',
            'code' => 'OTHR'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
        ]);
        $outsider = User::factory()->create(['company_id' => $other->id]);
        $otherProject = Project::create([
            'name' => 'Other',
            'key' => 'OTH',
            'color' => '#000000',
            'owner_id' => $outsider->id,
            'company_id' => $other->id,
        ]);
        ProjectGroup::createDefaultForProject($otherProject);

        Todo::create([
            'user_id' => $outsider->id,
            'company_id' => $other->id,
            'project_id' => $otherProject->id,
            'title' => 'Secret task',
            'priority' => 'Medium',
            'status' => 'todo',
            'type' => 'Task',
        ]);

        $result = ApiKey::generate($company, 'Integration', $user);
        $plainKey = $result['plain_text_key'];

        $this->withToken($plainKey)
            ->postJson('/api/v1/tasks', [
                'title' => 'Renew Gas Safety Certificate',
                'due_date' => now()->addMonth()->toDateString(),
                'priority' => 'high',
                'project_id' => $project->id,
                'source' => 'api',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Renew Gas Safety Certificate');

        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Renew Gas Safety Certificate',
            'company_id' => $company->id,
            'source' => 'api',
        ]);

        // Raw key is never stored
        $this->assertDatabaseMissing('taskit_api_keys', [
            'key_hash' => $plainKey,
        ]);
        $this->assertTrue(str_starts_with($plainKey, 'zt_live_'));

        $list = $this->withToken($plainKey)
            ->getJson('/api/v1/tasks')
            ->assertOk()
            ->json('data.tasks');

        $titles = collect($list)->pluck('title');
        $this->assertTrue($titles->contains('Renew Gas Safety Certificate'));
        $this->assertFalse($titles->contains('Secret task'));
    }

    public function test_cannot_access_another_company_task_by_id(): void
    {
        [$user, $company] = $this->createCompanySetup();
        $other = Company::create([
            'name' => 'Other Co',
            'code' => 'OTHR'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
        ]);
        $outsider = User::factory()->create(['company_id' => $other->id]);
        $otherProject = Project::create([
            'name' => 'Other',
            'key' => 'OTH',
            'color' => '#000000',
            'owner_id' => $outsider->id,
            'company_id' => $other->id,
        ]);
        ProjectGroup::createDefaultForProject($otherProject);

        $foreignTodo = Todo::create([
            'user_id' => $outsider->id,
            'company_id' => $other->id,
            'project_id' => $otherProject->id,
            'title' => 'Foreign',
            'priority' => 'Medium',
            'status' => 'todo',
            'type' => 'Task',
        ]);

        $plainKey = ApiKey::generate($company, 'Key', $user)['plain_text_key'];

        $this->withToken($plainKey)
            ->getJson('/api/v1/tasks/'.$foreignTodo->id)
            ->assertNotFound();
    }

    public function test_cannot_access_another_company_by_id(): void
    {
        [$user, $company] = $this->createCompanySetup();
        $other = Company::create([
            'name' => 'Other Co',
            'code' => 'OTHR'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/companies/'.$other->id)
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/v1/companies/'.$company->id)
            ->assertOk()
            ->assertJsonPath('data.id', $company->id);
    }

    public function test_workspaces_and_assets_crud(): void
    {
        [$user, $company] = $this->createCompanySetup();

        $this->actingAs($user)
            ->postJson('/api/v1/workspaces', [
                'name' => 'Fleet',
                'type' => 'fleet',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Fleet');

        $workspaceId = Workspace::query()->where('company_id', $company->id)->where('name', 'Fleet')->value('id');

        $this->actingAs($user)
            ->postJson('/api/v1/assets', [
                'type' => 'vehicle',
                'name' => 'Ford Transit',
                'reference' => 'AB12 CDE',
                'workspace_id' => $workspaceId,
                'metadata' => ['make' => 'Ford', 'model' => 'Transit'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'AB12 CDE');

        $this->actingAs($user)
            ->getJson('/api/v1/assets?type=vehicle')
            ->assertOk()
            ->assertJsonPath('data.assets.0.name', 'Ford Transit');
    }

    public function test_checklist_items_on_task(): void
    {
        [$user, $company, $project] = $this->createCompanySetup();

        $todo = Todo::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'project_id' => $project->id,
            'title' => 'Gas Safety Inspection',
            'priority' => 'High',
            'status' => 'todo',
            'type' => 'Task',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/tasks/{$todo->id}/checklist", [
                'title' => 'Contact engineer',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Contact engineer');

        $itemId = $todo->checklistItems()->first()->id;

        $this->actingAs($user)
            ->patchJson("/api/v1/tasks/{$todo->id}/checklist/{$itemId}", [
                'completed' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.completed', true);
    }

    public function test_ai_endpoint_returns_structured_preview_without_creating(): void
    {
        [$user, $company, $project] = $this->createCompanySetup();

        OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'vehicle',
            'name' => 'Ford Transit',
            'reference' => 'AB12 CDE',
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/ai', [
                'message' => 'Remind Sarah to renew the insurance for van AB12 CDE two weeks before it expires on 14 November.',
                'context' => 'task_creation',
                'project_id' => $project->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('intent', 'create_task')
            ->assertJsonPath('requires_confirmation', true);

        $this->assertNotEmpty($response->json('task.title'));
        $this->assertDatabaseMissing('taskit_todos', [
            'source' => 'ai',
            'user_id' => $user->id,
        ]);

        $taskPayload = $response->json('task');

        $this->actingAs($user)
            ->postJson('/api/ai', [
                'confirm' => true,
                'message' => 'Remind Sarah...',
                'project_id' => $project->id,
                'task' => $taskPayload,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('taskit_todos', [
            'user_id' => $user->id,
            'source' => 'ai',
            'company_id' => $company->id,
        ]);
    }

    public function test_ai_heuristic_extracts_due_date_two_weeks_before(): void
    {
        [$user] = $this->createCompanySetup();
        $service = app(AiTaskCreationService::class);

        Carbon::setTestNow(Carbon::parse('2026-09-22'));

        $result = $service->propose(
            $user,
            'Remind Sarah to renew the insurance for van AB12 CDE two weeks before it expires on 14 November.'
        );

        $this->assertSame('create_task', $result['intent']);
        $this->assertSame('2026-10-31', $result['task']['due_date']);
        $this->assertSame('Sarah Smith', $result['task']['assigned_to']);
        $this->assertSame('AB12 CDE', $result['task']['asset_reference']);

        Carbon::setTestNow();
    }

    public function test_date_reached_automation_creates_task(): void
    {
        [$user, $company, $project] = $this->createCompanySetup();

        Automation::create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'name' => 'Insurance reminder',
            'trigger_type' => Automation::TRIGGER_DATE_REACHED,
            'trigger_config' => ['date' => '2026-10-31'],
            'action_type' => Automation::ACTION_CREATE_TASK,
            'action_config' => [
                'title' => 'Renew van insurance',
                'project_id' => $project->id,
                'priority' => 'High',
            ],
            'enabled' => true,
        ]);

        $ran = app(AutomationRunnerService::class)
            ->runDateReachedAutomations(Carbon::parse('2026-10-31'));

        $this->assertSame(1, $ran);
        $this->assertDatabaseHas('taskit_todos', [
            'title' => 'Renew van insurance',
            'company_id' => $company->id,
            'source' => 'automation',
        ]);
    }

    public function test_api_key_permission_denied_without_tasks_write(): void
    {
        [$user, $company] = $this->createCompanySetup();

        $result = ApiKey::generate($company, 'Read only', $user, ['tasks.read', 'assets.read']);

        $this->withToken($result['plain_text_key'])
            ->postJson('/api/v1/tasks', [
                'title' => 'Should fail',
            ])
            ->assertForbidden();
    }
}
