<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IonosService
{
    private string $publicPrefix;
    private string $secret;
    private string $baseUrl = 'https://api.hosting.ionos.com/dns/v1';

    public function __construct()
    {
        $this->publicPrefix = config('services.ionos.public_prefix');
        $this->secret = config('services.ionos.secret');
    }

    /**
     * Get the combined API key for Basic Auth
     */
    private function getApiKey(): string
    {
        return base64_encode($this->publicPrefix . ':' . $this->secret);
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

            // Try to get zone ID, if not found, try to create zone
            $zoneId = $this->getZoneId($domain);
            if (!$zoneId) {
                Log::info('Zone not found, attempting to create zone', ['domain' => $domain]);
                $zoneResult = $this->createZone($domain);
                if (!$zoneResult['success']) {
                    // If zone creation fails due to permissions, provide helpful message
                    if (strpos($zoneResult['message'], 'UNAUTHORIZED') !== false) {
                        return [
                            'success' => false,
                            'message' => 'DNS zone management requires additional permissions. Please contact your Ionos administrator to enable DNS zone creation for your API key, or manually create a DNS zone for ' . $domain . ' in your Ionos control panel.'
                        ];
                    }
                    return [
                        'success' => false,
                        'message' => 'Zone not found and could not be created: ' . $zoneResult['message']
                    ];
                }
                $zoneId = $zoneResult['zone_id'];
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
            $zoneId = $this->getZoneId($domain);
            if (!$zoneId) {
                return false;
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->publicPrefix . '.' . $this->secret,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/zones/{$zoneId}/records");

            if ($response->successful()) {
                $data = $response->json();
                $records = $data['items'] ?? [];
                foreach ($records as $record) {
                    if ($record['properties']['name'] === $subdomain && $record['properties']['type'] === 'A') {
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
     * Create a DNS zone for the domain
     */
    private function createZone(string $domain): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->publicPrefix . '.' . $this->secret,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/zones", [
                'properties' => [
                    'name' => $domain,
                    'type' => 'NATIVE'
                ]
            ]);

            Log::info('Ionos create zone API response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'zone_id' => $data['id'],
                    'message' => 'Zone created successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create zone: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Error creating zone', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Zone creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get zone ID for domain
     */
    private function getZoneId(string $domain): ?string
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->publicPrefix . '.' . $this->secret,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/zones");

            Log::info('Ionos zones API response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $zones = $data['items'] ?? [];
                
                Log::info('Available zones', [
                    'zones' => $zones,
                    'looking_for' => $domain
                ]);
                
                foreach ($zones as $zone) {
                    if ($zone['properties']['name'] === $domain) {
                        Log::info('Found zone', [
                            'zone_id' => $zone['id'],
                            'zone_name' => $zone['properties']['name']
                        ]);
                        return $zone['id'];
                    }
                }
            } else {
                Log::error('Ionos zones API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting zone ID', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create DNS A record for subdomain
     */
    private function createDnsRecord(string $subdomain, string $domain): array
    {
        try {
            // First get the zone ID
            $zoneId = $this->getZoneId($domain);
            if (!$zoneId) {
                return [
                    'success' => false,
                    'message' => 'Zone not found for domain: ' . $domain
                ];
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->publicPrefix . '.' . $this->secret,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/zones/{$zoneId}/records", [
                'properties' => [
                    'name' => $subdomain,
                    'type' => 'A',
                    'content' => $this->getMainDomainIp(), // Point to main domain IP
                    'ttl' => 3600
                ]
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
        // Get the IP address of zaptask.co.uk
        $ip = gethostbyname('zaptask.co.uk');
        return $ip !== 'zaptask.co.uk' ? $ip : '1.2.3.4'; // Fallback if DNS lookup fails
    }

    /**
     * Get manual setup instructions for DNS zone
     */
    public function getManualSetupInstructions(string $domain = 'zaptask.co.uk'): array
    {
        $ip = $this->getMainDomainIp();
        
        return [
            'success' => false,
            'message' => 'Manual DNS setup required',
            'instructions' => [
                'title' => 'Manual DNS Zone Setup Required',
                'steps' => [
                    '1. Log into your Ionos control panel',
                    '2. Navigate to DNS management for ' . $domain,
                    '3. Create a DNS zone for ' . $domain . ' if it doesn\'t exist',
                    '4. Add the following A record:',
                    '   - Name: {subdomain}',
                    '   - Type: A',
                    '   - Value: ' . $ip,
                    '   - TTL: 3600',
                    '5. Once the zone is set up, try creating the subdomain again'
                ],
                'note' => 'Your API key needs DNS zone management permissions. Contact your Ionos administrator to enable these permissions.'
            ]
        ];
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

            $zoneId = $this->getZoneId($domain);
            if (!$zoneId) {
                return [
                    'success' => false,
                    'message' => 'Zone not found for domain: ' . $domain
                ];
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->publicPrefix . '.' . $this->secret,
                'Content-Type' => 'application/json'
            ])->delete("{$this->baseUrl}/zones/{$zoneId}/records/{$recordId}");

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
            $zoneId = $this->getZoneId($domain);
            if (!$zoneId) {
                return null;
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->publicPrefix . '.' . $this->secret,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/zones/{$zoneId}/records");

            if ($response->successful()) {
                $data = $response->json();
                $records = $data['items'] ?? [];
                foreach ($records as $record) {
                    if ($record['properties']['name'] === $subdomain && $record['properties']['type'] === 'A') {
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
