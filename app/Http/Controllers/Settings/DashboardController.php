<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load('company'); // Ensure company is loaded
        $company = $user->company;

        return Inertia::render('settings/Dashboard', [
            'user' => $user,
            'company' => $company,
        ]);
    }

    public function update(Request $request)
    {
        // Debug logging
        error_log('DASHBOARD UPDATE DEBUG: Request received');
        error_log('DASHBOARD UPDATE DEBUG: Request data: ' . json_encode($request->all()));
        
        $request->validate([
            'prune_completed_tasks' => 'boolean',
        ]);

        $user = Auth::user();
        $company = $user->company;

        error_log('DASHBOARD UPDATE DEBUG: User ID: ' . $user->id);
        error_log('DASHBOARD UPDATE DEBUG: Company ID: ' . ($company ? $company->id : 'null'));

        if (!$company) {
            error_log('DASHBOARD UPDATE DEBUG: No company found');
            return back()->withErrors(['error' => 'No company found.']);
        }

        $oldValue = $company->prune_completed_tasks;
        $newValue = $request->boolean('prune_completed_tasks');
        
        error_log('DASHBOARD UPDATE DEBUG: Old prune_completed_tasks: ' . ($oldValue ? 'true' : 'false'));
        error_log('DASHBOARD UPDATE DEBUG: New prune_completed_tasks: ' . ($newValue ? 'true' : 'false'));

        $company->update([
            'prune_completed_tasks' => $newValue,
        ]);

        // Reload the company to get fresh data
        $company->refresh();
        $user->load('company');
        
        error_log('DASHBOARD UPDATE DEBUG: After update prune_completed_tasks: ' . ($company->prune_completed_tasks ? 'true' : 'false'));
        error_log('DASHBOARD UPDATE DEBUG: Company data being returned: ' . json_encode($company->toArray()));
        error_log('DASHBOARD UPDATE DEBUG: Update completed successfully');

        // Return with fresh company data to ensure frontend gets updated values
        return Inertia::render('settings/Dashboard', [
            'user' => $user,
            'company' => $company,
        ])->with('success', 'Dashboard settings updated successfully.');
    }
}
