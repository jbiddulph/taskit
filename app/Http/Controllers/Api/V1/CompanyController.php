<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends PlatformController
{
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->companyId($request);

        // Never allow access to another company by ID
        if ((int) $id !== $companyId) {
            return $this->fail('Company not found.', 404);
        }

        $company = $this->platformUser($request)->company;
        if (! $company) {
            return $this->fail('Company not found.', 404);
        }

        return $this->ok([
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->code,
            'type' => $company->industry,
            'logo_url' => $company->logo_url,
            'subscription_plan' => $company->subscription_type,
            'created_at' => optional($company->created_at)?->toIso8601String(),
            'updated_at' => optional($company->updated_at)?->toIso8601String(),
        ]);
    }
}
