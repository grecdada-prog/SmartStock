<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('manager') && $category->created_by === $user->id);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole('manager') && $category->created_by === $user->id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }

    public function toggleStatus(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
