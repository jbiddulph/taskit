<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\User;
use App\Support\CertificateTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyComplianceManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_compliance_page_is_property_compliance_manager_with_upload_hub(): void
    {
        [$user, $company] = $this->createMaxiUser('estate-agents');
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => '12 Harbour View',
            'address_line_1' => '12 Harbour View',
            'city' => 'Brighton',
            'postal_code' => 'BN1 1AA',
            'created_by_user_id' => $user->id,
        ]);
        $project = Project::create([
            'name' => 'Lettings',
            'key' => 'LET',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Compliance/Index')
                ->has('sites', 1)
                ->where('sites.0.id', $site->id)
                ->where('sites.0.name', '12 Harbour View')
                ->has('projects', 1)
                ->where('projects.0.id', $project->id)
                ->has('certificateTypes', count(CertificateTypes::propertyManagerTypes()))
                ->where('certificateTypes.0.type', 'gas_safety')
                ->where('certificateTypes.1.type', 'eicr')
                ->where('certificateTypes.2.type', 'epc')
                ->where('certificateTypes.3.type', 'insurance')
                ->where('certificateTypes.4.type', 'boiler_service')
            );
    }

    public function test_can_upload_certificate_from_compliance_api_against_a_property(): void
    {
        Storage::fake('private');

        [$user, $company] = $this->createMaxiUser('property-management');
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => 'Flat 3, Oak Court',
            'created_by_user_id' => $user->id,
        ]);
        $project = Project::create([
            'name' => 'Compliance Board',
            'key' => 'CMP',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->create('gas-safety.pdf', 120, 'application/pdf');

        $this->actingAs($user)
            ->postJson("/api/sites/{$site->id}/documents", [
                'file' => $file,
                'extract' => false,
                'project_id' => $project->id,
                'title' => 'Gas Safety Certificate',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('taskit_operational_documents', [
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'title' => 'Gas Safety Certificate',
        ]);
    }

    public function test_property_management_template_includes_landlord_certificate_types(): void
    {
        [$user, $company] = $this->createMaxiUser('property-management');
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => 'Landlord House',
            'created_by_user_id' => $user->id,
        ]);

        app(\App\Services\ComplianceRequirementService::class)->applyIndustryTemplate($site);

        $types = $site->complianceRequirements()->pluck('requirement_type')->all();

        foreach (['gas_safety', 'eicr', 'epc', 'insurance', 'boiler_service'] as $type) {
            $this->assertContains($type, $types, "Expected {$type} on property-management template");
        }
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
}
