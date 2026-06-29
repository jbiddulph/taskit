<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceRequirement;
use App\Models\OperationalObject;
use App\Support\OperationalObjectTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OperationalObjectController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (! $user->company_id) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $objects = OperationalObject::forCompany($user->company_id)
            ->orderBy('name')
            ->get()
            ->map(fn ($object) => [
                'id' => $object->id,
                'name' => $object->name,
                'type' => $object->type,
                'type_label' => OperationalObjectTypes::label($object->type),
                'full_address' => $object->full_address,
                'parent_id' => $object->parent_id,
                'latitude' => $object->latitude,
                'longitude' => $object->longitude,
            ]);

        return response()->json(['success' => true, 'data' => $objects]);
    }

    public function complianceSummary(): JsonResponse
    {
        $user = Auth::user();

        if (! $user->company_id) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $requirements = ComplianceRequirement::forCompany($user->company_id)->get();

        foreach ($requirements as $requirement) {
            $requirement->refreshStatus();
        }

        $requirements = ComplianceRequirement::forCompany($user->company_id)
            ->with('operationalObject')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => [
                    'overdue' => $requirements->where('status', ComplianceRequirement::STATUS_OVERDUE)->count(),
                    'due_soon' => $requirements->where('status', ComplianceRequirement::STATUS_DUE_SOON)->count(),
                    'compliant' => $requirements->where('status', ComplianceRequirement::STATUS_COMPLIANT)->count(),
                    'missing' => $requirements->where('status', ComplianceRequirement::STATUS_MISSING)->count(),
                ],
                'overdue' => $requirements->where('status', ComplianceRequirement::STATUS_OVERDUE)
                    ->take(10)
                    ->values()
                    ->map(fn ($req) => $this->serializeRequirement($req)),
                'due_soon' => $requirements->where('status', ComplianceRequirement::STATUS_DUE_SOON)
                    ->take(10)
                    ->values()
                    ->map(fn ($req) => $this->serializeRequirement($req)),
                'missing' => $requirements->where('status', ComplianceRequirement::STATUS_MISSING)
                    ->take(10)
                    ->values()
                    ->map(fn ($req) => $this->serializeRequirement($req)),
            ],
        ]);
    }

    protected function serializeRequirement(ComplianceRequirement $requirement): array
    {
        return [
            'id' => $requirement->id,
            'label' => $requirement->label,
            'status' => $requirement->status,
            'next_due_date' => $requirement->next_due_date?->format('Y-m-d'),
            'site' => $requirement->operationalObject ? [
                'id' => $requirement->operationalObject->id,
                'name' => $requirement->operationalObject->name,
            ] : null,
        ];
    }
}
