<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ================================================================
// API v1 — Semua route dibungkus dalam prefix 'v1'
// URL menjadi: /api/v1/login, /api/v1/posts, dst.
// ================================================================
Route::prefix('v1')->group(function () {

    // ============================================================
    // PUBLIC ROUTES (tidak butuh login)
    // ============================================================

    // Login: dibatasi 5 request per menit per IP (cegah brute force)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    // Register bisa diakses oleh:
    //   - User yang belum login (publik) → didaftarkan sebagai 'user' biasa
    //   - Admin yang sudah login       → bisa mendaftarkan user dengan role apapun
    // User yang sudah login dengan role 'user' akan DITOLAK di controller.
    Route::post('/register', [AuthController::class, 'register']);

    // ============================================================
    // PROTECTED ROUTES (butuh login / Bearer token)
    // Rate limit: 60 request per menit per user (throttle:api)
    // ============================================================
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // -- Auth --
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        // -- Posts CRUD --
        Route::apiResource('posts', PostController::class);

        // -- Comments CRUD --
        Route::apiResource('comments', CommentController::class);
    });

});
