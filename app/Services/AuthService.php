<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Handle user authentication (Login)
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
            'user'         => $user,
        ];
    }

    /**
     * Handle user registration.
     * Hanya bisa dipanggil oleh admin (jika user sudah login).
     * Jika belum login (public), hanya boleh register sebagai 'user' biasa.
     */
    public function registerUser(array $data, ?User $actor = null): User
    {
        // Tentukan role: admin bisa menetapkan role; selain itu default 'user'
        $role = 'user';
        if ($actor && $actor->isAdmin() && isset($data['role'])) {
            $role = $data['role'];
        }

        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $role,
        ]);
    }

    /**
     * Handle user logout — revoke current token.
     */
    public function logoutUser(User $user): void
    {
        // Hapus token yang sedang dipakai saat ini
        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();
        $token->delete();
    }

    /**
     * Reset password user.
     * User hanya bisa reset password miliknya sendiri.
     * Admin bisa reset password siapa saja.
     */
    public function resetPassword(User $actor, array $data): void
    {
        // Jika bukan admin, pastikan old_password sesuai
        if (! $actor->isAdmin()) {
            if (! Hash::check($data['old_password'], $actor->password)) {
                throw ValidationException::withMessages([
                    'old_password' => ['Password lama tidak sesuai.'],
                ]);
            }
            // User hanya bisa reset password dirinya sendiri
            $target = $actor;
        } else {
            // Admin bisa reset password user lain berdasarkan email
            $target = User::where('email', $data['email'])->firstOrFail();
        }

        $target->update([
            'password' => Hash::make($data['new_password']),
        ]);
    }
}
