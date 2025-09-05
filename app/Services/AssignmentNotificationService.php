<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;

class AssignmentNotificationService
{
    /**
     * Send assignment notification when a todo is assigned to a user
     */
    public function sendAssignmentNotification(Todo $todo, ?string $oldAssignee = null): void
    {
        // Only send notification if assignee has changed and new assignee is not empty
        if ($todo->assignee && $todo->assignee !== $oldAssignee) {
            // Find the user by name (since assignee is stored as name string)
            $assignedUser = User::where('name', $todo->assignee)->first();
            
            if ($assignedUser) {
                // Don't send notification if user is assigning task to themselves
                $currentUser = auth()->user();
                if (!$currentUser || $assignedUser->id !== $currentUser->id) {
                    $this->createAssignmentNotification($todo, $assignedUser);
                }
            }
        }
    }

    /**
     * Create assignment notification for a user
     */
    private function createAssignmentNotification(Todo $todo, User $assignedUser): void
    {
        $project = $todo->project;
        $dueDateText = '';
        
        if ($todo->due_date) {
            $dueDate = Carbon::parse($todo->due_date);
            $dueDateText = " and needs to be completed by " . $dueDate->format('M j, Y');
        }

        $title = "New Task Assigned";
        $message = "Task '{$todo->title}' in project '{$project->name}' has been assigned to you{$dueDateText}.";

        // Check if notification already exists for this assignment
        $existingNotification = Notification::where('user_id', $assignedUser->id)
            ->where('type', 'assignment')
            ->whereDate('created_at', Carbon::now()->toDateString())
            ->whereRaw("(data->>'todo_id') = ?", [(string)$todo->id])
            ->first();

        if (!$existingNotification) {
            Notification::create([
                'user_id' => $assignedUser->id,
                'type' => 'assignment',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'todo_id' => $todo->id,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'todo_title' => $todo->title,
                    'due_date' => $todo->due_date,
                    'priority' => $todo->priority,
                    'assigned_by' => auth()->user()->name ?? 'System',
                ],
            ]);
        }
    }

    /**
     * Send assignment notification for newly created todos
     */
    public function sendNewTodoAssignmentNotification(Todo $todo): void
    {
        if ($todo->assignee) {
            $assignedUser = User::where('name', $todo->assignee)->first();
            
            if ($assignedUser) {
                // Don't send notification if user is assigning task to themselves
                $currentUser = auth()->user();
                if (!$currentUser || $assignedUser->id !== $currentUser->id) {
                    $this->createAssignmentNotification($todo, $assignedUser);
                }
            }
        }
    }
}
