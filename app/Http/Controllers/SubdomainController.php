<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class SubdomainController extends Controller
{
    /**
     * Show the subdomain dashboard
     */
    public function dashboard(Request $request)
    {
        $company = $request->attributes->get('company');
        
        if (!$company) {
            return redirect('https://zaptask.co.uk');
        }

        // Get users from this company
        $users = User::where('company_id', $company->id)->get();
        
        // Get company todos
        $todos = $company->todos()->with('user')->get();

        return Inertia::render('Subdomain/Dashboard', [
            'company' => $company,
            'users' => $users,
            'todos' => $todos,
            'isSubdomain' => true
        ]);
    }

    /**
     * Show the subdomain login page
     */
    public function login(Request $request)
    {
        $company = $request->attributes->get('company');
        
        if (!$company) {
            return redirect('https://zaptask.co.uk');
        }

        return Inertia::render('Subdomain/Login', [
            'company' => $company,
            'isSubdomain' => true
        ]);
    }

    /**
     * Handle subdomain login
     */
    public function authenticate(Request $request)
    {
        $company = $request->attributes->get('company');
        
        if (!$company) {
            return redirect('https://zaptask.co.uk');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Only allow users from this company to login
        $user = User::where('email', $credentials['email'])
                   ->where('company_id', $company->id)
                   ->first();

        if ($user && Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show company information for guests
     */
    public function company(Request $request)
    {
        $company = $request->attributes->get('company');
        
        if (!$company) {
            return redirect('https://zaptask.co.uk');
        }

        return Inertia::render('Subdomain/Company', [
            'company' => $company,
            'isSubdomain' => true
        ]);
    }
}