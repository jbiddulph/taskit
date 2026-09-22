<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class PlatformController extends Controller
{
    protected function companyId(Request $request): int
    {
        $companyId = $request->attributes->get('platform_company_id');

        if (! $companyId) {
            abort(response()->json([
                'success' => false,
                'message' => 'Company scope could not be determined from credentials.',
            ], 403));
        }

        return (int) $companyId;
    }

    protected function platformUser(Request $request): User
    {
        $user = $request->attributes->get('platform_user') ?? $request->user();

        if (! $user instanceof User) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401));
        }

        return $user;
    }

    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function fail(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
