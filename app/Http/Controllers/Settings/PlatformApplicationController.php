<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompanyApplication;
use App\Models\PlatformApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'A company is required to manage platform applications.');
        }

        $enabledSlugs = $company->platformApplications()
            ->where('enabled', true)
            ->with('application')
            ->get()
            ->pluck('application.slug')
            ->filter()
            ->values()
            ->all();

        $applications = PlatformApplication::query()
            ->orderBy('name')
            ->get()
            ->map(fn (PlatformApplication $app) => [
                'id' => $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
                'description' => $app->description,
                'enabled' => in_array($app->slug, $enabledSlugs, true),
                'is_core' => $app->slug === 'zaptask',
            ]);

        return Inertia::render('settings/PlatformApplications', [
            'applications' => $applications,
            'builderDocsUrl' => '/settings/platform',
            'sdkHint' => '@zaptask/sdk',
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403);
        }

        if ($slug === 'zaptask') {
            return redirect()
                ->route('platform-applications.settings')
                ->with('success', 'Core ZapTask is always enabled.');
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $application = PlatformApplication::query()->where('slug', $slug)->firstOrFail();

        if ($validated['enabled']) {
            $company->enablePlatformApplication($slug);
        } else {
            CompanyApplication::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'application_id' => $application->id,
                ],
                ['enabled' => false]
            );
        }

        $label = $validated['enabled'] ? 'enabled' : 'disabled';

        return redirect()
            ->route('platform-applications.settings')
            ->with('success', "{$application->name} {$label}.");
    }
}
