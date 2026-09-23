<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Stores property photos in Supabase Storage.
 *
 * Object key: {company_id}/{site_id}/{filename}
 */
class SupabaseObjectStore
{
    public function put(string $path, string $contents, string $mime): void
    {
        if ($this->usesLocalDisk()) {
            Storage::disk('supabase')->put($path, $contents);

            return;
        }

        $response = Http::withHeaders([
            ...$this->authHeaders(),
            'x-upsert' => 'true',
        ])->withBody($contents, $mime)->post($this->objectUrl($path));

        if (! $response->successful()) {
            $message = (string) ($response->json('message') ?: 'Supabase rejected the upload.');

            throw new RuntimeException('Could not store the photo in Supabase: '.$message);
        }
    }

    public function get(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'site-photos/')) {
            return Storage::disk('private')->exists($path)
                ? Storage::disk('private')->get($path)
                : null;
        }

        if ($this->usesLocalDisk()) {
            return Storage::disk('supabase')->exists($path)
                ? Storage::disk('supabase')->get($path)
                : null;
        }

        $response = Http::withHeaders($this->authHeaders())->get($this->objectUrl($path));

        return $response->successful() && $response->body() !== '' ? $response->body() : null;
    }

    public function delete(string $path): void
    {
        if ($path === '') {
            return;
        }

        if (str_starts_with($path, 'site-photos/')) {
            Storage::disk('private')->delete($path);

            return;
        }

        if ($this->usesLocalDisk()) {
            Storage::disk('supabase')->delete($path);

            return;
        }

        $response = Http::withHeaders($this->authHeaders())
            ->withBody(json_encode(['prefixes' => [$path]]), 'application/json')
            ->send('DELETE', $this->baseUrl().'/storage/v1/object/'.$this->bucket());

        if (! $response->successful()) {
            throw new RuntimeException('Could not delete the photo from Supabase.');
        }
    }

    /**
     * Tests talk to a faked filesystem disk instead of the live project.
     */
    private function usesLocalDisk(): bool
    {
        return app()->environment('testing');
    }

    private function authHeaders(): array
    {
        $key = (string) config('services.supabase_storage.key');
        if ($key === '' || $this->baseUrl() === '') {
            throw new RuntimeException('Supabase storage is not configured (VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY).');
        }

        return [
            'apikey' => $key,
            'Authorization' => 'Bearer '.$key,
        ];
    }

    private function objectUrl(string $path): string
    {
        return $this->baseUrl().'/storage/v1/object/'.$this->bucket().'/'.$this->encodePath($path);
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.supabase_storage.url'), '/');
    }

    private function bucket(): string
    {
        $bucket = (string) config('services.supabase_storage.bucket', 'taskit');

        return $bucket !== '' ? $bucket : 'taskit';
    }
}
