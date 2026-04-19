<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Scarica prodotti reali da Open Food Facts
        $url = 'https://world.openfoodfacts.net/api/v2/search?allergens_tags=-en:gluten&countries_tags_en=italy&fields=product_name%2Ccode%2Cimages&sort_by=popularity_key&page=24&page_size=49';
        $response = @file_get_contents($url);
        if ($response === false) {
            throw new \Exception('Impossibile recuperare dati da Open Food Facts');
        }
        $data = json_decode($response, true);
        if (!isset($data['products'])) {
            throw new \Exception('Risposta API non valida');
        }
        foreach ($data['products'] as $product) {
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
