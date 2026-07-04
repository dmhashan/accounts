<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app('tenant')->name }} - Campaign Registration</title>
    @php
        $__tenant = app('tenant');
        $__faviconUrl = $__tenant->logo_path
            ? app(\App\Services\MediaStorageService::class)->url($__tenant->logo_path)
            : asset('images/product-icon.svg');
        $__tenantThemeConfig = app(\App\Services\TenantConfigurationService::class)->all($__tenant->id);
    @endphp
    <link rel="icon" href="{{ $__faviconUrl }}">
    <script>
        (function () {
            var tenantDomain = @json($__tenant->domain);
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
    @vite(['resources/css/app.css', 'resources/js/public-campaign.js'])
</head>
<body class="font-sans antialiased bg-background-light dark:bg-background-dark">
    <div id="public-campaign-app" data-slug="{{ request()->route('slug') }}"></div>
</body>
</html>
