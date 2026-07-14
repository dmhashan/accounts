<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Middleware\IdentifyTenant;
use App\Services\Tenancy\TenantDatabaseManager;
use App\Services\TenantLandingPageService;
use Illuminate\Support\Facades\Route;

// Root route - check for tenant
Route::get('/', function (TenantDatabaseManager $tenancy) {
    $domain = $tenancy->domainForRequest(request());

    if ($domain === 'portal') {
        return view('portal-spa');
    }

    $tenancy->deactivate();
    $tenant = $domain ? $tenancy->activateByDomain($domain) : null;

    if (!$tenant) {
        return view('product-landing-page');
    }

    if (!$tenant->is_active) {
        $appType = auth()->check() ? 'Administrator Portal' : 'Website';

        return response()->view('tenant-blocked', [
            'tenant' => $tenant,
            'appType' => $appType,
        ]);
    }

    if (auth()->check()) {
        return view('spa');
    }

    $subdomain = $tenant->domain;
    $publicPath = public_path("tenant-pages/{$subdomain}.html");

    if (!file_exists($publicPath)) {
        $publicPath = public_path("tenant_pages/{$subdomain}.html");
    }

    if (file_exists($publicPath) || $tenant->use_custom_landing_page) {
        $customPagePath = app(TenantLandingPageService::class)->ensureCustomPageExists($tenant);

        return response()->file($customPagePath);
    }

    return view('tenant-landing', ['tenant' => $tenant]);
});

Route::middleware([IdentifyTenant::class])->group(function () {
    // Login routes
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login');
    Route::get('/auth/{provider}/redirect', [App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'apple'])
        ->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'apple'])
        ->name('auth.social.callback');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
        ->name('password.update');

    // Registration routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register.form');
    Route::post('/register', [RegisterController::class, 'register'])->name('register');

    // Public member portal (phone + OTP identification) — SPA root and sub-routes
    Route::get('/profile', function () {
        return view('members.public-profile');
    })->name('member.portal');
    Route::get('/profile/workout', fn () => view('members.public-profile'));
    Route::get('/profile/transactions', fn () => view('members.public-profile'));
    Route::get('/profile/profile', fn () => view('members.public-profile'));
    Route::get('/profile/event/{slug}', fn () => view('members.public-profile'))->where('slug', '[a-z0-9\-]+');
    Route::get('/profile/notifications', fn () => view('members.public-profile'));

    // Public campaign registration pages
    Route::get('/campaigns/{slug}', fn () => view('campaigns.public'))
        ->where('slug', '[a-z0-9\-]+')
        ->name('campaigns.public');

    // Dashboard route (requires authentication)
    Route::get('/dashboard', function () {
        return redirect('/#/dashboard');
    })->middleware('auth')->name('dashboard');

    // User Management routes (requires authentication and permissions)
    Route::middleware(['auth'])->group(function () {
        // Member-specific routes (Workout, Payments)
        Route::get('/workout-schedule', [App\Http\Controllers\WorkoutScheduleController::class, 'index'])
            ->name('workout-schedule.index');
        Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index'])
            ->name('payments.index');

        // Settings route
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])
            ->name('settings.index');
        Route::post('/settings/landing-page', [App\Http\Controllers\SettingsController::class, 'updateLandingPage'])
            ->name('settings.landing-page.update');

        // Reports route
        Route::get('/reports', [App\Http\Controllers\ReportsController::class, 'index'])
            ->middleware('permission:reports.view,reports.daily_summary,reports.real_profit,reports.statistics,reports.member_analysis,reports.customers,reports.products')
            ->name('reports.index');

        // Inventory routes
        Route::prefix('inventory')->name('inventory.')->middleware('permission:inventory.manage')->group(function () {
            Route::resource('products', App\Http\Controllers\ProductController::class)
                ->except(['show']);
            Route::resource('variations', App\Http\Controllers\ProductVariationController::class)
                ->except(['show']);
            Route::get('/stock', [App\Http\Controllers\StockController::class, 'index'])
                ->name('stock.index');
            Route::get('/stock/create', [App\Http\Controllers\StockController::class, 'create'])
                ->name('stock.create');
            Route::get('/stock/{stock}/edit', [App\Http\Controllers\StockController::class, 'edit'])
                ->name('stock.edit');
            Route::post('/stock', [App\Http\Controllers\StockController::class, 'store'])
                ->name('stock.store');
            Route::put('/stock/{stock}', [App\Http\Controllers\StockController::class, 'update'])
                ->name('stock.update');
            Route::delete('/stock/{stock}', [App\Http\Controllers\StockController::class, 'destroy'])
                ->name('stock.destroy');
        });

        // Sales routes
        Route::prefix('sales')->name('sales.')->middleware('permission:sales.process,sales.paid.view')->group(function () {
            Route::get('/', [App\Http\Controllers\SaleController::class, 'index'])
                ->name('index');
            Route::get('/create', [App\Http\Controllers\SaleController::class, 'create'])
                ->name('create');
            Route::post('/', [App\Http\Controllers\SaleController::class, 'store'])
                ->name('store');
            Route::delete('/{sale}', [App\Http\Controllers\SaleController::class, 'destroy'])
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
