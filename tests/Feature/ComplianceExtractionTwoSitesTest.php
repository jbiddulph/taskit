<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\User;
use App\Services\DocumentExtractionProposalService;
use App\Services\DocumentExtractionService;
use App\Services\MapboxService;
use App\Support\CertificateFieldExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ComplianceExtractionTwoSitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_extracted_certificates_stay_on_two_distinct_site_addresses(): void
    {
        Mail::fake();

        $this->mock(MapboxService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });

        $company = Company::create([
            'name' => 'Worthing Lettings',
            'code' => 'WRTH'.random_int(1000, 9999),
            'subscription_type' => 'MAXI',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $office = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => 'Example Office',
            'created_by_user_id' => $user->id,
        ]);
        $dwelling = OperationalObject::create([
            'company_id' => $company->id,
            'type' => 'property',
            'name' => '24 Example Road',
            'created_by_user_id' => $user->id,
        ]);

        $extraction = app(DocumentExtractionService::class);
        $approver = app(DocumentExtractionProposalService::class);

        $officeDocs = [
            'fire_alarm' => $this->attachExtractedDocument($extraction, $user, $company, $office, 'fire-alarm.pdf', 'fire_alarm.txt'),
            'pat_testing' => $this->attachExtractedDocument($extraction, $user, $company, $office, 'pat.pdf', 'pat_testing.txt'),
        ];
        $dwellingDocs = [
            'eicr' => $this->attachExtractedDocument($extraction, $user, $company, $dwelling, 'eicr.pdf', 'eicr.txt'),
            'gas_safety' => $this->attachExtractedDocument($extraction, $user, $company, $dwelling, 'gas.pdf', 'gas_safety.txt'),
        ];

        $this->assertSame('fire_alarm', $officeDocs['fire_alarm']->fresh()->document_type);
        $this->assertSame('pat_testing', $officeDocs['pat_testing']->fresh()->document_type);
        $this->assertSame('eicr', $dwellingDocs['eicr']->fresh()->document_type);
        $this->assertSame('gas_safety', $dwellingDocs['gas_safety']->fresh()->document_type);

        $this->assertSame('2027-03-04', $officeDocs['fire_alarm']->fresh()->expires_at?->toDateString());
        $this->assertSame('2027-09-04', $officeDocs['pat_testing']->fresh()->expires_at?->toDateString());
        $this->assertSame('2031-09-04', $dwellingDocs['eicr']->fresh()->expires_at?->toDateString());
        $this->assertSame('2027-09-04', $dwellingDocs['gas_safety']->fresh()->expires_at?->toDateString());

        DocumentExtractionProposal::query()->orderBy('id')->get()->each(function (DocumentExtractionProposal $proposal) use ($approver, $user) {
            $approver->approve($user, $proposal);
        });

        $office->refresh();
        $dwelling->refresh();

        $this->assertStringContainsString('10 High Street', (string) $office->address_line_1);
        $this->assertStringContainsString('BN11 2AB', (string) $office->address_line_1);
        $this->assertStringContainsString('24 Example Road', (string) $dwelling->address_line_1);
        $this->assertStringContainsString('BN11 1AA', (string) $dwelling->address_line_1);
        $this->assertStringNotContainsString('24 Example Road', (string) $office->address_line_1);
        $this->assertStringNotContainsString('10 High Street', (string) $dwelling->address_line_1);

        $this->assertSame($office->id, $officeDocs['fire_alarm']->fresh()->operational_object_id);
        $this->assertSame($office->id, $officeDocs['pat_testing']->fresh()->operational_object_id);
        $this->assertSame($dwelling->id, $dwellingDocs['eicr']->fresh()->operational_object_id);
        $this->assertSame($dwelling->id, $dwellingDocs['gas_safety']->fresh()->operational_object_id);

        $this->assertTrue(
            $office->complianceRequirements()->where('requirement_type', 'fire_alarm')->exists()
        );
        $this->assertTrue(
            $office->complianceRequirements()->where('requirement_type', 'pat_testing')->exists()
        );
        $this->assertTrue(
            $dwelling->complianceRequirements()->where('requirement_type', 'eicr')->exists()
        );
        $this->assertTrue(
            $dwelling->complianceRequirements()->where('requirement_type', 'gas_safety')->exists()
        );
        $this->assertFalse(
            $office->complianceRequirements()->whereIn('requirement_type', ['eicr', 'gas_safety'])->exists()
        );
        $this->assertFalse(
            $dwelling->complianceRequirements()->whereIn('requirement_type', ['fire_alarm', 'pat_testing'])->exists()
        );
    }

    public function test_pdftotext_extracts_layout_pdf_text_when_available(): void
    {
        if (trim((string) shell_exec('command -v pdftotext')) === '') {
            $this->markTestSkipped('pdftotext is not installed');
        }

        $path = storage_path('app/private/compliance-extraction-sample.pdf');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->minimalPdf('FIRE ALARM INSPECTION Certificate No. FA-TEST-9 Next Service Due 04/03/2027 Premises 10 High Street, Worthing, BN11 2AB'));

        $service = app(DocumentExtractionService::class);
        $method = (new \ReflectionClass($service))->getMethod('extractPdfText');
        $method->setAccessible(true);
        $text = $method->invoke($service, $path);

        $this->assertStringContainsString('FIRE ALARM INSPECTION', $text);
        $this->assertStringContainsString('FA-TEST-9', $text);
        $this->assertStringContainsString('BN11 2AB', $text);

        $extracted = CertificateFieldExtractor::fromText($text);
        $this->assertSame('fire_alarm', $extracted['document_type']);
        $this->assertSame('2027-03-04', $extracted['expiry_date']);
        $this->assertSame('FA-TEST-9', $extracted['certificate_number']);
        $this->assertStringContainsString('10 High Street', (string) $extracted['address']);
    }

    protected function attachExtractedDocument(
        DocumentExtractionService $extraction,
        User $user,
        Company $company,
        OperationalObject $site,
        string $filename,
        string $fixture,
    ): OperationalDocument {
        $document = OperationalDocument::create([
            'company_id' => $company->id,
            'operational_object_id' => $site->id,
            'uploaded_by_user_id' => $user->id,
            'title' => $filename,
            'filename' => $filename,
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'file_path' => 'operational-documents/'.$filename,
            'file_size' => 123,
        ]);

        $extracted = CertificateFieldExtractor::fromText(
            (string) file_get_contents(base_path('tests/fixtures/certificates/'.$fixture))
        );

        $extraction->createProposalFromExtraction($document, $user, $extracted);

        return $document;
    }

    protected function minimalPdf(string $text): string
    {
        $safe = str_replace(['(', ')'], ['\\(', '\\)'], $text);
        $stream = "BT /F1 12 Tf 50 700 Td ({$safe}) Tj ET";
        $length = strlen($stream);

        return <<<PDF
%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length {$length} >>
stream
{$stream}
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
trailer
<< /Root 1 0 R /Size 6 >>
%%EOF
PDF;
    }
}
