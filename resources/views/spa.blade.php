<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app('tenant')->name }} - App</title>
    <script>
        (function () {
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var savedTheme = localStorage.theme;
            var shouldUseDark = savedTheme === 'dark' || (!savedTheme && prefersDark);

            document.documentElement.classList.toggle('dark', shouldUseDark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/spa/main.js'])
</head>
<body class="font-sans antialiased bg-background-light dark:bg-background-dark">
    @php
        $user = auth()->user();

        $context = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => [
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
                'profile' => route('member.portal'),
                'workout' => route('workout-schedule.index'),
                'diet' => route('diet-plan.index'),
                'payments' => route('payments.index'),
                'attendance' => route('attendance.index'),
                'logout' => route('logout'),
            ],
        ];
    @endphp

    <div id="spa-root" data-context='@json($context)'></div>
</body>
</html>
