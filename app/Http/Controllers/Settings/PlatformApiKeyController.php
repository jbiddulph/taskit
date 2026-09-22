<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformApiKeyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'A company is required to manage platform API keys.');
        }

        $keys = ApiKey::query()
            ->forCompany($company->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ApiKey $key) => [
                'id' => $key->id,
                'name' => $key->name,
                'key_prefix' => $key->key_prefix,
                'permissions' => $key->permissions,
                'last_used_at' => optional($key->last_used_at)?->toIso8601String(),
                'expires_at' => optional($key->expires_at)?->toIso8601String(),
                'created_at' => optional($key->created_at)?->toIso8601String(),
            ]);

        return Inertia::render('settings/PlatformApiKeys', [
            'apiKeys' => $keys,
            'plainTextKey' => $request->session()->pull('plainTextPlatformKey'),
            'apiBaseUrl' => rtrim(config('app.url'), '/').'/api/v1',
            'defaultPermissions' => ApiKey::DEFAULT_PERMISSIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:100',
        ]);

        if (ApiKey::query()->forCompany($company->id)->count() >= 20) {
            return back()->withErrors(['name' => 'You can have at most 20 platform API keys.']);
        }

        $result = ApiKey::generate(
            $company,
            $validated['name'],
            $user,
            $validated['permissions'] ?? ApiKey::DEFAULT_PERMISSIONS,
        );

        return redirect()
            ->route('platform-api-keys.settings')
            ->with('plainTextPlatformKey', $result['plain_text_key']);
    }

    public function destroy(Request $request, int $apiKeyId): RedirectResponse
    {
        $user = $request->user();

        if (! $user->company_id) {
            abort(403);
        }

        ApiKey::query()
            ->forCompany($user->company_id)
            ->where('id', $apiKeyId)
            ->delete();

        return redirect()->route('platform-api-keys.settings')->with('success', 'API key revoked.');
    }
}
