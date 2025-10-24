<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SetAppUrlMiddleware
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
        // Set the app URL to the current request host for multi-domain support
        $host = $request->getHost();
        if ($host && !str_contains($host, 'localhost')) {
            $scheme = $request->secure() ? 'https' : 'http';
            $appUrl = $scheme . '://' . $host;
            
            // Force the app URL to use the current request's domain
            URL::forceRootUrl($appUrl);
            
            // Also update the config for this request
            config(['app.url' => $appUrl]);
        }

        return $next($request);
    }
}
