<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

// Root route - check for tenant
Route::get('/', function () {
    $host = request()->getHost();
    $baseDomain = config('app.domain', 'localhost');
    
    // Extract subdomain from host
    $subdomain = str_replace('.' . $baseDomain, '', $host);
    
    // If no subdomain (e.g., just localhost), show landing page
    if ($subdomain === $baseDomain) {
        return view('landing');
    }
    
    $tenant = \App\Models\Tenant::where('domain', $subdomain)->first();
    
    if (!$tenant) {
        return view('landing');
    }
    
    // If tenant exists, store it and show tenant landing page
    app()->instance('tenant', $tenant);
    
    return view('tenant-landing', ['tenant' => $tenant]);
});

Route::middleware([IdentifyTenant::class])->group(function () {
    // Login routes
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login');
    
    // Registration routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register.form');
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
    
    // Dashboard route (requires authentication)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('auth')->name('dashboard');
    
    // Logout route
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('register.form');
    })->name('logout');
});

