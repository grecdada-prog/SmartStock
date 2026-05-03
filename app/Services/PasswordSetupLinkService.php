<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;

class PasswordSetupLinkService
{
    public function createUrl(User $user): string
    {
        $token = Password::broker()->createToken($user);

        return route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }
}
