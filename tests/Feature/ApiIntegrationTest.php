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
    public function test_update_rating_via_api(): void
    {
        $user = User::factory()->create();
        $barcode = '1234567890999';
        $this->actingAs($user)->postJson('/api/rate', [
            'barcode' => $barcode,
            'value' => 'ok',
        ]);
        $rating = Rating::where('user_id', $user->id)->whereHas('product', fn($q) => $q->where('barcode', $barcode))->first();
        $response = $this->actingAs($user)->putJson('/api/rate/' . $rating->id, [
            'value' => 'meh',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('ratings', [
            'id' => $rating->id,
            'rating' => 'meh',
        ]);
    }

    public function test_delete_rating_via_api(): void
    {
        $user = User::factory()->create();
        $barcode = '1234567890888';
        $this->actingAs($user)->postJson('/api/rate', [
            'barcode' => $barcode,
            'value' => 'ok',
        ]);
        $rating = Rating::where('user_id', $user->id)->whereHas('product', fn($q) => $q->where('barcode', $barcode))->first();
        $response = $this->actingAs($user)->deleteJson('/api/rate/' . $rating->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('ratings', [
            'id' => $rating->id,
        ]);
    }

    public function test_ratings_pagination_via_api(): void
    {
        $user = User::factory()->create();
        // Crea 15 rating diversi
        foreach (range(1, 15) as $i) {
            $barcode = '1234567890' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $this->actingAs($user)->postJson('/api/rate', [
                'barcode' => $barcode,
                'value' => 'ok',
            ]);
        }
        $response = $this->actingAs($user)->getJson('/api/ratings?per_page=10');
        $response->assertStatus(200);
        $response->assertJsonFragment(['per_page' => 10]);
        $response->assertJsonCount(10, 'data');
    }

    public function test_create_rating_via_api(): void
    {
        // Arrange
        $user = User::factory()->create();
        $barcode = '1234567890123';
        $validData = ['barcode' => $barcode, 'value' => 'gnuf'];

        // Act
        $response = $this->actingAs($user)->postJson('/api/rate', $validData);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'rating' => 'gnuf',
        ]);
    }

    public function test_invalid_rating_value(): void
    {
        // Arrange
        $user = User::factory()->create();
        $barcode = '1234567890123';
        $invalidData = ['barcode' => $barcode, 'value' => 'invalid'];

        // Act
        $response = $this->actingAs($user)->postJson('/api/rate', $invalidData);

        // Assert
        $response->assertStatus(422);
    }

    public function test_missing_user_auth(): void
    {
        // Arrange
        $barcode = '1234567890123';
        $validData = ['barcode' => $barcode, 'value' => 'ok'];

        // Act
        $response = $this->postJson('/api/rate', $validData);

        // Assert
        $response->assertStatus(401);
    }

    public function test_missing_barcode(): void
    {
        // Arrange
        $user = User::factory()->create();
        $invalidData = ['value' => 'ok'];

        // Act
        $response = $this->actingAs($user)->postJson('/api/rate', $invalidData);

        // Assert
        $response->assertStatus(422);
    }
}