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
            \Log::info('Subdomain creation request detected in middleware', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'request_path' => $request->path(),
                'request_method' => $request->method()
            ]);
            
            // Check if user is the company owner (first user or has admin privileges)
            $isOwner = $company->users()->orderBy('created_at')->first()->id === $user->id;
            
            \Log::info('Subdomain creation middleware check', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'is_owner' => $isOwner,
                'first_user_id' => $company->users()->orderBy('created_at')->first()->id
            ]);
            
            if ($isOwner) {
                \Log::info('Allowing subdomain creation for company owner');
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
