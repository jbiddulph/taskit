<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OperationalObject;
use App\Models\OperationalObjectPhoto;
use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyPhotosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Company, 2: OperationalObject}
     */
    protected function createSiteSetup(): array
    {
        $company = Company::create([
            'name' => 'Estate Agency',
            'code' => 'EST'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
            'industry' => 'estate-agents',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $site = OperationalObject::create([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
            'type' => 'property',
            'name' => '14 Victoria Road',
            'property_type' => 'house',
            'bedrooms' => 3,
            'occupancy_status' => 'vacant',
            'city' => 'Manchester',
            'postal_code' => 'M1 1AE',
            'is_active' => true,
            'status' => 'active',
        ]);

        return [$user, $company, $site];
    }

    public function test_can_upload_multiple_property_photos_on_site(): void
    {
        Storage::fake('private');
        [$user, $company, $site] = $this->createSiteSetup();

        $this->actingAs($user)
            ->post("/sites/{$site->id}/photos", [
                'photos' => [
                    UploadedFile::fake()->image('front.jpg', 800, 600),
                    UploadedFile::fake()->image('kitchen.png', 640, 480),
                ],
                'caption' => 'Front elevation',
            ])
            ->assertRedirect();

        $this->assertSame(2, $site->photos()->count());
        $cover = $site->photos()->where('is_cover', true)->first();
        $this->assertNotNull($cover);
        $this->assertSame('Front elevation', $cover->caption);
        Storage::disk('private')->assertExists($cover->file_path);
    }

    public function test_first_photo_is_cover_and_can_be_changed(): void
    {
        Storage::fake('private');
        [$user, , $site] = $this->createSiteSetup();

        $this->actingAs($user)->post("/sites/{$site->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ]);
        $this->actingAs($user)->post("/sites/{$site->id}/photos", [
            'photos' => [UploadedFile::fake()->image('b.jpg')],
        ]);

        $second = $site->photos()->where('is_cover', false)->first();
        $this->assertNotNull($second);

        $this->actingAs($user)
            ->patch("/sites/{$site->id}/photos/{$second->id}", ['is_cover' => true])
            ->assertRedirect();

        $this->assertTrue($second->fresh()->is_cover);
        $this->assertSame(1, $site->photos()->where('is_cover', true)->count());
    }

    public function test_site_show_includes_photos_and_cover_url(): void
    {
        Storage::fake('private');
        [$user, , $site] = $this->createSiteSetup();

        $this->actingAs($user)->post("/sites/{$site->id}/photos", [
            'photos' => [UploadedFile::fake()->image('listing.jpg')],
        ]);

        $photo = $site->photos()->first();

        $this->actingAs($user)
            ->get("/sites/{$site->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Sites/Show')
                ->has('site.photos', 1)
                ->where('site.cover_photo_url', "/sites/{$site->id}/photos/{$photo->id}")
                ->where('site.photos.0.url', "/sites/{$site->id}/photos/{$photo->id}")
            );
    }

    public function test_can_view_edit_caption_and_delete_photo(): void
    {
        Storage::fake('private');
        [$user, , $site] = $this->createSiteSetup();

        $this->actingAs($user)->post("/sites/{$site->id}/photos", [
            'photos' => [UploadedFile::fake()->image('room.jpg')],
            'caption' => 'Living room',
        ]);

        $photo = $site->photos()->first();
        $this->assertNotNull($photo);

        $this->actingAs($user)
            ->get("/sites/{$site->id}/photos/{$photo->id}")
            ->assertOk()
            ->assertHeader('Content-Type', $photo->mime_type ?: 'image/jpeg');

        $disposition = $this->actingAs($user)
            ->get("/sites/{$site->id}/photos/{$photo->id}")
            ->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('inline', strtolower($disposition));

        $this->actingAs($user)
            ->patch("/sites/{$site->id}/photos/{$photo->id}", [
                'caption' => 'Updated living room',
            ])
            ->assertRedirect();

        $this->assertSame('Updated living room', $photo->fresh()->caption);

        $this->actingAs($user)
            ->delete("/sites/{$site->id}/photos/{$photo->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('taskit_operational_object_photos', ['id' => $photo->id]);
    }

    public function test_platform_api_can_upload_and_list_asset_photos(): void
    {
        Storage::fake('private');
        [$user, $company, $site] = $this->createSiteSetup();
        $key = ApiKey::generate($company, 'Estate app', $user)['plain_text_key'];

        $this->withToken($key)
            ->post("/api/v1/assets/{$site->id}/photos", [
                'photo' => UploadedFile::fake()->image('room.webp'),
                'caption' => 'Living room',
                'as_cover' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.photos.0.caption', 'Living room')
            ->assertJsonPath('data.photos.0.is_cover', true);

        $this->withToken($key)
            ->getJson("/api/v1/assets/{$site->id}")
            ->assertOk()
            ->assertJsonPath('data.photo_count', 1)
            ->assertJsonPath('data.photos.0.caption', 'Living room');
    }

    public function test_deleting_cover_promotes_next_photo(): void
    {
        Storage::fake('private');
        [$user, , $site] = $this->createSiteSetup();

        $this->actingAs($user)->post("/sites/{$site->id}/photos", [
            'photos' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ],
        ]);

        $cover = $site->photos()->where('is_cover', true)->first();
        $other = $site->photos()->where('is_cover', false)->first();

        $this->actingAs($user)
            ->delete("/sites/{$site->id}/photos/{$cover->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('taskit_operational_object_photos', ['id' => $cover->id]);
        $this->assertTrue($other->fresh()->is_cover);
    }
}
