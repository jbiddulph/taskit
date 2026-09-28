<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAccessAfterPlanChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_open_their_project_after_manual_maxi_upgrade(): void
    {
        $company = Company::create([
            'name' => 'Lee Estate Agents',
            'industry' => 'estate-agents',
            'subscription_type' => 'FREE',
        ]);

        $user = User::factory()->create([
            'email' => 'lee@leequigley.co.uk',
            'company_id' => $company->id,
        ]);

        $project = $this->makeProject($user, $company, 'Listings', 'LIST', 1);

        $company->update(['subscription_type' => 'MAXI']);

        $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_fourth_project_opens_after_subscription_type_is_set_to_maxi(): void
    {
        $company = Company::create([
            'name' => 'Lee Estate Agents',
            'subscription_type' => 'FREE',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->makeProject($user, $company, 'One', 'ONE', 1);
        $this->makeProject($user, $company, 'Two', 'TWO', 2);
        $this->makeProject($user, $company, 'Three', 'THREE', 3);
        $fourth = $this->makeProject($user, $company, 'Four', 'FOUR', 4);

        $this->actingAs($user)
            ->getJson("/api/projects/{$fourth->id}")
            ->assertForbidden();

        $company->update([
            'subscription_type' => 'MAXI',
            'scheduled_subscription_type' => 'FREE',
            'scheduled_change_date' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->getJson("/api/projects/{$fourth->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $fourth->id);
    }

    public function test_company_member_can_open_a_project_linked_by_company_id(): void
    {
        $company = Company::create([
            'name' => 'Lee Estate Agents',
            'subscription_type' => 'MAXI',
        ]);
        $member = User::factory()->create(['company_id' => $company->id]);
        $formerOwner = User::factory()->create(['company_id' => null]);

        $project = Project::create([
            'name' => 'Listings',
            'key' => 'LINK',
            'color' => '#3B82F6',
            'owner_id' => $formerOwner->id,
            'company_id' => $company->id,
            'viewing_order' => 1,
        ]);

        $this->actingAs($member)
            ->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_another_companys_project_stays_forbidden_on_maxi(): void
    {
        $company = Company::create([
            'name' => 'Lee Estate Agents',
            'subscription_type' => 'MAXI',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->makeProject($user, $company, 'Listings', 'LIST', 1);

        $other = Company::create([
            'name' => 'Other Co',
            'subscription_type' => 'MAXI',
        ]);
        $otherUser = User::factory()->create(['company_id' => $other->id]);
        $otherProject = $this->makeProject($otherUser, $other, 'Secret', 'SECR', 1);

        $this->actingAs($user)
            ->getJson("/api/projects/{$otherProject->id}")
            ->assertForbidden();
    }

    public function test_numeric_string_company_id_still_grants_access(): void
    {
        $company = Company::create([
            'name' => 'Lee Estate Agents',
            'subscription_type' => 'MAXI',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->company_id = (string) $company->id;

        $this->assertTrue($company->userCanAccess($user));
    }

    public function test_plan_limits_honor_a_manual_maxi_value_regardless_of_case(): void
    {
        $company = new Company(['subscription_type' => '  maxi  ']);

        $this->assertSame(100, $company->getProjectLimit());
        $this->assertSame(20, $company->getMemberLimit());
        $this->assertTrue($company->canAccessSites());
    }

    private function makeProject(User $user, Company $company, string $name, string $key, int $order): Project
    {
        return Project::create([
            'name' => $name,
            'key' => $key,
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'viewing_order' => $order,
        ]);
    }
}
