<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OperationalObject;
use App\Models\User;
use App\Services\MapboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapboxGeocodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_returns_results_when_mapbox_is_configured(): void
    {
        $this->mock(MapboxService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('geocode')
                ->once()
                ->with('10 Downing Street, London', null, null)
                ->andReturn([
                    [
                        'location_name' => '10 Downing Street',
                        'location_address' => '10 Downing Street, London, SW1A 2AA, United Kingdom',
                        'latitude' => 51.5034,
                        'longitude' => -0.1276,
                    ],
                ]);
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/mapbox/geocode?query='.urlencode('10 Downing Street, London'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.latitude', 51.5034)
            ->assertJsonPath('data.0.longitude', -0.1276)
            ->assertJsonPath('data.0.location_name', '10 Downing Street');
    }

    public function test_geocode_requires_query(): void
    {
        $this->mock(MapboxService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/mapbox/geocode')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_geocode_returns_503_when_mapbox_is_not_configured(): void
    {
        $this->mock(MapboxService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/mapbox/geocode?query=London')
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_creating_a_site_geocodes_address_when_coords_missing(): void
    {
        $this->mock(MapboxService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('geocode')
                ->once()
                ->withArgs(fn (string $query) => $query === '14 Oak Lane, Brighton, BN1 1AA, United Kingdom')
                ->andReturn([
                    [
                        'location_name' => '14 Oak Lane',
                        'location_address' => '14 Oak Lane, Brighton, BN1 1AA, United Kingdom',
                        'latitude' => 50.8225,
                        'longitude' => -0.1372,
                    ],
                ]);
        });

        $company = Company::create([
            'name' => 'Geo Co',
            'code' => 'G'.random_int(10000, 99999),
            'subscription_type' => 'MAXI',
            'industry' => 'property-management',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->post(route('sites.store'), [
                'type' => 'property',
                'name' => 'Oak Lane Flat',
                'address_line_1' => '14 Oak Lane',
                'city' => 'Brighton',
                'postal_code' => 'BN1 1AA',
                'country' => 'United Kingdom',
            ])
            ->assertRedirect();

        $site = OperationalObject::query()->where('name', 'Oak Lane Flat')->first();
        $this->assertNotNull($site);
        $this->assertEqualsWithDelta(50.8225, (float) $site->latitude, 0.0001);
        $this->assertEqualsWithDelta(-0.1372, (float) $site->longitude, 0.0001);
    }
}
