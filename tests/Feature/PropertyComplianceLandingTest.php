<?php

namespace Tests\Feature;

use Tests\TestCase;

class PropertyComplianceLandingTest extends TestCase
{
    public function test_property_compliance_landing_page_renders(): void
    {
        $response = $this->get(route('property-compliance'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('PropertyCompliance'));
    }

    public function test_legacy_property_compliance_path_redirects(): void
    {
        $response = $this->get('/property-compliance');

        $response->assertRedirect('/prop-compliance');
    }

    public function test_sitemap_includes_property_compliance_landing(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee(route('property-compliance'), false);
        $response->assertSee('/prop-compliance', false);
    }
}
