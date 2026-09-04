<?php

namespace Tests\Unit;

use App\Support\CertificateTypes;
use App\Support\ComplianceTemplates;
use App\Support\Industries;
use App\Support\InspectionTemplates;
use Tests\TestCase;

class ComplianceTemplatesTest extends TestCase
{
    public function test_every_industry_has_a_compliance_template(): void
    {
        foreach (array_keys(Industries::list()) as $slug) {
            $templates = ComplianceTemplates::forIndustry($slug);

            $this->assertNotEmpty(
                $templates,
                "Expected compliance templates for industry [{$slug}].",
            );
            $this->assertTrue(ComplianceTemplates::hasTemplates($slug));

            $types = array_column($templates, 'type');
            $this->assertSame(
                count($types),
                count(array_unique($types)),
                "Duplicate requirement types in [{$slug}] compliance template.",
            );
        }
    }

    public function test_unknown_industry_falls_back_to_general_compliance_template(): void
    {
        $this->assertNotEmpty(ComplianceTemplates::forIndustry('not-a-real-industry'));
        $this->assertSame(
            ComplianceTemplates::forIndustry('general'),
            ComplianceTemplates::forIndustry('not-a-real-industry'),
        );
    }

    public function test_every_industry_has_inspection_checklists(): void
    {
        foreach (array_keys(Industries::list()) as $slug) {
            $templates = InspectionTemplates::forIndustry($slug);

            $this->assertNotEmpty(
                $templates,
                "Expected inspection templates for industry [{$slug}].",
            );
            $this->assertArrayHasKey('general_inspection', $templates);
        }
    }

    public function test_every_compliance_template_type_is_a_known_certificate_type(): void
    {
        $known = CertificateTypes::ids();

        foreach (config('compliance_templates') as $industry => $templates) {
            foreach ($templates as $template) {
                $type = $template['type'] ?? null;
                $this->assertContains(
                    $type,
                    $known,
                    "Compliance template [{$industry}] uses unknown certificate type [{$type}].",
                );
            }
        }
    }
}
