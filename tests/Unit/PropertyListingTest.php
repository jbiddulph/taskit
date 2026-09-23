<?php

namespace Tests\Unit;

use App\Support\PropertyListing;
use PHPUnit\Framework\TestCase;

class PropertyListingTest extends TestCase
{
    public function test_price_labels_match_a_uk_listing(): void
    {
        $this->assertSame('£1,750 pcm', PropertyListing::formatPrice(1750, 'pcm'));
        $this->assertSame('£400 pw', PropertyListing::formatPrice(400, 'pw'));
        $this->assertSame('Offers over £450,000', PropertyListing::formatPrice(450000, 'offers_over'));
        $this->assertSame('POA', PropertyListing::formatPrice(null, 'poa'));
        $this->assertSame('£2,019', PropertyListing::formatMoney(2019));
    }

    public function test_visibility_defaults_to_shown_and_honours_explicit_hides(): void
    {
        $visibility = PropertyListing::normalizeVisibility([
            'council_tax' => false,
            'deposit' => true,
        ]);

        $this->assertFalse($visibility['council_tax']);
        $this->assertTrue($visibility['deposit']);
        $this->assertTrue($visibility['broadband']);
        $this->assertTrue($visibility['features']);
        $this->assertArrayNotHasKey('notes', $visibility);
    }
}
