<!DOCTYPE html>
@php
    $__tenantThemeConfig = app(\App\Services\TenantConfigurationService::class)->all(app('tenant')->id);
@endphp
<html
    lang="en"
    data-tenant-domain="{{ app('tenant')->domain }}"
    data-theme="{{ $__tenantThemeConfig['general.color_theme'] ?? 'crimson' }}"
    data-color-mode="{{ $__tenantThemeConfig['general.color_mode'] ?? 'system' }}"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $__faviconUrl = app('tenant')->logo_path
            ? app(\App\Services\MediaStorageService::class)->url(app('tenant')->logo_path)
            : asset('images/product-icon.svg');
    @endphp
    <link rel="icon" href="{{ $__faviconUrl }}">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    <script>
        (function () {
            var root = document.documentElement;
            var savedMode = localStorage.getItem('theme:' + root.dataset.tenantDomain);
            var mode = savedMode || root.dataset.colorMode || 'system';
            root.classList.toggle('dark', mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    {{ $slot }}
</body>
</html>
