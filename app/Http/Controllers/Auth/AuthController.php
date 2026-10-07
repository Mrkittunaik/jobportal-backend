<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function register(RegisterRequest $r)
    {
        return ApiResponse::created($this->auth->register($r->validated()), 'Registration successful');
    }

    public function login(LoginRequest $r)
    {
        return ApiResponse::success($this->auth->login($r->email, $r->password), 'Login successful');
    }

    public function logout(Request $r)
    {
        $this->auth->logout($r->user());
        return ApiResponse::success(null, 'Logged out');
    }

    public function me(Request $r)
    {
        return ApiResponse::success($this->auth->withProfile($r->user()));
    }

    public function forgotPassword(Request $r)
    {
        $r->validate(['email' => ['required', 'email']]);
        $this->auth->sendResetLink($r->email);
        return ApiResponse::success(null, 'If that email exists, a reset link has been sent');
    }

    public function resetPassword(Request $r)
    {
        $d = $r->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $this->auth->resetPassword($d + ['password_confirmation' => $r->password_confirmation]);
        return ApiResponse::success(null, 'Password reset successful');
    }
}
