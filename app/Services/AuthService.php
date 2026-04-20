<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Handle user authentication
     */
    public function loginUser(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email atau password salah.'],
            ]);
        }

        return [
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ];
    }
}
