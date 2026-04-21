<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST /api/login
     * Endpoint publik untuk login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->authService->loginUser($credentials);

        return response()->json([
            'success'      => true,
            'message'      => 'Login berhasil.',
            'access_token' => $result['access_token'],
            'token_type'   => 'Bearer',
            'user'         => [
                'id'    => $result['user']->id,
                'name'  => $result['user']->name,
                'email' => $result['user']->email,
                'role'  => $result['user']->role,
            ],
        ]);
    }

    /**
     * POST /api/register
     *
     * Skenario akses:
     *   1. Belum login (publik)     → BOLEH register, role otomatis 'user'
     *   2. Login sebagai 'user'     → DITOLAK 403
     *   3. Login sebagai 'admin'    → BOLEH register, bisa pilih role target
     */
    public function register(Request $request)
    {
        // Cek secara opsional apakah request membawa token yang valid
        /** @var User|null $actor */
        $actor = Auth::guard('sanctum')->user();

        // Skenario 2: Sudah login tapi role-nya bukan admin → tolak
        if ($actor && ! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Pengguna yang sudah login tidak dapat melakukan registrasi.',
            ], 403);
        }

        // Validasi input (berlaku untuk semua skenario)
        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];

        // Hanya admin yang boleh menentukan role
        if ($actor && $actor->isAdmin()) {
            $rules['role'] = 'sometimes|in:admin,user';
        }

        $data = $request->validate($rules);

        // Skenario 1 ($actor = null): registerUser akan default ke role 'user'
        // Skenario 3 ($actor = admin): registerUser akan pakai role dari $data jika ada
        $user = $this->authService->registerUser($data, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], 201);
    }

    /**
     * POST /api/logout
     * Endpoint untuk logout (membutuhkan autentikasi).
     */
    public function logout(Request $request)
    {
        $this->authService->logoutUser($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil. Token telah dihapus.',
        ]);
    }

    /**
     * POST /api/reset-password
     * - User biasa: harus menyertakan old_password dan new_password (untuk password sendiri).
     * - Admin: menyertakan email target, new_password (tanpa perlu old_password).
     */
    public function resetPassword(Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->isAdmin()) {
            // Admin mereset password user lain berdasarkan email
            $data = $request->validate([
                'email'        => 'required|email|exists:users,email',
                'new_password' => 'required|string|min:8|confirmed',
            ]);
        } else {
            // User biasa mereset password dirinya sendiri
            $data = $request->validate([
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed|different:old_password',
            ]);
            // Paksa email ke email diri sendiri agar service tahu target-nya
            $data['email'] = $actor->email;
        }

        $this->authService->resetPassword($actor, $data);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset.',
        ]);
    }
}
