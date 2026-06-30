<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_midi_plan_cannot_access_sites(): void
    {
        [$user] = $this->createCompanyUser('MIDI');

        $this->actingAs($user)
            ->get(route('sites.index'))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHasErrors('subscription');
    }

    public function test_maxi_plan_can_access_sites(): void
    {
        [$user] = $this->createCompanyUser('MAXI');

        $this->actingAs($user)
            ->get(route('sites.index'))
            ->assertOk();
    }

    public function test_ltd_agency_plan_can_access_sites(): void
    {
        [$user] = $this->createCompanyUser('LTD_AGENCY');

        $this->actingAs($user)
            ->get(route('sites.index'))
            ->assertOk();
    }

    public function test_ltd_business_plan_can_access_sites(): void
    {
        [$user] = $this->createCompanyUser('LTD_BUSINESS');

        $this->actingAs($user)
            ->get(route('sites.index'))
            ->assertOk();
    }

    public function test_midi_plan_cannot_access_sites_api(): void
    {
        [$user] = $this->createCompanyUser('MIDI');

        $this->actingAs($user)
            ->getJson('/api/sites')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_maxi_plan_can_access_sites_api(): void
    {
        [$user] = $this->createCompanyUser('MAXI');

        $this->actingAs($user)
            ->getJson('/api/sites')
            ->assertOk();
    }

    /**
     * @return array{0: User}
     */
    protected function createCompanyUser(string $subscriptionType): array
    {
        $company = Company::create([
            'name' => 'Test Co',
            'code' => 'TEST'.random_int(1000, 9999),
            'subscription_type' => $subscriptionType,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        return [$user];
    }
}
