<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompetitorLandingController extends Controller
{
    public function comparison(Request $request)
    {
        $slug = basename($request->path());

        return $this->render($request, 'comparison', 'comparisons', $slug, 'vs/'.$slug);
    }

    public function alternative(Request $request)
    {
        $slug = basename($request->path());

        return $this->render($request, 'alternative', 'alternatives', $slug, 'alternative-to/'.$slug);
    }

    private function render(Request $request, string $kind, string $configKey, string $slug, string $path)
    {
        $slugs = config("competitor_landing.{$configKey}", []);

        if (! in_array($slug, $slugs, true)) {
            throw new NotFoundHttpException();
        }

        $seoConfig = config("competitor_landing.seo.{$configKey}.{$slug}", []);
        $seo = [
            'title' => $seoConfig['title'] ?? 'ZapTask',
            'description' => $seoConfig['description'] ?? '',
            'keywords' => $seoConfig['keywords'] ?? '',
            'canonical' => url('/'.$path),
            'image' => url('/zap_icon.png'),
        ];

        return Inertia::render('CompetitorLanding', [
            'kind' => $kind,
            'slug' => $slug,
            'seo' => $seo,
        ])->withViewData([
            'seo' => $seo,
        ]);
    }
}
