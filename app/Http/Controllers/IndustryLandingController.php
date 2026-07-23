<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IndustryLandingController extends Controller
{
    public function __invoke(Request $request)
    {
        $slug = trim($request->path(), '/');
        $slugs = config('industry_landing.slugs', []);

        if (! in_array($slug, $slugs, true)) {
            throw new NotFoundHttpException();
        }

        $seoConfig = config("industry_landing.seo.{$slug}", []);
        $seo = [
            'title' => $seoConfig['title'] ?? 'ZapTask',
            'description' => $seoConfig['description'] ?? '',
            'keywords' => $seoConfig['keywords'] ?? '',
            'canonical' => url('/'.$slug),
            'image' => url('/images/activity-feed.png'),
        ];

        return Inertia::render('IndustryLanding', [
            'slug' => $slug,
            'seo' => $seo,
        ])->withViewData([
            'seo' => $seo,
        ]);
    }
}
