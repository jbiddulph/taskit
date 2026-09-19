<?php

namespace Tests\Feature;

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
}
