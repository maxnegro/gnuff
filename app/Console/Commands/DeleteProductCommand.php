<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class DeleteProductCommand extends Command
{
    protected $signature = 'product:delete {product : ID or barcode of the product} {--force : Skip confirmation}';
    protected $description = 'Delete a product from the database';

    public function handle(): int
    {
        $identifier = $this->argument('product');

        $product = Product::where('id', $identifier)
            ->orWhere('barcode', $identifier)
            ->first();

        if (! $product) {
            $this->error("Product '{$identifier}' not found.");
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to delete '{$product->name}' (barcode: {$product->barcode})?", false)) {
            $this->info('Deletion cancelled.');
            return self::SUCCESS;
        }

        $product->delete();

        $this->info("Product deleted successfully: {$product->name} (barcode: {$product->barcode})");

        return self::SUCCESS;
    }
}
