<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Company;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ZapPropertyPortalApiTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'zp_live_test_portal_key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.zapproperty.api_key' => self::KEY]);
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function createAgency(string $name): array
    {
        $company = Company::create([
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/\W/', '', $name), 0, 4)).random_int(1000, 9999),
            'subscription_type' => 'MAXI',
            'industry' => 'estate-agents',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        return [$company, $user];
    }

    private function createSite(Company $company, User $user, string $name, bool $published, array $extra = []): OperationalObject
    {
        return OperationalObject::create(array_merge([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
            'type' => 'property',
            'name' => $name,
            'status' => 'active',
            'is_active' => true,
            'show_on_zapproperty' => $published,
            'listing_type' => 'rent',
            'price_amount' => 1750,
            'price_qualifier' => 'pcm',
        ], $extra));
    }

    private function portal()
    {
        return $this->withHeader('Authorization', 'Bearer '.self::KEY);
    }

    public function test_portal_lists_published_sites_from_every_company(): void
    {
        [$agencyA, $userA] = $this->createAgency('Worthing Lets');
        [$agencyB, $userB] = $this->createAgency('Brighton Homes');

        $this->createSite($agencyA, $userA, 'Belsize Road', true);
        $this->createSite($agencyA, $userA, 'Hidden Flat', false);
        $this->createSite($agencyB, $userB, 'Rectory Road', true, ['type' => 'unit']);
        $this->createSite($agencyB, $userB, 'Deactivated', true, ['is_active' => false]);

        $response = $this->portal()->getJson('/api/v1/zapproperty/listings')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.meta.unpublished_total', 1);

        $names = collect($response->json('data.listings'))->pluck('name')->sort()->values()->all();
        $this->assertSame(['Belsize Road', 'Rectory Road'], $names);

        $rectory = collect($response->json('data.listings'))->firstWhere('name', 'Rectory Road');
        $this->assertSame('Brighton Homes', $rectory['agent']['name']);
        $this->assertSame($agencyB->id, $rectory['agent']['id']);
        $this->assertTrue($rectory['property']['listing']['show_on_zapproperty']);
        $this->assertSame('£1,750 pcm', $rectory['property']['listing']['price_label']);
    }

    public function test_portal_detail_returns_only_published_sites(): void
    {
        [$agency, $user] = $this->createAgency('Worthing Lets');
        $published = $this->createSite($agency, $user, 'Belsize Road', true);
        $hidden = $this->createSite($agency, $user, 'Hidden Flat', false);

        $this->portal()->getJson("/api/v1/zapproperty/listings/{$published->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Belsize Road')
            ->assertJsonPath('data.agent.name', 'Worthing Lets')
            ->assertJsonPath('data.photos', []);

        $this->portal()->getJson("/api/v1/zapproperty/listings/{$hidden->id}")
            ->assertNotFound();
    }

    public function test_portal_photo_urls_point_at_the_portal_and_stream(): void
    {
        Storage::fake('supabase');
        [$agency, $user] = $this->createAgency('Worthing Lets');
        $site = $this->createSite($agency, $user, 'Belsize Road', true);

        $this->actingAs($user)->post("/sites/{$site->id}/photos", [
            'photos' => [UploadedFile::fake()->image('front.jpg', 320, 240)],
        ]);
        $photo = $site->photos()->firstOrFail();

        $expected = url("/api/v1/zapproperty/listings/{$site->id}/photos/{$photo->id}");

        $this->portal()->getJson("/api/v1/zapproperty/listings/{$site->id}")
            ->assertOk()
            ->assertJsonPath('data.cover_photo_url', $expected)
            ->assertJsonPath('data.photos.0.url', $expected);

        $this->portal()->get("/api/v1/zapproperty/listings/{$site->id}/photos/{$photo->id}")
            ->assertOk()
            ->assertHeader('Content-Type', $photo->mime_type);
    }

    public function test_portal_rejects_missing_wrong_and_company_keys(): void
    {
        [$agency, $user] = $this->createAgency('Worthing Lets');
        $this->createSite($agency, $user, 'Belsize Road', true);

        $this->getJson('/api/v1/zapproperty/listings')->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer zp_live_wrong')
            ->getJson('/api/v1/zapproperty/listings')
            ->assertUnauthorized();

        $companyKey = ApiKey::generate($agency, 'Integration', $user)['plain_text_key'];
        $this->withHeader('Authorization', 'Bearer '.$companyKey)
            ->getJson('/api/v1/zapproperty/listings')
            ->assertUnauthorized();

        // …and the portal key is not a company key.
        $this->portal()->getJson('/api/v1/assets')->assertUnauthorized();
    }

    public function test_portal_is_disabled_until_a_key_is_configured(): void
    {
        config(['services.zapproperty.api_key' => null]);

        $this->portal()->getJson('/api/v1/zapproperty/listings')
            ->assertStatus(503);
    }

    public function test_portal_tasks_are_created_in_the_listing_owners_company(): void
    {
        [$agency, $user] = $this->createAgency('Brighton Homes');
        $project = Project::create([
            'name' => 'Lettings',
            'key' => 'LET',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $agency->id,
        ]);
        ProjectGroup::createDefaultForProject($project);
        $site = $this->createSite($agency, $user, 'Rectory Road', true);

        $this->portal()->postJson("/api/v1/zapproperty/listings/{$site->id}/tasks", [
            'title' => 'Viewing request: Saturday 10am',
            'priority' => 'normal',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Viewing request: Saturday 10am')
            ->assertJsonPath('data.source', 'zapproperty')
            ->assertJsonPath('data.asset_id', $site->id);

        $this->assertDatabaseHas('taskit_todos', [
            'company_id' => $agency->id,
            'operational_object_id' => $site->id,
            'title' => 'Viewing request: Saturday 10am',
        ]);

        $this->portal()->getJson("/api/v1/zapproperty/listings/{$site->id}/tasks")
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.tasks.0.title', 'Viewing request: Saturday 10am');
    }
}
