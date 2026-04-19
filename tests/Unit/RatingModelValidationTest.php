<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Rating;
use App\Enums\RatingEnum;
use App\Models\Product;
use App\Models\User;

class RatingModelValidationTest extends TestCase
{
    public function test_user_id_must_be_present(): void
    {
        // Attempt to create a rating without a user_id
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Rating::create(['user_id' => '']);
    }

    public function test_product_id_must_be_present(): void
    {
        // Attempt to create a rating without a product_id
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Rating::create(['product_id' => '']);
    }

public function test_rating_must_be_valid(): void
{
    // Create a product and user for the test
    $product = Product::factory()->make();
    $user = User::factory()->make();

    // Test with valid values
    $validRatings = RatingEnum::values();
    foreach ($validRatings as $rating) {
        $ratingModel = Rating::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $rating,
        ]);
        $this->assertInstanceOf(Rating::class, $ratingModel);
    }

    // Test with invalid values
    $invalidRatings = ['invalid', 'test', ''];
    foreach ($invalidRatings as $rating) {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Rating::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $rating,
        ]);
    }
}
}