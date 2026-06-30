<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSitesAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->company_id || ! $user->company) {
            abort(403, 'Access denied.');
        }

        if (! $user->company->canAccessSites()) {
            $message = 'Sites & Assets is available on MAXI, LTD Agency, and LTD Business plans. Please upgrade to access this feature.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return redirect()
                ->route('subscription.index')
                ->withErrors(['subscription' => $message]);
        }

        return $next($request);
    }
}
