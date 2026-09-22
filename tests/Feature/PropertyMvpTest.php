<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ComplianceType;
use App\Models\OperationalObject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyMvpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Company}
     */
    protected function createSitesUser(): array
    {
        $company = Company::create([
            'name' => 'Property Co',
            'code' => 'PROP'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
            'industry' => 'property-management',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $company->enablePlatformApplication('property');

        return [$user, $company];
    }

    public function test_can_create_site_with_property_fields(): void
    {
        [$user, $company] = $this->createSitesUser();

        $this->actingAs($user)
            ->post('/sites', [
                'type' => 'property',
                'name' => '24 High Street',
                'property_type' => 'flat',
                'bedrooms' => 2,
                'tenure' => 'leasehold',
                'occupancy_status' => 'occupied',
                'city' => 'London',
                'postal_code' => 'E1 1AA',
                'country' => 'United Kingdom',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('taskit_operational_objects', [
            'company_id' => $company->id,
            'name' => '24 High Street',
            'property_type' => 'flat',
            'bedrooms' => 2,
            'tenure' => 'leasehold',
            'occupancy_status' => 'occupied',
        ]);
    }

    public function test_sites_index_exposes_property_labels(): void
    {
        [$user, $company] = $this->createSitesUser();

        OperationalObject::create([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
            'type' => 'property',
            'name' => 'Unit 4',
            'property_type' => 'house',
            'bedrooms' => 3,
            'occupancy_status' => 'vacant',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/sites')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Index')
                ->has('sites', 1)
                ->where('sites.0.property_type_label', 'House')
                ->where('sites.0.occupancy_label', 'Vacant')
                ->where('hierarchyHint', 'Company → Clients → Compliance → Sites → Projects → Tasks')
            );
    }

    public function test_compliance_types_are_seeded(): void
    {
        $this->assertTrue(ComplianceType::query()->where('slug', 'gas_safety')->exists());
        $this->assertTrue(ComplianceType::query()->where('slug', 'epc')->exists());
    }

    public function test_company_can_enable_property_application(): void
    {
        [$user, $company] = $this->createSitesUser();

        $this->assertTrue($company->fresh()->hasPlatformApplication('property'));
    }
}
