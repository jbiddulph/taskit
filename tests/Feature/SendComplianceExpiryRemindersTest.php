<?php

namespace Tests\Feature;

use App\Mail\ComplianceExpiryMail;
use App\Models\Company;
use App\Models\ComplianceRequirement;
use App\Models\Notification;
use App\Models\OperationalObject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendComplianceExpiryRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_company_users_30_days_before_expiry(): void
    {
        Mail::fake();

        $company = Company::create([
            'name' => 'Acme Lettings',
            'code' => 'ACME'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
        ]);
        $other = Company::create([
            'name' => 'Other Co',
            'code' => 'OTHR'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
        ]);

        $owner = User::factory()->create(['company_id' => $company->id]);
        $teammate = User::factory()->create(['company_id' => $company->id]);
        $outsider = User::factory()->create(['company_id' => $other->id]);

        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => '12 High Street',
            'created_by_user_id' => $owner->id,
        ]);

        ComplianceRequirement::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'requirement_type' => 'boiler_service',
            'label' => 'Boiler Servicing',
            'frequency' => 'annual',
            'lead_time_days' => 30,
            'next_due_date' => now()->addDays(30)->toDateString(),
            'status' => 'due_soon',
        ]);

        $this->artisan('compliance:send-expiry-reminders')->assertSuccessful();

        $this->assertSame(1, Notification::query()->where('user_id', $owner->id)->count());
        $this->assertSame(1, Notification::query()->where('user_id', $teammate->id)->count());
        $this->assertSame(0, Notification::query()->where('user_id', $outsider->id)->count());

        Mail::assertSent(ComplianceExpiryMail::class, 2);
        Mail::assertNotSent(ComplianceExpiryMail::class, fn (ComplianceExpiryMail $mail) => $mail->hasTo($outsider->email));

        $this->artisan('compliance:send-expiry-reminders')->assertSuccessful();

        $this->assertSame(1, Notification::query()->where('user_id', $owner->id)->count());
    }
}
