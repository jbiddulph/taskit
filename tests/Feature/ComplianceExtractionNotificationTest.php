<?php

namespace Tests\Feature;

use App\Mail\ComplianceExtractionMail;
use App\Models\Company;
use App\Models\DocumentExtractionProposal;
use App\Models\Notification;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\User;
use App\Services\DocumentExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ComplianceExtractionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_extraction_notifies_every_user_on_the_company_code(): void
    {
        Mail::fake();

        [$company, $uploader, $teammate, $outsider] = $this->makeCompanies();
        $site = $this->makeSite($company, $uploader);
        $document = $this->makeDocument($company, $site, $uploader);

        $service = app(DocumentExtractionService::class);
        $proposal = $service->createProposalFromExtraction(
            $document,
            $uploader,
            [
                'document_type' => 'pat',
                'label' => 'PAT Testing',
                'expiry_date' => '12/03/2027',
                'summary' => 'PAT test expires 2027-03-12.',
            ],
        );

        $this->assertSame('pat_testing', $proposal->extracted_data['document_type']);
        $this->assertSame('2027-03-12', $proposal->extracted_data['expiry_date']);
        $this->assertSame('pat_testing', $document->fresh()->document_type);
        $this->assertSame('2027-03-12', $document->fresh()->expires_at?->toDateString());

        $this->assertSame(1, Notification::query()->where('user_id', $uploader->id)->where('type', 'document_extraction')->count());
        $this->assertSame(1, Notification::query()->where('user_id', $teammate->id)->where('type', 'document_extraction')->count());
        $this->assertSame(0, Notification::query()->where('user_id', $outsider->id)->count());

        Mail::assertSent(ComplianceExtractionMail::class, 2);
        Mail::assertSent(ComplianceExtractionMail::class, fn (ComplianceExtractionMail $mail) => $mail->hasTo($uploader->email));
        Mail::assertSent(ComplianceExtractionMail::class, fn (ComplianceExtractionMail $mail) => $mail->hasTo($teammate->email));
        Mail::assertNotSent(ComplianceExtractionMail::class, fn (ComplianceExtractionMail $mail) => $mail->hasTo($outsider->email));
    }

    public function test_company_teammate_can_approve_another_users_extraction(): void
    {
        Mail::fake();

        [$company, $uploader, $teammate] = $this->makeCompanies();
        $site = $this->makeSite($company, $uploader);
        $document = $this->makeDocument($company, $site, $uploader);

        $proposal = app(DocumentExtractionService::class)->createProposalFromExtraction(
            $document,
            $uploader,
            [
                'document_type' => 'boiler_service',
                'label' => 'Boiler Servicing',
                'expiry_date' => '2027-06-01',
            ],
        );

        $this->actingAs($teammate)
            ->postJson("/api/document-extraction/proposals/{$proposal->id}/approve")
            ->assertOk();

        $this->assertSame(DocumentExtractionProposal::STATUS_APPROVED, $proposal->fresh()->status);
    }

    public function test_other_company_cannot_approve_extraction(): void
    {
        Mail::fake();

        [$company, $uploader, , $outsider] = $this->makeCompanies();
        $site = $this->makeSite($company, $uploader);
        $document = $this->makeDocument($company, $site, $uploader);

        $proposal = app(DocumentExtractionService::class)->createProposalFromExtraction(
            $document,
            $uploader,
            [
                'document_type' => 'fire_safety',
                'expiry_date' => '2026-09-15',
            ],
        );

        $this->actingAs($outsider)
            ->postJson("/api/document-extraction/proposals/{$proposal->id}/approve")
            ->assertForbidden();
    }

    public function test_maxi_user_can_open_company_compliance_page(): void
    {
        Mail::fake();

        [$company, $uploader] = $this->makeCompanies();
        $this->makeSite($company, $uploader);

        $this->actingAs($uploader)
            ->get(route('compliance.index'))
            ->assertOk();
    }

    /**
     * @return array{0: Company, 1: User, 2: User, 3: User}
     */
    protected function makeCompanies(): array
    {
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

        $uploader = User::factory()->create(['company_id' => $company->id, 'name' => 'Uploader']);
        $teammate = User::factory()->create(['company_id' => $company->id, 'name' => 'Teammate']);
        $outsider = User::factory()->create(['company_id' => $other->id, 'name' => 'Outsider']);

        return [$company, $uploader, $teammate, $outsider];
    }

    protected function makeSite(Company $company, User $user): OperationalObject
    {
        return OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => '12 High Street',
            'created_by_user_id' => $user->id,
        ]);
    }

    protected function makeDocument(Company $company, OperationalObject $site, User $user): OperationalDocument
    {
        return OperationalDocument::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'uploaded_by_user_id' => $user->id,
            'title' => 'Upload.pdf',
            'filename' => 'upload.pdf',
            'original_filename' => 'upload.pdf',
            'mime_type' => 'application/pdf',
            'file_path' => 'operational-documents/upload.pdf',
            'file_size' => 123,
        ]);
    }
}
