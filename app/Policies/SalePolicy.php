<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function view(User $user, Sale $sale): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('seller')) {
            return $sale->seller_id === $user->id;
        }

        if ($user->hasRole('manager')) {
            return $sale->seller()
                ->where('created_by', $user->id)
                ->exists();
        }

        return false;
    }

    public function printReceipt(User $user, Sale $sale): bool
    {
        return $this->view($user, $sale);
    }
}
