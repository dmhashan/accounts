<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Member Portal - {{ app('tenant')->name }}</title>
    @php
        $__tenant = app('tenant');
        $__logoUrl = $__tenant->logo_path
            ? app(\App\Services\MediaStorageService::class)->url($__tenant->logo_path)
            : null;
        $__faviconUrl = $__logoUrl ?: asset('images/product-icon.svg');
    @endphp
    <link rel="icon" href="{{ $__faviconUrl }}">
    <script>
        (function () {
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var savedTheme = localStorage.getItem('member_portal_theme') || (prefersDark ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        })();
        window.__tenantName    = @json($__tenant->name);
        window.__tenantLogoUrl = @json($__logoUrl);
        window.__tenantAddress = @json($__tenant->address);
        window.__tenantEmail   = @json($__tenant->email);
        window.__tenantPhone   = @json($__tenant->phone);
    </script>
    @vite(['resources/css/public-profile.css', 'resources/js/public-profile.js'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-zinc-950 text-gray-900 dark:text-white">
    <div id="public-profile-app"></div>
</body>
</html>
