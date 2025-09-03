<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Project;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDueTodoNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskit:send-due-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create notifications for todos due within 1, 3, and 7 days';

    public function handle(): int
    {
        $now = Carbon::now();
        $tomorrow = $now->copy()->addDay()->startOfDay();
        $inThreeDays = $now->copy()->addDays(3)->endOfDay();
        $inSevenDays = $now->copy()->addDays(7)->endOfDay();

        // Due tomorrow (exact date match)
        $dueTomorrow = Todo::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', $tomorrow->toDateString())
            ->where('status', '!=', 'done')
            ->with(['project'])
            ->get();

        // Due within 3 days (2-3 days from now, excluding tomorrow)
        $dueWithinThreeDays = Todo::query()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now->copy()->addDays(2)->startOfDay(), $inThreeDays])
            ->where('status', '!=', 'done')
            ->with(['project'])
            ->get();

        // Due within 7 days (4-7 days from now)
        $dueWithinSevenDays = Todo::query()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now->copy()->addDays(4)->startOfDay(), $inSevenDays])
            ->where('status', '!=', 'done')
            ->with(['project'])
            ->get();

        $created = 0;

        // Notifications for todos due tomorrow
        foreach ($dueTomorrow as $todo) {
            $created += $this->notifyIfNotExists(
                userId: $todo->user_id,
                type: 'warning',
                title: 'Todo due tomorrow',
                message: $this->buildMessage($todo->title, $todo->project, Carbon::parse($todo->due_date)),
                data: [
                    'todo_id' => $todo->id,
                    'project_id' => $todo->project_id,
                    'due_in_days' => 1,
                    'scope' => 'due_tomorrow',
                ],
            );
        }

        // Notifications for todos due within 3 days
        foreach ($dueWithinThreeDays as $todo) {
            $dueDate = Carbon::parse($todo->due_date);
            $days = Carbon::now()->startOfDay()->diffInDays($dueDate, false);
            if ($days < 2 || $days > 3) {
                continue; // guard in case of edge cases
            }

            $created += $this->notifyIfNotExists(
                userId: $todo->user_id,
                type: 'warning',
                title: 'Todo due in 3 days',
                message: $this->buildMessage($todo->title, $todo->project, $dueDate, $days),
                data: [
                    'todo_id' => $todo->id,
                    'project_id' => $todo->project_id,
                    'due_in_days' => $days,
                    'scope' => 'due_within_3_days',
                ],
            );
        }

        // Notifications for todos due within 7 days
        foreach ($dueWithinSevenDays as $todo) {
            $dueDate = Carbon::parse($todo->due_date);
            $days = Carbon::now()->startOfDay()->diffInDays($dueDate, false);
            if ($days < 4 || $days > 7) {
                continue; // guard in case of edge cases
            }

            $created += $this->notifyIfNotExists(
                userId: $todo->user_id,
                type: 'info',
                title: 'Todo due in 7 days',
                message: $this->buildMessage($todo->title, $todo->project, $dueDate, $days),
                data: [
                    'todo_id' => $todo->id,
                    'project_id' => $todo->project_id,
                    'due_in_days' => $days,
                    'scope' => 'due_within_7_days',
                ],
            );
        }

        $this->info("Notifications created: {$created}");
        return self::SUCCESS;
    }

    private function buildMessage(string $todoTitle, ?Project $project, Carbon $dueDate, ?int $days = null): string
    {
        $projectName = $project?->name ?? 'Unknown Project';
        $projectKey = $project?->key ? " ({$project->key})" : '';
        $when = $days === null ? 'tomorrow' : ($days === 1 ? 'tomorrow' : "in {$days} days");
        $dateStr = $dueDate->toFormattedDateString();

        return "'{$todoTitle}' in {$projectName}{$projectKey} is due {$when} ({$dateStr}).";
    }

    private function notifyIfNotExists(int $userId, string $type, string $title, string $message, array $data): int
    {
        // Avoid creating duplicate notifications for the same todo + scope on the same day
        $exists = Notification::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereDate('created_at', Carbon::now()->toDateString())
            ->whereRaw("(data->>'todo_id') = ?", [(string)($data['todo_id'] ?? '')])
            ->whereRaw("(data->>'scope') = ?", [(string)($data['scope'] ?? '')])
            ->exists();

        if ($exists) {
            return 0;
        }

        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        return 1;
    }
}


