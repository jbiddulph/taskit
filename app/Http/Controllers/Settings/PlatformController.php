<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Automation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformController extends Controller
{
    /**
     * Platform overview — API keys, automations, and AI entry points.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'A company is required to use the ZapTask platform.');
        }

        return Inertia::render('settings/Platform', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'stats' => [
                'api_keys' => ApiKey::query()->forCompany($company->id)->count(),
                'automations' => Automation::query()->forCompany($company->id)->count(),
            ],
            'apiBaseUrl' => rtrim(config('app.url'), '/').'/api/v1',
        ]);
    }
}
