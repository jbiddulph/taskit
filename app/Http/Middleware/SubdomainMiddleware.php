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
        // Skip Filament admin routes and Livewire endpoints (used by Filament/Livewire)
        if ($request->is('admin*') || $request->is('livewire/*')) {
            return $next($request);
        }
        
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

                // Handle subdomain-specific PUBLIC routes only.
                // All authenticated app routes (/dashboard, /projects, /api, /settings, etc.)
                // should behave exactly the same as on the main domain.
                $path = $request->path();

                if ($path === '' || $path === '/') {
                    // Show company landing page for root path on the subdomain
                    \Log::info('SubdomainMiddleware: Routing to company page', [
                        'path' => $path,
                        'company' => $company->name,
                    ]);

                    return app(SubdomainController::class)->company($request);
                }

                if ($path === 'public') {
                    \Log::info('Public dashboard route accessed', [
                        'company' => $company->name,
                        'is_public' => $company->is_public,
                        'path' => $path,
                    ]);

                    // Only show public dashboard if the company is marked public
                    if ($company->is_public) {
                        return app(SubdomainController::class)->publicDashboard($request);
                    }

                    // Company is not public, redirect to main marketing site
                    \Log::info('Company is not public, redirecting to main site');

                    return redirect('https://www.zaptask.co.uk');
                }

                // For all other paths, continue with normal routing so that
                // logged-in users see the same app experience on subdomains
                // as on https://www.zaptask.co.uk.
                return $next($request);
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