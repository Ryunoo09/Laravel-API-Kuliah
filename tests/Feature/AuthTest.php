<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ============================================================
 *  Feature Test: Authentication API Endpoints
 * ============================================================
 *
 *  Skenario yang diuji:
 *  1. Login (/api/v1/login)
 *     - Sukses login dengan kredensial valid
 *     - Gagal login dengan password salah
 *     - Gagal login dengan data tidak lengkap
 *  2. Register (/api/v1/register)
 *     - Sukses registrasi publik (role default 'user')
 *     - Sukses registrasi oleh Admin (role kustom 'admin')
 *     - Gagal registrasi karena user biasa sudah login (403)
 *     - Gagal registrasi karena validasi input tidak valid
 *  3. Logout (/api/v1/logout)
 *     - Sukses logout jika terotentikasi
 *     - Gagal logout jika belum terotentikasi (401)
 *  4. Reset Password (/api/v1/reset-password)
 *     - Sukses ganti password sendiri (dengan password lama)
 *     - Gagal ganti password sendiri jika password lama salah
 *     - Sukses admin reset password user lain tanpa password lama
 *     - Gagal jika password baru & konfirmasi tidak cocok
 *
 *  RefreshDatabase menjamin database dalam kondisi bersih di setiap test.
 * ============================================================
 */
class AuthTest extends TestCase
{
    // ─────────────────────────────────────────────────────────
    //  SKENARIO 1 — LOGIN
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function login_succeeds_with_valid_credentials(): void
    {
        // Buat user menggunakan factory
        $user = User::factory()->asUser()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'user@example.com')
            ->assertJsonPath('user.role', 'user');

        $this->assertNotEmpty($response->json('access_token'));
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $user = User::factory()->asUser()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response
            ->assertUnprocessable() // HTTP 422
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function login_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 2 — REGISTER
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function register_succeeds_for_public_as_regular_user(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'admin', // Publik mencoba mengirim role admin, seharusnya diabaikan
        ]);

        $response
            ->assertCreated() // HTTP 201
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.name', 'John Doe')
            ->assertJsonPath('user.email', 'john@example.com')
            ->assertJsonPath('user.role', 'user'); // Role harus tetap 'user' karena request publik

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role'  => 'user',
        ]);
    }

    #[Test]
    public function register_by_admin_succeeds_with_custom_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/register', [
                'name'                  => 'New Admin',
                'email'                 => 'newadmin@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'role'                  => 'admin', // Admin diperbolehkan mengisi role kustom
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'role'  => 'admin',
        ]);
    }

    #[Test]
    public function register_fails_for_logged_in_non_admin(): void
    {
        $user = User::factory()->asUser()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/register', [
                'name'                  => 'Another User',
                'email'                 => 'another@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertForbidden() // HTTP 403
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Akses ditolak. Pengguna yang sudah login tidak dapat melakukan registrasi.');
    }

    #[Test]
    public function register_fails_with_invalid_input(): void
    {
        // Uji konfirmasi password tidak sesuai
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'User Test',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        // Uji duplikasi email
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'User Test',
            'email'                 => 'existing@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 3 — LOGOUT
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function logout_succeeds_for_authenticated_user(): void
    {
        $user = User::factory()->asUser()->create();

        // Buat token asli melalui Sanctum agar currentAccessToken tidak null
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/logout');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout berhasil. Token telah dihapus.');
    }

    #[Test]
    public function logout_fails_for_unauthenticated_user(): void
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertUnauthorized(); // HTTP 401
    }

    // ─────────────────────────────────────────────────────────
    //  SKENARIO 4 — RESET PASSWORD (CHANGE PASSWORD)
    // ─────────────────────────────────────────────────────────

    #[Test]
    public function regular_user_can_reset_own_password_with_valid_old_password(): void
    {
        $user = User::factory()->asUser()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/reset-password', [
                'old_password'              => 'oldpassword123',
                'new_password'              => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password berhasil direset.');

        // Pastikan password baru ter-update dan bisa diverifikasi
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function regular_user_cannot_reset_own_password_with_invalid_old_password(): void
    {
        $user = User::factory()->asUser()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/reset-password', [
                'old_password'              => 'wrongpassword',
                'new_password'              => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['old_password']);
    }

    #[Test]
    public function admin_can_reset_password_of_any_user_without_old_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->asUser()->create([
            'email'    => 'target@example.com',
            'password' => Hash::make('targetpassword'),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/reset-password', [
                'email'                     => 'target@example.com',
                'new_password'              => 'adminreset123',
                'new_password_confirmation' => 'adminreset123',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        // Verifikasi password target ter-update
        $user->refresh();
        $this->assertTrue(Hash::check('adminreset123', $user->password));
    }

    #[Test]
    public function reset_password_fails_with_unmatched_new_password_confirmation(): void
    {
        $user = User::factory()->asUser()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/reset-password', [
                'old_password'              => 'oldpassword123',
                'new_password'              => 'newpassword123',
                'new_password_confirmation' => 'mismatch123',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    }
}
