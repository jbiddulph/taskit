<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Automation;
use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsUiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Company}
     */
    protected function createCompanyUser(): array
    {
        $company = Company::create([
            'name' => 'Platform Co',
            'code' => 'PLAT'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        return [$user, $company];
    }

    public function test_platform_overview_requires_company_and_ensures_default_workspace(): void
    {
        [$user, $company] = $this->createCompanyUser();

        $this->actingAs($user)
            ->get('/settings/platform')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Platform')
                ->has('stats')
                ->where('company.id', $company->id)
            );

        $this->assertDatabaseHas('taskit_workspaces', [
            'company_id' => $company->id,
            'name' => 'General',
            'is_default' => true,
        ]);
    }

    public function test_user_can_create_workspace(): void
    {
        [$user, $company] = $this->createCompanyUser();

        $this->actingAs($user)
            ->post('/settings/workspaces', [
                'name' => 'Property Portfolio',
                'type' => 'property',
                'description' => 'UK rental stock',
            ])
            ->assertRedirect('/settings/workspaces');

        $this->assertDatabaseHas('taskit_workspaces', [
            'company_id' => $company->id,
            'name' => 'Property Portfolio',
            'type' => 'property',
        ]);
    }

    public function test_user_can_create_platform_api_key(): void
    {
        [$user, $company] = $this->createCompanyUser();

        $response = $this->actingAs($user)
            ->post('/settings/platform-api-keys', [
                'name' => 'n8n',
            ])
            ->assertRedirect('/settings/platform-api-keys');

        $response->assertSessionHas('plainTextPlatformKey');
        $plain = session('plainTextPlatformKey');
        $this->assertIsString($plain);
        $this->assertTrue(str_starts_with($plain, 'zt_live_'));

        $this->assertSame(1, ApiKey::query()->forCompany($company->id)->count());
    }

    public function test_user_can_create_automation(): void
    {
        [$user, $company] = $this->createCompanyUser();

        $this->actingAs($user)
            ->post('/settings/automations', [
                'name' => 'Insurance reminder',
                'trigger_type' => Automation::TRIGGER_DATE_REACHED,
                'action_type' => Automation::ACTION_CREATE_TASK,
                'trigger_date' => now()->addMonth()->toDateString(),
                'task_title' => 'Renew insurance',
                'enabled' => true,
            ])
            ->assertRedirect('/settings/automations');

        $this->assertDatabaseHas('taskit_automations', [
            'company_id' => $company->id,
            'name' => 'Insurance reminder',
            'enabled' => true,
        ]);
    }

    public function test_cannot_delete_default_workspace(): void
    {
        [$user, $company] = $this->createCompanyUser();
        $workspace = Workspace::ensureDefaultForCompany($company);

        $this->actingAs($user)
            ->from('/settings/workspaces')
            ->delete('/settings/workspaces/'.$workspace->id)
            ->assertRedirect('/settings/workspaces')
            ->assertSessionHasErrors('workspace');

        $this->assertDatabaseHas('taskit_workspaces', ['id' => $workspace->id]);
    }

    public function test_dashboard_shares_workspaces_and_switch_updates_session(): void
    {
        [$user, $company] = $this->createCompanyUser();
        $default = Workspace::ensureDefaultForCompany($company);
        $property = Workspace::create([
            'company_id' => $company->id,
            'name' => 'Property',
            'type' => 'property',
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('platform.workspaces', 2)
                ->where('platform.currentWorkspaceId', $default->id)
            );

        $this->actingAs($user)
            ->from('/dashboard')
            ->post('/settings/workspaces/switch', ['workspace_id' => $property->id])
            ->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('platform.currentWorkspaceId', $property->id)
            );
    }
}
