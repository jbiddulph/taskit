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
                
                // Try using the local storage first (like todo attachments do)
                error_log('LOGO UPLOAD DEBUG: Trying local storage upload first');
                $localPath = $file->storeAs('logos', basename($path), 'public');
                error_log('LOGO UPLOAD DEBUG: Local upload result: ' . ($localPath ? 'SUCCESS' : 'FAILED'));
                
                if ($localPath) {
                    // If local works, we know the file processing is fine
                    // Now try Supabase with the exact method that works for todo images
                    error_log('LOGO UPLOAD DEBUG: Local worked, now trying Supabase putFileAs');
                    $uploaded = Storage::disk('supabase')->putFileAs('logos', $file, basename($path));
                    error_log('LOGO UPLOAD DEBUG: Supabase putFileAs result: ' . ($uploaded ? 'SUCCESS' : 'FAILED'));
                    
                    // If putFileAs fails, try the original method for comparison
                    if (!$uploaded) {
                        error_log('LOGO UPLOAD DEBUG: Trying Supabase put method');
                        $uploaded = Storage::disk('supabase')->put($path, $fileContents);
                        error_log('LOGO UPLOAD DEBUG: Supabase put result: ' . ($uploaded ? 'SUCCESS' : 'FAILED'));
                    }
                    
                    // Clean up local file if Supabase worked
                    if ($uploaded) {
                        Storage::disk('public')->delete('logos/' . basename($path));
                        error_log('LOGO UPLOAD DEBUG: Cleaned up local file');
                    }
                } else {
                    error_log('LOGO UPLOAD DEBUG: Local upload failed - file processing issue');
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
                // Get the public URL from Supabase
                $logoUrl = Storage::disk('supabase')->url($path);
                
                \Log::info('Generated logo URL', ['logo_url' => $logoUrl]);
                
                // Update company with logo URL
                $company->update(['logo_url' => $logoUrl]);
                
                return back()->with('success', 'Company logo uploaded successfully!');
            } else {
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
