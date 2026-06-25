<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\Todo;
use App\Models\User;

class ViewingBoardTemplate
{
    /**
     * @return array<int, array{title: string, type: string, status: string}>
     */
    public static function tasks(): array
    {
        return [
            ['title' => 'Prepare listing pack', 'type' => 'Property', 'status' => 'todo'],
            ['title' => 'Book photographer', 'type' => 'Property', 'status' => 'todo'],
            ['title' => 'Schedule first viewings', 'type' => 'Viewing', 'status' => 'todo'],
            ['title' => 'Chase enquiries', 'type' => 'Lead', 'status' => 'in-progress'],
            ['title' => 'Review offers', 'type' => 'Offer', 'status' => 'in-progress'],
            ['title' => 'Pre-sale compliance checks', 'type' => 'Compliance', 'status' => 'qa-testing'],
            ['title' => 'Arrange maintenance before exchange', 'type' => 'Maintenance', 'status' => 'todo'],
        ];
    }

    public static function seed(ProjectGroup $group, Project $project, User $user): int
    {
        $created = 0;

        foreach (self::tasks() as $index => $task) {
            Todo::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'project_group_id' => $group->id,
                'company_id' => $user->company_id,
                'title' => $task['title'],
                'type' => $task['type'],
                'status' => $task['status'],
                'priority' => 'Medium',
                'order' => $index + 1,
            ]);
            $created++;
        }

        return $created;
    }
}
