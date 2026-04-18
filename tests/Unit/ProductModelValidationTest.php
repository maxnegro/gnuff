<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;

class ProductModelValidationTest extends TestCase
{
    public function test_barcode_must_be_present(): void
    {
        // Attempt to create a product without a barcode
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Product::factory()->create(['barcode' => '']);
    }

    public function test_name_must_be_present(): void
    {
        // Attempt to create a product without a name
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Product::factory()->create(['name' => '']);
    }

    public function test_image_url_must_be_valid_url(): void
    {
        // Attempt to create a product with invalid image URL
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Product::factory()->create(['image_url' => 'invalid-url']);
    }
}