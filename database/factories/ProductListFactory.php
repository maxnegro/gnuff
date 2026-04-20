<?php

namespace Database\Factories;

use App\Models\ProductList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductListFactory extends Factory
{
    protected $model = ProductList::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            // aggiungi qui altri campi obbligatori se presenti nella migration
        ];
    }
}
