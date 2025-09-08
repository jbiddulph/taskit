<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    /**
     * Get clients for the authenticated user's company
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->company_id) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $clients = Client::forCompany($user->company_id)
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }
}