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

    public function test_it_categorizes_dbs_and_gdpr_documents(): void
    {
        $dbs = CertificateFieldExtractor::fromText('Enhanced DBS check. Disclosure and Barring Service. Expiry date 04/08/2027.');
        $this->assertSame('dbs', $dbs['document_type']);
        $this->assertSame('people', $dbs['category']);
        $this->assertSame('2027-08-04', $dbs['expiry_date']);

        $gdpr = CertificateFieldExtractor::fromText('GDPR / data protection review. Next review due 01 January 2028.');
        $this->assertSame('gdpr', $gdpr['document_type']);
        $this->assertSame('data_quality', $gdpr['category']);
        $this->assertSame('2028-01-01', $gdpr['expiry_date']);
    }

    public function test_renewal_date_is_used_when_expiry_is_missing(): void
    {
        $extracted = CertificateFieldExtractor::fromText(
            'Professional indemnity insurance. Policy no PI-900. Renewal date 22/04/2027.'
        );

        $this->assertSame('pi_insurance', $extracted['document_type']);
        $this->assertSame('2027-04-22', $extracted['expiry_date']);
        $this->assertSame('2027-04-22', $extracted['renewal_date']);
        $this->assertSame('insurance_legal', $extracted['category']);
    }

    public function test_normalize_aliases(): void
    {
        $this->assertSame('pat_testing', CertificateTypes::normalize('pat'));
        $this->assertSame('boiler_service', CertificateTypes::normalize('boiler'));
        $this->assertSame('eicr', CertificateTypes::normalize('electrical'));
        $this->assertSame('fire_safety', CertificateTypes::normalize('fra'));
        $this->assertSame('contract', CertificateTypes::normalize('tenancy'));
        $this->assertSame('dbs', CertificateTypes::normalize('dbs_check'));
        $this->assertSame('gdpr', CertificateTypes::normalize('data_protection'));
        $this->assertSame('pi_insurance', CertificateTypes::normalize('professional_indemnity'));
        $this->assertSame('People & safeguarding', CertificateTypes::categoryLabel('dbs'));
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
