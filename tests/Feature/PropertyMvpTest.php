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

    public function test_estate_agent_can_publish_a_rental_listing_on_zapproperty(): void
    {
        [$user, $company] = $this->createSitesUser();
        $company->update(['industry' => 'estate-agents']);

        $this->actingAs($user)
            ->post('/sites', [
                'type' => 'property',
                'name' => 'Rectory Road, Tarring, Worthing',
                'property_type' => 'flat',
                'bedrooms' => 4,
                'tenure' => 'leasehold',
                'occupancy_status' => 'vacant',
                'city' => 'Worthing',
                'postal_code' => 'BN13',
                'country' => 'United Kingdom',
                'show_on_zapproperty' => true,
                'listing_type' => 'rent',
                'price_amount' => 1750,
                'price_qualifier' => 'pcm',
                'bathrooms' => 1,
                'deposit_amount' => 2019,
                'available_from' => '2026-09-28',
                'council_tax_band' => 'not_available',
                'epc_rating' => 'C',
                'broadband' => 'up to 1000Mbps',
                'key_features' => [
                    'Private entrance',
                    'Freshly decorated throughout',
                    'Local shops',
                ],
                'listing_description' => 'A four-bedroom maisonette on Rectory Road in Tarring.',
                'listing_visibility' => [
                    'price' => true,
                    'deposit' => true,
                    'council_tax' => false,
                    'broadband' => true,
                    'features' => true,
                    'description' => true,
                ],
            ])
            ->assertRedirect();

        $site = OperationalObject::query()->where('name', 'Rectory Road, Tarring, Worthing')->first();
        $this->assertNotNull($site);
        $this->assertTrue($site->show_on_zapproperty);
        $this->assertSame('rent', $site->listing_type);
        $this->assertSame(1, $site->bathrooms);
        $this->assertSame('C', $site->epc_rating);
        $this->assertSame('not_available', $site->council_tax_band);
        $this->assertSame('2026-09-28', $site->available_from->toDateString());
        $this->assertSame([
            'Private entrance',
            'Freshly decorated throughout',
            'Local shops',
        ], $site->key_features);
        $this->assertFalse($site->listing_visibility['council_tax']);
        $this->assertTrue($site->listing_visibility['deposit']);

        $this->actingAs($user)
            ->get('/sites')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sites.0.show_on_zapproperty', true)
                ->where('sites.0.price_label', '£1,750 pcm')
                ->where('sites.0.listing_type_label', 'To rent')
            );

        $this->actingAs($user)
            ->get('/sites/'.$site->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('site.listing.show_on_zapproperty', true)
                ->where('site.listing.price_label', '£1,750 pcm')
                ->where('site.listing.deposit_label', '£2,019')
                ->where('site.listing.available_from_label', 'from 28 September 2026')
                ->where('site.listing.council_tax_label', 'Not available')
                ->where('site.listing.epc_label', 'EPC C')
                ->where('site.listing.broadband', 'up to 1000Mbps')
                ->where('site.listing.listing_visibility.council_tax', false)
            );
    }

    public function test_listing_visibility_can_be_updated(): void
    {
        [$user] = $this->createSitesUser();

        $site = OperationalObject::create([
            'company_id' => $user->company_id,
            'created_by_user_id' => $user->id,
            'type' => 'property',
            'name' => 'Rectory Road',
            'show_on_zapproperty' => true,
            'listing_type' => 'rent',
            'price_amount' => 1750,
            'price_qualifier' => 'pcm',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put('/sites/'.$site->id, [
                'type' => 'property',
                'name' => 'Rectory Road',
                'show_on_zapproperty' => false,
                'listing_type' => 'rent',
                'price_amount' => 1750,
                'price_qualifier' => 'pcm',
                'listing_visibility' => [
                    'price' => false,
                    'description' => true,
                ],
            ])
            ->assertRedirect();

        $site->refresh();
        $this->assertFalse($site->show_on_zapproperty);
        $this->assertFalse($site->listing_visibility['price']);
        $this->assertTrue($site->listing_visibility['description']);
        $this->assertTrue($site->listing_visibility['deposit']);
    }

    public function test_platform_api_accepts_zapproperty_listing_fields(): void
    {
        [$user] = $this->createSitesUser();

        $this->actingAs($user)
            ->postJson('/api/v1/assets', [
                'type' => 'property',
                'name' => 'Rectory Road',
                'property_type' => 'flat',
                'bedrooms' => 4,
                'show_on_zapproperty' => true,
                'listing_type' => 'rent',
                'price_amount' => 1750,
                'price_qualifier' => 'pcm',
                'bathrooms' => 1,
                'epc_rating' => 'C',
                'key_features' => ['Private entrance'],
                'listing_visibility' => ['epc' => false],
            ])
            ->assertCreated()
            ->assertJsonPath('data.property.listing.show_on_zapproperty', true)
            ->assertJsonPath('data.property.listing.price_label', '£1,750 pcm')
            ->assertJsonPath('data.property.listing.visibility.epc', false)
            ->assertJsonPath('data.property.listing.visibility.price', true)
            ->assertJsonPath('data.property.bathrooms', 1);

        $assetId = OperationalObject::query()->where('name', 'Rectory Road')->value('id');

        $this->actingAs($user)
            ->patchJson('/api/v1/assets/'.$assetId, [
                'show_on_zapproperty' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.property.listing.show_on_zapproperty', false)
            ->assertJsonPath('data.property.listing.price_label', '£1,750 pcm');
    }
}
