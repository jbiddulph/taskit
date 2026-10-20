<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Industries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $throttleKey = strtolower($validated['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user->load('company');

        if ($user->company_id && $user->company && ! $user->company->userCanAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Your company has exceeded the user limit for the current subscription plan. Please upgrade your plan to regain access.',
            ], 403);
        }

        $deviceName = $validated['device_name'] ?? 'iPhone';
        $token = $user->createToken($deviceName, ['mobile'])->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => $this->payload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('company');

        return response()->json([
            'success' => true,
            'data' => $this->payload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $bearer = $request->bearerToken();

        if ($user && $bearer) {
            $resolved = \App\Models\PersonalAccessToken::findToken($bearer);
            $resolved?->delete();
        } elseif ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Signed out',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        $company = $user->company;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'is_admin' => (bool) $user->is_admin,
            ],
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'industry' => $company->industry,
                'subscription_type' => $company->subscription_type,
                'logo_url' => $company->logo_url,
                'can_access_sites' => $company->canAccessSites(),
                'current_member_count' => $company->getCurrentMemberCount(),
                'member_limit' => $company->getMemberLimit() === PHP_INT_MAX ? null : $company->getMemberLimit(),
                'current_project_count' => $company->getCurrentProjectCount(),
                'project_limit' => $company->getProjectLimit() === PHP_INT_MAX ? null : $company->getProjectLimit(),
            ] : null,
            'task_types' => Industries::typesFor($company?->industry),
        ];
    }
}
