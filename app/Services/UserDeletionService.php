<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->deleteUserAndOwnedUsers($user);
        });
    }

    private function deleteUserAndOwnedUsers(User $user): void
    {
        User::where('created_by', $user->id)
            ->get()
            ->each(fn (User $ownedUser) => $this->deleteUserAndOwnedUsers($ownedUser));

        $user->activeSessions()->delete();
        $user->tokens()->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->delete();
        DB::table('model_has_permissions')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->delete();

        ActivityLog::where('user_id', $user->id)->delete();

        $user->delete();
    }
}
