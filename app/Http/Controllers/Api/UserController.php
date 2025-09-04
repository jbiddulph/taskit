<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user || !$user->company_id) {
            return response()->json([]);
        }
        
        // Get all users from the same company
        $users = $user->company->users()
            ->select('id', 'name', 'email')
            ->get();
            
        return response()->json($users);
    }
}
