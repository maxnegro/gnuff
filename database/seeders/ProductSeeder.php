<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Services\OpenFoodFactsService;
use Illuminate\Support\Facades\App;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Scarica prodotti reali da Open Food Facts tramite service
        /** @var OpenFoodFactsService $offService */
        $offService = App::make(OpenFoodFactsService::class);
        $products = $offService->searchProducts([
            'allergens_tags' => '-en:gluten',
            'countries_tags_en' => 'italy',
            'fields' => 'product_name,code,images',
            'sort_by' => 'popularity_key',
            'page' => 24,
            'page_size' => 49,
        ]);
        if (!is_array($products)) {
            throw new \Exception('Impossibile recuperare dati da Open Food Facts');
        }
        foreach ($products as $product) {
            $barcode = $product['code'] ?? null;
            $name = $product['product_name'] ?? null;
            // Prende la prima immagine disponibile
            $image_url = null;
            if (isset($product['images']) && is_array($product['images'])) {
                foreach ($product['images'] as $img) {
                    if (isset($img['url'])) {
                        $image_url = $img['url'];
                        break;
                    }
                }
            }
            if ($barcode && $name) {
                Product::create([
                    'barcode' => $barcode,
                    'name' => $name,
                    'image_url' => $image_url,
                ]);
            }
        }
    }

    /**
     * Genera un codice EAN-13 valido.
     */
    public static function generateEan13(): string
    {
        $code = str_pad(strval(mt_rand(100000000000, 999999999999)), 12, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$code[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        return $code . $check;
    }
}
