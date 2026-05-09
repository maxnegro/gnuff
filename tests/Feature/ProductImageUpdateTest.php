<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductList;

class ProductImageUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_update_image_url_and_remove_it()
    {
        $user = $this->authenticateUser();
        $product = Product::factory()->create([
            'image_url' => 'https://example.com/old.jpg',
        ]);

        // Aggiorna immagine
        $response = $this->putJson("/product/{$product->barcode}", [
            'name' => $product->name,
            'image_url' => 'https://example.com/new.jpg',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'barcode' => $product->barcode,
            'image_url' => 'https://example.com/new.jpg',
        ]);

        // Rimuovi immagine (setta null)
        $response = $this->putJson("/product/{$product->barcode}", [
            'name' => $product->name,
            'image_url' => null,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'barcode' => $product->barcode,
            'image_url' => null,
        ]);

        // Rimuovi immagine (stringa vuota)
        $response = $this->putJson("/product/{$product->barcode}", [
            'name' => $product->name,
            'image_url' => '',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'barcode' => $product->barcode,
            'image_url' => null,
        ]);
    }

    public function test_create_product_with_image_url_and_without()
    {
        $this->authenticateUser();
        // Con immagine
        $response = $this->postJson('/product', [
            'barcode' => '1234567890000',
            'name' => 'Test Product',
            'image_url' => 'https://example.com/img.jpg',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'barcode' => '1234567890000',
            'image_url' => 'https://example.com/img.jpg',
        ]);
        // Senza immagine
        $response = $this->postJson('/product', [
            'barcode' => '1234567890001',
            'name' => 'Test Product 2',
            'image_url' => null,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'barcode' => '1234567890001',
            'image_url' => null,
        ]);
    }

    public function test_put_product_creates_new_product_when_missing()
    {
        $this->authenticateUser();

        $response = $this->putJson('/product/1234567890123', [
            'name' => 'Nuovo prodotto da PUT',
            'image_url' => 'https://example.com/new-product.jpg',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('created', true);

        $this->assertDatabaseHas('products', [
            'barcode' => '1234567890123',
            'name' => 'Nuovo prodotto da PUT',
            'image_url' => 'https://example.com/new-product.jpg',
        ]);
    }

    public function test_full_manual_flow_update_image_then_save_rating()
    {
        $user = $this->authenticateUser();
        $list = ProductList::factory()->create(['owner_id' => $user->id]);
        $product = Product::factory()->create([
            'barcode' => '8000000000001',
            'name' => 'Prodotto test rating',
            'image_url' => 'https://example.com/original.jpg',
        ]);

        // Simula "Salva immagine" con URL vuoto: deve cancellare l'immagine senza errori di validazione
        $this->putJson('/product/8000000000001', [
            'name' => 'Prodotto test rating',
            'image_url' => '',
        ])->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'barcode' => '8000000000001',
            'image_url' => null,
        ]);

        // Simula "Salva valutazione" dopo update prodotto
        $this->withSession(['active_list_id' => $list->id])
            ->postJson('/rate', [
                'barcode' => $product->barcode,
                'value' => 'ok',
            ])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Valutazione salvata');

        $this->assertDatabaseHas('ratings', [
            'product_list_id' => $list->id,
            'product_id' => $product->id,
            'rating' => 'ok',
        ]);
    }

    public function test_put_product_requires_name_and_returns_validation_error()
    {
        $this->authenticateUser();

        $response = $this->putJson('/product/1234567890999', [
            'image_url' => 'https://example.com/only-image.jpg',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
