# Gambaran Arsitektur API

Dokumen ini menjelaskan komponen arsitektur utama proyek Laravel 12 API, fokus pada **Versioning**, **Transformasi Data (Transformers)**, **Autentikasi dengan Sanctum (token gaya JWT)**, dan **Rate Limiting**. Setiap bagian berisi deskripsi singkat, tujuan, dan contoh potongan kode relevan dari proyek.

---

## 1. Versioning API

**Tujuan**: Memungkinkan evolusi API secara backward‑compatible. Klien dapat menargetkan versi tertentu tanpa terpengaruh saat ditambahkan endpoint baru.

**Implementasi**:
- Semua route dibungkus dalam prefix `v1` menggunakan grup route Laravel.
- Prefix versi menjadi bagian dari URL (misalnya `/api/v1/posts`).

**File Kunci**: `routes/api.php`

```php
<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ================================================================
// API v1 — Semua route dibungkus dalam prefix 'v1'
// ================================================================
Route::prefix('v1')->group(function () {
    // PUBLIC ROUTES (tidak butuh login)
    // Login: dibatasi 5 request per menit per IP (cegah brute force)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    // Register dapat diakses oleh publik atau admin yang sudah login
    Route::post('/register', [AuthController::class, 'register']);

    // PROTECTED ROUTES (butuh login / Bearer token)
    // Rate limit: 60 request per menit per user (throttle:api)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::apiResource('posts', PostController::class);
        Route::apiResource('comments', CommentController::class);
    });
});
```

**Cara kerja**:
1. Pemanggilan `prefix('v1')` menambahkan `/v1` ke semua route yang didefinisikan.
2. Ketika diperlukan versi mayor baru, cukup tambahkan grup baru misalnya `prefix('v2')` di samping yang lama, sehingga klien lama tetap dapat berfungsi.

---

## 2. Transformasi Data (Transformers)

**Tujuan**: Mengubah model Eloquent menjadi struktur JSON yang bersih dan konsisten untuk respons API, serupa dengan *Fractal Transformer*.

**Implementasi**:
- **Resources** Laravel berperan sebagai transformer.
- Setiap model (Post, Comment, User) memiliki kelas resource tersendiri yang mendefinisikan field output serta relasi yang harus disertakan.

**File Kunci**:
- `app/Http/Resources/PostResource.php`
- `app/Http/Resources/CommentResource.php`
- `app/Http/Resources/UserResource.php`

### Contoh – `PostResource`
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Ini setara dengan method transform() di Fractal Transformer.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'status'     => $this->status,
            'content'    => $this->content,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            // Relasi: tampilkan data user pemilik post (nested resource)
            'user'       => new UserResource($this->whenLoaded('user')),
            // Relasi: tampilkan daftar comment (nested collection)
            'comments'   => CommentResource::collection($this->whenLoaded('comments')),
            // Link ke detail post
            'link'       => "/api/v1/posts/{$this->id}",
        ];
    }
}
```

**Cara kerja**:
- Metode `toArray` menerima model Eloquent yang sedang diproses (`$this`).
- `whenLoaded` memastikan relasi hanya di‑transform bila sudah di‑eager‑load, mencegah query N+1.
- Pada controller biasanya mengembalikan resource, misalnya `return PostResource::collection($posts);`.

---

## 3. Autentikasi dengan Sanctum (Token gaya JWT)

**Tujuan**: Menyediakan autentikasi berbasis token stateless yang cocok untuk SPA dan aplikasi mobile. Sanctum mengeluarkan **personal access token** yang berperilaku seperti JWT (berisi payload, ditandatangani, dan menyertakan ID pengguna).

**Implementasi**:
- Endpoint `login` memvalidasi kredensial lalu membuat token melalui `User::createToken()`.
- Token dikirim kembali ke klien dan digunakan pada header `Authorization: Bearer <token>` untuk route yang dilindungi.

**File Kunci**:
- `app/Http/Controllers/AuthController.php` (endpoint login)
- `app/Services/AuthService.php` (logika pembuatan token)

### Pembuatan token (AuthService)
```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
            // Sanctum creates a plain‑text personal access token
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'user'         => $user,
        ];
    }
}
```

### Penggunaan di controller login
```php
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
        'user' => [
            'id'    => $result['user']->id,
            'name'  => $result['user']->name,
            'email' => $result['user']->email,
            'role'  => $result['user']->role,
        ],
    ]);
}
```

**Cara kerja**:
1. Kredensial divalidasi.
2. Jika valid, Sanctum membuat personal access token yang terikat pada pengguna.
3. Token disimpan di sisi klien dan dikirim pada setiap permintaan selanjutnya.
4. Middleware `auth:sanctum` memverifikasi token dan mengisi `Auth::user()`.

---

## 4. Rate Limiting

**Tujuan**: Melindungi API dari penyalahgunaan (brute‑force login, DDoS) dengan membatasi jumlah permintaan per menit.

**Implementasi**:
- Middleware **Throttle** Laravel diterapkan per‑route.
- Dua batas yang berbeda didefinisikan:
  - `throttle:login` – 5 permintaan per menit per IP untuk endpoint login.
  - `throttle:api` – 60 permintaan per menit per pengguna yang terautentikasi untuk semua route yang dilindungi.

**Potongan kode (routes/api.php)**
```php
// Login: dibatasi 5 request per menit per IP (cegah brute force)
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Protected routes – rate limit 60 req/min per authenticated user
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // ... other routes ...
});
```

**Cara kerja**:
1. Middleware `throttle` menggunakan driver cache Laravel untuk menyimpan hitungan permintaan yang di‑key‑kan berdasarkan IP klien (untuk route tanpa autentikasi) atau ID pengguna yang terautentikasi (untuk route yang dilindungi Sanctum).
2. Ketika batas terlampaui, Laravel otomatis mengembalikan respons **429 Too Many Requests** dengan header `Retry-After`.

---

## 5. Tabel Referensi Cepat

| Komponen | File(s) | Fungsi Utama | Contoh Penggunaan |
|----------|----------|--------------|-------------------|
| Versioning | `routes/api.php` | Menambahkan prefix `/v1` (atau versi selanjutnya) pada semua route | `Route::prefix('v1')->group(...);` |
| Transformer | `app/Http/Resources/*.php` | Membentuk respons API, mengelola relasi ter‑nest | `return new PostResource($post);` |
| Sanctum JWT | `AuthService.php`, `AuthController.php` | Membuat & memvalidasi personal access token | `$user->createToken('auth_token')->plainTextToken` |
| Rate Limit | `routes/api.php` (middleware) | Batasi frekuensi request per IP/pengguna | `->middleware('throttle:login')` |

---

### Ringkasan
- **Versioning** menjaga kestabilan API pada setiap rilis.
- **Transformers (Resources)** menyediakan representasi data yang bersih dan dapat dipakai ulang.
- **Sanctum** menawarkan mekanisme token sederhana ala JWT untuk autentikasi stateless.
- **Rate limiting** melindungi layanan dari penggunaan berlebih dan serangan.

Dengan pola‑pola ini, API Laravel menjadi modern, mudah dipelihara, siap produksi, dan mudah untuk dikembangkan lebih lanjut.
