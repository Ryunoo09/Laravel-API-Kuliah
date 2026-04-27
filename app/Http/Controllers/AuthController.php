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
     * Login
     *
     * Autentikasi user dan mendapatkan access token.
     *
     * @group Authentication
     * @unauthenticated
     *
     * @bodyParam email string required Alamat email user. Example: admin@example.com
     * @bodyParam password string required Password user. Example: password
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login berhasil.",
     *   "access_token": "1|abc123def456...",
     *   "token_type": "Bearer",
     *   "user": {
     *     "id": 1,
     *     "name": "Admin",
     *     "email": "admin@example.com",
     *     "role": "admin"
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["Alamat email atau password salah."]
     *   }
     * }
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
     * Register
     *
     * Mendaftarkan user baru. Endpoint ini bisa diakses secara publik (role default 'user')
     * atau oleh admin yang sudah login (bisa menentukan role).
     *
     * @group Authentication
     * @unauthenticated
     *
     * @bodyParam name string required Nama lengkap user. Example: John Doe
     * @bodyParam email string required Alamat email unik. Example: john@example.com
     * @bodyParam password string required Password minimal 8 karakter. Example: password123
     * @bodyParam password_confirmation string required Konfirmasi password. Example: password123
     * @bodyParam role string Opsional, hanya untuk admin. Nilai: admin, user. Example: user
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Registrasi berhasil.",
     *   "user": {
     *     "id": 2,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "role": "user"
     *   }
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "Akses ditolak. Pengguna yang sudah login tidak dapat melakukan registrasi."
     * }
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
     * Logout
     *
     * Menghapus/revoke token yang sedang digunakan saat ini.
     *
     * @group Authentication
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Logout berhasil. Token telah dihapus."
     * }
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
     * Reset Password
     *
     * User biasa mereset password dirinya sendiri (wajib kirim old_password).
     * Admin bisa mereset password user lain berdasarkan email (tanpa old_password).
     *
     * @group Authentication
     * @authenticated
     *
     * @bodyParam old_password string Password lama (wajib untuk user biasa). Example: oldpassword123
     * @bodyParam new_password string required Password baru minimal 8 karakter. Example: newpassword123
     * @bodyParam new_password_confirmation string required Konfirmasi password baru. Example: newpassword123
     * @bodyParam email string Email target (wajib untuk admin). Example: john@example.com
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password berhasil direset."
     * }
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
