<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiKeyController extends PlatformController
{
    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::query()
            ->forCompany($this->companyId($request))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ApiKey $key) => $this->transform($key));

        return $this->ok(['api_keys' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->platformUser($request);
        $company = $user->company;

        if (! $company) {
            return $this->fail('User is not associated with a company.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:100',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $existingCount = ApiKey::query()->forCompany($company->id)->count();
        if ($existingCount >= 20) {
            return $this->fail('You can have at most 20 API keys.', 422);
        }

        $result = ApiKey::generate(
            $company,
            $request->input('name'),
            $user,
            $request->input('permissions'),
            $request->filled('expires_at') ? new \DateTimeImmutable($request->input('expires_at')) : null,
        );

        return $this->ok([
            ...$this->transform($result['model']),
            'key' => $result['plain_text_key'],
            'warning' => 'Store this key securely. It will not be shown again.',
        ], 'API key created.', 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $key = ApiKey::query()
            ->forCompany($this->companyId($request))
            ->where('id', $id)
            ->first();

        if (! $key) {
            return $this->fail('API key not found.', 404);
        }

        $key->delete();

        return $this->ok(null, 'API key revoked.');
    }

    private function transform(ApiKey $key): array
    {
        return [
            'id' => $key->id,
            'company_id' => $key->company_id,
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
            'permissions' => $key->permissions,
            'last_used_at' => optional($key->last_used_at)?->toIso8601String(),
            'expires_at' => optional($key->expires_at)?->toIso8601String(),
            'created_at' => optional($key->created_at)?->toIso8601String(),
        ];
    }
}
