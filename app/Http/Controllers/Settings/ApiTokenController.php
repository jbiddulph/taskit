<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    /**
     * Show the API tokens settings page.
     */
    public function edit(Request $request): Response
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'last_used_at', 'created_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ]);

        return Inertia::render('settings/ApiTokens', [
            'tokens' => $tokens,
            'plainTextToken' => $request->session()->pull('plainTextToken'),
            'apiBaseUrl' => rtrim(config('app.url'), '/').'/api',
        ]);
    }

    /**
     * Create a new personal access token for the Chrome extension / API clients.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $existingCount = $request->user()->tokens()->count();
        if ($existingCount >= 10) {
            throw ValidationException::withMessages([
                'name' => 'You can have at most 10 API tokens. Revoke an unused token first.',
            ]);
        }

        $token = $request->user()->createToken($validated['name'], ['extension']);

        return redirect()
            ->route('api-tokens.edit')
            ->with('plainTextToken', $token->plainTextToken);
    }

    /**
     * Revoke a personal access token.
     */
    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return redirect()->route('api-tokens.edit');
    }
}
