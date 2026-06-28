<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_company_can_search_homepage_images(): void
    {
        config(['services.unsplash.access_key' => 'test-key']);

        Http::fake([
            'api.unsplash.com/search/photos*' => Http::response([
                'results' => [
                    [
                        'id' => 'photo-123',
                        'description' => 'Office workspace',
                        'urls' => [
                            'regular' => 'https://images.unsplash.com/photo-123?regular',
                            'small' => 'https://images.unsplash.com/photo-123?small',
                        ],
                        'user' => [
                            'name' => 'Jane Doe',
                            'links' => ['html' => 'https://unsplash.com/@jane'],
                        ],
                        'links' => ['html' => 'https://unsplash.com/photos/photo-123'],
                    ],
                ],
            ], 200),
        ]);

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
            ->getJson('/settings/company/homepage/search-images?query=office');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('query', 'office')
            ->assertJsonPath('results.0.id', 'photo-123');
    }

    public function test_company_can_select_custom_homepage_image(): void
    {
        config(['services.unsplash.access_key' => 'test-key']);

        Http::fake([
            'api.unsplash.com/photos/photo-456' => Http::response([
                'id' => 'photo-456',
                'description' => 'Construction site',
                'urls' => [
                    'regular' => 'https://images.unsplash.com/photo-456?regular',
                ],
                'user' => [
                    'name' => 'John Smith',
                    'links' => ['html' => 'https://unsplash.com/@john'],
                ],
                'links' => ['html' => 'https://unsplash.com/photos/photo-456'],
            ], 200),
            'api.unsplash.com/photos/photo-456/download' => Http::response([], 200),
        ]);

        $company = Company::create([
            'name' => 'John B Ltd',
            'code' => 'JOHNB123',
            'industry' => 'construction',
            'subscription_type' => 'MIDI',
            'subdomain' => 'johnb',
            'subdomain_url' => 'https://johnb.zaptask.co.uk',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/settings/company/homepage/select-image', [
                'photo_id' => 'photo-456',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('taskit_companies', [
            'id' => $company->id,
            'homepage_background_mode' => 'custom',
            'homepage_background_unsplash_id' => 'photo-456',
            'homepage_background_url' => 'https://images.unsplash.com/photo-456?regular',
        ]);
    }

    public function test_refresh_homepage_background_resets_to_industry_mode(): void
    {
        config(['services.unsplash.access_key' => 'test-key']);

        Http::fake([
            'api.unsplash.com/search/photos*' => Http::response([
                'results' => [
                    [
                        'id' => 'industry-photo',
                        'urls' => [
                            'regular' => 'https://images.unsplash.com/industry?regular',
                        ],
                        'user' => [
                            'name' => 'Photographer',
                            'links' => ['html' => 'https://unsplash.com/@photo'],
                        ],
                        'links' => ['html' => 'https://unsplash.com/photos/industry-photo'],
                    ],
                ],
            ], 200),
        ]);

        $company = Company::create([
            'name' => 'John B Ltd',
            'code' => 'JOHNB123',
            'industry' => 'software-development',
            'subscription_type' => 'MIDI',
            'subdomain' => 'johnb',
            'subdomain_url' => 'https://johnb.zaptask.co.uk',
            'homepage_background_mode' => 'custom',
            'homepage_background_unsplash_id' => 'photo-456',
            'homepage_background_url' => 'https://images.unsplash.com/custom?regular',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/settings/company/homepage/refresh-background');

        $response->assertRedirect();

        $this->assertDatabaseHas('taskit_companies', [
            'id' => $company->id,
            'homepage_background_mode' => 'industry',
            'homepage_background_unsplash_id' => null,
            'homepage_background_url' => 'https://images.unsplash.com/industry?regular',
        ]);
    }
}
