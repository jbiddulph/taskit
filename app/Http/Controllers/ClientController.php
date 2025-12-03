<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        if (!$user->company_id) {
            abort(403, 'Access denied. Only company users can manage clients.');
        }

        $clients = Client::forCompany($user->company_id)
            ->with(['createdBy', 'activeProjects'])
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'key_contact_name' => $client->key_contact_name,
                    'key_contact_email' => $client->key_contact_email,
                    'key_contact_phone' => $client->key_contact_phone,
                    'full_address' => $client->full_address,
                    'website' => $client->website,
                    'notes' => $client->notes,
                    'created_by' => $client->createdBy->name,
                    'created_at' => $client->created_at->format('M j, Y'),
                    'stats' => $client->getStats(),
                ];
            });

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'code' => $user->company->code,
                'subscription_type' => $user->company->subscription_type,
            ] : null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $user = Auth::user();
        
        if (!$user->company_id) {
            abort(403, 'Access denied. Only company users can manage clients.');
        }

        return Inertia::render('Clients/Create', [
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'code' => $user->company->code,
                'subscription_type' => $user->company->subscription_type,
            ] : null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->company_id) {
            abort(403, 'Access denied. Only company users can manage clients.');
        }

        $company = $user->company;

        // Enforce client limit based on subscription type (especially for LTD tiers)
        if ($company) {
            $currentClientCount = $company->getCurrentClientCount();
            $clientLimit = $company->getClientLimit();

            if ($clientLimit !== PHP_INT_MAX && $currentClientCount >= $clientLimit) {
                $message = "Client limit reached ({$clientLimit} clients).";

                // Custom messaging for LTD tiers
                switch ($company->subscription_type) {
                    case 'LTD_TEAM':
                        $message = "Client limit reached ({$clientLimit} clients for your LTD Team plan).";
                        break;
                    case 'LTD_AGENCY':
                        $message = "Client limit reached ({$clientLimit} clients for your LTD Agency plan).";
                        break;
                    case 'LTD_BUSINESS':
                        $message = "Client limit reached ({$clientLimit} clients for your LTD Business plan).";
                        break;
                }

                return redirect()
                    .back()
                    ->withErrors(['name' => $message])
                    ->withInput();
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key_contact_name' => 'nullable|string|max:255',
            'key_contact_email' => 'nullable|email|max:255',
            'key_contact_phone' => 'nullable|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state_province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $client = Client::create([
            ...$validated,
            'company_id' => $user->company_id,
            'created_by_user_id' => $user->id,
        ]);

        return redirect()->route('clients.index')->with('success', 'Client created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): Response
    {
        $user = Auth::user();
        
        if (!$client->canAccess($user->id)) {
            abort(403, 'Access denied.');
        }

        $client->load(['createdBy', 'activeProjects.todos']);
        
        $clientData = [
            'id' => $client->id,
            'name' => $client->name,
            'key_contact_name' => $client->key_contact_name,
            'key_contact_email' => $client->key_contact_email,
            'key_contact_phone' => $client->key_contact_phone,
            'address_line_1' => $client->address_line_1,
            'address_line_2' => $client->address_line_2,
            'city' => $client->city,
            'state_province' => $client->state_province,
            'postal_code' => $client->postal_code,
            'country' => $client->country,
            'website' => $client->website,
            'notes' => $client->notes,
            'full_address' => $client->full_address,
            'created_by' => $client->createdBy->name,
            'created_at' => $client->created_at->format('M j, Y'),
            'updated_at' => $client->updated_at->format('M j, Y'),
            'stats' => $client->getStats(),
            'projects' => $client->activeProjects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'key' => $project->key,
                    'color' => $project->color,
                    'stats' => $project->getStats(),
                ];
            }),
        ];

        return Inertia::render('Clients/Show', [
            'client' => $clientData,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'code' => $user->company->code,
                'subscription_type' => $user->company->subscription_type,
            ] : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client): Response
    {
        $user = Auth::user();
        
        if (!$client->canAccess($user->id)) {
            abort(403, 'Access denied.');
        }

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'code' => $user->company->code,
                'subscription_type' => $user->company->subscription_type,
            ] : null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $user = Auth::user();
        
        if (!$client->canAccess($user->id)) {
            abort(403, 'Access denied.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key_contact_name' => 'nullable|string|max:255',
            'key_contact_email' => 'nullable|email|max:255',
            'key_contact_phone' => 'nullable|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state_province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client)->with('success', 'Client updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $user = Auth::user();
        
        if (!$client->canAccess($user->id)) {
            abort(403, 'Access denied.');
        }

        // Set client_id to null for all associated projects instead of deleting them
        $client->projects()->update(['client_id' => null]);
        
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully!');
    }
}
