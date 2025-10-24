<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Vite;

class ViteHelper
{
    /**
     * Generate Vite assets with relative URLs
     */
    public static function relativeAssets(array $assets, string $buildDirectory = 'build'): string
    {
        $html = '';
        
        foreach ($assets as $asset) {
            $manifestPath = public_path($buildDirectory . '/manifest.json');
            
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                
                if (isset($manifest[$asset])) {
                    $assetPath = $manifest[$asset]['file'];
                    $cssPath = $manifest[$asset]['css'][0] ?? null;
                    
                    // Generate relative URLs
                    if ($cssPath) {
                        $html .= '<link rel="stylesheet" href="/' . $buildDirectory . '/' . $cssPath . '">' . "\n";
                    }
                    
                    $html .= '<script type="module" src="/' . $buildDirectory . '/' . $assetPath . '"></script>' . "\n";
                }
            }
        }
        
        return $html;
    }
}
