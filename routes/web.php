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
        
        return view('product-landing-page');
    }
    
    $host = request()->getHost();
    $baseDomain = config('app.domain', 'localhost');
    
    // Extract subdomain from host
    $subdomain = str_replace('.' . $baseDomain, '', $host);
    
    // If no subdomain (e.g., just localhost), show landing page
    if ($subdomain === $baseDomain) {
        return view('product-landing-page');
    }
    
    $tenant = \App\Models\Tenant::where('domain', $subdomain)->first();
    
    if (!$tenant) {
        return view('product-landing-page');
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
        // Members should be redirected to their profile
        if (auth()->user()->hasRole('member')) {
            return redirect()->route('member.profile');
        }
        return view('dashboard');
    })->middleware('auth')->name('dashboard');
    
    // User Management routes (requires authentication and permissions)
    Route::middleware(['auth'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)
            ->middleware('permission:users.view');
        
        // Member Management routes (Admin/Staff only)
        Route::get('/members', [\App\Http\Controllers\MemberController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('members.index');
        Route::get('/members/create', [\App\Http\Controllers\MemberController::class, 'create'])
            ->middleware('permission:users.create')
            ->name('members.create');
        Route::post('/members', [\App\Http\Controllers\MemberController::class, 'store'])
            ->middleware('permission:users.create')
            ->name('members.store');
        Route::get('/members/{member}/edit', [\App\Http\Controllers\MemberController::class, 'edit'])
            ->middleware('permission:users.edit')
            ->name('members.edit');
        Route::put('/members/{member}', [\App\Http\Controllers\MemberController::class, 'update'])
            ->middleware('permission:users.edit')
            ->name('members.update');
        Route::patch('/members/{member}/toggle-status', [\App\Http\Controllers\MemberController::class, 'toggleStatus'])
            ->middleware('permission:users.edit')
            ->name('members.toggle-status');
        Route::patch('/members/{member}/toggle-verification', [\App\Http\Controllers\MemberController::class, 'toggleVerification'])
            ->middleware('permission:users.edit')
            ->name('members.toggle-verification');
        Route::delete('/members/{member}', [\App\Http\Controllers\MemberController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('members.destroy');
        
        // Member Profile (for members to view their own profile)
        Route::get('/profile', [\App\Http\Controllers\MemberController::class, 'profile'])
            ->name('member.profile');
        
        // Member-specific routes (Workout, Diet, Payments, Attendance)
        Route::get('/workout-schedule', [\App\Http\Controllers\WorkoutScheduleController::class, 'index'])
            ->name('workout-schedule.index');
        Route::get('/diet-plan', [\App\Http\Controllers\DietPlanController::class, 'index'])
            ->name('diet-plan.index');
        Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index'])
            ->name('payments.index');
        Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])
            ->name('attendance.index');
        
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

        // Inventory routes
        Route::prefix('inventory')->name('inventory.')->middleware('permission:inventory.manage')->group(function () {
            Route::resource('products', \App\Http\Controllers\ProductController::class)
                ->except(['show']);
            Route::resource('variations', \App\Http\Controllers\ProductVariationController::class)
                ->except(['show']);
            Route::get('/stock', [\App\Http\Controllers\StockController::class, 'index'])
                ->name('stock.index');
            Route::get('/stock/create', [\App\Http\Controllers\StockController::class, 'create'])
                ->name('stock.create');
            Route::get('/stock/{stock}/edit', [\App\Http\Controllers\StockController::class, 'edit'])
                ->name('stock.edit');
            Route::post('/stock', [\App\Http\Controllers\StockController::class, 'store'])
                ->name('stock.store');
            Route::put('/stock/{stock}', [\App\Http\Controllers\StockController::class, 'update'])
                ->name('stock.update');
            Route::delete('/stock/{stock}', [\App\Http\Controllers\StockController::class, 'destroy'])
                ->name('stock.destroy');
        });

        // Sales routes
        Route::prefix('sales')->name('sales.')->middleware('permission:sales.process')->group(function () {
            Route::get('/', [\App\Http\Controllers\SaleController::class, 'index'])
                ->name('index');
            Route::get('/create', [\App\Http\Controllers\SaleController::class, 'create'])
                ->name('create');
            Route::post('/', [\App\Http\Controllers\SaleController::class, 'store'])
                ->name('store');
            Route::delete('/{sale}', [\App\Http\Controllers\SaleController::class, 'destroy'])
                ->name('destroy');
        });
    });
    
    // Logout route
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

