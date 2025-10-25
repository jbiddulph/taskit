<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\CloudflareService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    protected CloudflareService $cloudflareService;

    public function __construct(CloudflareService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
    }

    /**
     * Create a subdomain for the company
     */
    public function createSubdomain(Request $request)
    {

        $request->validate([
            'company_name' => 'required|string|max:255'
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return back()->withErrors(['subdomain' => 'No company found for this user.']);
        }

        // Check if company already has a subdomain
        if ($company->subdomain) {
            return back()->withErrors(['subdomain' => 'Company already has a subdomain.']);
        }

        try {
            // Generate subdomain name first
            $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $request->company_name));
            $subdomainUrl = 'https://' . $subdomain . '.zaptask.co.uk';

            // Update database FIRST with subdomain information
            $company->update([
                'subdomain' => $subdomain,
                'subdomain_url' => $subdomainUrl
            ]);

            // Now create subdomain using Cloudflare and Heroku APIs
            $result = $this->cloudflareService->createSubdomain($request->company_name);

            if ($result['success']) {
                return back()->with('success', 'Subdomain created successfully! Your company can now be accessed at: ' . $subdomainUrl);
            } else {
                // If API creation fails, clean up the database
                
                $company->update([
                    'subdomain' => null,
                    'subdomain_url' => null
                ]);

                return back()->withErrors(['subdomain' => $result['message']]);
            }

        } catch (\Exception $e) {
            Log::error('Subdomain creation failed', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $request->company_name,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['subdomain' => 'Failed to create subdomain. Please try again.']);
        }
    }

    /**
     * Delete the company subdomain
     */
    public function deleteSubdomain(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company || !$company->subdomain) {
            return back()->withErrors(['subdomain' => 'No subdomain found to delete.']);
        }

        try {
            // Delete subdomain using Cloudflare and Heroku APIs
            $result = $this->cloudflareService->deleteSubdomain($company->subdomain);

            if ($result['success']) {
                // Remove subdomain information from company
                $company->update([
                    'subdomain' => null,
                    'subdomain_url' => null
                ]);

                return back()->with('success', 'Subdomain deleted successfully.');
            } else {
                return back()->withErrors(['subdomain' => $result['message']]);
            }

        } catch (\Exception $e) {
            Log::error('Subdomain deletion failed', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'subdomain' => $company->subdomain,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['subdomain' => 'Failed to delete subdomain. Please try again.']);
        }
    }

    /**
     * Check API permissions for Cloudflare and Heroku services
     */
    public function checkApiPermissions(): JsonResponse
    {
        try {
            $permissions = $this->cloudflareService->checkApiPermissions();
            return response()->json($permissions);
        } catch (\Exception $e) {
            Log::error('API permissions check failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to check API permissions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Test API connectivity for Cloudflare and Heroku
     */
    public function testApiKey(): JsonResponse
    {
        try {
            $result = $this->cloudflareService->testApiConnectivity();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('API connectivity test failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to test API connectivity: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check subdomain availability
     */
    public function checkSubdomainAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'subdomain' => 'required|string|min:3|max:50|regex:/^[a-z0-9-]+$/'
        ]);

        $subdomain = strtolower(trim($request->subdomain));
        
        // Check if subdomain is already taken
        $existingCompany = \App\Models\Company::where('subdomain', $subdomain)->first();
        
        if ($existingCompany) {
            return response()->json([
                'available' => false,
                'message' => 'Sorry, domain already taken!'
            ]);
        }
        
        // Check if subdomain is reserved or invalid
        $reserved = ['www', 'api', 'admin', 'app', 'mail', 'ftp', 'blog', 'shop', 'store', 'support', 'help', 'docs', 'status', 'cdn', 'assets', 'static', 'media', 'images', 'files', 'download', 'upload', 'test', 'dev', 'staging', 'demo', 'beta', 'alpha', 'preview', 'sandbox', 'playground', 'example', 'sample', 'template', 'default', 'main', 'primary', 'secondary', 'backup', 'archive', 'old', 'new', 'temp', 'tmp', 'cache', 'logs', 'config', 'settings', 'dashboard', 'panel', 'control', 'manage', 'admin', 'user', 'users', 'account', 'accounts', 'profile', 'profiles', 'login', 'logout', 'register', 'signup', 'signin', 'signout', 'auth', 'oauth', 'api', 'rest', 'graphql', 'webhook', 'callback', 'redirect', 'short', 'url', 'link', 'go', 'to', 'goto', 'jump', 'forward', 'proxy', 'mirror', 'clone', 'copy', 'duplicate', 'fork', 'branch', 'version', 'v1', 'v2', 'v3', 'latest', 'stable', 'release', 'rc', 'beta', 'alpha', 'dev', 'development', 'staging', 'production', 'prod', 'live', 'public', 'private', 'internal', 'external', 'secure', 'ssl', 'tls', 'https', 'http', 'ftp', 'sftp', 'ssh', 'telnet', 'smtp', 'pop', 'imap', 'dns', 'whois', 'ping', 'traceroute', 'nslookup', 'dig', 'host', 'curl', 'wget', 'rsync', 'scp', 'sftp', 'ftp', 'telnet', 'ssh', 'rsh', 'rlogin', 'rexec', 'rcp', 'rdist', 'rsh', 'rlogin', 'rexec', 'rcp', 'rdist', 'rsh', 'rlogin', 'rexec', 'rcp', 'rdist'];
        
        if (in_array($subdomain, $reserved)) {
            return response()->json([
                'available' => false,
                'message' => 'This subdomain is reserved and cannot be used'
            ]);
        }
        
        return response()->json([
            'available' => true,
            'message' => 'This subdomain is available',
            'subdomain' => $subdomain,
            'url' => "https://{$subdomain}.zaptask.co.uk"
        ]);
    }

    /**
     * Update company name
     */
    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|min:2'
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return back()->withErrors(['name' => 'No company found for this user.']);
        }

        // Verify user is an employee of the company
        if ($user->company_id !== $company->id) {
            Log::warning('Unauthorized company name update attempt', [
                'user_id' => $user->id,
                'user_company_id' => $user->company_id,
                'target_company_id' => $company->id,
                'new_name' => $request->name
            ]);

            return back()->withErrors(['name' => 'You are not authorized to update this company name.']);
        }

        try {
            $company->update([
                'name' => $request->name
            ]);

            Log::info('Company name updated successfully', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'old_name' => $company->getOriginal('name'),
                'new_name' => $request->name
            ]);

            return back()->with('success', 'Company name updated successfully.');

        } catch (\Exception $e) {
            Log::error('Company name update failed', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'new_name' => $request->name,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['name' => 'Failed to update company name. Please try again.']);
        }
    }

    /**
     * Toggle public dashboard setting
     */
    public function togglePublic(Request $request)
    {
        $request->validate([
            'is_public' => 'required|boolean'
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return back()->withErrors(['public' => 'No company found for this user.']);
        }

        try {
            $company->update([
                'is_public' => $request->is_public
            ]);

            $message = $request->is_public 
                ? 'Public dashboard enabled. Guests can now view your dashboard without logging in.'
                : 'Public dashboard disabled. Dashboard is now private.';

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Public dashboard toggle failed', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'is_public' => $request->is_public,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['public' => 'Failed to update public dashboard setting. Please try again.']);
        }
    }
}
