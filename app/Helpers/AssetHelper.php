<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class AssetHelper
{
    /**
     * Generate relative asset URLs for multi-domain support
     */
    public static function relativeAsset(string $path): string
    {
        // Remove leading slash if present
        $path = ltrim($path, '/');
        
        // Return relative URL
        return '/' . $path;
    }
    
    /**
     * Generate Vite asset URLs that work across multiple domains
     */
    public static function viteAsset(string $path): string
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!File::exists($manifestPath)) {
            return self::relativeAsset($path);
        }
        
        $manifest = json_decode(File::get($manifestPath), true);
        
        if (!isset($manifest[$path])) {
            return self::relativeAsset($path);
        }
        
        // Return relative URL for multi-domain support
        return '/build/' . $manifest[$path]['file'];
    }
    
    /**
     * Generate Vite CSS URLs that work across multiple domains
     */
    public static function viteCss(string $path): string
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!File::exists($manifestPath)) {
            return '';
        }
        
        $manifest = json_decode(File::get($manifestPath), true);
        
        if (!isset($manifest[$path])) {
            return '';
        }
        
        $css = $manifest[$path]['css'] ?? [];
        
        if (empty($css)) {
            return '';
        }
        
        // Return relative URLs for multi-domain support
        return collect($css)->map(fn($file) => '/build/' . $file)->implode(' ');
    }
}
