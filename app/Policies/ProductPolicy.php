<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('manager') && $product->created_by === $user->id)
            || ($user->hasRole('seller') && $product->is_active && $product->created_by === $user->created_by);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole('manager') && $product->created_by === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function toggleStatus(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
