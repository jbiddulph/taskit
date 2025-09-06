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
        // Use error_log to ensure we see logs on Heroku
        error_log('LOGO UPLOAD DEBUG: Started for user ' . Auth::id());
        error_log('LOGO UPLOAD DEBUG: Has file? ' . ($request->hasFile('logo') ? 'YES' : 'NO'));
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            error_log('LOGO UPLOAD DEBUG: File size: ' . $file->getSize());
            error_log('LOGO UPLOAD DEBUG: File mime: ' . $file->getMimeType());
            error_log('LOGO UPLOAD DEBUG: File extension: ' . $file->getClientOriginalExtension());
        }
        
        \Log::info('Logo upload request started', [
            'user_id' => Auth::id(),
            'has_file' => $request->hasFile('logo'),
            'request_data' => $request->all(),
            'files' => $request->allFiles()
        ]);

        $user = Auth::user();
        $company = $user->company;

        // Check if user has a company and it's a paid plan
        error_log('LOGO UPLOAD DEBUG: Checking subscription - Type: ' . ($company?->subscription_type ?? 'null'));
        if (!$company || !in_array($company->subscription_type, ['MIDI', 'MAXI'])) {
            error_log('LOGO UPLOAD DEBUG: Upload denied - insufficient plan');
            \Log::error('Logo upload denied - insufficient plan', [
                'user_id' => $user->id,
                'subscription_type' => $company?->subscription_type
            ]);
            return back()->withErrors(['logo' => 'Company logo upload is only available for MIDI and MAXI plans.']);
        }

        error_log('LOGO UPLOAD DEBUG: Starting validation');
        try {
            $request->validate([
                'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048', // 2MB max
            ]);
            error_log('LOGO UPLOAD DEBUG: Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            error_log('LOGO UPLOAD DEBUG: Validation failed - ' . json_encode($e->errors()));
            \Log::error('Logo upload validation failed', [
                'errors' => $e->errors(),
                'has_file' => $request->hasFile('logo'),
                'file_info' => $request->hasFile('logo') ? [
                    'size' => $request->file('logo')->getSize(),
                    'mime' => $request->file('logo')->getMimeType(),
                    'extension' => $request->file('logo')->getClientOriginalExtension()
                ] : null
            ]);
            throw $e;
        }

        error_log('LOGO UPLOAD DEBUG: Starting upload process');
        try {
            $file = $request->file('logo');
            
            // Generate filename: CompanyName_CompanyCode.extension
            $filename = $this->generateLogoFilename($company, $file->getClientOriginalExtension());
            
            // Upload to Supabase storage in logos folder
            $path = 'logos/' . $filename;
            
            error_log('LOGO UPLOAD DEBUG: Generated filename: ' . $filename);
            error_log('LOGO UPLOAD DEBUG: Upload path: ' . $path);
            
            \Log::info('Attempting logo upload', [
                'company_id' => $company->id,
                'filename' => $filename,
                'path' => $path,
                'file_size' => $file->getSize(),
                'file_mime' => $file->getMimeType()
            ]);
            
            // Test if Supabase disk is accessible
            error_log('LOGO UPLOAD DEBUG: Testing Supabase disk access');
            error_log('LOGO UPLOAD DEBUG: Supabase bucket: ' . config('filesystems.disks.supabase.bucket'));
            error_log('LOGO UPLOAD DEBUG: Supabase endpoint: ' . config('filesystems.disks.supabase.endpoint'));
            error_log('LOGO UPLOAD DEBUG: Supabase key set: ' . (config('filesystems.disks.supabase.key') ? 'YES' : 'NO'));
            \Log::info('Testing Supabase disk access');
            $disks = Storage::disk('supabase');
            \Log::info('Supabase disk created successfully');
            error_log('LOGO UPLOAD DEBUG: Supabase disk created successfully');
            
            // Store file using the supabase disk
            error_log('LOGO UPLOAD DEBUG: Attempting file upload to Supabase');
            
            // Try to get more detailed error information
            try {
                $fileContents = file_get_contents($file->getRealPath());
                error_log('LOGO UPLOAD DEBUG: File contents read successfully, size: ' . strlen($fileContents));
                
                // TEMPORARY FIX: Use local storage since Supabase S3 adapter is broken
                // Store file locally (this works perfectly)
                error_log('LOGO UPLOAD DEBUG: Using local storage as temporary fix');
                $localPath = $file->storeAs('logos', basename($path), 'public');
                error_log('LOGO UPLOAD DEBUG: Local upload result: ' . ($localPath ? 'SUCCESS' : 'FAILED'));
                
                if ($localPath) {
                    // Generate the public URL for the locally stored file
                    $logoUrl = asset('storage/' . $localPath);
                    error_log('LOGO UPLOAD DEBUG: Generated local URL: ' . $logoUrl);
                    
                    // Update company with the local logo URL
                    $company->update(['logo_url' => $logoUrl]);
                    error_log('LOGO UPLOAD DEBUG: Company updated with local logo URL');
                    
                    $uploaded = true; // Mark as successful
                } else {
                    error_log('LOGO UPLOAD DEBUG: Local upload failed');
                    $uploaded = false;
                }
                
            } catch (\Exception $uploadException) {
                error_log('LOGO UPLOAD DEBUG: Upload exception: ' . $uploadException->getMessage());
                error_log('LOGO UPLOAD DEBUG: Upload exception trace: ' . $uploadException->getTraceAsString());
                throw $uploadException;
            }
            
            \Log::info('Upload result', [
                'uploaded' => $uploaded,
                'upload_type' => gettype($uploaded)
            ]);
            
            if ($uploaded) {
                error_log('LOGO UPLOAD DEBUG: Upload successful, returning success response');
                \Log::info('Logo uploaded successfully', ['logo_url' => $company->fresh()->logo_url]);
                
                return back()->with('success', 'Company logo uploaded successfully!');
            } else {
                error_log('LOGO UPLOAD DEBUG: Upload failed, returning error');
                \Log::error('Upload returned false', ['path' => $path]);
                return back()->withErrors(['logo' => 'Failed to upload logo. Please try again.']);
            }
        } catch (\Exception $e) {
            error_log('LOGO UPLOAD DEBUG: Exception caught: ' . $e->getMessage());
            error_log('LOGO UPLOAD DEBUG: Exception trace: ' . $e->getTraceAsString());
            \Log::error('Logo upload failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filename' => $filename,
                'path' => $path
            ]);
            
            return back()->withErrors(['logo' => 'Failed to upload logo: ' . $e->getMessage()]);
        }
    }

    /**
     * Update company logo URL (from Supabase direct upload)
     */
    public function updateUrl(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company || !in_array($company->subscription_type, ['MIDI', 'MAXI'])) {
            return back()->withErrors(['logo' => 'Company logo upload is only available for MIDI and MAXI plans.']);
        }

        $request->validate([
            'logo_url' => 'required|url',
        ]);

        // Update company with the Supabase logo URL
        $company->update(['logo_url' => $request->logo_url]);

        return back()->with('success', 'Company logo uploaded successfully!');
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
