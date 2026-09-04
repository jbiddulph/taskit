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
        $this->assertSame('pat_testing', CertificateTypes::normalize('pat_report'));
        $this->assertSame('boiler_service', CertificateTypes::normalize('boiler'));
        $this->assertSame('eicr', CertificateTypes::normalize('electrical'));
        $this->assertSame('eicr', CertificateTypes::normalize('electrical_installation_condition_report'));
        $this->assertSame('fire_safety', CertificateTypes::normalize('fra'));
        $this->assertSame('fire_alarm', CertificateTypes::normalize('fire_alarm_inspection'));
        $this->assertSame('gas_safety', CertificateTypes::normalize('landlord_gas_safety'));
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
        $this->assertSame('property_safety', $merged['category']);
        $this->assertSame('Property & fire safety', $merged['category_label']);
    }

    public function test_it_extracts_issuer_and_address(): void
    {
        $extracted = CertificateFieldExtractor::fromText(
            'Gas safety certificate. Issued by Gas Safe Ltd. Engineer: Jane Smith. Address: 14 Oak Lane, Manchester. Expiry date 01/01/2027.'
        );

        $this->assertSame('gas_safety', $extracted['document_type']);
        $this->assertSame('Gas Safe Ltd', $extracted['issuer']);
        $this->assertSame('Jane Smith', $extracted['engineer_name']);
        $this->assertSame('14 Oak Lane, Manchester', $extracted['address']);
    }

    public function test_it_detects_right_to_rent_and_fire_door(): void
    {
        $rent = CertificateFieldExtractor::fromText('Right to Rent check. Valid until 12/12/2027.');
        $this->assertSame('right_to_rent', $rent['document_type']);
        $this->assertSame('people', $rent['category']);

        $door = CertificateFieldExtractor::fromText('Fire door inspection. Next inspection due 03 March 2027.');
        $this->assertSame('fire_door', $door['document_type']);
        $this->assertSame('property_safety', $door['category']);
    }

    public function test_it_extracts_real_uk_fire_alarm_certificate_text(): void
    {
        $extracted = CertificateFieldExtractor::fromText($this->fixture('fire_alarm.txt'));

        $this->assertSame('fire_alarm', $extracted['document_type']);
        $this->assertSame('property_safety', $extracted['category']);
        $this->assertSame('Property & fire safety', $extracted['category_label']);
        $this->assertSame('FA-2026-00321', $extracted['certificate_number']);
        $this->assertSame('2027-03-04', $extracted['expiry_date']);
        $this->assertSame('2027-03-04', $extracted['renewal_date']);
        $this->assertSame('2026-09-04', $extracted['issue_date']);
        $this->assertSame('Chris Green', $extracted['engineer_name']);
        $this->assertSame('Example Fire Systems Ltd', $extracted['issuer']);
        $this->assertSame('Example Office, 10 High Street, Worthing, BN11 2AB', $extracted['address']);
        $this->assertSame('pass', $extracted['result']);
        $this->assertStringContainsString('None affecting system operation', (string) $extracted['findings']);
    }

    public function test_it_extracts_real_uk_pat_report_text(): void
    {
        $extracted = CertificateFieldExtractor::fromText($this->fixture('pat_testing.txt'));

        $this->assertSame('pat_testing', $extracted['document_type']);
        $this->assertSame('electrical', $extracted['category']);
        $this->assertSame('PAT-2026-00451', $extracted['certificate_number']);
        $this->assertSame('2027-09-04', $extracted['expiry_date']);
        $this->assertSame('2026-09-04', $extracted['issue_date']);
        $this->assertSame('Jamie Brown', $extracted['engineer_name']);
        $this->assertSame('Example Testing Services', $extracted['issuer']);
        $this->assertSame('Example Office, 10 High Street, Worthing, BN11 2AB', $extracted['address']);
        $this->assertSame('fail', $extracted['result']);
        $this->assertStringContainsString('Damaged mains flex', (string) $extracted['findings']);
    }

    public function test_it_extracts_real_uk_eicr_text(): void
    {
        $extracted = CertificateFieldExtractor::fromText($this->fixture('eicr.txt'));

        $this->assertSame('eicr', $extracted['document_type']);
        $this->assertSame('electrical', $extracted['category']);
        $this->assertSame('EICR-2026-00182', $extracted['certificate_number']);
        $this->assertSame('2031-09-04', $extracted['expiry_date']);
        $this->assertSame('2026-09-04', $extracted['issue_date']);
        $this->assertSame('Alex Taylor', $extracted['engineer_name']);
        $this->assertSame('Example Electrical Ltd', $extracted['issuer']);
        $this->assertSame('24 Example Road, Worthing, West Sussex, BN11 1AA', $extracted['address']);
        $this->assertSame('pass', $extracted['result']);
    }

    public function test_it_extracts_real_uk_landlord_gas_safety_text(): void
    {
        $extracted = CertificateFieldExtractor::fromText($this->fixture('gas_safety.txt'));

        $this->assertSame('gas_safety', $extracted['document_type']);
        $this->assertSame('property_safety', $extracted['category']);
        $this->assertSame('GS-2026-001245', $extracted['certificate_number']);
        $this->assertSame('2027-09-04', $extracted['expiry_date']);
        $this->assertSame('2026-09-04', $extracted['issue_date']);
        $this->assertSame('James Smith', $extracted['engineer_name']);
        $this->assertSame('SafeGas Services Ltd', $extracted['issuer']);
        $this->assertSame('24 Example Road, Worthing, West Sussex, BN11 1AA', $extracted['address']);
        $this->assertSame('pass', $extracted['result']);
    }

    public function test_two_site_addresses_are_not_mixed_across_certificates(): void
    {
        $office = CertificateFieldExtractor::fromText($this->fixture('fire_alarm.txt'));
        $pat = CertificateFieldExtractor::fromText($this->fixture('pat_testing.txt'));
        $eicr = CertificateFieldExtractor::fromText($this->fixture('eicr.txt'));
        $gas = CertificateFieldExtractor::fromText($this->fixture('gas_safety.txt'));

        $this->assertSame($office['address'], $pat['address']);
        $this->assertSame($eicr['address'], $gas['address']);
        $this->assertNotSame($office['address'], $eicr['address']);
        $this->assertStringContainsString('10 High Street', (string) $office['address']);
        $this->assertStringContainsString('BN11 2AB', (string) $pat['address']);
        $this->assertStringContainsString('24 Example Road', (string) $eicr['address']);
        $this->assertStringContainsString('BN11 1AA', (string) $gas['address']);
        $this->assertStringNotContainsString('10 High Street', (string) $eicr['address']);
        $this->assertStringNotContainsString('24 Example Road', (string) $office['address']);
    }

    protected function fixture(string $filename): string
    {
        $path = dirname(__DIR__).'/fixtures/certificates/'.$filename;
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
