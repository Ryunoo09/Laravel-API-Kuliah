<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->authService->loginUser($credentials);

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'role' => $result['user']->role,
            ]
        ]);
    }
}
