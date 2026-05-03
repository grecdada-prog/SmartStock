<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manageAsSuperAdmin(User $user, User $target): bool
    {
        return $user->hasRole('super_admin');
    }

    public function manageManagerAsSuperAdmin(User $user, User $manager): bool
    {
        return $user->hasRole('super_admin') && $manager->hasRole('manager');
    }

    public function manageSellerAsSuperAdmin(User $user, User $seller): bool
    {
        return $user->hasRole('super_admin') && $seller->hasRole('seller');
    }

    public function deleteAsSuperAdmin(User $user, User $target): bool
    {
        return $user->hasRole('super_admin') && $user->id !== $target->id;
    }

    public function toggleStatusAsSuperAdmin(User $user, User $target): bool
    {
        return $this->deleteAsSuperAdmin($user, $target);
    }

    public function forceLogoutAsSuperAdmin(User $user, User $target): bool
    {
        return $this->deleteAsSuperAdmin($user, $target);
    }

    public function resetPasswordAsSuperAdmin(User $user, User $target): bool
    {
        return $user->hasRole('super_admin');
    }

    public function manageSeller(User $user, User $seller): bool
    {
        return $user->hasRole('manager')
            && $seller->hasRole('seller')
            && $seller->created_by === $user->id;
    }
}
