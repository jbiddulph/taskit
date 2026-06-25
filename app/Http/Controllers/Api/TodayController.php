<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TodaySummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodayController extends Controller
{
    public function __construct(
        protected TodaySummaryService $todaySummaryService,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $projectId = $request->filled('project_id') ? (int) $request->input('project_id') : null;

        $data = $this->todaySummaryService->summarize(Auth::user(), $projectId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
