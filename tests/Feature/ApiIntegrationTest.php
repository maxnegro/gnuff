<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Rating;
use App\Models\Product;
use App\Models\User;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_rating_via_api(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $validData = ['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5];

        // Act
        $response = $this->post('/api/ratings', $validData);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('ratings', $validData);
    }

    public function test_invalid_rating_value(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $invalidData = ['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 6];

        // Act
        $response = $this->post('/api/ratings', $invalidData);

        // Assert
        $response->assertStatus(422);
    }

    public function test_missing_user_id(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $invalidData = ['product_id' => $product->id, 'rating' => 5];

        // Act
        $response = $this->post('/api/ratings', $invalidData);

        // Assert
        $response->assertStatus(422);
    }

    public function test_missing_product_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $invalidData = ['user_id' => $user->id, 'rating' => 5];

        // Act
        $response = $this->post('/api/ratings', $invalidData);

        // Assert
        $response->assertStatus(422);
    }
}