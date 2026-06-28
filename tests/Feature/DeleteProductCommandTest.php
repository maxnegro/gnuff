<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteProductCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_a_product_by_id(): void
    {
        $product = Product::factory()->create();

        $this->artisan('product:delete', ['product' => $product->id, '--force' => true])
            ->expectsOutput("Product deleted successfully: {$product->name} (barcode: {$product->barcode})")
            ->assertSuccessful();

        $this->assertModelMissing($product);
    }

    public function test_it_deletes_a_product_by_barcode(): void
    {
        $product = Product::factory()->create();

        $this->artisan('product:delete', ['product' => $product->barcode, '--force' => true])
            ->expectsOutput("Product deleted successfully: {$product->name} (barcode: {$product->barcode})")
            ->assertSuccessful();

        $this->assertModelMissing($product);
    }

    public function test_it_returns_failure_when_product_not_found(): void
    {
        $this->artisan('product:delete', ['product' => 'nonexistent', '--force' => true])
            ->expectsOutput("Product 'nonexistent' not found.")
            ->assertFailed();
    }

    public function test_it_cancels_deletion_when_not_confirmed(): void
    {
        $product = Product::factory()->create();

        $this->artisan('product:delete', ['product' => $product->id])
            ->expectsConfirmation("Are you sure you want to delete '{$product->name}' (barcode: {$product->barcode})?", 'no')
            ->expectsOutput('Deletion cancelled.')
            ->assertSuccessful();

        $this->assertModelExists($product);
    }
}
