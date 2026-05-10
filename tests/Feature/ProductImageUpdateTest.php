<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductList;

class ProductImageUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function validImageBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Z4WQAAAAASUVORK5CYII=');
    }

    private function authenticateUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_update_image_url_and_remove_it()
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/*' => Http::response($this->validImageBytes(), 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

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
        $localImageUrl = $response->json('product.image_url');
        $this->assertNotNull($localImageUrl);
        $this->assertMatchesRegularExpression('#^/storage/products/[a-f0-9]{2}/[a-f0-9]{2}/'.$product->barcode.'-#', $localImageUrl);

        $this->assertDatabaseHas('products', [
            'barcode' => $product->barcode,
            'image_url' => $localImageUrl,
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
        Storage::fake('public');
        Http::fake([
            'https://example.com/*' => Http::response($this->validImageBytes(), 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $this->authenticateUser();
        // Con immagine
        $response = $this->postJson('/product', [
            'barcode' => '1234567890000',
            'name' => 'Test Product',
            'image_url' => 'https://example.com/img.jpg',
        ]);
        $response->assertStatus(200);
        $localImageUrl = $response->json('product.image_url');
        $this->assertNotNull($localImageUrl);
        $this->assertMatchesRegularExpression('#^/storage/products/[a-f0-9]{2}/[a-f0-9]{2}/1234567890000-#', $localImageUrl);

        $this->assertDatabaseHas('products', [
            'barcode' => '1234567890000',
            'image_url' => $localImageUrl,
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
        Storage::fake('public');
        Http::fake([
            'https://example.com/*' => Http::response($this->validImageBytes(), 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $this->authenticateUser();

        $response = $this->putJson('/product/1234567890123', [
            'name' => 'Nuovo prodotto da PUT',
            'image_url' => 'https://example.com/new-product.jpg',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('created', true);

        $localImageUrl = $response->json('product.image_url');
        $this->assertNotNull($localImageUrl);
        $this->assertMatchesRegularExpression('#^/storage/products/[a-f0-9]{2}/[a-f0-9]{2}/1234567890123-#', $localImageUrl);

        $this->assertDatabaseHas('products', [
            'barcode' => '1234567890123',
            'name' => 'Nuovo prodotto da PUT',
            'image_url' => $localImageUrl,
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
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_localizes_open_food_facts_image_url()
    {
        Storage::fake('public');
        Http::fake([
            'https://world.openfoodfacts.net/api/v2/*' => Http::response([
                'status' => 1,
                'product' => [
                    'code' => '3213213213213',
                    'product_name' => 'Prodotto OFF test',
                    'image_url' => 'https://cdn.example.com/off.jpg',
                ],
            ], 200),
            'https://cdn.example.com/*' => Http::response($this->validImageBytes(), 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $this->authenticateUser();

        $response = $this->getJson('/product/3213213213213');

        $response->assertStatus(200);
        $localImageUrl = $response->json('product.image_url');
        $this->assertNotNull($localImageUrl);
        $this->assertMatchesRegularExpression('#^/storage/products/[a-f0-9]{2}/[a-f0-9]{2}/3213213213213-#', $localImageUrl);

        $this->assertDatabaseHas('products', [
            'barcode' => '3213213213213',
            'name' => 'Prodotto OFF test',
            'image_url' => $localImageUrl,
        ]);
    }

    public function test_update_accepts_existing_local_storage_path_without_relocalizing()
    {
        $this->authenticateUser();
        $product = Product::factory()->create([
            'barcode' => '8000000000012',
            'name' => 'Prodotto locale',
            'image_url' => '/storage/products/aa/bb/8000000000012-local.jpg',
        ]);

        $response = $this->putJson('/product/8000000000012', [
            'name' => 'Prodotto locale aggiornato',
            'image_url' => '/storage/products/aa/bb/8000000000012-local.jpg',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'barcode' => $product->barcode,
            'name' => 'Prodotto locale aggiornato',
            'image_url' => '/storage/products/aa/bb/8000000000012-local.jpg',
        ]);
    }

    public function test_update_does_not_clear_existing_image_when_remote_localization_fails()
    {
        Storage::fake('public');
        Http::fake([
            'https://broken.example.com/*' => Http::response('fail', 500),
        ]);

        $this->authenticateUser();
        $product = Product::factory()->create([
            'barcode' => '8000000000099',
            'name' => 'Prodotto con immagine',
            'image_url' => '/storage/products/cc/dd/8000000000099-existing.jpg',
        ]);

        $response = $this->putJson('/product/8000000000099', [
            'name' => 'Prodotto con immagine',
            'image_url' => 'https://broken.example.com/image.jpg',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('products', [
            'barcode' => $product->barcode,
            'image_url' => '/storage/products/cc/dd/8000000000099-existing.jpg',
        ]);
    }

    public function test_update_accepts_absolute_local_storage_url_without_triggering_cache_download()
    {
        Http::fake();

        $this->authenticateUser();
        $product = Product::factory()->create([
            'barcode' => '8001720500049',
            'name' => 'Prodotto locale assoluto',
            'image_url' => '/storage/products/40/3f/8001720500049-0d55d5504e39c849.jpg',
        ]);

        $absoluteLocalUrl = 'http://localhost:8180/storage/products/40/3f/8001720500049-0d55d5504e39c849.jpg';

        $response = $this->putJson('/product/8001720500049', [
            'name' => 'Prodotto locale assoluto',
            'image_url' => $absoluteLocalUrl,
        ]);

        $response->assertStatus(200);
        Http::assertNothingSent();

        $this->assertDatabaseHas('products', [
            'barcode' => $product->barcode,
            'image_url' => '/storage/products/40/3f/8001720500049-0d55d5504e39c849.jpg',
        ]);
    }
}
