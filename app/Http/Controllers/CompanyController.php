<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * Display the specified company.
     */
    public function show(Company $company)
    {
        $user = Auth::user();
        
        // Check if user belongs to this company
        if (!$user->company_id || $user->company_id !== $company->id) {
            abort(403, 'You can only view your own company.');
        }

        return Inertia::render('Companies/Show', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'description' => $company->description,
                'subscription_type' => $company->subscription_type,
                'created_at' => $company->created_at,
                'current_member_count' => $company->getCurrentMemberCount(),
                'member_limit' => $company->getMemberLimit(),
                'current_project_count' => $company->getCurrentProjectCount(),
                'project_limit' => $company->getProjectLimit(),
            ],
            'members' => $company->users()->orderBy('created_at')->get(['id', 'name', 'email', 'created_at']),
            'projects' => $company->projects()->with('client', 'owner')->orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company)
    {
        $user = Auth::user();
        
        // Check if user belongs to this company
        if (!$user->company_id || $user->company_id !== $company->id) {
            abort(403, 'You can only edit your own company.');
        }

        return Inertia::render('Companies/Edit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'description' => $company->description,
                'subscription_type' => $company->subscription_type,
            ],
        ]);
    }

    /**
     * Update the specified company in storage.
     */
    public function update(Request $request, Company $company)
    {
        $user = Auth::user();
        
        // Check if user belongs to this company
        if (!$user->company_id || $user->company_id !== $company->id) {
            abort(403, 'You can only edit your own company.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $company->update($validated);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Company updated successfully.');
    }
}
