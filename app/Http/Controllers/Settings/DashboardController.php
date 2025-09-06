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
        $company = $user->company;

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

        return back()->with('success', 'Dashboard settings updated successfully.');
    }
}
