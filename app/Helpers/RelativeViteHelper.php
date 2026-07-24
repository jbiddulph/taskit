<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class RelativeViteHelper
{
    /**
     * Generate relative Vite asset URLs for multi-domain support
     */
    public static function asset(string $path): string
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!File::exists($manifestPath)) {
            return '/build/' . basename($path);
        }
        
        $manifest = json_decode(File::get($manifestPath), true);
        
        if (!isset($manifest[$path])) {
            return '/build/' . basename($path);
        }
        
        // Return relative URL for multi-domain support
        return '/build/' . $manifest[$path]['file'];
    }
    
    /**
     * Generate relative Vite CSS URLs for multi-domain support
     *
     * @return list<string>
     */
    public static function cssFiles(string $path): array
    {
        $manifestPath = public_path('build/manifest.json');

        if (! File::exists($manifestPath)) {
            return [];
        }

        $manifest = json_decode(File::get($manifestPath), true);

        if (! isset($manifest[$path])) {
            return [];
        }

        $css = $manifest[$path]['css'] ?? [];

        if (empty($css)) {
            return [];
        }

        return collect($css)
            ->map(fn ($file) => '/build/'.$file)
            ->values()
            ->all();
    }

    /**
     * Resolve the main app Vite assets for the root Blade layout.
     *
     * @return array{app: string, page: ?string, css: list<string>}
     */
    public static function forApp(?string $pageComponent = null): array
    {
        $pageAsset = null;

        if (is_string($pageComponent) && $pageComponent !== '') {
            $pageAsset = self::asset("resources/js/pages/{$pageComponent}.vue");
        }

        return [
            'app' => self::asset('resources/js/app.ts'),
            'page' => $pageAsset,
            'css' => self::cssFiles('resources/js/app.ts'),
        ];
    }
}
