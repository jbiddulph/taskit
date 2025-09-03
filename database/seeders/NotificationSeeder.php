<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find John Biddulph user
        $john = User::where('name', 'John Biddulph')->first();
        
        if (!$john) {
            $this->command->error('John Biddulph user not found. Please create the user first.');
            return;
        }

        // Create test notifications for John Biddulph
        $notifications = [
            [
                'user_id' => $john->id,
                'type' => 'warning',
                'title' => 'Task Due Soon',
                'message' => 'Task "Implement user authentication" in project "Website Redesign" is due in 2 days.',
                'data' => [
                    'project_id' => 1,
                    'todo_id' => 1,
                    'due_date' => now()->addDays(2)->toISOString(),
                ],
                'is_read' => false,
            ],
            [
                'user_id' => $john->id,
                'type' => 'error',
                'title' => 'Overdue Task',
                'message' => 'Task "Fix login bug" in project "Bug Fixes" is 1 day overdue.',
                'data' => [
                    'project_id' => 2,
                    'todo_id' => 2,
                    'due_date' => now()->subDay()->toISOString(),
                ],
                'is_read' => false,
            ],
            [
                'user_id' => $john->id,
                'type' => 'info',
                'title' => 'Task Reminder',
                'message' => 'Task "Update documentation" in project "Documentation" is due in 5 days.',
                'data' => [
                    'project_id' => 3,
                    'todo_id' => 3,
                    'due_date' => now()->addDays(5)->toISOString(),
                ],
                'is_read' => false,
            ],
            [
                'user_id' => $john->id,
                'type' => 'success',
                'title' => 'Task Completed',
                'message' => 'Task "Setup database" in project "Initial Setup" has been completed.',
                'data' => [
                    'project_id' => 4,
                    'todo_id' => 4,
                    'completed_at' => now()->subHours(2)->toISOString(),
                ],
                'is_read' => true,
                'read_at' => now()->subHour()->toISOString(),
            ],
            [
                'user_id' => $john->id,
                'type' => 'warning',
                'title' => 'Task Due Tomorrow',
                'message' => 'Task "Code review" in project "Quality Assurance" is due tomorrow.',
                'data' => [
                    'project_id' => 5,
                    'todo_id' => 5,
                    'due_date' => now()->addDay()->toISOString(),
                ],
                'is_read' => false,
            ],
        ];

        foreach ($notifications as $notificationData) {
            Notification::create($notificationData);
        }

        $this->command->info('Created ' . count($notifications) . ' test notifications for John Biddulph.');
    }
}