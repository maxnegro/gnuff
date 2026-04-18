<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Rating;

class RatingModelValidationTest extends TestCase
{
    public function test_user_id_must_be_present(): void
    {
        // Attempt to create a rating without a user_id
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Rating::factory()->create(['user_id' => '']);
    }

    public function test_product_id_must_be_present(): void
    {
        // Attempt to create a rating without a product_id
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Rating::factory()->create(['product_id' => '']);
    }

    public function test_rating_must_be_valid(): void
    {
        // Attempt to create a rating with invalid value (0)
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Rating::factory()->create(['rating' => 'invalid']);
    }
}