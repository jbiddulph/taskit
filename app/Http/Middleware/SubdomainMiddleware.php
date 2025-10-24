<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\View;

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
        
        // Check if this is a subdomain request (exclude www)
        if (str_contains($host, '.zaptask.co.uk') && $host !== 'zaptask.co.uk' && $host !== 'www.zaptask.co.uk') {
            $subdomain = str_replace('.zaptask.co.uk', '', $host);
            
            // Find company by subdomain
            $company = Company::where('subdomain', $subdomain)->first();
            
            if ($company) {
                // Store company in request for use in controllers
                $request->attributes->set('company', $company);
                
                // Share company data with all views
                View::share('subdomainCompany', $company);
                
                // Set a flag to indicate this is a subdomain request
                $request->attributes->set('isSubdomain', true);
                
                // For subdomain requests, allow all routes but handle them appropriately
                // The subdomain routes will be handled by the SubdomainController
            } else {
                // Subdomain not found, redirect to main site
                return redirect('https://zaptask.co.uk');
            }
        } else {
            // Not a subdomain request, set flag to false
            $request->attributes->set('isSubdomain', false);
        }
        
        return $next($request);
    }
}