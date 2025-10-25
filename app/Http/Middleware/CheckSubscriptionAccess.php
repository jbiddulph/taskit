<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionAccess
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user has access based on subscription limits
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Skip check if no user or no company
        if (!$user || !$user->company_id) {
            return $next($request);
        }
        
        $company = $user->company;
        
        // Allow subdomain creation for company owners regardless of user limits
        if ($request->is('settings/company/subdomain') && $request->isMethod('POST')) {
            // Check if user is the company owner (first user or has admin privileges)
            $isOwner = $company->users()->orderBy('created_at')->first()->id === $user->id;
            
            if ($isOwner) {
                return $next($request);
            }
        }
        
        // Check if user has access based on subscription limits
        $canAccess = $company->userCanAccess($user);
        
        if (!$canAccess) {
            Auth::logout();
            
            // Clear session
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Redirect to subscription page with error message (force HTTPS)
            return redirect()->route('subscription.index', [], true)->withErrors([
                'subscription' => 'Access denied. Your company has exceeded the user limit for the current subscription plan. Please upgrade your plan to regain access.'
            ]);
        }
        
        return $next($request);
    }
}
