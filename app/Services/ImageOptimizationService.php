<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ImageOptimizationService
{
    private ImageManager $manager;
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/tiff'
    ];

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Optimize and convert image to WebP format
     */
    public function optimizeImage(UploadedFile $file, string $path, array $options = []): array
    {
        try {
            // Validate file type
            if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
                throw new \InvalidArgumentException('Unsupported image type');
            }

            // Set default options
            $defaultOptions = [
                'max_width' => 1920,
                'max_height' => 1080,
                'quality' => 85,
                'generate_webp' => true,
                'generate_thumbnails' => true,
                'thumbnail_sizes' => [
                    'small' => [150, 150],
                    'medium' => [300, 300],
                    'large' => [600, 600]
                ]
            ];

            $options = array_merge($defaultOptions, $options);

            // Load image
            $image = $this->manager->read($file->getPathname());

            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Resize if necessary
            if ($originalWidth > $options['max_width'] || $originalHeight > $options['max_height']) {
                $image->scaleDown($options['max_width'], $options['max_height']);
            }

            // Generate optimized versions
            $results = [];

            // Original optimized version
            $optimizedPath = $this->saveOptimizedImage($image, $path, $options['quality']);
            $results['original'] = $optimizedPath;

            // WebP version
            if ($options['generate_webp']) {
                $webpPath = $this->saveWebPImage($image, $path, $options['quality']);
                $results['webp'] = $webpPath;
            }

            // Thumbnails
            if ($options['generate_thumbnails']) {
                $results['thumbnails'] = $this->generateThumbnails($image, $path, $options);
            }

            // Store metadata
            $metadata = [
                'original_size' => $file->getSize(),
                'original_dimensions' => [$originalWidth, $originalHeight],
                'optimized_dimensions' => [$image->width(), $image->height()],
                'mime_type' => $file->getMimeType(),
                'created_at' => now()->toISOString()
            ];

            $this->saveMetadata($path, $metadata);

            Log::info('Image optimized successfully', [
                'path' => $path,
                'original_size' => $file->getSize(),
                'optimized_size' => Storage::size($optimizedPath)
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Save optimized image
     */
    private function saveOptimizedImage($image, string $path, int $quality): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $directory = dirname($path);

        $optimizedPath = $directory . '/' . $filename . '_optimized.' . $extension;

        // Encode with quality settings
        $encoded = $image->encode(quality: $quality);
        
        Storage::put($optimizedPath, $encoded);

        return $optimizedPath;
    }

    /**
     * Save WebP version
     */
    private function saveWebPImage($image, string $path, int $quality): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = dirname($path);

        $webpPath = $directory . '/' . $filename . '.webp';

        // Convert to WebP
        $encoded = $image->toWebp(quality: $quality);
        
        Storage::put($webpPath, $encoded);

        return $webpPath;
    }

    /**
     * Generate thumbnails
     */
    private function generateThumbnails($image, string $path, array $options): array
    {
        $thumbnails = [];
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = dirname($path);

        foreach ($options['thumbnail_sizes'] as $size => $dimensions) {
            $thumbnailImage = clone $image;
            $thumbnailImage->cover($dimensions[0], $dimensions[1]);

            $thumbnailPath = $directory . '/' . $filename . '_' . $size . '.webp';
            $encoded = $thumbnailImage->toWebp(quality: 80);
            
            Storage::put($thumbnailPath, $encoded);
            $thumbnails[$size] = $thumbnailPath;
        }

        return $thumbnails;
    }

    /**
     * Save metadata
     */
    private function saveMetadata(string $path, array $metadata): void
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = dirname($path);
        $metadataPath = $directory . '/' . $filename . '_metadata.json';

        Storage::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    /**
     * Get optimized image URL
     */
    public function getOptimizedImageUrl(string $path, string $format = 'webp'): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = dirname($path);

        if ($format === 'webp') {
            $webpPath = $directory . '/' . $filename . '.webp';
            if (Storage::exists($webpPath)) {
                return Storage::url($webpPath);
            }
        }

        $optimizedPath = $directory . '/' . $filename . '_optimized.' . pathinfo($path, PATHINFO_EXTENSION);
        if (Storage::exists($optimizedPath)) {
            return Storage::url($optimizedPath);
        }

        return Storage::url($path);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(string $path, string $size = 'medium'): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = dirname($path);
        $thumbnailPath = $directory . '/' . $filename . '_' . $size . '.webp';

        if (Storage::exists($thumbnailPath)) {
            return Storage::url($thumbnailPath);
        }

        return $this->getOptimizedImageUrl($path);
    }

    /**
     * Clean up old optimized images
     */
    public function cleanupOptimizedImages(string $path): void
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = dirname($path);

        $patterns = [
            $directory . '/' . $filename . '_optimized.*',
            $directory . '/' . $filename . '.webp',
            $directory . '/' . $filename . '_small.webp',
            $directory . '/' . $filename . '_medium.webp',
            $directory . '/' . $filename . '_large.webp',
            $directory . '/' . $filename . '_metadata.json'
        ];

        foreach ($patterns as $pattern) {
            $files = Storage::glob($pattern);
            foreach ($files as $file) {
                Storage::delete($file);
            }
        }
    }

    /**
     * Get image metadata
     */
    public function getImageMetadata(string $path): ?array
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = dirname($path);
        $metadataPath = $directory . '/' . $filename . '_metadata.json';

        if (Storage::exists($metadataPath)) {
            $content = Storage::get($metadataPath);
            return json_decode($content, true);
        }

        return null;
    }

    /**
     * Check if image supports optimization
     */
    public function supportsOptimization(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), $this->allowedMimeTypes);
    }
}
