<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ================================================================
// PUBLIC ROUTES (tidak butuh login)
// ================================================================
Route::post('/login', [AuthController::class, 'login']);

// Register bisa diakses oleh:
//   - User yang belum login (publik) → didaftarkan sebagai 'user' biasa
//   - Admin yang sudah login       → bisa mendaftarkan user dengan role apapun
// User yang sudah login dengan role 'user' akan DITOLAK di controller.
Route::post('/register', [AuthController::class, 'register']);

// ================================================================
// PROTECTED ROUTES (butuh login / Bearer token)
// ================================================================
Route::middleware('auth:sanctum')->group(function () {

    // -- Auth --
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // -- Posts CRUD --
    Route::apiResource('posts', PostController::class);

    // -- Comments CRUD --
    Route::apiResource('comments', CommentController::class);
});
