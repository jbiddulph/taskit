<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the ZapProperty portal — a single platform-level key
 * (ZAPPROPERTY_API_KEY) that may read every listing an agent has published
 * with "Show on ZapProperty", across all companies.
 *
 * Deliberately separate from platform.auth: company zt_live_ keys stay
 * company-scoped, and this key is never accepted on company endpoints.
 */
class AuthenticateZapProperty
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.zapproperty.api_key');

        if ($expected === '') {
            return response()->json([
                'success' => false,
                'message' => 'ZapProperty portal is not enabled on this ZapTask (ZAPPROPERTY_API_KEY is not set).',
            ], 503);
        }

        $bearer = (string) $request->bearerToken();

        if ($bearer === '' || ! hash_equals($expected, $bearer)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ZapProperty portal key.',
            ], 401);
        }

        $request->attributes->set('platform_auth_type', 'zapproperty');

        return $next($request);
    }
}
