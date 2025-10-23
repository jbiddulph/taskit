<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IonosService
{
    private string $publicPrefix;
    private string $secret;
    private string $baseUrl = 'https://api.ionos.com/dns/v1';

    public function __construct()
    {
        $this->publicPrefix = config('services.ionos.public_prefix');
        $this->secret = config('services.ionos.secret');
    }

    /**
     * Create a subdomain for the given company name
     */
    public function createSubdomain(string $companyName, string $domain = 'zaptask.co.uk'): array
    {
        try {
            // Clean the company name to make it subdomain-safe
            $subdomain = $this->sanitizeSubdomain($companyName);
            
            // Check if subdomain already exists
            if ($this->subdomainExists($subdomain, $domain)) {
                return [
                    'success' => false,
                    'message' => 'Subdomain already exists',
                    'subdomain' => $subdomain
                ];
            }

            // Create the subdomain record
            $response = $this->createDnsRecord($subdomain, $domain);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Subdomain created successfully',
                    'subdomain' => $subdomain,
                    'url' => "https://{$subdomain}.{$domain}"
                ];
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Ionos subdomain creation failed', [
                'company_name' => $companyName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create subdomain: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if a subdomain already exists
     */
    public function subdomainExists(string $subdomain, string $domain = 'zaptask.co.uk'): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secret,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/zones/{$domain}/records");

            if ($response->successful()) {
                $records = $response->json();
                foreach ($records as $record) {
                    if ($record['name'] === $subdomain && $record['type'] === 'A') {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error checking subdomain existence', [
                'subdomain' => $subdomain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create DNS A record for subdomain
     */
    private function createDnsRecord(string $subdomain, string $domain): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secret,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/zones/{$domain}/records", [
                'name' => $subdomain,
                'type' => 'A',
                'content' => $this->getMainDomainIp(), // Point to main domain IP
                'ttl' => 300
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create DNS record: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'DNS record creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sanitize company name to be subdomain-safe
     */
    private function sanitizeSubdomain(string $companyName): string
    {
        // Convert to lowercase
        $subdomain = strtolower($companyName);
        
        // Remove spaces and replace with hyphens
        $subdomain = str_replace(' ', '-', $subdomain);
        
        // Remove special characters except hyphens
        $subdomain = preg_replace('/[^a-z0-9-]/', '', $subdomain);
        
        // Remove multiple consecutive hyphens
        $subdomain = preg_replace('/-+/', '-', $subdomain);
        
        // Remove leading/trailing hyphens
        $subdomain = trim($subdomain, '-');
        
        // Ensure it's not empty and not too long
        if (empty($subdomain) || strlen($subdomain) > 63) {
            $subdomain = 'company-' . substr(md5($companyName), 0, 8);
        }
        
        return $subdomain;
    }

    /**
     * Get the IP address of the main domain
     */
    private function getMainDomainIp(): string
    {
        // You can either hardcode this or fetch it dynamically
        // For now, we'll use a placeholder - you should replace with actual IP
        return '1.2.3.4'; // Replace with your actual server IP
    }

    /**
     * Delete a subdomain
     */
    public function deleteSubdomain(string $subdomain, string $domain = 'zaptask.co.uk'): array
    {
        try {
            // First, find the record ID
            $recordId = $this->getRecordId($subdomain, $domain);
            
            if (!$recordId) {
                return [
                    'success' => false,
                    'message' => 'Subdomain record not found'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secret,
                'Content-Type' => 'application/json'
            ])->delete("{$this->baseUrl}/zones/{$domain}/records/{$recordId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Subdomain deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete subdomain: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Ionos subdomain deletion failed', [
                'subdomain' => $subdomain,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete subdomain: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get record ID for a subdomain
     */
    private function getRecordId(string $subdomain, string $domain): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secret,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/zones/{$domain}/records");

            if ($response->successful()) {
                $records = $response->json();
                foreach ($records as $record) {
                    if ($record['name'] === $subdomain && $record['type'] === 'A') {
                        return $record['id'];
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting record ID', [
                'subdomain' => $subdomain,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
