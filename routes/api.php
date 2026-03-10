<?php

use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\SaleApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Middleware\IdentifyTenant;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\StockEntry;
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

    Route::get('/profile', [ProfileApiController::class, 'show'])->middleware('auth');

    Route::get('/dashboard/overview', function (Request $request) {
        $user = $request->user();
        $tenant = app('tenant');
        $tenantId = $tenant->id;
        $today = now()->toDateString();
        $lowStockThreshold = 5;

        $canViewStockSummary = $user->hasPermission('inventory.manage');
        $canViewSalesSummary = $user->hasPermission('sales.process');

        $stockSummary = [
            'can_view' => $canViewStockSummary,
            'available_units' => 0,
            'tracked_variations' => 0,
            'low_stock_variations' => 0,
            'low_stock_threshold' => $lowStockThreshold,
            'variation_availability' => [],
        ];

        if ($canViewStockSummary) {
            $variationAvailability = ProductVariation::query()
                ->where('product_variations.tenant_id', $tenantId)
                ->leftJoin('products', 'products.id', '=', 'product_variations.product_id')
                ->leftJoin('stock_entries', function ($join) use ($tenantId, $today) {
                    $join->on('stock_entries.product_variation_id', '=', 'product_variations.id')
                        ->where('stock_entries.tenant_id', $tenantId)
                        ->where(function ($query) use ($today) {
                            $query->whereDate('stock_entries.expiry_date', '>=', $today)
                                ->orWhereNull('stock_entries.expiry_date');
                        });
                })
                ->groupBy('product_variations.id', 'product_variations.name', 'products.name')
                ->orderBy('products.name')
                ->orderBy('product_variations.name')
                ->selectRaw('product_variations.id as variation_id, product_variations.name as variation_name, products.name as product_name, COALESCE(SUM(stock_entries.quantity), 0) as available_quantity')
                ->get()
                ->map(function ($item) use ($lowStockThreshold) {
                    $availableQuantity = (int) $item->available_quantity;
                    $productName = (string) ($item->product_name ?? 'Product');
                    $variationName = (string) $item->variation_name;

                    return [
                        'variation_id' => (int) $item->variation_id,
                        'product_name' => $productName,
                        'variation_name' => $variationName,
                        'label' => trim($productName.' - '.$variationName),
                        'available_quantity' => $availableQuantity,
                        'is_low_stock' => $availableQuantity <= $lowStockThreshold,
                    ];
                })
                ->values();

            $stockSummary['available_units'] = (int) $variationAvailability->sum('available_quantity');
            $stockSummary['tracked_variations'] = $variationAvailability->count();
            $stockSummary['low_stock_variations'] = $variationAvailability
                ->filter(fn ($item) => $item['is_low_stock'])
                ->count();
            $stockSummary['variation_availability'] = $variationAvailability;
        }

        $dailySalesSummary = [
            'can_view' => $canViewSalesSummary,
            'date' => $today,
            'transactions' => 0,
            'gross_amount' => 0,
            'paid_amount' => 0,
        ];

        if ($canViewSalesSummary) {
            $todaySalesQuery = Sale::query()
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', $today);

            $dailySalesSummary['transactions'] = (int) $todaySalesQuery->count();
            $dailySalesSummary['gross_amount'] = (float) $todaySalesQuery->sum('total_amount');
            $dailySalesSummary['paid_amount'] = (float) $todaySalesQuery->sum('paid_amount');
        }

        return response()->json([
            'tenant' => [
                'name' => $tenant->name,
                'id' => $tenant->id,
                'domain' => $tenant->domain,
            ],
            'user' => [
                'name' => $user->name,
                'id' => $user->id,
                'email' => $user->email,
            ],
            'stock_summary' => $stockSummary,
            'daily_sales_summary' => $dailySalesSummary,
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
    Route::get('/sales/{sale}', [SaleApiController::class, 'show'])->middleware(['auth', 'permission:sales.process']);
    Route::put('/sales/{sale}', [SaleApiController::class, 'update'])->middleware(['auth', 'permission:sales.process']);
    Route::delete('/sales/{sale}', [SaleApiController::class, 'destroy'])->middleware(['auth', 'permission:sales.process']);
});
