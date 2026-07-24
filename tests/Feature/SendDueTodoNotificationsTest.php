<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendDueTodoNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_notification_is_created_only_once(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Website',
            'key' => 'WEB',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);

        $todo = Todo::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Late follow-up',
            'priority' => 'Medium',
            'type' => 'Task',
            'status' => 'todo',
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('taskit:send-due-notifications')->assertSuccessful();

        $this->assertSame(1, Notification::query()
            ->where('user_id', $user->id)
            ->where('title', 'Task overdue')
            ->whereRaw("(data->>'todo_id') = ?", [(string) $todo->id])
            ->whereRaw("(data->>'scope') = ?", ['overdue'])
            ->count());

        // Simulate the next day's hourly/daily run
        $this->travel(1)->days();
        $this->artisan('taskit:send-due-notifications')->assertSuccessful();

        $this->assertSame(1, Notification::query()
            ->where('user_id', $user->id)
            ->where('title', 'Task overdue')
            ->whereRaw("(data->>'todo_id') = ?", [(string) $todo->id])
            ->whereRaw("(data->>'scope') = ?", ['overdue'])
            ->count());
    }
}
