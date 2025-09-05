<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyLogoController extends Controller
{
    /**
     * Upload company logo to Supabase storage
     */
    public function upload(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        // Check if user has a company and it's a paid plan
        if (!$company || !in_array($company->subscription_type, ['MIDI', 'MAXI'])) {
            return back()->withErrors(['logo' => 'Company logo upload is only available for MIDI and MAXI plans.']);
        }

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048', // 2MB max
        ]);

        try {
            $file = $request->file('logo');
            
            // Generate filename: CompanyName_CompanyCode.extension
            $filename = $this->generateLogoFilename($company, $file->getClientOriginalExtension());
            
            // Upload to Supabase storage in logos folder
            $path = 'logos/' . $filename;
            
            // Store file using the supabase disk
            $uploaded = Storage::disk('supabase')->put($path, file_get_contents($file->getRealPath()));
            
            if ($uploaded) {
                // Get the public URL from Supabase
                $logoUrl = Storage::disk('supabase')->url($path);
                
                // Update company with logo URL
                $company->update(['logo_url' => $logoUrl]);
                
                return back()->with('success', 'Company logo uploaded successfully!');
            } else {
                return back()->withErrors(['logo' => 'Failed to upload logo. Please try again.']);
            }
        } catch (\Exception $e) {
            \Log::error('Logo upload failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withErrors(['logo' => 'Failed to upload logo: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove company logo
     */
    public function remove(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company || !in_array($company->subscription_type, ['MIDI', 'MAXI'])) {
            return back()->withErrors(['logo' => 'Company logo management is only available for MIDI and MAXI plans.']);
        }

        try {
            // If there's an existing logo, try to delete it from Supabase
            if ($company->logo_url) {
                $path = $this->extractPathFromUrl($company->logo_url);
                if ($path) {
                    Storage::disk('supabase')->delete($path);
                }
            }
            
            // Remove logo URL from company
            $company->update(['logo_url' => null]);
            
            return back()->with('success', 'Company logo removed successfully!');
        } catch (\Exception $e) {
            \Log::error('Logo removal failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withErrors(['logo' => 'Failed to remove logo: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate filename for logo: CompanyName_CompanyCode.extension
     */
    private function generateLogoFilename($company, $extension)
    {
        $companyName = Str::slug($company->name, '_');
        $companyCode = $company->code;
        
        return "{$companyName}_{$companyCode}.{$extension}";
    }

    /**
     * Extract file path from Supabase URL
     */
    private function extractPathFromUrl($url)
    {
        // Extract path from Supabase URL structure
        $pattern = '/\/storage\/v1\/object\/public\/[^\/]+\/(.+)$/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
