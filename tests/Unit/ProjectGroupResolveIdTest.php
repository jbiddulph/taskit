<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGroupResolveIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_id_for_project_ignores_another_projects_group(): void
    {
        $user = User::factory()->create();
        $projectA = Project::create([
            'name' => 'A',
            'key' => 'AAA',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
        ]);
        $projectB = Project::create([
            'name' => 'B',
            'key' => 'BBB',
            'color' => '#10B981',
            'owner_id' => $user->id,
        ]);
        $groupA = ProjectGroup::createDefaultForProject($projectA);
        $groupB = ProjectGroup::createDefaultForProject($projectB);

        $this->assertSame(
            $groupB->id,
            ProjectGroup::resolveIdForProject($projectB->id, $groupA->id),
        );
        $this->assertSame(
            $groupB->id,
            ProjectGroup::resolveIdForProject($projectB->id, $groupB->id),
        );
        $this->assertSame(
            $groupB->id,
            ProjectGroup::resolveIdForProject($projectB->id, null),
        );
    }
}
