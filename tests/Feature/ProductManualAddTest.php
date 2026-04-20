<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Rating;

class ProductManualAddTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_product_and_rating_manually_with_openfoodfacts_integration()
    {
        $user = User::factory()->create();
        $productList = \App\Models\ProductList::factory()->create(['owner_id' => $user->id]);
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
        $productList = \App\Models\ProductList::factory()->create(['owner_id' => $user->id]);
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
}
