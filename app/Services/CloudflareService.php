<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    private string $zoneId;
    private string $accountId;
    private string $apiToken;
    private string $herokuApiKey;
    private string $herokuAppName;
    private string $baseUrl = 'https://api.cloudflare.com/client/v4';
    private string $herokuUrl = 'https://api.heroku.com';

    public function __construct()
    {
        $this->zoneId = config('services.cloudflare.zone_id');
        $this->accountId = config('services.cloudflare.account_id');
        $this->apiToken = config('services.cloudflare.api_token');
        $this->herokuApiKey = config('services.heroku.api_key');
        $this->herokuAppName = config('services.heroku.app_name');
    }

    /**
     * Get the headers for Cloudflare API requests
     */
    private function getCloudflareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Get the headers for Heroku API requests
     */
    private function getHerokuHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.heroku+json; version=3',
            'Authorization' => 'Bearer ' . $this->herokuApiKey,
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Check API permissions for Cloudflare and Heroku
     */
    public function checkApiPermissions(): array
    {
        try {
            Log::info('API permissions check - URLs and headers', [
                'cloudflare_zone_id' => $this->zoneId,
                'cloudflare_account_id' => $this->accountId,
                'api_token_length' => strlen($this->apiToken),
                'heroku_api_key_length' => strlen($this->herokuApiKey),
                'heroku_app_name' => $this->herokuAppName,
                'heroku_url' => "{$this->herokuUrl}/apps/{$this->herokuAppName}"
            ]);

            // Test Cloudflare API access
            $cloudflareResponse = Http::withHeaders($this->getCloudflareHeaders())
                ->get("{$this->baseUrl}/zones/{$this->zoneId}");

            // Test Heroku API access
            $herokuResponse = Http::withHeaders($this->getHerokuHeaders())
                ->get("{$this->herokuUrl}/apps/{$this->herokuAppName}");

            Log::info('API permissions check - responses', [
                'cloudflare_status' => $cloudflareResponse->status(),
                'cloudflare_body' => $cloudflareResponse->body(),
                'heroku_status' => $herokuResponse->status(),
                'heroku_body' => $herokuResponse->body()
            ]);

            return [
                'cloudflare_api' => [
                    'accessible' => $cloudflareResponse->successful(),
                    'status' => $cloudflareResponse->status(),
                    'message' => $cloudflareResponse->successful() ? 'Cloudflare API accessible' : $cloudflareResponse->body()
                ],
                'heroku_api' => [
                    'accessible' => $herokuResponse->successful(),
                    'status' => $herokuResponse->status(),
                    'message' => $herokuResponse->successful() ? 'Heroku API accessible' : $herokuResponse->body()
                ],
                'note' => 'Both APIs are required for subdomain creation'
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
     * Create a subdomain using Heroku and Cloudflare
     */
    public function createSubdomain(string $companyName): array
    {
        try {
            $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $companyName));
            $hostname = $subdomain . '.zaptask.co.uk';

            Log::info('Creating subdomain', [
                'company_name' => $companyName,
                'subdomain' => $subdomain,
                'hostname' => $hostname
            ]);

            // Step 1: Add domain to Heroku
            $herokuResponse = Http::withHeaders($this->getHerokuHeaders())
                ->post("{$this->herokuUrl}/apps/{$this->herokuAppName}/domains", [
                    'hostname' => $hostname
                ]);

            Log::info('Heroku domain creation response', [
                'status' => $herokuResponse->status(),
                'body' => $herokuResponse->body()
            ]);

            if (!$herokuResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to create Heroku domain: ' . $herokuResponse->body()
                ];
            }

            $herokuData = $herokuResponse->json();
            $cname = $herokuData['cname'];

            Log::info('Heroku domain created', [
                'hostname' => $herokuData['hostname'],
                'cname' => $cname
            ]);

            // Step 2: Create DNS record in Cloudflare
            $cloudflareResponse = Http::withHeaders($this->getCloudflareHeaders())
                ->post("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                    'type' => 'CNAME',
                    'name' => $hostname,
                    'content' => $cname,
                    'ttl' => 3600,
                    'proxied' => true
                ]);

            Log::info('Cloudflare DNS record creation response', [
                'status' => $cloudflareResponse->status(),
                'body' => $cloudflareResponse->body()
            ]);

            if (!$cloudflareResponse->successful()) {
                // If Cloudflare fails, we should clean up the Heroku domain
                $this->deleteHerokuDomain($hostname);
                
                return [
                    'success' => false,
                    'message' => 'Failed to create Cloudflare DNS record: ' . $cloudflareResponse->body()
                ];
            }

            $cloudflareData = $cloudflareResponse->json();

            return [
                'success' => true,
                'subdomain' => $subdomain,
                'url' => 'https://' . $hostname,
                'hostname' => $hostname,
                'cname' => $cname,
                'cloudflare_record_id' => $cloudflareData['result']['id'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Subdomain creation failed', [
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
     * Delete a subdomain
     */
    public function deleteSubdomain(string $subdomain): array
    {
        try {
            $hostname = $subdomain . '.zaptask.co.uk';

            Log::info('Deleting subdomain', [
                'subdomain' => $subdomain,
                'hostname' => $hostname
            ]);

            // Step 1: Delete DNS record from Cloudflare
            $dnsRecords = $this->getDnsRecords($hostname);
            
            foreach ($dnsRecords as $record) {
                if ($record['name'] === $hostname && $record['type'] === 'CNAME') {
                    $deleteResponse = Http::withHeaders($this->getCloudflareHeaders())
                        ->delete("{$this->baseUrl}/zones/{$this->zoneId}/dns_records/{$record['id']}");

                    Log::info('Cloudflare DNS record deletion response', [
                        'status' => $deleteResponse->status(),
                        'body' => $deleteResponse->body()
                    ]);
                }
            }

            // Step 2: Delete domain from Heroku
            $herokuResponse = $this->deleteHerokuDomain($hostname);

            return [
                'success' => true,
                'message' => 'Subdomain deleted successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Subdomain deletion failed', [
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
     * Get DNS records for a hostname
     */
    private function getDnsRecords(string $hostname): array
    {
        try {
            $response = Http::withHeaders($this->getCloudflareHeaders())
                ->get("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                    'name' => $hostname
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['result'] ?? [];
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Failed to get DNS records', [
                'hostname' => $hostname,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Delete domain from Heroku
     */
    private function deleteHerokuDomain(string $hostname): array
    {
        try {
            $response = Http::withHeaders($this->getHerokuHeaders())
                ->delete("{$this->herokuUrl}/apps/{$this->herokuAppName}/domains/{$hostname}");

            Log::info('Heroku domain deletion response', [
                'hostname' => $hostname,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to delete Heroku domain', [
                'hostname' => $hostname,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test API connectivity
     */
    public function testApiConnectivity(): array
    {
        try {
            // Test Cloudflare API
            $cloudflareResponse = Http::withHeaders($this->getCloudflareHeaders())
                ->get("{$this->baseUrl}/zones/{$this->zoneId}");

            // Test Heroku API
            $herokuResponse = Http::withHeaders($this->getHerokuHeaders())
                ->get("{$this->herokuUrl}/apps/{$this->herokuAppName}");

            return [
                'cloudflare' => [
                    'accessible' => $cloudflareResponse->successful(),
                    'status' => $cloudflareResponse->status(),
                    'zone_name' => $cloudflareResponse->successful() ? ($cloudflareResponse->json()['result']['name'] ?? 'Unknown') : null
                ],
                'heroku' => [
                    'accessible' => $herokuResponse->successful(),
                    'status' => $herokuResponse->status(),
                    'app_name' => $herokuResponse->successful() ? ($herokuResponse->json()['name'] ?? 'Unknown') : null
                ]
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to test API connectivity: ' . $e->getMessage()
            ];
        }
    }
}
