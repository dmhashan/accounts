<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app('tenant')->name }} - App</title>
    @php
        $__faviconUrl = app('tenant')->logo_path
            ? app(\App\Services\MediaStorageService::class)->url(app('tenant')->logo_path)
            : asset('images/product-icon.svg');
    @endphp
    <link rel="icon" href="{{ $__faviconUrl }}">
    @php
        $__tenantThemeConfig = app(\App\Services\TenantConfigurationService::class)->all(app('tenant')->id);
    @endphp
    <script>
        (function () {
            var tenantDomain = @json(app('tenant')->domain);
            var tenantTheme = @json($__tenantThemeConfig['general.color_theme'] ?? 'crimson');
            var tenantMode = @json($__tenantThemeConfig['general.color_mode'] ?? 'system');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var savedTheme = localStorage.getItem('theme:' + tenantDomain);
            var effectiveMode = savedTheme || tenantMode;
            var shouldUseDark = effectiveMode === 'dark' || (effectiveMode === 'system' && prefersDark);

            document.documentElement.dataset.theme = tenantTheme;
            document.documentElement.classList.toggle('dark', shouldUseDark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/spa/main.js'])
</head>
<body class="font-sans antialiased bg-background-light dark:bg-background-dark">
    @php
        $user = auth()->user();
        $memberPortalUrl = app(\App\Services\MemberPortalUrlService::class)->urlForTenant(app('tenant'));

        $context = [
            ...app(\App\Services\AppContextService::class)->build($user, app('tenant')),
            'legacyUrls' => [
                'dashboard' => '/dashboard',
                'users' => '/#/users',
                'usersCreate' => '/#/users/new',
                'members' => '/#/members',
                'membersCreate' => '/#/members/new',
                'roles' => '/#/roles',
                'settings' => route('settings.index'),
                'reports' => route('reports.index'),
                'inventoryProducts' => route('inventory.products.index'),
                'inventoryStock' => route('inventory.stock.index'),
                'sales' => route('sales.index'),
                'salesCreate' => route('sales.create'),
                'workout' => route('workout-schedule.index'),
                'payments' => route('payments.index'),
                'logout' => route('logout'),
            ],
        ];
    @endphp

    <div id="spa-root" data-context='@json($context)'></div>
</body>
</html>
