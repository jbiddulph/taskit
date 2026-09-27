<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ComplianceRequirement;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\User;
use App\Services\AiPortfolioQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiPortfolioQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_ai_lists_gas_certificates_expiring_within_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-27'));

        [$user, $company] = $this->createMaxiUser();
        $soon = $this->createProperty($company, $user, '22 Richmond Road');
        $later = $this->createProperty($company, $user, '9 Quiet Lane');

        ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $soon->id,
            'requirement_type' => 'gas_safety',
            'label' => 'Gas Safety (CP12)',
            'frequency' => 'annual',
            'lead_time_days' => 30,
            'next_due_date' => '2026-10-10',
            'status' => ComplianceRequirement::STATUS_DUE_SOON,
            'auto_create_tasks' => true,
        ]);

        ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $later->id,
            'requirement_type' => 'gas_safety',
            'label' => 'Gas Safety (CP12)',
            'frequency' => 'annual',
            'lead_time_days' => 30,
            'next_due_date' => '2027-03-01',
            'status' => ComplianceRequirement::STATUS_COMPLIANT,
            'auto_create_tasks' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/ai', [
                'message' => 'Which properties have gas certificates expiring in the next 60 days?',
                'context' => 'portfolio',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('intent', 'portfolio_answer');

        $this->assertStringContainsString('22 Richmond Road', $response->json('answer'));
        $this->assertStringNotContainsString('9 Quiet Lane', $response->json('answer'));
        $this->assertCount(1, $response->json('matches'));

        Carbon::setTestNow();
    }

    public function test_portfolio_ai_finds_properties_with_no_eicr(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $withEicr = $this->createProperty($company, $user, 'Flat 1 Oak Court');
        $without = $this->createProperty($company, $user, 'Flat 2 Oak Court');

        ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $withEicr->id,
            'requirement_type' => 'eicr',
            'label' => 'EICR',
            'frequency' => '5_years',
            'lead_time_days' => 60,
            'next_due_date' => now()->addYear()->toDateString(),
            'status' => ComplianceRequirement::STATUS_COMPLIANT,
            'auto_create_tasks' => true,
        ]);

        // Stub without dates should still count as missing for the other flat
        ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $without->id,
            'requirement_type' => 'eicr',
            'label' => 'EICR',
            'frequency' => '5_years',
            'lead_time_days' => 60,
            'next_due_date' => null,
            'status' => ComplianceRequirement::STATUS_MISSING,
            'auto_create_tasks' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/ai', [
                'message' => 'Show me properties with no EICR.',
                'context' => 'portfolio',
            ])
            ->assertOk()
            ->assertJsonPath('intent', 'portfolio_answer');

        $this->assertStringContainsString('Flat 2 Oak Court', $response->json('answer'));
        $this->assertStringNotContainsString('Flat 1 Oak Court', $response->json('answer'));
    }

    public function test_portfolio_ai_answers_boiler_last_serviced(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $site = $this->createProperty($company, $user, '22 Richmond Road');

        ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'requirement_type' => 'boiler_service',
            'label' => 'Boiler Servicing',
            'frequency' => 'annual',
            'lead_time_days' => 30,
            'last_completed_at' => '2026-04-15',
            'next_due_date' => '2027-04-15',
            'status' => ComplianceRequirement::STATUS_COMPLIANT,
            'auto_create_tasks' => true,
        ]);

        OperationalDocument::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'uploaded_by_user_id' => $user->id,
            'title' => 'Boiler service record',
            'document_type' => 'boiler_service',
            'filename' => 'boiler.pdf',
            'original_filename' => 'boiler.pdf',
            'mime_type' => 'application/pdf',
            'file_path' => 'documents/boiler.pdf',
            'file_size' => 1200,
            'status' => OperationalDocument::STATUS_ACTIVE,
            'extracted_data' => ['service_date' => '2026-04-15'],
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/ai', [
                'message' => 'When was the boiler at 22 Richmond Road last serviced?',
                'context' => 'portfolio',
            ])
            ->assertOk()
            ->assertJsonPath('intent', 'portfolio_answer');

        $this->assertStringContainsString('22 Richmond Road', $response->json('answer'));
        $this->assertStringContainsString('15 Apr 2026', $response->json('answer'));
    }

    public function test_create_task_from_compliance_requirement(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $project = Project::create([
            'name' => 'Compliance Board',
            'key' => 'CMP',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $site = $this->createProperty($company, $user, 'Harbour View');

        $requirement = ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'project_id' => $project->id,
            'requirement_type' => 'gas_safety',
            'label' => 'Gas Safety (CP12)',
            'frequency' => 'annual',
            'lead_time_days' => 30,
            'next_due_date' => now()->addDays(5)->toDateString(),
            'status' => ComplianceRequirement::STATUS_DUE_SOON,
            'auto_create_tasks' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/api/compliance/requirements/{$requirement->id}/create-task")
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('task.requirement_id', $requirement->id);

        $this->assertDatabaseHas('taskit_todos', [
            'compliance_requirement_id' => $requirement->id,
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
        ]);

        // Second call returns existing open task
        $this->actingAs($user)
            ->postJson("/api/compliance/requirements/{$requirement->id}/create-task")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, \App\Models\Todo::query()->where('compliance_requirement_id', $requirement->id)->count());
    }

    public function test_compliance_page_includes_attention_insights(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-27'));

        [$user, $company] = $this->createMaxiUser();
        $site = $this->createProperty($company, $user, '12 Harbour View');

        ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'requirement_type' => 'gas_safety',
            'label' => 'Gas Safety (CP12)',
            'frequency' => 'annual',
            'lead_time_days' => 30,
            'next_due_date' => '2026-10-05',
            'status' => ComplianceRequirement::STATUS_DUE_SOON,
            'auto_create_tasks' => true,
        ]);

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('attentionInsights')
                ->where('attentionInsights.0.type', 'gas_safety')
            );

        $insights = app(AiPortfolioQueryService::class)->attentionInsights($company->id);
        $this->assertNotEmpty($insights);
        $this->assertSame('gas_safety', $insights[0]['type']);

        Carbon::setTestNow();
    }

    /**
     * @return array{0: User, 1: Company}
     */
    protected function createMaxiUser(string $industry = 'property-management'): array
    {
        $company = Company::create([
            'name' => 'Harbour Lets',
            'code' => 'H'.random_int(10000, 99999),
            'subscription_type' => 'MAXI',
            'industry' => $industry,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        return [$user, $company];
    }

    protected function createProperty(Company $company, User $user, string $name): OperationalObject
    {
        return OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => $name,
            'address_line_1' => $name,
            'city' => 'Brighton',
            'postal_code' => 'BN1 1AA',
            'created_by_user_id' => $user->id,
            'is_active' => true,
        ]);
    }
}
