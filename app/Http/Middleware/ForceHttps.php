<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS for all URLs, but only if we're not already on HTTPS
        // and if the request is coming from a non-secure connection
        if (config('app.force_https', true) && 
            !$request->secure() && 
            !$request->header('X-Forwarded-Proto') === 'https') {
            return redirect()->secure($request->getRequestUri());
        }
        
        return $next($request);
    }
}
