<?php

namespace Tests\Unit;

use App\Support\CertificateFieldExtractor;
use App\Support\CertificateTypes;
use PHPUnit\Framework\TestCase;

class CertificateFieldExtractorTest extends TestCase
{
    public function test_it_extracts_pat_test_expiry_from_labelled_text(): void
    {
        $extracted = CertificateFieldExtractor::fromText(
            'Portable Appliance Testing certificate. Certificate no PAT-1234. Expiry date: 12/03/2027. Engineer: Jane Smith.'
        );

        $this->assertSame('pat_testing', $extracted['document_type']);
        $this->assertSame('2027-03-12', $extracted['expiry_date']);
        $this->assertSame('PAT-1234', $extracted['certificate_number']);
        $this->assertSame('Jane Smith', $extracted['engineer_name']);
        $this->assertTrue(CertificateFieldExtractor::hasUsefulFields($extracted));
    }

    public function test_it_extracts_boiler_service_and_fire_safety(): void
    {
        $boiler = CertificateFieldExtractor::fromText('Annual boiler service. Next service due 01 June 2027.');
        $this->assertSame('boiler_service', $boiler['document_type']);
        $this->assertSame('2027-06-01', $boiler['expiry_date']);

        $fire = CertificateFieldExtractor::fromText('Fire risk assessment. Valid until 15/09/2026.');
        $this->assertSame('fire_safety', $fire['document_type']);
        $this->assertSame('2026-09-15', $fire['expiry_date']);
    }

    public function test_it_extracts_contract_end_date(): void
    {
        $extracted = CertificateFieldExtractor::fromText(
            'Maintenance contract. Contract expiry 30/11/2026. Contract no MC-88.'
        );

        $this->assertSame('contract', $extracted['document_type']);
        $this->assertSame('2026-11-30', $extracted['expiry_date']);
    }

    public function test_normalize_aliases(): void
    {
        $this->assertSame('pat_testing', CertificateTypes::normalize('pat'));
        $this->assertSame('boiler_service', CertificateTypes::normalize('boiler'));
        $this->assertSame('eicr', CertificateTypes::normalize('electrical'));
        $this->assertSame('fire_safety', CertificateTypes::normalize('fra'));
        $this->assertSame('contract', CertificateTypes::normalize('tenancy'));
    }

    public function test_openai_result_is_merged_over_rules(): void
    {
        $rules = CertificateFieldExtractor::fromText('Gas safety certificate. Expiry date 01/01/2027.');
        $merged = CertificateFieldExtractor::merge([
            'document_type' => 'gas_safety',
            'label' => 'Gas Safety (CP12)',
            'expiry_date' => '2027-02-01',
            'source' => 'openai',
            'confidence' => 0.9,
        ], $rules);

        $this->assertSame('2027-02-01', $merged['expiry_date']);
        $this->assertSame('openai', $merged['source']);
        $this->assertSame('gas_safety', $merged['document_type']);
    }
}
