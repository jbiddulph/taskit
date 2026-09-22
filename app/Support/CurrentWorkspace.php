<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Workspace;
use Illuminate\Http\Request;

class CurrentWorkspace
{
    public const SESSION_KEY = 'current_workspace_id';

    /**
     * Resolve the active workspace for the authenticated user's company.
     */
    public static function resolve(Request $request): ?Workspace
    {
        $user = $request->user();
        $company = $user?->company;

        if (! $company instanceof Company) {
            return null;
        }

        $default = Workspace::ensureDefaultForCompany($company);
        $sessionId = $request->session()->get(self::SESSION_KEY);

        if ($sessionId) {
            $workspace = Workspace::query()
                ->forCompany($company->id)
                ->where('id', (int) $sessionId)
                ->first();

            if ($workspace) {
                return $workspace;
            }
        }

        $request->session()->put(self::SESSION_KEY, $default->id);

        return $default;
    }

    /**
     * @return list<array{id: int, name: string, type: string, is_default: bool}>
     */
    public static function listForShare(Company $company): array
    {
        Workspace::ensureDefaultForCompany($company);

        return Workspace::query()
            ->forCompany($company->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_default'])
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'type' => $workspace->type,
                'is_default' => (bool) $workspace->is_default,
            ])
            ->values()
            ->all();
    }

    public static function switchTo(Request $request, Workspace $workspace): void
    {
        $request->session()->put(self::SESSION_KEY, $workspace->id);
    }
}
