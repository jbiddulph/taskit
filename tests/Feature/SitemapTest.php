<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_lists_core_marketing_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $response->assertSee(route('home'), false);
        $response->assertSee(route('demo'), false);
        $response->assertSee(route('property-compliance'), false);
        $response->assertSee(route('competitors'), false);
        $response->assertSee(route('alternative-to'), false);
        $response->assertSee(route('industries'), false);
        $response->assertSee(route('case-studies'), false);
        $response->assertSee(route('contact'), false);
    }

    public function test_sitemap_lists_industry_landing_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();

        foreach (config('industry_landing.slugs', []) as $slug) {
            $response->assertSee(route("industry.{$slug}"), false);
        }
    }

    public function test_sitemap_lists_competitor_and_alternative_landing_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();

        foreach (config('competitor_landing.comparisons', []) as $slug) {
            $response->assertSee(route("competitors.{$slug}"), false);
            $response->assertSee('/vs/'.$slug, false);
        }

        foreach (config('competitor_landing.alternatives', []) as $slug) {
            $response->assertSee(route("alternative-to.{$slug}"), false);
            $response->assertSee('/alternative-to/'.$slug, false);
        }
    }

    public function test_competitor_landing_pages_render(): void
    {
        foreach (config('competitor_landing.comparisons', []) as $slug) {
            $response = $this->get(route("competitors.{$slug}"));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('CompetitorLanding')
                ->where('kind', 'comparison')
                ->where('slug', $slug));
        }

        foreach (config('competitor_landing.alternatives', []) as $slug) {
            $response = $this->get(route("alternative-to.{$slug}"));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('CompetitorLanding')
                ->where('kind', 'alternative')
                ->where('slug', $slug));
        }
    }

    public function test_unknown_competitor_landing_pages_return_not_found(): void
    {
        $this->get('/vs/not-a-real-tool')->assertNotFound();
        $this->get('/alternative-to/not-a-real-tool')->assertNotFound();
    }
}
