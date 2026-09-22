<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePlatformApi
{
    /**
     * Authenticate platform API requests via:
     * 1. Company API key (zt_live_*)
     * 2. Sanctum personal access token
     * 3. Session cookie (SPA)
     *
     * Company scope is always derived from credentials — never from request body IDs.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer && str_starts_with($bearer, 'zt_live_')) {
            return $this->authenticateApiKey($request, $next, $bearer, $permission);
        }

        $user = $this->resolveUser($request, $bearer);

        if (! $user instanceof User) {
            return $this->unauthorized('Unauthenticated. Provide a session cookie, Sanctum token, or zt_live_ API key.');
        }

        if (! $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'User is not associated with a company.',
            ], 403);
        }

        Auth::setUser($user);

        $request->attributes->set('platform_company_id', (int) $user->company_id);
        $request->attributes->set('platform_auth_type', 'user');
        $request->attributes->set('platform_user', $user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function resolveUser(Request $request, ?string $bearer): ?User
    {
        if (Auth::check() && Auth::user() instanceof User) {
            return Auth::user();
        }

        if (Auth::guard('sanctum')->check() && Auth::guard('sanctum')->user() instanceof User) {
            return Auth::guard('sanctum')->user();
        }

        if ($bearer && ! str_starts_with($bearer, 'zt_live_')) {
            $token = PersonalAccessToken::findToken($bearer);
            if ($token && $token->tokenable instanceof User) {
                return $token->tokenable;
            }
        }

        return null;
    }

    private function authenticateApiKey(Request $request, Closure $next, string $bearer, ?string $permission): Response
    {
        $apiKey = ApiKey::findByPlainTextKey($bearer);

        if (! $apiKey || $apiKey->isExpired()) {
            return $this->unauthorized('Invalid or expired API key.');
        }

        if ($permission && ! $apiKey->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "API key lacks permission: {$permission}",
            ], 403);
        }

        $apiKey->touchLastUsed();

        $company = $apiKey->company;
        $actor = $apiKey->creator
            ?? $company->users()->orderBy('created_at')->first();

        if (! $actor) {
            return response()->json([
                'success' => false,
                'message' => 'API key company has no users.',
            ], 403);
        }

        Auth::setUser($actor);

        $request->attributes->set('platform_company_id', (int) $apiKey->company_id);
        $request->attributes->set('platform_auth_type', 'api_key');
        $request->attributes->set('platform_api_key', $apiKey);
        $request->attributes->set('platform_user', $actor);
        $request->setUserResolver(fn () => $actor);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
