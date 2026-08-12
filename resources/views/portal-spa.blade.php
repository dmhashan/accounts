<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SaaS Administration Portal</title>
    <link rel="icon" href="{{ asset('images/product-icon.svg') }}">
    <script>
        (function () {
            var savedTheme = localStorage.getItem('portal_theme') || 'dark';
            var shouldUseDark = savedTheme === 'dark';
            document.documentElement.classList.toggle('dark', shouldUseDark);
        })();
    </script>
    @vite(['resources/css/portal.css', 'resources/js/portal/main.js'])
</head>
<body class="font-sans antialiased bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen transition-colors duration-200">
    @php
        $user = auth('portal')->user();
        $context = [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
            ] : null,
            'appDomain' => config('app.domain'),
            'appUrl' => config('app.url'),
        ];
    @endphp

    <div id="portal-root" data-context='@json($context)'></div>
</body>
</html>
