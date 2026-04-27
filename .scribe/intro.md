# Introduction

RESTful API untuk manajemen Posts dan Comments dengan autentikasi berbasis Laravel Sanctum.

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

    Dokumentasi ini menyediakan semua informasi yang diperlukan untuk menggunakan API kami.

    ## Autentikasi
    API ini menggunakan **Bearer Token** via Laravel Sanctum. Untuk mendapatkan token, lakukan request `POST /api/v1/login` dengan email dan password Anda.

    Setelah mendapatkan token, sertakan di setiap request pada header:
    ```
    Authorization: Bearer {YOUR_AUTH_KEY}
    ```

    ## Rate Limiting
    - **Login**: Maksimal 5 request per menit
    - **Endpoint lainnya**: Maksimal 60 request per menit per user

    <aside>Saat Anda scroll ke bawah, Anda akan melihat contoh kode untuk berbagai bahasa pemrograman di area gelap sebelah kanan.</aside>

