<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MapboxService
{
    public function isConfigured(): bool
    {
        return ! empty($this->accessToken());
    }

    public function geocode(string $query, ?float $proximityLng = null, ?float $proximityLat = null): array
    {
        $token = $this->accessToken();
        if (! $token) {
            return [];
        }

        $params = [
            'access_token' => $token,
            'limit' => 5,
            'types' => 'address,poi,place,locality,neighborhood',
        ];

        if ($proximityLng !== null && $proximityLat !== null) {
            $params['proximity'] = "{$proximityLng},{$proximityLat}";
        }

        $response = Http::get(
            'https://api.mapbox.com/geocoding/v5/mapbox.places/'.rawurlencode($query).'.json',
            $params
        );

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('features', []))
            ->map(fn (array $feature) => $this->formatGeocodeFeature($feature))
            ->filter()
            ->values()
            ->all();
    }

    public function reverseGeocode(float $longitude, float $latitude): ?array
    {
        $token = $this->accessToken();
        if (! $token) {
            return null;
        }

        $response = Http::get(
            "https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitude},{$latitude}.json",
            [
                'access_token' => $token,
                'limit' => 1,
                'types' => 'address,poi,place,locality,neighborhood',
            ]
        );

        if (! $response->successful()) {
            return null;
        }

        $feature = $response->json('features.0');
        if (! is_array($feature)) {
            return null;
        }

        return $this->formatGeocodeFeature($feature);
    }

    /**
     * @param  array<int, array{longitude: float, latitude: float}>  $coordinates
     */
    public function directions(array $coordinates, string $profile = 'driving'): ?array
    {
        $token = $this->accessToken();
        if (! $token || count($coordinates) < 2) {
            return null;
        }

        $coordinateString = collect($coordinates)
            ->map(fn (array $point) => "{$point['longitude']},{$point['latitude']}")
            ->implode(';');

        $response = Http::get(
            "https://api.mapbox.com/directions/v5/mapbox/{$profile}/{$coordinateString}",
            [
                'access_token' => $token,
                'geometries' => 'geojson',
                'overview' => 'full',
                'steps' => 'false',
            ]
        );

        if (! $response->successful()) {
            return null;
        }

        $route = $response->json('routes.0');
        if (! is_array($route)) {
            return null;
        }

        return [
            'distance_meters' => $route['distance'] ?? null,
            'duration_seconds' => $route['duration'] ?? null,
            'geometry' => $route['geometry'] ?? null,
        ];
    }

    private function accessToken(): ?string
    {
        return config('services.mapbox.access_token');
    }

    private function formatGeocodeFeature(array $feature): ?array
    {
        $coordinates = $feature['geometry']['coordinates'] ?? null;
        if (! is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        return [
            'location_name' => $feature['text'] ?? null,
            'location_address' => $feature['place_name'] ?? null,
            'longitude' => (float) $coordinates[0],
            'latitude' => (float) $coordinates[1],
        ];
    }
}
