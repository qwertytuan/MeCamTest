<?php

use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', static function () {
    return view('home');
})->name('home');

Route::get('/login', static function () {
    return view('auth.login');
})->name('login');

Route::get('/register', static function () {
    return view('auth.register');
})->name('register');

// Public shared camera stream (no auth required)
Route::get('/share/camera/{token}', static function ($token) {
    return view('shared.camera-stream', ['token' => $token]);
})->name('shared.camera');

// Protected routes (JWT auth handled client-side)
Route::get('/dashboard', static function () {
    return view('dashboard.index');
})->name('dashboard');

Route::get('/profile', static function () {
    return view('profile');
})->name('profile');

Route::get('/admin/cameras', static function () {
    return view('admin.cameras');
})->name('admin.cameras');

