<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProductList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductManualAddTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_product_and_rating_manually_with_openfoodfacts_integration()
    {
        $user = User::factory()->create();
        $productList = ProductList::factory()->create(['owner_id' => $user->id]);
        $barcode = '8002885005110'; // Si&No di Mais

        // Step 1: Richiesta GET per recuperare il prodotto (simula la ricerca EAN)
        $response = $this->actingAs($user)->getJson("/product/{$barcode}");
        $response->assertStatus(200);
        $data = $response->json('product');
        $this->assertEquals($barcode, $data['barcode']);
        $this->assertNotEmpty($data['name']);
        $this->assertNotEmpty($data['image_url']);

        // Step 2: Salva la valutazione
        $response = $this->actingAs($user)
            ->withSession(['active_list_id' => $productList->id])
            ->postJson('/rate', [
                'barcode' => $barcode,
                'value' => 'gnuf',
            ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('ratings', [
            'product_list_id' => $productList->id,
            'rating' => 'gnuf',
        ]);
        $this->assertDatabaseHas('products', [
            'barcode' => $barcode,
        ]);
    }

    public function test_add_product_and_rating_manually_with_custom_name()
    {
        $user = User::factory()->create();
        $productList = ProductList::factory()->create(['owner_id' => $user->id]);
        $barcode = '9999999999999'; // barcode fittizio
        $customName = 'Test Prodotto Manuale';

        // Step 1: Salva il prodotto manualmente
        $response = $this->actingAs($user)->postJson('/product', [
            'barcode' => $barcode,
            'name' => $customName,
            'image_url' => null,
        ]);
        $response->assertStatus(200);
        $data = $response->json('product');
        $this->assertEquals($barcode, $data['barcode']);
        $this->assertEquals($customName, $data['name']);

        // Step 2: Salva la valutazione
        $response = $this->actingAs($user)
            ->withSession(['active_list_id' => $productList->id])
            ->postJson('/rate', [
                'barcode' => $barcode,
                'value' => 'ok',
            ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('ratings', [
            'product_list_id' => $productList->id,
            'rating' => 'ok',
        ]);
        $this->assertDatabaseHas('products', [
            'barcode' => $barcode,
        ]);
    }

    public function test_openfoodfacts_fields_are_limited()
    {
        $user = User::factory()->create();
        $barcode = '8002885005110'; // barcode reale

        $response = $this->actingAs($user)->getJson("/product/{$barcode}");
        $response->assertStatus(200);
        $data = $response->json('product');
        // I campi devono essere solo questi
        $this->assertIsArray($data);
        $this->assertArrayHasKey('barcode', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('image_url', $data);
        $this->assertCount(3, $data, 'Devono essere presenti solo barcode, name, image_url');
    }

    public function test_openfoodfacts_cache_hit_does_not_consume_upstream_budget(): void
    {
        Cache::flush();
        config([
            'openfoodfacts.product_lookup_limit_per_minute' => 1,
            'openfoodfacts.server_id' => 'cache-hit-test',
        ]);

        Http::fake([
            'https://world.openfoodfacts.net/api/v2/product/8002885005110*' => Http::response([
                'status' => 1,
                'product' => [
                    'code' => '8002885005110',
                    'product_name' => 'Prodotto OFF cache',
                    'image_url' => null,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/product/8002885005110')->assertStatus(200);
        $this->actingAs($user)->getJson('/product/8002885005110')->assertStatus(200);

        Http::assertSentCount(1);
    }

    public function test_openfoodfacts_server_wide_budget_blocks_extra_real_lookups(): void
    {
        Cache::flush();
        config([
            'openfoodfacts.product_lookup_limit_per_minute' => 1,
            'openfoodfacts.server_id' => 'budget-test',
        ]);

        Http::fake([
            'https://world.openfoodfacts.net/api/v2/product/8002885005110*' => Http::response([
                'status' => 1,
                'product' => [
                    'code' => '8002885005110',
                    'product_name' => 'Prodotto OFF budget',
                    'image_url' => null,
                ],
            ], 200),
            'https://world.openfoodfacts.net/api/v2/product/8002885005111*' => Http::response([
                'status' => 1,
                'product' => [
                    'code' => '8002885005111',
                    'product_name' => 'Prodotto OFF budget 2',
                    'image_url' => null,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/product/8002885005110')->assertStatus(200);
        $response = $this->actingAs($user)->getJson('/product/8002885005111');

        $response
            ->assertStatus(502)
            ->assertJsonPath('details.error_code', 'OFF_RATE_LIMITED');
        $this->assertGreaterThanOrEqual(1, $response->json('details.retry_after'));

        Http::assertSentCount(1);
    }

    public function test_openfoodfacts_429_opens_short_circuit_and_is_cached(): void
    {
        Cache::flush();
        config([
            'openfoodfacts.product_lookup_limit_per_minute' => 10,
            'openfoodfacts.server_id' => 'rate-limit-test',
        ]);

        Http::fake([
            'https://world.openfoodfacts.net/api/v2/product/8002885005112*' => Http::response([
                'status' => 0,
                'product' => null,
            ], 429, ['Retry-After' => '123']),
        ]);

        $user = User::factory()->create();

        $first = $this->actingAs($user)->getJson('/product/8002885005112');
        $second = $this->actingAs($user)->getJson('/product/8002885005112');

        $first
            ->assertStatus(502)
            ->assertJsonPath('details.error_code', 'OFF_RATE_LIMITED')
            ->assertJsonPath('details.retry_after', 123);

        $second
            ->assertStatus(502)
            ->assertJsonPath('details.error_code', 'OFF_RATE_LIMITED')
            ->assertJsonPath('details.retry_after', 123);

        Http::assertSentCount(1);
    }
}
