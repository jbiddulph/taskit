<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TodaySummaryService
{
    public function summarize(User $user, ?int $projectId = null): array
    {
        $today = Carbon::today();
        $todos = $this->accessibleTodos($user, $projectId);

        $active = $todos->filter(fn (Todo $todo) => $todo->status !== 'done');

        $overdue = $active->filter(function (Todo $todo) use ($today) {
            return $todo->due_date && Carbon::parse($todo->due_date)->lt($today);
        })->values();

        $dueToday = $active->filter(function (Todo $todo) use ($today) {
            return $todo->due_date && Carbon::parse($todo->due_date)->isSameDay($today);
        })->values();

        $viewings = $active->filter(function (Todo $todo) use ($today) {
            if (strtolower((string) $todo->type) !== 'viewing') {
                return false;
            }

            if (! $todo->due_date) {
                return true;
            }

            return Carbon::parse($todo->due_date)->lte($today->copy()->endOfDay());
        })->values();

        $routeStops = $active->filter(function (Todo $todo) use ($today) {
            if ($todo->latitude === null || $todo->longitude === null) {
                return false;
            }

            if (! $todo->due_date) {
                return false;
            }

            $due = Carbon::parse($todo->due_date);

            return $due->lte($today) || $due->isSameDay($today);
        })->values();

        $pendingCheckIns = $routeStops->filter(fn (Todo $todo) => $todo->checked_in_at === null)->values();

        return [
            'overdue' => $this->serializeTodos($overdue),
            'due_today' => $this->serializeTodos($dueToday),
            'viewings' => $this->serializeTodos($viewings),
            'route_stops' => $this->serializeTodos($routeStops),
            'pending_check_ins' => $this->serializeTodos($pendingCheckIns),
            'spoken_summary' => $this->buildSpokenSummary($overdue, $dueToday, $viewings, $routeStops, $pendingCheckIns),
        ];
    }

    private function accessibleTodos(User $user, ?int $projectId): Collection
    {
        return Todo::query()
            ->with(['project'])
            ->whereNull('parent_task_id')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->where(function ($query) use ($user) {
                if ($user->company_id) {
                    $query->whereHas('user', fn ($q) => $q->where('company_id', $user->company_id));
                } else {
                    $query->where('user_id', $user->id);
                }
            })
            ->orderBy('due_date')
            ->orderBy('priority')
            ->get();
    }

    private function serializeTodos(Collection $todos): array
    {
        return $todos->map(fn (Todo $todo) => [
            'id' => $todo->id,
            'title' => $todo->title,
            'type' => $todo->type,
            'status' => $todo->status,
            'due_date' => $todo->due_date instanceof \Carbon\Carbon
                ? $todo->due_date->format('Y-m-d')
                : $todo->due_date,
            'priority' => $todo->priority,
            'location_name' => $todo->location_name,
            'location_address' => $todo->location_address,
            'latitude' => $todo->latitude,
            'longitude' => $todo->longitude,
            'checked_in_at' => $todo->checked_in_at?->toIso8601String(),
            'project_id' => $todo->project_id,
            'project_name' => $todo->project?->name,
            'reference_id' => $this->referenceIdForTodo($todo),
        ])->all();
    }

    private function referenceIdForTodo(Todo $todo): string
    {
        $prefix = strtoupper($todo->project?->key ?? substr($todo->project?->name ?? 'TASK', 0, 4));

        return "{$prefix}-{$todo->id}";
    }

    private function buildSpokenSummary(
        Collection $overdue,
        Collection $dueToday,
        Collection $viewings,
        Collection $routeStops,
        Collection $pendingCheckIns,
    ): string {
        $parts = [];

        if ($overdue->isNotEmpty()) {
            $parts[] = $overdue->count() === 1
                ? 'You have 1 overdue task'
                : "You have {$overdue->count()} overdue tasks";
        }

        if ($dueToday->isNotEmpty()) {
            $parts[] = $dueToday->count() === 1
                ? '1 task due today'
                : "{$dueToday->count()} tasks due today";
        }

        if ($viewings->isNotEmpty()) {
            $parts[] = $viewings->count() === 1
                ? '1 viewing on your list'
                : "{$viewings->count()} viewings on your list";
        }

        if ($routeStops->isNotEmpty()) {
            $parts[] = $routeStops->count() === 1
                ? '1 stop on your route'
                : "{$routeStops->count()} stops on your route";
        }

        if ($pendingCheckIns->isNotEmpty()) {
            $parts[] = $pendingCheckIns->count() === 1
                ? '1 check-in still pending'
                : "{$pendingCheckIns->count()} check-ins still pending";
        }

        if ($parts === []) {
            return 'Nothing urgent on your list for today. You are all caught up.';
        }

        $summary = 'For today: '.implode(', ', $parts).'.';

        $highlight = $overdue->first() ?? $dueToday->first() ?? $viewings->first();
        if ($highlight) {
            $ref = $this->referenceIdForTodo($highlight);
            $summary .= " Next up: {$ref}, {$highlight->title}.";
        }

        return $summary;
    }
}
