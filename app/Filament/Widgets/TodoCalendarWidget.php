<?php

namespace App\Filament\Widgets;

use App\Models\Todo;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TodoCalendarWidget extends FullCalendarWidget
{
    protected static ?string $heading = 'Todos Calendar';
    
    public static function shouldRegister(): bool
    {
        // Only register on dashboard page to avoid loading on other pages
        return request()->routeIs('filament.admin.pages.dashboard') || 
               str_contains(request()->url(), '/admin');
    }

    /**
     * Fetch events for the calendar.
     *
     * @param  array<string, mixed>  $fetchInfo
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(array $fetchInfo = []): array
    {
        $start = $fetchInfo['start'] ?? null;
        $end = $fetchInfo['end'] ?? null;

        $query = Todo::query()
            ->whereNotNull('due_date');

        if ($start && $end) {
            $query->whereBetween('due_date', [$start, $end]);
        }

        return $query->get()->map(function (Todo $todo): array {
            return [
                'id' => $todo->id,
                'title' => $todo->title,
                'start' => $todo->due_date?->toDateString(),
                'url' => route('todos.show.web', $todo),
                'extendedProps' => [
                    'status' => $todo->status,
                    'priority' => $todo->priority,
                    'project_id' => $todo->project_id,
                    'company_id' => $todo->company_id,
                ],
            ];
        })->toArray();
    }
}


