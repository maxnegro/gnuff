<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductList;
use App\Models\Rating;
use App\Enums\RatingEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRatingMultipleListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_different_ratings_to_same_product_in_different_lists()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $list1 = ProductList::factory()->create(['owner_id' => $user->id]);
        $list2 = ProductList::factory()->create(['owner_id' => $user->id]);

        $rating1 = Rating::create([
            'product_list_id' => $list1->id,
            'product_id' => $product->id,
            'rating' => RatingEnum::GNUF->value,
        ]);

        $rating2 = Rating::create([
            'product_list_id' => $list2->id,
            'product_id' => $product->id,
            'rating' => RatingEnum::OK->value,
        ]);

        $this->assertDatabaseHas('ratings', [
            'product_list_id' => $list1->id,
            'product_id' => $product->id,
            'rating' => RatingEnum::GNUF->value,
        ]);
        $this->assertDatabaseHas('ratings', [
            'product_list_id' => $list2->id,
            'product_id' => $product->id,
            'rating' => RatingEnum::OK->value,
        ]);
    }
}
