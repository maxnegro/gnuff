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
        $faker = Faker::create();
        // EAN-13 validi (compatibili con Open Food Facts)
        for ($i = 0; $i < 50; $i++) {
            $barcode = self::generateEan13();
            Product::create([
                'barcode' => $barcode,
                'name' => $faker->words(3, true),
                'image_url' => $faker->imageUrl(640, 480, 'food', true, 'Prodotto'),
            ]);
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
