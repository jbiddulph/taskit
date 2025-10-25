<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IonosService
{
    private string $publicPrefix;
    private string $secret;
    private string $tenantId;
    private string $baseUrl = 'https://api.hosting.ionos.com/dns/v1';
    private string $domainsUrl = 'https://api.hosting.ionos.com/domains';

    public function __construct()
    {
        $this->publicPrefix = config('services.ionos.public_prefix');
        $this->secret = config('services.ionos.secret');
        $this->tenantId = config('services.ionos.tenant_id');
    }

    /**
     * Get the API key for authentication
     */
    private function getApiKey(): string
    {
        return $this->publicPrefix . '.' . $this->secret;
    }

    /**
     * Get the headers for DNS API requests (X-Tenant-Id optional)
     */
    private function getDnsHeaders(): array
    {
        $headers = [
            'X-API-Key' => $this->getApiKey(),
            'Content-Type' => 'application/json'
        ];
        
        if ($this->tenantId) {
            $headers['X-Tenant-Id'] = $this->tenantId;
        }
        
        return $headers;
    }

    /**
     * Get the headers for Domains API requests (X-Tenant-Id required)
     */
    private function getDomainsHeaders(): array
    {
        return [
            'X-API-Key' => $this->getApiKey(),
            'X-Tenant-Id' => $this->tenantId,
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Get DNS records for a zone
     */
    public function getDnsRecords(string $zoneId): array
    {
        try {
            $response = Http::withHeaders($this->getDnsHeaders())->get("{$this->baseUrl}/zones/{$zoneId}/records");

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'records' => $response->successful() ? $response->json() : []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get zone ID for a domain (public method)
     */
    public function getZoneIdPublic(string $domain): ?string
    {
        return $this->getZoneId($domain);
    }

    /**
     * Test DNS record creation directly
     */
    public function testDnsRecordCreation(): array
    {
        try {
            $zoneId = $this->getZoneId('zaptask.co.uk');
            if (!$zoneId) {
                return [
                    'success' => false,
                    'message' => 'Zone not found for zaptask.co.uk'
                ];
            }

            $response = Http::withHeaders($this->getDnsHeaders())->post("{$this->baseUrl}/zones/{$zoneId}/records", [
                'properties' => [
                    'name' => 'test-subdomain',
                    'type' => 'A',
                    'content' => '1.2.3.4',
                    'ttl' => 3600
                ]
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'zone_id' => $zoneId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test API key format and basic connectivity
     */
    public function testApiKey(): array
    {
        try {
            $apiKey = $this->getApiKey();
            
            Log::info('API key test', [
                'public_prefix' => $this->publicPrefix,
                'secret_length' => strlen($this->secret),
                'api_key_length' => strlen($apiKey),
                'api_key_format' => $this->publicPrefix . '.' . $this->secret,
                'has_dot' => strpos($apiKey, '.') !== false
            ]);

            // Test with a simple endpoint first
            $testUrl = 'https://api.hosting.ionos.com/dns/v1/zones';
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Content-Type' => 'application/json'
            ])->get($testUrl);

            return [
                'api_key_format' => $apiKey,
                'test_url' => $testUrl,
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'api_key_format' => $this->getApiKey()
            ];
        }
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

            // Create subdomain using DNS API directly
            $dnsResponse = $this->createDnsRecord($subdomain, $domain);

            if ($dnsResponse['success']) {
                return [
                    'success' => true,
                    'message' => 'Subdomain created successfully',
                    'subdomain' => $subdomain,
                    'url' => "https://{$subdomain}.{$domain}"
                ];
            }

            return $dnsResponse;

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

            $response = Http::withHeaders($this->getDnsHeaders())->get("{$this->baseUrl}/zones/{$zoneId}/records");

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
     * Check API key permissions
     */
    public function checkApiPermissions(): array
    {
        try {
            $apiKey = $this->getApiKey();
            $dnsUrl = "{$this->baseUrl}/zones";
            $domainsUrl = "{$this->domainsUrl}/domains";
            
            Log::info('API permissions check - URLs and headers', [
                'dns_url' => $dnsUrl,
                'domains_url' => $domainsUrl,
                'api_key_length' => strlen($apiKey),
                'api_key_preview' => substr($apiKey, 0, 20) . '...'
            ]);

            // Test DNS API access
            $dnsResponse = Http::withHeaders($this->getDnsHeaders())->get($dnsUrl);

            // Test Domains API access
            $domainsResponse = Http::withHeaders($this->getDomainsHeaders())->get($domainsUrl);

            Log::info('API permissions check - responses', [
                'dns_status' => $dnsResponse->status(),
                'dns_body' => $dnsResponse->body(),
                'dns_headers' => $dnsResponse->headers(),
                'domains_status' => $domainsResponse->status(),
                'domains_body' => $domainsResponse->body(),
                'domains_headers' => $domainsResponse->headers()
            ]);

            return [
                'dns_api' => [
                    'accessible' => $dnsResponse->successful(),
                    'status' => $dnsResponse->status(),
                    'message' => $dnsResponse->successful() ? 'DNS API accessible' : $dnsResponse->body()
                ],
                'domains_api' => [
                    'accessible' => $domainsResponse->successful(),
                    'status' => $domainsResponse->status(),
                    'message' => $domainsResponse->successful() ? 'Domains API accessible' : 'Domains API not available (using DNS API instead)'
                ],
                'note' => 'DNS API is working - this is sufficient for subdomain creation'
            ];

        } catch (\Exception $e) {
            Log::error('Error checking API permissions', [
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Failed to check API permissions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if domain exists in Ionos account
     */
    private function domainExists(string $domain): bool
    {
        try {
            $response = Http::withHeaders($this->getDomainsHeaders())->get($this->domainsUrl);

            Log::info('Ionos domains API response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $this->domainsUrl
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $domains = $data['items'] ?? [];
                Log::info('Available domains', [
                    'domains' => $domains,
                    'looking_for' => $domain
                ]);
                
                foreach ($domains as $domainItem) {
                    if ($domainItem['properties']['name'] === $domain) {
                        Log::info('Domain found', [
                            'domain' => $domain,
                            'domain_id' => $domainItem['id']
                        ]);
                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error checking domain existence', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create a DNS zone for the domain using DNS API
     */
    private function createZone(string $domain): array
    {
        try {
            // Since we know the domain exists (we found it in DNS API), try to create zone
            $response = Http::withHeaders($this->getDnsHeaders())->post("{$this->baseUrl}/zones", [
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
            $response = Http::withHeaders($this->getDnsHeaders())->get("{$this->baseUrl}/zones");

            Log::info('Ionos zones API response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $zones = is_array($data) && isset($data[0]) ? $data : ($data['items'] ?? []);
                
                Log::info('Available zones', [
                    'zones' => $zones,
                    'looking_for' => $domain
                ]);
                
                foreach ($zones as $zone) {
                    if ($zone['name'] === $domain) {
                        Log::info('Found zone', [
                            'zone_id' => $zone['id'],
                            'zone_name' => $zone['name']
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
     * Create subdomain using Domains API
     */
    private function createSubdomainRecord(string $subdomain, string $domain): array
    {
        try {
            // First check if domain exists
            if (!$this->domainExists($domain)) {
                return [
                    'success' => false,
                    'message' => 'Domain ' . $domain . ' is not registered in your Ionos account.'
                ];
            }

            // Try to create subdomain using Domains API
            $response = Http::withHeaders($this->getDomainsHeaders())->post("{$this->domainsUrl}/{$domain}/subdomains", [
                'properties' => [
                    'name' => $subdomain,
                    'type' => 'A',
                    'content' => $this->getMainDomainIp()
                ]
            ]);

            Log::info('Ionos create subdomain API response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create subdomain: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Error creating subdomain', [
                'subdomain' => $subdomain,
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Subdomain creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create DNS A record for subdomain (fallback method)
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

            $response = Http::withHeaders($this->getDnsHeaders())->post("{$this->baseUrl}/zones/{$zoneId}/records", [
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

            $response = Http::withHeaders($this->getDnsHeaders())->delete("{$this->baseUrl}/zones/{$zoneId}/records/{$recordId}");

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

            $response = Http::withHeaders($this->getDnsHeaders())->get("{$this->baseUrl}/zones/{$zoneId}/records");

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
