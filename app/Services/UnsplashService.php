<?php

namespace App\Services;

use App\Models\Company;
use App\Support\Industries;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnsplashService
{
    public function backgroundQueryFor(?string $industrySlug): string
    {
        $slug = Industries::resolve($industrySlug);

        return config("industries.background_queries.{$slug}")
            ?? config('industries.background_queries.general', 'professional business team office');
    }

    /**
     * @return array<int, array{id: string, thumb_url: string, regular_url: string, description: string|null, attribution: array{name: string, profile_url: string, photo_url: string}}>
     */
    public function searchPhotos(string $query, int $page = 1, int $perPage = 12): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $accessKey = config('services.unsplash.access_key');

        if (! $accessKey) {
            throw new \RuntimeException('Unsplash is not configured. Add UNSPLASH_ACCESS_KEY to your server environment.');
        }

        $perPage = max(1, min($perPage, 30));
        $page = max(1, $page);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Client-ID '.$accessKey,
                'Accept-Version' => 'v1',
            ])->get('https://api.unsplash.com/search/photos', [
                'query' => $query,
                'orientation' => 'landscape',
                'page' => $page,
                'per_page' => $perPage,
                'content_filter' => 'high',
            ]);

            if ($response->status() === 401) {
                throw new \RuntimeException('Invalid Unsplash access key. Check UNSPLASH_ACCESS_KEY in your server environment.');
            }

            if (! $response->successful()) {
                Log::warning('Unsplash search failed', [
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->json('errors') ?? $response->body(),
                ]);

                throw new \RuntimeException('Unsplash search failed. Please try again later.');
            }

            return collect($response->json('results', []))
                ->filter(fn ($photo) => is_array($photo) && ! empty($photo['id']))
                ->map(function (array $photo) {
                    $formatted = $this->formatPhoto($photo);

                    return [
                        'id' => (string) $photo['id'],
                        'thumb_url' => $photo['urls']['small'] ?? $photo['urls']['thumb'] ?? $formatted['url'],
                        'regular_url' => $formatted['url'],
                        'description' => $photo['description'] ?? $photo['alt_description'] ?? null,
                        'attribution' => $formatted['attribution'],
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException) {
                throw $e;
            }

            Log::warning('Unsplash search exception', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Unsplash search failed. Please try again later.');
        }
    }

    /**
     * @return array{url: string, attribution: array{name: string, profile_url: string, photo_url: string}}|null
     */
    public function fetchBackgroundForIndustry(?string $industrySlug, bool $forceFresh = false): ?array
    {
        $slug = Industries::resolve($industrySlug);
        $query = $this->backgroundQueryFor($slug);
        $accessKey = config('services.unsplash.access_key');

        if (! $accessKey) {
            return $this->fallbackBackground($slug);
        }

        $cacheKey = 'unsplash:industry:'.$slug.':'.md5($query);

        if ($forceFresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($query, $accessKey, $slug) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Client-ID '.$accessKey,
                    'Accept-Version' => 'v1',
                ])->get('https://api.unsplash.com/search/photos', [
                    'query' => $query,
                    'orientation' => 'landscape',
                    'per_page' => 1,
                    'content_filter' => 'high',
                ]);

                if (! $response->successful()) {
                    return $this->fallbackBackground($slug);
                }

                $photo = $response->json('results.0');

                if (! is_array($photo)) {
                    return $this->fallbackBackground($slug);
                }

                return $this->formatPhoto($photo);
            } catch (\Throwable $e) {
                return $this->fallbackBackground($slug);
            }
        });
    }

    /**
     * @return array{url: string, attribution: array{name: string, profile_url: string, photo_url: string}}
     */
    public function ensureCompanyHomepageBackground(Company $company, bool $forceRefresh = false): array
    {
        if ($company->homepage_background_mode === 'custom' && $company->homepage_background_url && ! $forceRefresh) {
            return [
                'url' => $company->homepage_background_url,
                'attribution' => $company->homepage_background_attribution ?? [],
            ];
        }

        $industry = Industries::resolve($company->industry);

        if (
            ! $forceRefresh
            && $company->homepage_background_url
            && $company->homepage_background_mode !== 'custom'
            && $company->homepage_background_industry === $industry
        ) {
            return [
                'url' => $company->homepage_background_url,
                'attribution' => $company->homepage_background_attribution ?? [],
            ];
        }

        $background = $this->fetchBackgroundForIndustry($industry, $forceRefresh)
            ?? $this->fallbackBackground($industry);

        $company->update([
            'homepage_background_url' => $background['url'],
            'homepage_background_attribution' => $background['attribution'],
            'homepage_background_industry' => $industry,
            'homepage_background_mode' => 'industry',
            'homepage_background_unsplash_id' => null,
        ]);

        return $background;
    }

    /**
     * @return array{url: string, attribution: array{name: string, profile_url: string, photo_url: string}}
     */
    public function applyPhotoToCompany(Company $company, string $photoId): array
    {
        $accessKey = config('services.unsplash.access_key');

        if (! $accessKey) {
            throw new \RuntimeException('Unsplash is not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Client-ID '.$accessKey,
            'Accept-Version' => 'v1',
        ])->get('https://api.unsplash.com/photos/'.urlencode($photoId));

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to load the selected Unsplash photo.');
        }

        $photo = $response->json();

        if (! is_array($photo)) {
            throw new \RuntimeException('Invalid Unsplash photo response.');
        }

        $this->triggerDownload($photoId);

        $background = $this->formatPhoto($photo);

        $company->update([
            'homepage_background_url' => $background['url'],
            'homepage_background_attribution' => $background['attribution'],
            'homepage_background_industry' => null,
            'homepage_background_mode' => 'custom',
            'homepage_background_unsplash_id' => $photoId,
        ]);

        return $background;
    }

    protected function triggerDownload(string $photoId): void
    {
        $accessKey = config('services.unsplash.access_key');

        if (! $accessKey) {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => 'Client-ID '.$accessKey,
                'Accept-Version' => 'v1',
            ])->get('https://api.unsplash.com/photos/'.urlencode($photoId).'/download');
        } catch (\Throwable $e) {
            Log::warning('Unsplash download trigger failed', [
                'photo_id' => $photoId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $photo
     * @return array{url: string, attribution: array{name: string, profile_url: string, photo_url: string}}
     */
    protected function formatPhoto(array $photo): array
    {
        $url = $photo['urls']['regular'] ?? $photo['urls']['full'] ?? null;

        if (! $url) {
            throw new \RuntimeException('Unsplash photo missing URL.');
        }

        return [
            'url' => $url,
            'attribution' => [
                'name' => $photo['user']['name'] ?? 'Unsplash',
                'profile_url' => $photo['user']['links']['html'] ?? 'https://unsplash.com',
                'photo_url' => $photo['links']['html'] ?? 'https://unsplash.com',
            ],
        ];
    }

    /**
     * @return array{url: string, attribution: array{name: string, profile_url: string, photo_url: string}}
     */
    protected function fallbackBackground(string $industrySlug): array
    {
        $fallbacks = config('industries.background_fallbacks', []);
        $url = $fallbacks[$industrySlug] ?? $fallbacks['general'] ?? null;

        if (! $url) {
            $url = 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80';
        }

        return [
            'url' => $url,
            'attribution' => [
                'name' => 'Unsplash',
                'profile_url' => 'https://unsplash.com',
                'photo_url' => 'https://unsplash.com',
            ],
        ];
    }
}
