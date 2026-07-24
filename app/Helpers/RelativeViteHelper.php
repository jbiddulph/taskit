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
     * Generate relative Vite CSS URLs for multi-domain support
     *
     * @deprecated Prefer cssFiles() and render one <link> per file
     */
    public static function css(string $path): string
    {
        return implode(' ', self::cssFiles($path));
    }
}
