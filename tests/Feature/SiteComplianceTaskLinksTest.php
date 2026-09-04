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

    public function test_site_show_hides_empty_template_stubs_but_keeps_dated_items(): void
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

        $unscheduledCount = $site->fresh()
            ->complianceRequirements()
            ->get()
            ->filter(fn ($requirement) => ! $requirement->isActiveOnSitePage())
            ->count();

        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Show')
                ->has('site.compliance_requirements', 1)
                ->where('site.compliance_requirements.0.label', 'Fire Alarm Inspection')
                ->where('site.compliance_requirements.0.has_open_task', false)
                ->where('site.compliance_requirements.0.open_todo', null)
                ->where('site.unscheduled_compliance_count', $unscheduledCount)
            );

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->where('summary.total', $site->complianceRequirements()->count())
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
