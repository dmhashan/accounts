<!DOCTYPE html>
<html lang="en">
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
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    {{ $slot }}
</body>
</html>
