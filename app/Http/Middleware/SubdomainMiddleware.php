<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Http\Controllers\SubdomainController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class SubdomainMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        
        // Only process actual subdomains (not www or root domain)
        if (str_contains($host, '.zaptask.co.uk') && $host !== 'zaptask.co.uk' && $host !== 'www.zaptask.co.uk') {
            $subdomain = str_replace('.zaptask.co.uk', '', $host);
            
            \Log::info('Subdomain middleware processing', [
                'host' => $host,
                'subdomain' => $subdomain,
                'path' => $request->path()
            ]);
            
            // Find company by subdomain
            $company = Company::where('subdomain', $subdomain)->first();
            
            if ($company) {
                // Store company in request for use in controllers
                $request->attributes->set('company', $company);
                
                // Share company data with all views
                View::share('subdomainCompany', $company);
                
                // Set a flag to indicate this is a subdomain request
                $request->attributes->set('isSubdomain', true);
                
                // Handle subdomain routing directly
                $path = $request->path();
                
                if ($path === '' || $path === '/') {
                    // Show company page for root path
                    \Log::info('SubdomainMiddleware: Routing to company page', ['path' => $path, 'company' => $company->name]);
                    return app(SubdomainController::class)->company($request);
                } elseif ($path === 'login') {
                    // Check if user is already authenticated
                    if (Auth::check()) {
                        // User is authenticated, redirect to dashboard
                        return redirect('https://' . $request->getHost() . '/dashboard');
                    }
                    
                    // Handle both GET and POST requests to /login
                    if ($request->isMethod('POST')) {
                        // Handle login form submission
                        return app(SubdomainController::class)->authenticate($request);
                    } else {
                        // Show login page
                        return app(SubdomainController::class)->login($request);
                    }
                } elseif ($path === 'dashboard') {
                    // Show private dashboard (requires authentication)
                    return app(SubdomainController::class)->dashboard($request);
                } elseif ($path === 'public') {
                    \Log::info('Public dashboard route accessed', [
                        'company' => $company->name,
                        'is_public' => $company->is_public,
                        'path' => $path
                    ]);
                    
                    // Check if company is public
                    if ($company->is_public) {
                        // Show public dashboard for guests
                        return app(SubdomainController::class)->publicDashboard($request);
                    } else {
                        // Company is not public, redirect to main site
                        \Log::info('Company is not public, redirecting to main site');
                        return redirect('https://www.zaptask.co.uk');
                    }
                } elseif (str_starts_with($path, 'settings') || 
                          str_starts_with($path, 'clients') || 
                          str_starts_with($path, 'companies') ||
                          str_starts_with($path, 'api/')) {
                    // Block main site routes on subdomains
                    \Log::info('SubdomainMiddleware: Blocking main site route on subdomain', [
                        'path' => $path,
                        'company' => $company->name
                    ]);
                    
                    // Redirect to appropriate main site route
                    if (str_starts_with($path, 'settings')) {
                        return redirect('https://www.zaptask.co.uk/settings');
                    } else {
                        return redirect('https://www.zaptask.co.uk');
                    }
                } else {
                    // For other paths, continue with normal routing
                    return $next($request);
                }
            } else {
                // Subdomain not found, redirect to main site with www
                return redirect('https://www.zaptask.co.uk');
            }
        } else {
            // Not a subdomain request (www or root domain), let it pass through
            $request->attributes->set('isSubdomain', false);
            return $next($request);
        }
    }
}