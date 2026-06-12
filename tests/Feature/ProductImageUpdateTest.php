<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

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

    /**
     * Test POST /product/{barcode}/image with valid Base64 image
     */
    public function test_update_image_with_valid_base64()
    {
        Storage::fake('public');
        $this->authenticateUser();

        $product = Product::factory()->create([
            'barcode' => '1234567890111',
            'name' => 'Test Product',
            'image_url' => null,
        ]);

        // Creare un'immagine Base64 JPEG valida (1x1 pixel rosso)
        $base64Image = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDi6KKK+ZP3E//Z';

        $response = $this->postJson('/product/1234567890111/image', [
            'image_base64' => $base64Image,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Immagine prodotto aggiornata con successo.');

        $imageUrl = $response->json('image_url');
        $this->assertNotNull($imageUrl);
        $this->assertMatchesRegularExpression('#^/storage/products/[a-f0-9]{2}/[a-f0-9]{2}/1234567890111-#', $imageUrl);

        // Verificare che il prodotto sia stato aggiornato
        $this->assertDatabaseHas('products', [
            'barcode' => '1234567890111',
            'image_url' => $imageUrl,
        ]);

        // Verificare che il file sia stato salvato in storage
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $imageUrl));
    }

    /**
     * Test POST /product/{barcode}/image replacing existing image
     */
    public function test_update_image_replaces_existing_image()
    {
        Storage::fake('public');
        $this->authenticateUser();

        $product = Product::factory()->create([
            'barcode' => '1234567890222',
            'name' => 'Test Product',
            'image_url' => '/storage/products/old/path/1234567890222-old.jpg',
        ]);

        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->postJson('/product/1234567890222/image', [
            'image_base64' => $base64Image,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Immagine prodotto aggiornata con successo.');

        $newImageUrl = $response->json('image_url');
        $this->assertNotNull($newImageUrl);

        // Verificare che l'immagine sia stata sostituita
        $product->refresh();
        $this->assertNotEquals('/storage/products/old/path/1234567890222-old.jpg', $product->image_url);
        $this->assertEquals($newImageUrl, $product->image_url);
    }

    /**
     * Test POST /product/{barcode}/image creates product if not exists
     */
    public function test_update_image_creates_product_if_not_exists()
    {
        Storage::fake('public');
        $this->authenticateUser();

        $base64Image = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDi6KKK+ZP3E//Z';

        $response = $this->postJson('/product/9876543210000/image', [
            'image_base64' => $base64Image,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Immagine prodotto caricata con successo.');

        // Verificare che il prodotto sia stato creato
        $this->assertDatabaseHas('products', [
            'barcode' => '9876543210000',
        ]);
    }

    /**
     * Test POST /product/{barcode}/image with invalid base64
     */
    public function test_update_image_rejects_invalid_base64()
    {
        $this->authenticateUser();

        $response = $this->postJson('/product/1234567890333/image', [
            'image_base64' => 'not-valid-base64',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image_base64']);
    }

    /**
     * Test POST /product/{barcode}/image with oversized image
     */
    public function test_update_image_rejects_oversized_image()
    {
        $this->authenticateUser();

        // Creare un Base64 > 5MB
        $largeBase64 = 'data:image/jpeg;base64,'.str_repeat('A', 7 * 1024 * 1024);

        $response = $this->postJson('/product/1234567890444/image', [
            'image_base64' => $largeBase64,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image_base64']);
    }

    /**
     * Test POST /product/{barcode}/image with unsupported format
     */
    public function test_update_image_rejects_unsupported_format()
    {
        $this->authenticateUser();

        // GIF è non supportato (solo jpeg, png, webp)
        $gifBase64 = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        $response = $this->postJson('/product/1234567890555/image', [
            'image_base64' => $gifBase64,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image_base64']);
    }

    /**
     * Test POST /product/{barcode}/image without authentication
     */
    public function test_update_image_requires_authentication()
    {
        $base64Image = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAA1ADUDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWm5ybnJ2eoqOkpaanqKmqsrO0tba2uLm6wsPExcbHyMnK0tPU1dbW2Nna4uPk5ebn6Onq8vP09fb2+Pn6/8QAHwEAAwEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlbaWmJmaoqOkpaanqKmqsrO0tba2uLm6wsPExcbHyMnK0tPU1dbW2Nna4uPk5ebn6Onq8vP09fb2+Pn6/9oADAMBAAIRAxEAPwD3+iiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigD/2Q==';

        $response = $this->postJson('/product/1234567890666/image', [
            'image_base64' => $base64Image,
        ]);

        $response->assertStatus(401);
    }
}
