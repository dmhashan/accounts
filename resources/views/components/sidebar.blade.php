<!-- Mobile Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 w-64 bg-white dark:bg-secondary-900 border-r border-secondary-200 dark:border-secondary-700 flex flex-col z-50 transition-transform duration-300 ease-in-out">
    <!-- Logo/Brand -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-secondary-200 dark:border-secondary-700">
        <div class="flex items-center min-w-0 mx-auto">
            <img src="{{ asset('images/black-text-logo.png') }}" alt="{{ app('tenant')->name }}" class="h-32 block dark:hidden">
            <img src="{{ asset('images/white-text-logo.png') }}" alt="{{ app('tenant')->name }}" class="h-32 hidden dark:block">
        </div>
        <!-- Close button for mobile -->
        <button onclick="toggleSidebar()" class="lg:hidden p-2 text-secondary-400 dark:text-secondary-500 hover:text-secondary-600 dark:hover:text-secondary-300 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" title="Close menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        @if(auth()->user()->hasPermission('member.profile.view'))
        <!-- Member Navigation -->
        <a href="{{ route('member.profile') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('member.profile') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Profile
        </a>
        @endif

        @if(auth()->user()->hasPermission('member.workout.view'))
        <a href="{{ route('workout-schedule.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('workout-schedule.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            Workout Schedule
        </a>
        @endif

        @if(auth()->user()->hasPermission('member.diet.view'))
        <a href="{{ route('diet-plan.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('diet-plan.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Diet Plan
        </a>
        @endif

        @if(auth()->user()->hasPermission('member.payments.view'))
        <a href="{{ route('payments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('payments.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            Payments
        </a>
        @endif

        @if(auth()->user()->hasPermission('member.attendance.view'))
        <a href="{{ route('attendance.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('attendance.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h6" />
            </svg>
            Attendance
        </a>
        @endif

        @if(auth()->user()->hasPermission('dashboard.view'))
        <!-- Admin/Staff Navigation -->
        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>
        @endif

        @if(auth()->user()->hasPermission('users.view'))
        <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('users.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Users
        </a>
        @endif

        @if(auth()->user()->hasPermission('users.view'))
        <a href="{{ route('members.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('members.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Members
        </a>
        @endif

        @if(auth()->user()->hasPermission('roles.view'))
        <a href="{{ route('roles.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('roles.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Roles
        </a>
        @endif

        @if(auth()->user()->hasPermission('settings.manage'))
        <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('settings.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
        </a>
        @endif

        @if(auth()->user()->hasPermission('reports.view'))
        <a href="{{ route('reports.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('reports.*') ? 'text-white bg-gradient-to-r from-primary-500 to-primary-700' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700' }} rounded-lg transition-colors">
            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Reports
        </a>
        @endif
    </nav>
    <div class="border-t border-secondary-200 dark:border-secondary-700 p-4 space-y-3">
        <div class="flex items-center mb-3">
            <div class="h-10 w-10 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-sm font-semibold text-white">{{ substr(auth()->user()->name, 0, 2) }}</span>
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>