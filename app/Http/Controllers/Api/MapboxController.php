<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MapboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MapboxController extends Controller
{
    public function __construct(
        protected MapboxService $mapboxService
    ) {}

    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'configured' => $this->mapboxService->isConfigured(),
            ],
        ]);
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        if (! $this->mapboxService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Mapbox is not configured.',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->mapboxService->reverseGeocode(
            (float) $request->input('longitude'),
            (float) $request->input('latitude'),
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function directions(Request $request): JsonResponse
    {
        if (! $this->mapboxService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Mapbox is not configured.',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'coordinates' => 'required|array|min:2|max:25',
            'coordinates.*.latitude' => 'required|numeric|between:-90,90',
            'coordinates.*.longitude' => 'required|numeric|between:-180,180',
            'profile' => 'nullable|in:driving,driving-traffic,walking,cycling',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $route = $this->mapboxService->directions(
            $request->input('coordinates'),
            $request->input('profile', 'driving')
        );

        if (! $route) {
            return response()->json([
                'success' => false,
                'message' => 'Could not calculate route for the selected stops.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $route,
        ]);
    }
}
