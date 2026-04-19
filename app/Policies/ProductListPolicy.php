<?php

namespace App\Policies;

use App\Models\ProductList;
use App\Models\User;

class ProductListPolicy
{
    public function update(User $user, ProductList $list): bool
    {
        return $list->owner_id === $user->id;
    }

    public function delete(User $user, ProductList $list): bool
    {
        return $list->owner_id === $user->id;
    }
}
