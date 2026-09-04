<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Inspection;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Services\InspectionPdfService;
use App\Services\InspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteClientComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_member_can_link_a_site_to_a_client(): void
    {
        [$user, $company] = $this->createMaxiUser('electricians');
        $client = $this->makeClient($company, $user);

        $this->actingAs($user)
            ->post(route('sites.store'), [
                'type' => 'property',
                'name' => '14 Oak Lane',
                'client_id' => $client->id,
                'apply_compliance_template' => true,
            ])
            ->assertRedirect();

        $site = OperationalObject::query()->where('name', '14 Oak Lane')->first();
        $this->assertNotNull($site);
        $this->assertSame($client->id, $site->client_id);
        $this->assertGreaterThan(0, $site->complianceRequirements()->count());
    }

    public function test_cannot_link_site_to_another_companys_client(): void
    {
        [$user, $company] = $this->createMaxiUser();
        [$outsider] = $this->createMaxiUser();
        $foreignClient = $this->makeClient($outsider->company, $outsider);

        $this->actingAs($user)
            ->post(route('sites.store'), [
                'type' => 'property',
                'name' => 'Blocked Site',
                'client_id' => $foreignClient->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('taskit_operational_objects', [
            'company_id' => $company->id,
            'name' => 'Blocked Site',
        ]);
    }

    public function test_client_show_includes_linked_sites_for_company_members(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $client = $this->makeClient($company, $user);
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'property',
            'name' => 'Riverside Court',
            'created_by_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Show')
                ->has('client.sites', 1)
                ->where('client.sites.0.id', $site->id)
                ->where('client.sites.0.name', 'Riverside Court')
            );
    }

    public function test_compliance_page_includes_client_for_linked_sites(): void
    {
        [$user, $company] = $this->createMaxiUser('electricians');
        $client = $this->makeClient($company, $user);
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'property',
            'name' => 'Unit 4',
            'created_by_user_id' => $user->id,
        ]);

        app(\App\Services\ComplianceRequirementService::class)->applyIndustryTemplate($site);

        $eicr = $site->complianceRequirements()->where('requirement_type', 'eicr')->first();
        $this->assertNotNull($eicr);
        $eicr->update(['next_due_date' => '2027-06-01']);
        $eicr->refreshStatus();

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('requirements', 1)
                ->where('requirements.0.client.name', $client->name)
                ->where('requirements.0.site.name', 'Unit 4')
            );
    }

    public function test_deleting_a_client_nulls_linked_site_client_id(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $client = $this->makeClient($company, $user);
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'type' => 'property',
            'name' => 'Unlinked After Delete',
            'created_by_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseMissing('taskit_clients', ['id' => $client->id]);
        $this->assertNull($site->fresh()->client_id);
    }

    public function test_inspection_complete_does_not_create_follow_up_tasks_unless_requested(): void
    {
        [$user, $company] = $this->createMaxiUser('construction');
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'site',
            'name' => 'Site A',
            'created_by_user_id' => $user->id,
        ]);
        $project = Project::create([
            'name' => 'Board',
            'key' => 'BRD',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->mock(InspectionPdfService::class, function ($mock) {
            $mock->shouldReceive('generate')->andReturn('inspection-reports/test.pdf');
        });

        $inspection = app(InspectionService::class)->createDraft($site, $user, 'site_safety', 'construction');
        $inspection->update([
            'responses' => [
                'ppe' => 'fail',
                'housekeeping' => 'pass',
                'scaffold' => 'pass',
                'hazards' => 'pass',
            ],
        ]);

        $this->actingAs($user)
            ->postJson("/api/inspections/{$inspection->id}/complete", [
                'project_id' => $project->id,
            ])
            ->assertOk();

        $this->assertSame(0, Todo::query()->where('source', 'inspection_follow_up')->count());
        $this->assertSame(Inspection::STATUS_COMPLETED, $inspection->fresh()->status);
    }

    public function test_inspection_complete_creates_follow_up_tasks_when_ticked(): void
    {
        [$user, $company] = $this->createMaxiUser('construction');
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'site',
            'name' => 'Site B',
            'created_by_user_id' => $user->id,
        ]);
        $project = Project::create([
            'name' => 'Board',
            'key' => 'BRD',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->mock(InspectionPdfService::class, function ($mock) {
            $mock->shouldReceive('generate')->andReturn('inspection-reports/test.pdf');
        });

        $inspection = app(InspectionService::class)->createDraft($site, $user, 'site_safety', 'construction');
        $inspection->update([
            'responses' => [
                'ppe' => 'fail',
                'housekeeping' => 'fail',
                'scaffold' => 'pass',
                'hazards' => 'pass',
            ],
        ]);

        $this->actingAs($user)
            ->postJson("/api/inspections/{$inspection->id}/complete", [
                'project_id' => $project->id,
                'create_follow_up_tasks' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.follow_up_task_count', 2);

        $this->assertSame(2, Todo::query()->where('source', 'inspection_follow_up')->where('project_id', $project->id)->count());
    }

    /**
     * @return array{0: User, 1: Company}
     */
    protected function createMaxiUser(string $industry = 'property-management'): array
    {
        $company = Company::create([
            'name' => 'Test Co',
            'code' => 'T'.random_int(10000, 99999),
            'subscription_type' => 'MAXI',
            'industry' => $industry,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        return [$user, $company];
    }

    protected function makeClient(Company $company, User $user): Client
    {
        return Client::create([
            'name' => 'Acme Holdings',
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
        ]);
    }
}
