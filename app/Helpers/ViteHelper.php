<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class ViteHelper
{
    /**
     * Generate Vite asset URLs that work across multiple domains
     */
    public static function asset(string $path): string
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!File::exists($manifestPath)) {
            return asset($path);
        }
        
        $manifest = json_decode(File::get($manifestPath), true);
        
        if (!isset($manifest[$path])) {
            return asset($path);
        }
        
        // Return relative URL for multi-domain support
        return '/build/' . $manifest[$path]['file'];
    }
    
    /**
     * Generate Vite CSS URLs that work across multiple domains
     */
    public static function css(string $path): string
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!File::exists($manifestPath)) {
            return asset($path);
        }
        
        $manifest = json_decode(File::get($manifestPath), true);
        
        if (!isset($manifest[$path])) {
            return asset($path);
        }
        
        $css = $manifest[$path]['css'] ?? [];
        
        if (empty($css)) {
            return '';
        }
        
        // Return relative URLs for multi-domain support
        return collect($css)->map(fn($file) => '/build/' . $file)->implode(' ');
    }
}
