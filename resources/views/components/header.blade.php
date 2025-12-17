<header class="h-16 bg-white dark:bg-secondary-900 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between px-4 md:px-6">
    <!-- Mobile Menu Button -->
    <button onclick="toggleSidebar()" class="lg:hidden p-2 text-secondary-400 dark:text-secondary-500 hover:text-secondary-600 dark:hover:text-secondary-300 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" title="Toggle menu">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    
    <h1 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">{{ $title ?? 'Dashboard' }}</h1>
    <div class="flex items-center space-x-2">
        <!-- Theme Toggle -->
        <button onclick="toggleTheme()" class="p-2 text-secondary-400 dark:text-secondary-500 hover:text-secondary-600 dark:hover:text-secondary-300 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" title="Toggle theme">
            <svg class="h-6 w-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg class="h-6 w-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
        
        <!-- Notifications -->
        <button class="p-2 text-secondary-400 dark:text-secondary-500 hover:text-secondary-600 dark:hover:text-secondary-300 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" title="Notifications">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </button>
    </div>
</header>
