<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

// Root route - check for tenant
Route::get('/', function () {
    // Check if multitenancy is bypassed
    if (!config('app.multitenancy_enabled', true)) {
        $bypassDomain = config('app.multitenancy_bypass_domain');
        
        if ($bypassDomain) {
            $tenant = \App\Models\Tenant::where('domain', $bypassDomain)->first();
            
            if ($tenant) {
                app()->instance('tenant', $tenant);
                return view('tenant-landing', ['tenant' => $tenant]);
            }
        }
        
        return view('landing');
    }
    
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
    
    // User Management routes (requires authentication and permissions)
    Route::middleware(['auth'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)
            ->middleware('permission:users.view');
        
        // Role Management routes
        Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');
        Route::get('/roles/{role}', [\App\Http\Controllers\RoleController::class, 'show'])
            ->middleware('permission:roles.view')
            ->name('roles.show');
        Route::post('/roles/{role}/permissions', [\App\Http\Controllers\RoleController::class, 'updatePermissions'])
            ->middleware('permission:roles.permissions')
            ->name('roles.permissions.update');
        
        // Settings route
        Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])
            ->name('settings.index');
        
        // Reports route
        Route::get('/reports', [\App\Http\Controllers\ReportsController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.index');
    });
    
    // Logout route
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('register.form');
    })->name('logout');
});

