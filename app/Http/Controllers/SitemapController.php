<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    /**
     * Generate the XML sitemap for public-facing pages.
     */
    public function __invoke(): Response
    {
        $now = CarbonImmutable::now()->toAtomString();

        $urls = Collection::make([
            [
                'loc' => route('home'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('demo'),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('contact'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('privacy'),
                'changefreq' => 'yearly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('terms'),
                'changefreq' => 'yearly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('login'),
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ],
            [
                'loc' => route('register'),
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ],
            [
                'loc' => route('password.request'),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
        ])->map(function (array $entry) use ($now) {
            return $entry + ['lastmod' => $now];
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}

