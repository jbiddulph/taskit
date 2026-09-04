<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\CloudflareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDashboardRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_apex_public_path_returns_not_found(): void
    {
        $this->get('https://www.zaptask.co.uk/public')
            ->assertNotFound();
    }

    public function test_subdomain_public_path_redirects_to_company_homepage(): void
    {
        $this->makeCompany('johnb');

        $this->get('https://johnb.zaptask.co.uk/public')
            ->assertRedirect('https://johnb.zaptask.co.uk');
    }

    public function test_subdomain_homepage_still_renders_company_page(): void
    {
        $this->makeCompany('johnb', isPublic: true);

        $this->get('https://johnb.zaptask.co.uk/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Subdomain/Company')
                ->where('company.subdomain', 'johnb')
                ->where('isSubdomain', true)
            );
    }

    public function test_authenticated_dashboard_still_works_on_subdomain(): void
    {
        [$user] = $this->makeCompany('johnb');

        $this->actingAs($user)
            ->get('https://johnb.zaptask.co.uk/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard'));
    }

    public function test_company_settings_can_toggle_public_dashboard(): void
    {
        [$user, $company] = $this->makeCompany('johnb');

        $this->actingAs($user)
            ->get(route('company'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Company')
                ->where('company.is_public', false)
                ->where('company.subdomain_url', 'https://johnb.zaptask.co.uk')
            );

        $this->mock(CloudflareService::class);

        $this->actingAs($user)
            ->patch(route('company.public.toggle'), [
                'is_public' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('taskit_companies', [
            'id' => $company->id,
            'is_public' => true,
        ]);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    protected function makeCompany(string $subdomain, bool $isPublic = false): array
    {
        $company = Company::create([
            'name' => 'John B Ltd',
            'code' => 'JOHNB123',
            'industry' => 'software-development',
            'subscription_type' => 'MIDI',
            'subdomain' => $subdomain,
            'subdomain_url' => 'https://'.$subdomain.'.zaptask.co.uk',
            'is_public' => $isPublic,
            'homepage_background_url' => 'https://images.unsplash.com/example',
            'homepage_background_mode' => 'industry',
            'homepage_background_industry' => 'software-development',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        return [$user, $company];
    }
}
