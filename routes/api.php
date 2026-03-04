<?php

use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\SaleApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', IdentifyTenant::class])->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    Route::post('/auth/login', function (Request $request) {
        $tenant = app('tenant');

        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['login'];
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

        $attemptData = [
            'tenant_id' => $tenant->id,
            'password' => $credentials['password'],
            $isEmail ? 'email' : 'username' => $login,
        ];

        if (!Auth::attempt($attemptData)) {
            return response()->json([
                'message' => 'Invalid username/email or password for this tenant.',
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'redirect' => '/#/dashboard',
        ]);
    });

    Route::post('/auth/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    })->middleware('auth');

    Route::post('/auth/refresh', function (Request $request) {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Session refreshed successfully.',
        ]);
    });

    Route::get('/app/context', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => [
                'id' => app('tenant')->id,
                'name' => app('tenant')->name,
                'domain' => app('tenant')->domain,
            ],
            'permissions' => [
                'dashboard' => $user->hasPermission('dashboard.view'),
                'users' => $user->hasPermission('users.view'),
                'members' => $user->hasPermission('users.view'),
                'roles' => $user->hasPermission('roles.view'),
                'settings' => $user->hasPermission('settings.manage'),
                'reports' => $user->hasPermission('reports.view'),
                'inventory' => $user->hasPermission('inventory.manage'),
                'sales' => $user->hasPermission('sales.process'),
                'profile' => $user->hasPermission('member.profile.view') || $user->hasRole('member'),
                'workout' => $user->hasPermission('member.workout.view'),
                'diet' => $user->hasPermission('member.diet.view'),
                'payments' => $user->hasPermission('member.payments.view'),
                'attendance' => $user->hasPermission('member.attendance.view'),
            ],
        ]);
    })->middleware('auth');

    Route::get('/dashboard/overview', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'tenant' => [
                'name' => app('tenant')->name,
                'id' => app('tenant')->id,
                'domain' => app('tenant')->domain,
            ],
            'user' => [
                'name' => $user->name,
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    })->middleware('auth');

    Route::middleware(['auth', 'permission:users.view'])->group(function () {
        Route::get('/users/meta', [UserApiController::class, 'meta']);
        Route::get('/users', [UserApiController::class, 'index']);
        Route::get('/users/{user}', [UserApiController::class, 'show']);
        Route::post('/users', [UserApiController::class, 'store'])->middleware('permission:users.create');
        Route::put('/users/{user}', [UserApiController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('/users/{user}', [UserApiController::class, 'destroy'])->middleware('permission:users.delete');

        Route::get('/members/meta', [MemberApiController::class, 'meta']);
        Route::get('/members', [MemberApiController::class, 'index']);
        Route::get('/members/export/google-contacts', [MemberApiController::class, 'exportGoogleContacts']);
        Route::get('/members/{member}', [MemberApiController::class, 'show']);
        Route::post('/members', [MemberApiController::class, 'store'])->middleware('permission:users.create');
        Route::put('/members/{member}', [MemberApiController::class, 'update'])->middleware('permission:users.edit');
        Route::patch('/members/{member}/toggle-status', [MemberApiController::class, 'toggleStatus'])->middleware('permission:users.edit');
        Route::patch('/members/{member}/toggle-verification', [MemberApiController::class, 'toggleVerification'])->middleware('permission:users.edit');
        Route::delete('/members/{member}', [MemberApiController::class, 'destroy'])->middleware('permission:users.delete');
    });

    Route::get('/roles', [RoleApiController::class, 'index'])->middleware(['auth', 'permission:roles.view']);
    Route::post('/roles', [RoleApiController::class, 'store'])->middleware(['auth', 'permission:roles.permissions']);
    Route::get('/roles/{role}', [RoleApiController::class, 'show'])->middleware(['auth', 'permission:roles.view']);
    Route::put('/roles/{role}', [RoleApiController::class, 'update'])->middleware(['auth', 'permission:roles.permissions']);
    Route::patch('/roles/{role}/permissions', [RoleApiController::class, 'updatePermissions'])->middleware(['auth', 'permission:roles.permissions']);

    Route::get('/reports/overview', function () {
        return response()->json([
            'status' => 'coming-soon',
            'features' => [
                ['title' => 'User Activity', 'description' => 'Track user engagement and activity patterns'],
                ['title' => 'Permission Reports', 'description' => 'Analyze role and permission usage'],
                ['title' => 'Performance Metrics', 'description' => 'Monitor system performance and trends'],
                ['title' => 'Audit Logs', 'description' => 'Review system activity and changes'],
            ],
        ]);
    })->middleware(['auth', 'permission:reports.view']);

    Route::prefix('/inventory')->middleware(['auth', 'permission:inventory.manage'])->group(function () {
        Route::get('/meta', [InventoryApiController::class, 'meta']);

        Route::get('/products', [InventoryApiController::class, 'products']);
        Route::get('/products/{product}', [InventoryApiController::class, 'showProduct']);
        Route::post('/products', [InventoryApiController::class, 'storeProduct']);
        Route::put('/products/{product}', [InventoryApiController::class, 'updateProduct']);
        Route::delete('/products/{product}', [InventoryApiController::class, 'destroyProduct']);

        Route::get('/variations', [InventoryApiController::class, 'variations']);
        Route::post('/variations', [InventoryApiController::class, 'storeVariation']);
        Route::put('/variations/{variation}', [InventoryApiController::class, 'updateVariation']);
        Route::delete('/variations/{variation}', [InventoryApiController::class, 'destroyVariation']);

        Route::get('/stock', [InventoryApiController::class, 'stock']);
        Route::get('/stock/{stock}', [InventoryApiController::class, 'showStock']);
        Route::post('/stock', [InventoryApiController::class, 'storeStock']);
        Route::put('/stock/{stock}', [InventoryApiController::class, 'updateStock']);
        Route::delete('/stock/{stock}', [InventoryApiController::class, 'destroyStock']);
    });

    Route::get('/sales/meta', [SaleApiController::class, 'meta'])->middleware(['auth', 'permission:sales.process']);
    Route::get('/sales/member-wallet/{member}', [SaleApiController::class, 'memberWallet'])->middleware(['auth', 'permission:sales.process']);
    Route::get('/sales', [SaleApiController::class, 'index'])->middleware(['auth', 'permission:sales.process']);
    Route::post('/sales', [SaleApiController::class, 'store'])->middleware(['auth', 'permission:sales.process']);
    Route::delete('/sales/{sale}', [SaleApiController::class, 'destroy'])->middleware(['auth', 'permission:sales.process']);
});
