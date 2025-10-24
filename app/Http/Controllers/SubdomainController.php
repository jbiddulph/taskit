<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SubdomainController extends Controller
{
    /**
     * Show the subdomain dashboard
     */
    public function dashboard(Request $request)
    {
        $company = $request->attributes->get('company');
        
        \Log::info('Subdomain dashboard accessed', [
            'company' => $company ? $company->name : 'null',
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId()
        ]);
        
        if (!$company) {
            return redirect('https://www.zaptask.co.uk');
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            \Log::info('User not authenticated, redirecting to login');
            return redirect()->route('subdomain.login');
        }

        // Check if user belongs to this company
        $user = Auth::user();
        if ($user->company_id !== $company->id) {
            Auth::logout();
            return redirect()->route('subdomain.login')->withErrors([
                'email' => 'You are not authorized to access this company portal.'
            ]);
        }

        // Get users from this company
        $users = User::where('company_id', $company->id)->get();
        
        // Get company todos
        $todos = $company->todos()->with('user')->get();

        // Get user's projects
        $projects = $user->projects()->with('owner')->get();

        return Inertia::render('Subdomain/Dashboard', [
            'company' => $company,
            'users' => $users,
            'todos' => $todos,
            'projects' => $projects,
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
            return redirect('https://www.zaptask.co.uk');
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
            return redirect('https://www.zaptask.co.uk');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Only allow users from this company to login
        $user = User::where('email', $credentials['email'])
                   ->where('company_id', $company->id)
                   ->first();

        \Log::info('Subdomain authentication attempt', [
            'email' => $credentials['email'],
            'company_id' => $company->id,
            'user_found' => $user ? $user->id : 'null',
            'password_check' => $user ? Hash::check($credentials['password'], $user->password) : 'no_user'
        ]);

        if ($user && Hash::check($credentials['password'], $user->password)) {
            // Manually log in the user
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            
            // Force session to be saved
            $request->session()->save();
            
            \Log::info('Subdomain authentication successful', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'session_id' => $request->session()->getId()
            ]);
            
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
        
        \Log::info('SubdomainController::company called', [
            'company' => $company ? $company->name : 'null',
            'path' => $request->path()
        ]);
        
        if (!$company) {
            return redirect('https://www.zaptask.co.uk');
        }

        return Inertia::render('Subdomain/Company', [
            'company' => $company,
            'isSubdomain' => true
        ]);
    }

}