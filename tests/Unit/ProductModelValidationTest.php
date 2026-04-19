<?php  

namespace Tests\Unit;  

use Tests\TestCase;  
use App\Models\Product;  

class ProductModelValidationTest extends TestCase  
{  
    public function test_barcode_must_be_present(): void  
    {  
        // Bypass factory to directly create with empty barcode  
        $product = Product::factory()->make(['barcode' => '']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $product->save();
    }  

    public function test_name_must_be_present(): void  
    {  
        $this->expectException(\Illuminate\Validation\ValidationException::class);  
        $product = Product::factory()->make(['name' => '']);
        $product->save();
    }  

    public function test_image_url_must_be_valid_url(): void  
    {  
        $this->expectException(\Illuminate\Validation\ValidationException::class);  
        $product = Product::factory()->make(['image_url' => 'invalid-url']);
        $product->save();
    }  
}