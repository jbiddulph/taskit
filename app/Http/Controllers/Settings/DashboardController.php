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
        
        // Refresh company to ensure we have the latest data
        if ($company) {
            $company->refresh();
            error_log('DASHBOARD INDEX DEBUG: Company prune_completed_tasks: ' . ($company->prune_completed_tasks ? 'true' : 'false'));
        }

        return Inertia::render('settings/Dashboard', [
            'user' => $user,
            'company' => $company,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'prune_completed_tasks' => 'boolean',
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            return back()->withErrors(['error' => 'No company found.']);
        }

        $company->update([
            'prune_completed_tasks' => $request->boolean('prune_completed_tasks'),
        ]);

        // Reload the company to get fresh data
        $company->refresh();
        $user->load('company');

        // Return with fresh company data to ensure frontend gets updated values
        return Inertia::render('settings/Dashboard', [
            'user' => $user,
            'company' => $company,
        ])->with('success', 'Dashboard settings updated successfully.');
    }
}
