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
| FakerPHP | ^1.23 | Generate data palsu untuk seeder |

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
│   └── factories/                 # Factory untuk testing (jika digunakan)
│
├── routes/
│   ├── api.php                    # Semua route API (prefix: /api/v1/...)
│   ├── web.php                    # Route web (default kosong)
│   └── console.php                # Route untuk Artisan command schedule
│
├── storage/                       # File yang di-generate aplikasi (log, cache, upload)
├── tests/                         # Unit & Feature test
├── public/                        # Entry point web server (index.php)
├── resources/                     # View blade, JS, CSS (tidak digunakan di API-only)
├── vendor/                        # Dependensi Composer (jangan diedit manual)
│
├── .env                           # Konfigurasi environment (JANGAN commit ke Git!)
├── .env.example                   # Template .env untuk onboarding developer baru
├── composer.json                  # Definisi dependensi PHP
├── artisan                        # CLI Laravel
└── phpunit.xml                    # Konfigurasi pengujian PHPUnit
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

*Dibuat dengan ❤️ menggunakan Laravel 12 — Pemrograman Integratif*