<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Models\User;
use App\Services\AiPortfolioQueryService;
use App\Services\PropertyAddressMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplianceInboxAndRagTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_matcher_matches_postcode_and_house_number(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $richmond = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => '22 Richmond Road',
            'address_line_1' => '22 Richmond Road',
            'city' => 'Brighton',
            'postal_code' => 'BN1 1AA',
            'created_by_user_id' => $user->id,
            'is_active' => true,
        ]);
        OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => '9 Quiet Lane',
            'address_line_1' => '9 Quiet Lane',
            'city' => 'Brighton',
            'postal_code' => 'BN2 2BB',
            'created_by_user_id' => $user->id,
            'is_active' => true,
        ]);

        $match = app(PropertyAddressMatcher::class)->match(
            $company->id,
            '22 Richmond Road, Brighton BN1 1AA',
        );

        $this->assertSame(PropertyAddressMatcher::STATUS_MATCHED, $match['status']);
        $this->assertSame($richmond->id, $match['site']->id);
        $this->assertGreaterThanOrEqual(70, $match['confidence']);
    }

    public function test_inbox_upload_auto_matches_property_from_extracted_address(): void
    {
        Storage::fake('private');

        [$user, $company] = $this->createMaxiUser();
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => '22 Richmond Road',
            'address_line_1' => '22 Richmond Road',
            'city' => 'Brighton',
            'postal_code' => 'BN1 1AA',
            'created_by_user_id' => $user->id,
            'is_active' => true,
        ]);
        Project::create([
            'name' => 'Compliance',
            'key' => 'CMP',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Minimal PDF-like text file accepted as pdf mime via fake
        $file = UploadedFile::fake()->create('gas-22-richmond.pdf', 40, 'application/pdf');

        // Seed extracted text path by creating document then proposal via service after upload
        $response = $this->actingAs($user)
            ->postJson('/api/compliance/documents/inbox', [
                'file' => $file,
                'extract' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $documentId = $response->json('data.results.0.document.id');
        $this->assertNotNull($documentId);

        $document = OperationalDocument::findOrFail($documentId);
        $this->assertNull($document->operational_object_id);

        // Simulate extraction with address and run matcher via createProposalFromExtraction
        $proposal = app(\App\Services\DocumentExtractionService::class)->createProposalFromExtraction(
            $document,
            $user,
            [
                'document_type' => 'gas_safety',
                'label' => 'Gas Safety (CP12)',
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'address' => '22 Richmond Road, Brighton, BN1 1AA',
                'summary' => 'Gas certificate for 22 Richmond Road.',
            ],
        );

        $document->refresh();
        $proposal->refresh();

        $this->assertSame($site->id, $document->operational_object_id);
        $this->assertSame(PropertyAddressMatcher::STATUS_MATCHED, $document->match_status);
        $this->assertSame($site->id, $proposal->operational_object_id);
        $this->assertSame($site->id, $proposal->suggested_operational_object_id);
    }

    public function test_portfolio_ai_rag_finds_documents_relating_to_topic(): void
    {
        [$user, $company] = $this->createMaxiUser();
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => 'Flat 4 Oak Court',
            'created_by_user_id' => $user->id,
            'is_active' => true,
        ]);

        OperationalDocument::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'uploaded_by_user_id' => $user->id,
            'title' => 'Roof inspection report',
            'document_type' => 'other',
            'filename' => 'roof.pdf',
            'original_filename' => 'roof.pdf',
            'mime_type' => 'application/pdf',
            'file_path' => 'documents/roof.pdf',
            'file_size' => 1000,
            'status' => OperationalDocument::STATUS_ACTIVE,
            'extracted_text' => 'Survey of the pitched roof and ridge tiles. Minor moss on the south elevation roof slope.',
            'extracted_data' => ['findings' => 'Moss on roof tiles'],
        ]);

        OperationalDocument::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'uploaded_by_user_id' => $user->id,
            'title' => 'Boiler service',
            'document_type' => 'boiler_service',
            'filename' => 'boiler.pdf',
            'original_filename' => 'boiler.pdf',
            'mime_type' => 'application/pdf',
            'file_path' => 'documents/boiler.pdf',
            'file_size' => 800,
            'status' => OperationalDocument::STATUS_ACTIVE,
            'extracted_text' => 'Boiler serviced, no issues.',
        ]);

        $result = app(AiPortfolioQueryService::class)->ask(
            $user,
            'Show me everything relating to the roof',
        );

        $this->assertSame('portfolio_answer', $result['intent']);
        $this->assertStringContainsString('roof', strtolower($result['answer']));
        $this->assertStringContainsString('Roof inspection', $result['answer']);
        $this->assertStringNotContainsString('Boiler service', $result['answer']);
    }

    public function test_approve_requires_site_when_unmatched(): void
    {
        Storage::fake('private');
        [$user, $company] = $this->createMaxiUser();
        $site = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => 'Harbour View',
            'created_by_user_id' => $user->id,
            'is_active' => true,
        ]);
        $project = Project::create([
            'name' => 'Compliance',
            'key' => 'CMP',
            'color' => '#3B82F6',
            'owner_id' => $user->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $document = OperationalDocument::create([
            'company_id' => $company->id,
            'operational_object_id' => null,
            'uploaded_by_user_id' => $user->id,
            'title' => 'Unknown cert',
            'filename' => 'x.pdf',
            'original_filename' => 'x.pdf',
            'mime_type' => 'application/pdf',
            'file_path' => 'documents/x.pdf',
            'file_size' => 100,
            'status' => OperationalDocument::STATUS_ACTIVE,
            'match_status' => PropertyAddressMatcher::STATUS_UNMATCHED,
        ]);

        $proposal = DocumentExtractionProposal::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'operational_document_id' => $document->id,
            'operational_object_id' => null,
            'status' => DocumentExtractionProposal::STATUS_PENDING,
            'extracted_data' => [
                'document_type' => 'epc',
                'label' => 'EPC',
                'expiry_date' => now()->addYear()->toDateString(),
            ],
            'summary' => 'EPC without address',
        ]);

        $this->actingAs($user)
            ->postJson("/api/document-extraction/proposals/{$proposal->id}/approve", [
                'project_id' => $project->id,
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson("/api/document-extraction/proposals/{$proposal->id}/approve", [
                'project_id' => $project->id,
                'site_id' => $site->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame($site->id, $document->fresh()->operational_object_id);
    }

    public function test_document_ai_allowance_maps_to_plan_tiers(): void
    {
        $company = Company::create([
            'name' => 'Test',
            'code' => 'T'.random_int(10000, 99999),
            'subscription_type' => 'MIDI',
            'industry' => 'property-management',
        ]);

        $this->assertSame(500, $company->getDocumentAiAllowance());
        $company->subscription_type = 'MAXI';
        $this->assertSame(1000, $company->getDocumentAiAllowance());
        $company->subscription_type = 'BUSINESS';
        $this->assertSame(2000, $company->getDocumentAiAllowance());
        $company->subscription_type = 'LTD_TEAM';
        $this->assertSame(1000, $company->getDocumentAiAllowance());
        $company->subscription_type = 'LTD_BUSINESS';
        $this->assertSame(2000, $company->getDocumentAiAllowance());
    }

    public function test_document_ai_allowance_blocks_extraction_when_exhausted(): void
    {
        Storage::fake('private');

        [$user, $company] = $this->createMaxiUser();

        // Exhaust MAXI's 1000 monthly reads without running extractions.
        $rows = [];
        $now = now();
        for ($i = 0; $i < 1000; $i++) {
            $rows[] = [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'kind' => \App\Models\DocumentAiUsage::KIND_EXTRACTION,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        \App\Models\DocumentAiUsage::insert($rows);

        $this->assertFalse($company->fresh()->canConsumeDocumentAi());

        $file = UploadedFile::fake()->create('gas.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->postJson('/api/compliance/documents/inbox', [
                'file' => $file,
                'extract' => true,
            ])
            ->assertStatus(429)
            ->assertJsonPath('error', 'document_ai_allowance_exceeded')
            ->assertJsonPath('document_ai.exceeded', true);

        $this->assertDatabaseCount('taskit_operational_documents', 1);
    }

    public function test_portfolio_ask_consumes_document_ai_allowance(): void
    {
        [$user, $company] = $this->createMaxiUser();

        $before = $company->getDocumentAiUsageThisMonth();

        $this->actingAs($user)
            ->postJson('/api/ai', [
                'message' => 'What needs attention this week?',
                'context' => 'portfolio',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame($before + 1, $company->fresh()->getDocumentAiUsageThisMonth());
    }

    /**
     * @return array{0: User, 1: Company}
     */
    protected function createMaxiUser(): array
    {
        $company = Company::create([
            'name' => 'Harbour Lets',
            'code' => 'H'.random_int(10000, 99999),
            'subscription_type' => 'MAXI',
            'industry' => 'property-management',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        return [$user, $company];
    }
}
