# 🚀 Laravel 12 REST API — Blog System

> API RESTful berbasis **Laravel 12** dengan fitur autentikasi Sanctum, manajemen post, komentar, serta sistem role (`admin` / `user`). Dibangun menggunakan pola **Service Layer** dan **API Resources** untuk arsitektur yang bersih dan terstruktur.

---

## 📋 Daftar Isi

- [Teknologi & Dependensi](#-teknologi--dependensi)
- [Struktur Folder](#-struktur-folder)
- [Diagram Relasi Database (ERD)](#-diagram-relasi-database-erd)
- [Instalasi & Setup](#-instalasi--setup)
- [Akun Default (Seeder)](#-akun-default-seeder)
- [Dokumentasi Endpoint API](#-dokumentasi-endpoint-api)
  - [Authentication](#-authentication)
  - [Posts](#-posts)
  - [Comments](#-comments)
- [Sistem Role & Otorisasi](#-sistem-role--otorisasi)
- [Rate Limiting](#-rate-limiting)
- [Format Response](#-format-response)
- [Error Handling](#-error-handling)
- [🧪 Automated Testing](#-automated-testing)
  - [Setup & Konfigurasi](#setup--konfigurasi)
  - [Struktur Test](#struktur-test)
  - [Menjalankan Test](#menjalankan-test)
  - [Skenario Test](#skenario-test)

---

## 🛠 Teknologi & Dependensi

| Komponen | Versi | Keterangan |
|---|---|---|
| PHP | ^8.2 | Runtime utama |
| Laravel Framework | ^12.0 | Framework utama |
| Laravel Sanctum | ^4.0 | Autentikasi berbasis token (Bearer) |
| Laravel Tinker | ^2.10 | REPL interaktif untuk debugging |
| Knuckleswtf/Scribe | ^5.9 | Auto-generate dokumentasi API |
| MySQL | — | Database utama (dapat diganti SQLite untuk dev) |
| FakerPHP | ^1.23 | Generate data palsu untuk seeder / testing |
| PHPUnit | ^11.5 | Framework automated testing (bawaan Laravel) |
| SQLite `:memory:` | — | Database in-memory khusus untuk sesi testing |

---

## 📁 Struktur Folder

```
laravel_12_tester/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/           # Menerima request, validasi input, kembalikan response
│   │   │   ├── Controller.php     # Base controller (kosong, titik ekstensi)
│   │   │   ├── AuthController.php # Login, Register, Logout, Reset Password
│   │   │   ├── PostController.php # CRUD Post
│   │   │   └── CommentController.php # CRUD Comment
│   │   │
│   │   └── Resources/             # Transformasi output JSON (pengganti Fractal)
│   │       ├── UserResource.php   # Format output data User
│   │       ├── PostResource.php   # Format output data Post (+ relasi user & comments)
│   │       └── CommentResource.php # Format output data Comment (+ relasi user)
│   │
│   ├── Models/                    # Representasi tabel database & relasinya
│   │   ├── User.php               # Model User (HasApiTokens, role admin/user)
│   │   ├── Post.php               # Model Post (belongsTo User, hasMany Comment)
│   │   └── Comment.php            # Model Comment (belongsTo User & Post)
│   │
│   ├── Services/                  # Business logic dipisahkan dari controller
│   │   ├── AuthService.php        # Logic: login, register, logout, reset password
│   │   ├── PostService.php        # Logic: CRUD post + otorisasi kepemilikan
│   │   └── CommentService.php     # Logic: CRUD comment + otorisasi kepemilikan
│   │
│   └── Providers/                 # Service Provider Laravel (default)
│
├── bootstrap/
│   └── app.php                    # Entry point konfigurasi aplikasi:
│                                  #   - Routing (web, api, console)
│                                  #   - Rate Limiter (api: 60/mnt, login: 5/mnt)
│                                  #   - Custom Exception Handler (JSON error 404, 403)
│
├── config/                        # File konfigurasi framework
│   ├── app.php                    # Konfigurasi dasar aplikasi
│   ├── auth.php                   # Guard & provider autentikasi
│   ├── database.php               # Konfigurasi koneksi database
│   ├── sanctum.php                # Konfigurasi Laravel Sanctum
│   └── ...                        # Konfigurasi lainnya (cache, queue, dll.)
│
├── database/
│   ├── migrations/                # Skema tabel database (dieksekusi berurutan)
│   │   ├── ..._create_users_table.php          # Tabel users (name, email, password)
│   │   ├── ..._create_cache_table.php          # Tabel cache (Laravel cache driver)
│   │   ├── ..._create_jobs_table.php           # Tabel queue jobs
│   │   ├── ..._create_personal_access_tokens_table.php  # Tabel token Sanctum
│   │   ├── ..._create_posts_table.php          # Tabel posts (title, status, content)
│   │   ├── ..._create_comments_table.php       # Tabel comments (comment, post_id)
│   │   └── ..._add_role_to_users_table.php     # Tambah kolom 'role' ke users
│   │
│   ├── seeders/
│   │   └── DatabaseSeeder.php     # Isi data awal: 1 admin, 6 user, 7 post, 7 comment
│   │
│   └── factories/                 # Factory untuk generate data dummy saat testing
│       ├── UserFactory.php        # Factory User + state: admin(), asUser()
│       └── PostFactory.php        # Factory Post + state: published(), draft()
│
├── routes/
│   ├── api.php                    # Semua route API (prefix: /api/v1/...)
│   ├── web.php                    # Route web (default kosong)
│   └── console.php                # Route untuk Artisan command schedule
│
├── storage/                       # File yang di-generate aplikasi (log, cache, upload)
├── tests/                         # Automated test (PHPUnit)
│   ├── TestCase.php               # Base test class (use RefreshDatabase)
│   ├── Feature/
│   │   └── PostTest.php           # 13 skenario API test (401/201/200/403/404/422)
│   └── Unit/
│       └── PostServiceTest.php    # 6 skenario unit test business logic
├── public/                        # Entry point web server (index.php)
├── resources/                     # View blade, JS, CSS (tidak digunakan di API-only)
├── vendor/                        # Dependensi Composer (jangan diedit manual)
│
├── .env                           # Konfigurasi environment (JANGAN commit ke Git!)
├── .env.example                   # Template .env untuk onboarding developer baru
├── composer.json                  # Definisi dependensi PHP
├── artisan                        # CLI Laravel
├── phpunit.xml                    # Konfigurasi PHPUnit (SQLite :memory: aktif)
└── .env.testing                   # Override environment khusus testing
```

### Penjelasan Folder Utama

| Folder / File | Fungsi |
|---|---|
| `app/Http/Controllers/` | Menerima HTTP request, melakukan validasi input, memanggil Service, dan mengembalikan JSON response. Tidak mengandung business logic. |
| `app/Http/Resources/` | Mengontrol format data JSON yang dikembalikan ke klien. Menggantikan pola Fractal Transformer pada Lumen lama. |
| `app/Models/` | Merepresentasikan tabel database. Mendefinisikan relasi Eloquent (hasMany, belongsTo) dan atribut yang bisa diisi (`$fillable`). |
| `app/Services/` | Tempat seluruh business logic diproses. Dipisahkan dari controller agar mudah diuji dan digunakan ulang. |
| `bootstrap/app.php` | Titik pusat konfigurasi aplikasi Laravel 12. Mendefinisikan routing, rate limiter, dan custom exception handler. |
| `routes/api.php` | Mendefinisikan semua endpoint API di bawah prefix `/api/v1/`. |
| `database/migrations/` | Mendefinisikan struktur tabel secara programatik. Dieksekusi dengan `php artisan migrate`. |
| `database/seeders/` | Mengisi database dengan data awal untuk keperluan pengembangan dan pengujian. |

---

## 🗄 Diagram Relasi Database (ERD)

```
┌─────────────────┐         ┌─────────────────────┐         ┌──────────────────┐
│      users      │         │        posts         │         │     comments     │
├─────────────────┤         ├─────────────────────┤         ├──────────────────┤
│ id (PK)         │◄──┐     │ id (PK)              │◄──┐     │ id (PK)          │
│ name            │   │     │ title                │   │     │ comment          │
│ email (unique)  │   │     │ status (draft|pub)   │   │     │ post_id (FK) ────┘
│ password        │   └─────│ user_id (FK)         │   └─────│ user_id (FK) ────┐
│ role (admin|usr)│         │ content              │         │ created_at       │
│ created_at      │         │ created_at           │         │ updated_at       │
│ updated_at      │◄────────│ updated_at           │         └──────────────────┘
└─────────────────┘         └─────────────────────┘
        ▲
        │ HasApiTokens (Sanctum)
        │
┌───────────────────────────┐
│  personal_access_tokens   │
├───────────────────────────┤
│ id (PK)                   │
│ tokenable_type            │
│ tokenable_id (FK→users)   │
│ name                      │
│ token (hashed)            │
│ abilities                 │
│ last_used_at              │
│ expires_at                │
│ created_at / updated_at   │
└───────────────────────────┘
```

**Relasi:**
- `User` **memiliki banyak** `Post` (`hasMany`)
- `User` **memiliki banyak** `Comment` (`hasMany`)
- `Post` **dimiliki oleh** `User` (`belongsTo`)
- `Post` **memiliki banyak** `Comment` (`hasMany`)
- `Comment` **dimiliki oleh** `User` (`belongsTo`)
- `Comment` **dimiliki oleh** `Post` (`belongsTo`)

---

## ⚙️ Instalasi & Setup

### Prasyarat

- PHP >= 8.2
- Composer
- MySQL (atau MariaDB)
- Node.js & NPM (untuk asset Vite, opsional untuk API-only)

### Langkah Instalasi

```bash
# 1. Clone repository
git clone <url-repository>
cd laravel_12_tester

# 2. Install dependensi PHP
composer install

# 3. Salin file environment
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Konfigurasi database di .env
#    Ubah nilai berikut sesuai setup lokal:
#    DB_DATABASE=blog2
#    DB_USERNAME=root
#    DB_PASSWORD=

# 6. Jalankan migrasi dan seeder
php artisan migrate --seed

# 7. Jalankan development server
php artisan serve
```

Server berjalan di: **`http://localhost:8000`**

Base URL API: **`http://localhost:8000/api/v1`**

---

## 👤 Akun Default (Seeder)

Setelah menjalankan `php artisan migrate --seed`, akun berikut tersedia:

| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@gmail.com` | `password` |
| **User** | *(6 akun acak via Faker)* | `password` |

> Seeder juga membuat **7 Post** dan **7 Comment** secara acak untuk keperluan testing.

---

## 📡 Dokumentasi Endpoint API

**Base URL:** `http://localhost:8000/api/v1`

**Header yang diperlukan untuk endpoint terproteksi:**
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

---

### 🔐 Authentication

#### `POST /api/v1/login`

Login dan mendapatkan Bearer Token.

- **Akses:** Public
- **Rate Limit:** 5 request/menit per IP (anti brute-force)

**Request Body:**
```json
{
    "email": "admin@gmail.com",
    "password": "password"
}
```

**Response 200 OK:**
```json
{
    "success": true,
    "message": "Login berhasil.",
    "access_token": "1|abc123def456...",
    "token_type": "Bearer",
    "user": {
        "id": 1,
        "name": "Admin Utama",
        "email": "admin@gmail.com",
        "role": "admin"
    }
}
```

**Response 422 Unprocessable Entity:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["Alamat email atau password salah."]
    }
}
```

---

#### `POST /api/v1/register`

Mendaftarkan akun baru.

- **Akses:** Public (role otomatis `user`) **ATAU** Admin yang sudah login (bisa menentukan role)
- **Rate Limit:** Throttle API umum (60/menit)

> ⚠️ User yang sudah login dengan role `user` **tidak dapat** mengakses endpoint ini (403).

**Request Body (Public):**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Request Body (Admin — opsional tambahkan role):**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "admin"
}
```

**Response 201 Created:**
```json
{
    "success": true,
    "message": "Registrasi berhasil.",
    "user": {
        "id": 8,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
    }
}
```

**Response 403 Forbidden:**
```json
{
    "success": false,
    "message": "Akses ditolak. Pengguna yang sudah login tidak dapat melakukan registrasi."
}
```

---

#### `POST /api/v1/logout` 🔒

Logout dan menghapus (revoke) token yang aktif.

- **Akses:** Terproteksi (perlu Bearer Token)

**Response 200 OK:**
```json
{
    "success": true,
    "message": "Logout berhasil. Token telah dihapus."
}
```

---

#### `POST /api/v1/reset-password` 🔒

Reset password. Behavior berbeda antara `admin` dan `user`.

- **Akses:** Terproteksi

**Request Body (User biasa — wajib kirim old_password):**
```json
{
    "old_password": "password",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

**Request Body (Admin — bisa reset password user lain):**
```json
{
    "email": "john@example.com",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

**Response 200 OK:**
```json
{
    "success": true,
    "message": "Password berhasil direset."
}
```

---

### 📝 Posts

Semua endpoint Post bersifat **terproteksi** (perlu Bearer Token).

#### `GET /api/v1/posts` 🔒

Menampilkan daftar post.

- **User biasa:** Hanya melihat post miliknya sendiri.
- **Admin:** Melihat semua post dari semua user.

**Response 200 OK:**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Judul Post Pertama",
            "status": "published",
            "content": "Isi konten post...",
            "created_at": "2026-04-20 10:00:00",
            "updated_at": "2026-04-20 10:00:00",
            "user": null,
            "comments": [],
            "link": "/api/v1/posts/1"
        }
    ]
}
```

---

#### `POST /api/v1/posts` 🔒

Membuat post baru. Post otomatis dimiliki oleh user yang sedang login.

**Request Body:**
```json
{
    "title": "Judul Post Baru",
    "content": "Isi konten post yang baru dibuat.",
    "status": "published"
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `title` | string | ✅ | Maksimal 100 karakter |
| `content` | string | ✅ | Isi post |
| `status` | string | ❌ | `draft` atau `published`. Default: `draft` |

**Response 201 Created:**
```json
{
    "data": {
        "id": 8,
        "title": "Judul Post Baru",
        "status": "published",
        "content": "Isi konten post yang baru dibuat.",
        "created_at": "2026-04-27 10:00:00",
        "updated_at": "2026-04-27 10:00:00",
        "user": null,
        "comments": [],
        "link": "/api/v1/posts/8"
    }
}
```

---

#### `GET /api/v1/posts/{id}` 🔒

Menampilkan detail satu post berdasarkan ID.

- **User biasa:** Hanya bisa melihat post miliknya sendiri.
- **Admin:** Bisa melihat post siapa saja.

**Response 200 OK:**
```json
{
    "data": {
        "id": 1,
        "title": "Judul Post Pertama",
        "status": "published",
        "content": "Isi konten post...",
        "created_at": "2026-04-20 10:00:00",
        "updated_at": "2026-04-20 10:00:00",
        "user": null,
        "comments": [],
        "link": "/api/v1/posts/1"
    }
}
```

---

#### `PUT /api/v1/posts/{id}` 🔒

Mengubah data post. Hanya **pemilik post** atau **admin** yang bisa mengubah.

**Request Body (semua field opsional):**
```json
{
    "title": "Judul yang Diperbarui",
    "content": "Konten yang sudah diperbarui.",
    "status": "published"
}
```

**Response 200 OK:**
```json
{
    "data": {
        "id": 1,
        "title": "Judul yang Diperbarui",
        "status": "published",
        "content": "Konten yang sudah diperbarui.",
        "created_at": "2026-04-20 10:00:00",
        "updated_at": "2026-04-27 12:00:00",
        "user": null,
        "comments": [],
        "link": "/api/v1/posts/1"
    }
}
```

---

#### `DELETE /api/v1/posts/{id}` 🔒

Menghapus post. Hanya **pemilik post** atau **admin** yang bisa menghapus.

**Response 200 OK:**
```json
{
    "id": 1,
    "deleted": true
}
```

---

### 💬 Comments

Semua endpoint Comment bersifat **terproteksi** (perlu Bearer Token).

#### `GET /api/v1/comments` 🔒

Menampilkan daftar komentar.

- **User biasa:** Hanya komentar miliknya sendiri.
- **Admin:** Semua komentar.

**Response 200 OK:**
```json
{
    "data": [
        {
            "id": 1,
            "comment": "Komentar pertama yang menarik.",
            "post_id": 1,
            "created_at": "2026-04-20 10:00:00",
            "updated_at": "2026-04-20 10:00:00",
            "user": {
                "id": 1,
                "name": "Admin Utama",
                "email": "admin@gmail.com",
                "role": "admin"
            }
        }
    ]
}
```

---

#### `POST /api/v1/comments` 🔒

Membuat komentar baru pada post tertentu.

**Request Body:**
```json
{
    "post_id": 1,
    "comment": "Artikel yang sangat informatif dan bermanfaat!"
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `post_id` | integer | ✅ | ID post yang ada di database |
| `comment` | string | ✅ | Isi komentar, maksimal 250 karakter |

**Response 201 Created:**
```json
{
    "data": {
        "id": 8,
        "comment": "Artikel yang sangat informatif dan bermanfaat!",
        "post_id": 1,
        "created_at": "2026-04-27 10:00:00",
        "updated_at": "2026-04-27 10:00:00",
        "user": {
            "id": 1,
            "name": "Admin Utama",
            "email": "admin@gmail.com",
            "role": "admin"
        }
    }
}
```

---

#### `GET /api/v1/comments/{id}` 🔒

Menampilkan detail satu komentar berdasarkan ID.

**Response 200 OK:**
```json
{
    "data": {
        "id": 1,
        "comment": "Komentar pertama.",
        "post_id": 1,
        "created_at": "2026-04-20 10:00:00",
        "updated_at": "2026-04-20 10:00:00",
        "user": null
    }
}
```

---

#### `PUT /api/v1/comments/{id}` 🔒

Mengubah isi komentar. Hanya **pemilik komentar** atau **admin** yang bisa mengubah.

**Request Body:**
```json
{
    "comment": "Komentar yang sudah diperbarui."
}
```

**Response 200 OK:**
```json
{
    "data": {
        "id": 1,
        "comment": "Komentar yang sudah diperbarui.",
        "post_id": 1,
        "created_at": "2026-04-20 10:00:00",
        "updated_at": "2026-04-27 12:00:00",
        "user": null
    }
}
```

---

#### `DELETE /api/v1/comments/{id}` 🔒

Menghapus komentar. Hanya **pemilik komentar** atau **admin** yang bisa menghapus.

**Response 200 OK:**
```json
{
    "id": 1,
    "deleted": true
}
```

---

## 🛡 Sistem Role & Otorisasi

Project ini menggunakan sistem dua role:

| Role | Deskripsi |
|---|---|
| `admin` | Akses penuh. Bisa melihat/edit/hapus semua data milik siapa pun. Bisa register user dengan role apapun. Bisa reset password user lain. |
| `user` | Akses terbatas. Hanya bisa CRUD data miliknya sendiri. Tidak bisa register (jika sudah login). Hanya bisa reset password diri sendiri. |

### Matriks Otorisasi

| Aksi | Admin | User (pemilik) | User (bukan pemilik) |
|---|---|---|---|
| Lihat semua Post/Comment | ✅ | ❌ (hanya miliknya) | ❌ |
| Buat Post/Comment | ✅ | ✅ | ✅ |
| Edit Post/Comment | ✅ | ✅ | ❌ (403) |
| Hapus Post/Comment | ✅ | ✅ | ❌ (403) |
| Register user baru | ✅ (bisa set role) | ❌ (403) | ✅ (role default `user`) |
| Reset password user lain | ✅ | ❌ | ❌ |

---

## ⏱ Rate Limiting

Dikonfigurasi di `bootstrap/app.php`:

| Rate Limiter | Batasan | Berlaku Pada | Tujuan |
|---|---|---|---|
| `throttle:login` | **5 request/menit** per kombinasi email+IP | `POST /api/v1/login` | Mencegah brute-force login |
| `throttle:api` | **60 request/menit** per user ID (atau IP jika belum login) | Semua route terproteksi | Mencegah abuse API |

Ketika batas terlampaui, response yang dikirim:

```
HTTP 429 Too Many Requests
Retry-After: {detik}
```

---

## 📄 Format Response

### Sukses (data tunggal)
```json
{
    "data": { ... }
}
```

### Sukses (koleksi/list)
```json
{
    "data": [ ... ]
}
```

### Sukses (aksi tanpa data — delete, logout)
```json
{
    "success": true,
    "message": "..."
}
```

### Gagal (validasi — 422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Pesan error validasi."]
    }
}
```

---

## ❌ Error Handling

Custom error handler dikonfigurasi di `bootstrap/app.php` untuk memastikan semua route `/api/*` selalu mengembalikan JSON:

| HTTP Status | Kondisi | Contoh Response |
|---|---|---|
| `401 Unauthorized` | Token tidak ada atau tidak valid | `{"message": "Unauthenticated."}` |
| `403 Forbidden` | Tidak punya izin untuk aksi tersebut | `{"success": false, "message": "Akses ditolak..."}` |
| `404 Not Found` | Endpoint atau data tidak ditemukan | `{"success": false, "message": "Endpoint atau Data tidak ditemukan (404 Not Found)."}` |
| `422 Unprocessable` | Validasi input gagal | `{"message": "...", "errors": {...}}` |
| `429 Too Many Requests` | Rate limit terlampaui | Header `Retry-After` |
| `500 Server Error` | Error internal server | `{"message": "Server Error"}` |

---

## 📚 Perintah Artisan yang Berguna

```bash
# Jalankan server development
php artisan serve

# Jalankan migrasi
php artisan migrate

# Reset dan isi ulang database dengan data seeder
php artisan migrate:fresh --seed

# Generate dokumentasi API (Scribe)
php artisan scribe:generate

# Lihat semua route yang terdaftar
php artisan route:list

# Bersihkan cache konfigurasi
php artisan config:clear

# Masuk ke REPL interaktif Laravel
php artisan tinker
```

---

## 🗺 Ringkasan Semua Endpoint

| Method | Endpoint | Akses | Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/login` | Public | Login & dapatkan token |
| `POST` | `/api/v1/register` | Public / Admin | Registrasi akun baru |
| `POST` | `/api/v1/logout` | 🔒 Auth | Logout & hapus token |
| `POST` | `/api/v1/reset-password` | 🔒 Auth | Reset password |
| `GET` | `/api/v1/posts` | 🔒 Auth | List semua post |
| `POST` | `/api/v1/posts` | 🔒 Auth | Buat post baru |
| `GET` | `/api/v1/posts/{id}` | 🔒 Auth | Detail post |
| `PUT` | `/api/v1/posts/{id}` | 🔒 Auth | Update post |
| `DELETE` | `/api/v1/posts/{id}` | 🔒 Auth | Hapus post |
| `GET` | `/api/v1/comments` | 🔒 Auth | List semua komentar |
| `POST` | `/api/v1/comments` | 🔒 Auth | Buat komentar baru |
| `GET` | `/api/v1/comments/{id}` | 🔒 Auth | Detail komentar |
| `PUT` | `/api/v1/comments/{id}` | 🔒 Auth | Update komentar |
| `DELETE` | `/api/v1/comments/{id}` | 🔒 Auth | Hapus komentar |

---

## 🧪 Automated Testing

> Mengadopsi konsep **Bab 8** — pengujian otomatis berbasis **PHPUnit** dengan database **SQLite `:memory:`** untuk isolasi dan kecepatan penuh.

**Hasil uji terbaru:** `22 tests passed · 46 assertions · ⚡ 1.75s`

---

### Setup & Konfigurasi

#### `phpunit.xml` — Database Testing

Laravel sudah menyertakan `phpunit.xml` di root project. Konfigurasi berikut mengaktifkan SQLite `:memory:` agar setiap test run menggunakan database baru yang terisolasi:

```xml
<php>
    <env name="APP_ENV"          value="testing"/>
    <env name="DB_CONNECTION"    value="sqlite"/>     <!-- ← SQLite, bukan MySQL -->
    <env name="DB_DATABASE"      value=":memory:"/>   <!-- ← in-memory, super cepat -->
    <env name="BCRYPT_ROUNDS"    value="4"/>          <!-- ← hash lebih cepat saat test -->
    <env name="CACHE_STORE"      value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER"   value="array"/>
</php>
```

#### `.env.testing` — Override Environment

File ini dibaca Laravel **secara otomatis** saat `APP_ENV=testing`, meng-override `.env` utama:

```ini
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
QUEUE_CONNECTION=sync
BCRYPT_ROUNDS=4
MAIL_MAILER=array
```

> ⚠️ **Penting:** Database production (MySQL) **tidak pernah tersentuh** saat testing karena SQLite `:memory:` dibuat dari nol dan hilang setelah proses test selesai.

---

### Struktur Test

```
tests/
├── TestCase.php               ← Base class: use RefreshDatabase (berlaku global)
│
├── Feature/                   ← API / HTTP testing (end-to-end)
│   └── PostTest.php           ← 13 skenario uji endpoint /api/v1/posts
│
└── Unit/                      ← Business logic testing (tanpa HTTP)
    └── PostServiceTest.php    ← 6 skenario uji method di PostService
```

#### `tests/TestCase.php` — Base Test Class

```php
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;  // ← Otomatis jalankan migration & reset DB setiap test
}
```

`RefreshDatabase` memastikan setiap test method mendapat database yang **bersih**. Karena menggunakan SQLite `:memory:`, seluruh siklus migration selesai dalam hitungan milidetik.

---

#### Factory — Generate Data Dummy

Factory digunakan untuk membuat data dummy secara otomatis tanpa perlu menulis SQL manual:

**`UserFactory`** — membuat user dengan role tertentu:
```php
// User biasa
$user = User::factory()->asUser()->create();

// User admin
$admin = User::factory()->admin()->create();

// 5 user sekaligus
$users = User::factory()->count(5)->asUser()->create();
```

**`PostFactory`** — membuat post dengan status tertentu:
```php
// Post acak milik user tertentu
$post = Post::factory()->create(['user_id' => $user->id]);

// 3 post published
Post::factory()->count(3)->published()->create(['user_id' => $user->id]);

// Post draft
$draft = Post::factory()->draft()->create(['user_id' => $user->id]);
```

---

### Menjalankan Test

```bash
# Jalankan SEMUA test
php artisan test

# Hanya Feature tests (API/HTTP)
php artisan test --testsuite=Feature

# Hanya Unit tests (business logic)
php artisan test --testsuite=Unit

# Jalankan satu file test
php artisan test tests/Feature/PostTest.php

# Filter berdasarkan nama method
php artisan test --filter=authenticated_user_can_create_post

# Output detail per method
php artisan test --verbose

# Buat file test baru
php artisan make:test NamaTest           # Feature test
php artisan make:test NamaTest --unit    # Unit test
```

---

### Skenario Test

#### `tests/Feature/PostTest.php` — 13 Skenario

| # | Skenario | HTTP | Method |
|---|---|---|---|
| 1 | Tanpa token → GET `/posts` | `401` | `unauthenticated_user_cannot_list_posts` |
| 2 | Tanpa token → POST `/posts` | `401` | `unauthenticated_user_cannot_create_post` |
| 3 | Tanpa token → PUT `/posts/{id}` | `401` | `unauthenticated_user_cannot_update_post` |
| 4 | Login + buat post → validasi JSON Resource | `201` | `authenticated_user_can_create_post` |
| 5 | Login + list post → hanya milik sendiri | `200` | `authenticated_user_can_list_own_posts` |
| 6 | Admin login → melihat semua post | `200` | `admin_can_see_all_posts` |
| 7 | Login + lihat detail post sendiri | `200` | `user_can_view_own_post` |
| 8 | User A edit post milik User B | `403` | `user_cannot_update_post_of_another_user` |
| 9 | User A lihat detail post milik User B | `403` | `user_cannot_view_post_of_another_user` |
| 10 | User biasa coba hapus post (non-admin) | `403` | `regular_user_cannot_delete_any_post` |
| 11 | Request ke ID post yang tidak ada | `404` | `returns_404_for_nonexistent_post` |
| 12 | Update ke ID post yang tidak ada | `404` | `returns_404_when_updating_nonexistent_post` |
| 13 | Admin hapus post siapapun | `200` | `admin_can_delete_any_post` |
| 14 | Buat post tanpa field `title` | `422` | `cannot_create_post_without_required_title` |

**Contoh skenario 401 (Unauthenticated):**
```php
#[Test]
public function unauthenticated_user_cannot_list_posts(): void
{
    $response = $this->getJson('/api/v1/posts');
    $response->assertUnauthorized(); // HTTP 401
}
```

**Contoh skenario 201 (Authenticated + validasi JSON Resource):**
```php
#[Test]
public function authenticated_user_can_create_post(): void
{
    $user = User::factory()->asUser()->create();

    $response = $this->actingAs($user, 'sanctum')  // Simulasi login
        ->postJson('/api/v1/posts', [
            'title'   => 'Belajar Automated Testing',
            'content' => 'Testing adalah kunci kualitas software.',
            'status'  => 'published',
        ]);

    $response
        ->assertCreated()                    // HTTP 201
        ->assertJsonStructure([              // Validasi struktur JSON Resource
            'data' => ['id', 'title', 'status', 'content', 'created_at', 'link'],
        ])
        ->assertJsonPath('data.status', 'published');

    $this->assertDatabaseHas('posts', [      // Verifikasi tersimpan di DB
        'title'   => 'Belajar Automated Testing',
        'user_id' => $user->id,
    ]);
}
```

**Contoh skenario 403 (Forbidden — Otorisasi):**
```php
#[Test]
public function user_cannot_update_post_of_another_user(): void
{
    $userA       = User::factory()->asUser()->create();
    $userB       = User::factory()->asUser()->create();
    $postOfUserA = Post::factory()->create(['user_id' => $userA->id]);

    // userB mencoba edit post milik userA
    $response = $this->actingAs($userB, 'sanctum')
        ->putJson("/api/v1/posts/{$postOfUserA->id}", ['title' => 'Upaya Tidak Sah']);

    $response->assertForbidden(); // HTTP 403
}
```

**Contoh skenario 404 (Not Found):**
```php
#[Test]
public function returns_404_for_nonexistent_post(): void
{
    $user = User::factory()->asUser()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/posts/9999');  // ID tidak ada

    $response->assertNotFound(); // HTTP 404
}
```

---

#### `tests/Unit/PostServiceTest.php` — 6 Skenario

| # | Skenario | Method |
|---|---|---|
| 1 | Admin mendapat semua post | `admin_gets_all_posts` |
| 2 | User biasa hanya lihat post miliknya | `regular_user_gets_only_own_posts` |
| 3 | Non-owner update → melempar HTTP 403 | `update_throws_403_for_non_owner` |
| 4 | Non-admin delete → melempar HTTP 403 | `delete_throws_403_for_non_admin` |
| 5 | Admin hapus post tanpa exception | `admin_can_delete_any_post` |
| 6 | `createPost` menyimpan `user_id` yang benar | `create_post_assigns_correct_user_id` |

Unit test memanggil **service method langsung** tanpa HTTP request:
```php
#[Test]
public function update_throws_403_for_non_owner(): void
{
    $this->expectException(HttpException::class);

    $owner = User::factory()->asUser()->create();
    $other = User::factory()->asUser()->create();
    $post  = Post::factory()->create(['user_id' => $owner->id]);

    // Langsung panggil service — bukan via HTTP
    $this->postService->updatePost($post, ['title' => 'Hack'], $other);
}
```

---

*Dibuat dengan ❤️ menggunakan Laravel 12 — Pemrograman Integratif*