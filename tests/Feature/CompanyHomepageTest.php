<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_homepage_content_can_be_updated(): void
    {
        $company = Company::create([
            'name' => 'John B Ltd',
            'code' => 'JOHNB123',
            'industry' => 'software-development',
            'subscription_type' => 'MIDI',
            'subdomain' => 'johnb',
            'subdomain_url' => 'https://johnb.zaptask.co.uk',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/settings/company/homepage', [
                'about_text' => 'We build brilliant software for teams.',
                'homepage_tagline' => 'Software that works',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('taskit_companies', [
            'id' => $company->id,
            'about_text' => 'We build brilliant software for teams.',
            'homepage_tagline' => 'Software that works',
        ]);
    }
}
