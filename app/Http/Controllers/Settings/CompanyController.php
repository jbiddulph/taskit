<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\IonosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    protected IonosService $ionosService;

    public function __construct(IonosService $ionosService)
    {
        $this->ionosService = $ionosService;
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
            // Create subdomain using Ionos API
            $result = $this->ionosService->createSubdomain($request->company_name);

            if ($result['success']) {
                // Update company with subdomain information
                $company->update([
                    'subdomain' => $result['subdomain'],
                    'subdomain_url' => $result['url']
                ]);

                return back()->with('success', 'Subdomain created successfully! Your company can now be accessed at: ' . $result['url']);
            } else {
                // Check if it's a permission issue and provide helpful instructions
                if (strpos($result['message'], 'DNS zone management requires additional permissions') !== false) {
                    $instructions = $this->ionosService->getManualSetupInstructions();
                    return back()->withErrors([
                        'subdomain' => $result['message'],
                        'instructions' => $instructions['instructions']
                    ]);
                }
                
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
            // Delete subdomain using Ionos API
            $result = $this->ionosService->deleteSubdomain($company->subdomain);

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
}
