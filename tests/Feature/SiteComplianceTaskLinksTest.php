<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ComplianceRequirement;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Services\ComplianceRequirementService;
use App\Services\DocumentExtractionService;
use App\Services\MapboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SiteComplianceTaskLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_show_includes_board_url_after_extraction_approve(): void
    {
        Mail::fake();
        $this->mock(MapboxService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });

        [$user, $company] = $this->createMaxiUser('property-management');
        $site = $this->makeSite($company, $user, 'Riverside Court');
        $project = $this->makeProject($company, $user);
        $document = $this->makeDocument($company, $site, $user);

        $proposal = app(DocumentExtractionService::class)->createProposalFromExtraction(
            $document,
            $user,
            [
                'document_type' => 'fire_alarm',
                'label' => 'Fire Alarm Inspection',
                'expiry_date' => '2027-03-05',
            ],
        );

        $this->actingAs($user)
            ->postJson("/api/document-extraction/proposals/{$proposal->id}/approve", [
                'project_id' => $project->id,
            ])
            ->assertOk();

        $todo = Todo::query()
            ->where('compliance_requirement_id', $proposal->fresh()->operationalDocument->compliance_requirement_id)
            ->where('status', '!=', 'done')
            ->latest('id')
            ->first();

        $this->assertNotNull($todo);
        $this->assertSame($user->name, $todo->assignee);
        $this->assertStringContainsString('todo='.$todo->id, $todo->dashboardUrl());
        $this->assertStringContainsString('project='.$project->id, $todo->dashboardUrl());

        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Show')
                ->has('site.compliance_requirements', 1)
                ->where('site.compliance_requirements.0.label', 'Fire Alarm Inspection')
                ->where('site.compliance_requirements.0.has_open_task', true)
                ->where('site.compliance_requirements.0.open_todo.id', $todo->id)
                ->where('site.compliance_requirements.0.open_todo.project_id', $project->id)
                ->where('site.compliance_requirements.0.open_todo.url', $todo->dashboardUrl())
                ->where('site.compliance_requirements.0.assignee', $user->name)
            );
    }

    public function test_site_show_lists_unscheduled_template_stubs_separately_from_dated_items(): void
    {
        [$user, $company] = $this->createMaxiUser('property-management');
        $site = $this->makeSite($company, $user, '24 High Street');
        $project = $this->makeProject($company, $user);

        app(ComplianceRequirementService::class)->applyIndustryTemplate($site, $project->id);

        $this->assertGreaterThan(1, $site->complianceRequirements()->count());

        $fireAlarm = $site->complianceRequirements()->where('requirement_type', 'fire_alarm')->first();
        $this->assertNotNull($fireAlarm);
        $fireAlarm->update([
            'next_due_date' => '2027-03-05',
            'assignee' => 'johnb',
        ]);
        $fireAlarm->refreshStatus();

        $unscheduled = $site->fresh()
            ->complianceRequirements()
            ->get()
            ->filter(fn ($requirement) => ! $requirement->isActiveOnSitePage())
            ->sortBy('label')
            ->values();

        $this->assertGreaterThan(0, $unscheduled->count());

        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Show')
                ->has('site.compliance_requirements', 1)
                ->where('site.compliance_requirements.0.label', 'Fire Alarm Inspection')
                ->where('site.compliance_requirements.0.has_open_task', false)
                ->where('site.compliance_requirements.0.open_todo', null)
                ->where('site.unscheduled_compliance_count', $unscheduled->count())
                ->has('site.unscheduled_compliance_requirements', $unscheduled->count())
                ->where('site.unscheduled_compliance_requirements.0.id', $unscheduled[0]->id)
                ->where('site.unscheduled_compliance_requirements.0.label', $unscheduled[0]->label)
                ->where('site.unscheduled_compliance_requirements.0.next_due_date', null)
            );

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('requirements', 1)
                ->where('requirements.0.label', 'Fire Alarm Inspection')
                ->where('summary.total', 1)
                ->where('summary.missing', 0)
            );
    }

    public function test_unscheduled_compliance_item_can_be_given_a_due_date_from_the_site_page(): void
    {
        [$user, $company] = $this->createMaxiUser('property-management');
        $site = $this->makeSite($company, $user, 'Unit 9');
        $project = $this->makeProject($company, $user);

        app(ComplianceRequirementService::class)->applyIndustryTemplate($site, $project->id);

        $stub = $site->complianceRequirements()->where('requirement_type', 'gas_safety')->first();
        $this->assertNotNull($stub);
        $this->assertFalse($stub->isActiveOnSitePage());

        $this->actingAs($user)
            ->from(route('sites.show', $site))
            ->patch(route('sites.compliance.update', [$site, $stub]), [
                'next_due_date' => '2027-09-01',
                'assignee' => 'johnb',
            ])
            ->assertRedirect();

        $stub->refresh();
        $this->assertTrue($stub->isActiveOnSitePage());
        $this->assertSame('2027-09-01', $stub->next_due_date->format('Y-m-d'));

        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Show')
                ->where('site.compliance_requirements.0.id', $stub->id)
                ->where('site.compliance_requirements.0.assignee', 'johnb')
                ->where('site.unscheduled_compliance_requirements', fn ($items) => collect($items)->every(
                    fn ($item) => $item['id'] !== $stub->id
                ))
            );

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('requirements', 1)
                ->where('requirements.0.id', $stub->id)
                ->where('requirements.0.site.id', $site->id)
            );
    }

    public function test_company_compliance_hides_empty_software_template_stubs(): void
    {
        [$user, $company] = $this->createMaxiUser('software-development');
        $site = $this->makeSite($company, $user, 'Shakespere');
        $project = $this->makeProject($company, $user);

        app(ComplianceRequirementService::class)->applyIndustryTemplate($site, $project->id);

        $stubLabels = $site->complianceRequirements()
            ->whereIn('requirement_type', [
                'iso27001',
                'gdpr',
                'cyber_insurance',
                'pi_insurance',
                'disaster_recovery',
                'contract',
            ])
            ->pluck('label');

        $this->assertGreaterThanOrEqual(6, $stubLabels->count());

        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Show')
                ->has('site.compliance_requirements', 0)
                ->has('site.unscheduled_compliance_requirements', $site->complianceRequirements()->count())
            );

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('requirements', 0)
                ->where('summary.total', 0)
                ->where('summary.missing', 0)
            );

        $iso = $site->complianceRequirements()->where('requirement_type', 'iso27001')->first();
        $this->assertNotNull($iso);
        $iso->update(['next_due_date' => '2027-04-01']);
        $iso->refreshStatus();

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('requirements', 1)
                ->where('requirements.0.label', 'ISO 27001 / Information Security Review')
                ->where('summary.total', 1)
                ->where('requirements.0.has_document', false)
                ->where('requirements.0.has_linked_task', false)
            );
    }

    public function test_company_compliance_includes_undated_items_with_a_document_or_task(): void
    {
        [$user, $company] = $this->createMaxiUser('software-development');
        $site = $this->makeSite($company, $user, 'Shakespere');
        $project = $this->makeProject($company, $user);

        app(ComplianceRequirementService::class)->applyIndustryTemplate($site, $project->id);

        $gdpr = $site->complianceRequirements()->where('requirement_type', 'gdpr')->first();
        $cyber = $site->complianceRequirements()->where('requirement_type', 'cyber_insurance')->first();
        $this->assertNotNull($gdpr);
        $this->assertNotNull($cyber);
        $this->assertFalse($gdpr->isActiveOnSitePage());
        $this->assertFalse($cyber->isActiveOnSitePage());

        $document = $this->makeDocument($company, $site, $user);
        $document->update(['compliance_requirement_id' => $gdpr->id]);

        Todo::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'project_id' => $project->id,
            'operational_object_id' => $site->id,
            'compliance_requirement_id' => $cyber->id,
            'source' => 'compliance_schedule',
            'title' => 'Cyber Insurance — Shakespere',
            'assignee' => 'johnb',
            'status' => 'todo',
        ]);

        $this->assertTrue($gdpr->fresh()->isActiveOnSitePage());
        $this->assertTrue($cyber->fresh()->isActiveOnSitePage());

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('requirements', 2)
                ->where('summary.total', 2)
                ->where('requirements', fn ($items) => collect($items)->pluck('id')->sort()->values()->all() === collect([$gdpr->id, $cyber->id])->sort()->values()->all())
                ->where('requirements', fn ($items) => collect($items)->firstWhere('id', $gdpr->id)['has_document'] === true)
                ->where('requirements', fn ($items) => collect($items)->firstWhere('id', $cyber->id)['has_linked_task'] === true)
            );
    }

    public function test_attaching_a_todo_exposes_the_board_link_on_site_show(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $site = $this->makeSite($company, $user, 'Unit 4');
        $project = $this->makeProject($company, $user);

        $requirement = ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'project_id' => $project->id,
            'requirement_type' => 'gas_safety',
            'label' => 'Gas Safety Certificate',
            'frequency' => 'annual',
            'lead_time_days' => 30,
            'next_due_date' => '2027-09-04',
            'assignee' => 'johnb',
            'status' => ComplianceRequirement::STATUS_COMPLIANT,
            'auto_create_tasks' => true,
        ]);

        $todo = Todo::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'project_id' => $project->id,
            'operational_object_id' => $site->id,
            'compliance_requirement_id' => $requirement->id,
            'source' => 'compliance_schedule',
            'title' => 'Gas Safety Certificate — Unit 4',
            'assignee' => 'johnb',
            'status' => 'todo',
        ]);

        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Show')
                ->has('site.compliance_requirements', 1)
                ->where('site.compliance_requirements.0.open_todo.id', $todo->id)
                ->where('site.compliance_requirements.0.open_todo.url', $todo->dashboardUrl())
                ->where('site.compliance_requirements.0.assignee', 'johnb')
            );
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
            'name' => 'johnb',
        ]);

        return [$user, $company];
    }

    protected function makeSite(Company $company, User $user, string $name): OperationalObject
    {
        return OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => $name,
            'created_by_user_id' => $user->id,
        ]);
    }

    protected function makeProject(Company $company, User $user): Project
    {
        return Project::create([
            'name' => 'Board',
            'key' => 'BRD',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);
    }

    protected function makeDocument(Company $company, OperationalObject $site, User $user): OperationalDocument
    {
        return OperationalDocument::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'uploaded_by_user_id' => $user->id,
            'title' => 'Fire Alarm.pdf',
            'filename' => 'fire-alarm.pdf',
            'original_filename' => 'Fire Alarm.pdf',
            'mime_type' => 'application/pdf',
            'file_path' => 'operational-documents/fire-alarm.pdf',
            'file_size' => 123,
        ]);
    }
}
