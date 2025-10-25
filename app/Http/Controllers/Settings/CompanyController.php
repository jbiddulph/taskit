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
            // Create subdomain using Cloudflare and Heroku APIs
            $result = $this->cloudflareService->createSubdomain($request->company_name);

            if ($result['success']) {
                // Update company with subdomain information
                $company->update([
                    'subdomain' => $result['subdomain'],
                    'subdomain_url' => $result['url']
                ]);

                return back()->with('success', 'Subdomain created successfully! Your company can now be accessed at: ' . $result['url']);
            } else {
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
