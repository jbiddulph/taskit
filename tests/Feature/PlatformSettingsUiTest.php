<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Automation;
use App\Models\Company;
use App\Models\User;
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

    public function test_platform_overview_requires_company(): void
    {
        [$user, $company] = $this->createCompanyUser();

        $this->actingAs($user)
            ->get('/settings/platform')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Platform')
                ->has('stats')
                ->where('company.id', $company->id)
                ->missing('stats.workspaces')
            );
    }

    public function test_workspaces_settings_route_is_removed(): void
    {
        [$user] = $this->createCompanyUser();

        $this->actingAs($user)
            ->get('/settings/workspaces')
            ->assertNotFound();
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
}
