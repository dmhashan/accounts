<x-app-layout>
    <x-slot name="title">Dashboard - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Component -->
            <x-header>
                <x-slot name="title">Dashboard</x-slot>
            </x-header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg px-6 py-4 flex items-center">
                        <svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Welcome Card -->
                <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl shadow-xl overflow-hidden mb-6">
                    <div class="px-6 md:px-8 py-8 md:py-12 text-white">
                        <h2 class="text-2xl md:text-3xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}! 👋</h2>
                        <p class="text-primary-100">You're logged into {{ app('tenant')->name }}</p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <!-- Tenant Info -->
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="h-10 w-10 bg-primary-100 dark:bg-primary-900/50 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-secondary-900 dark:text-white">Tenant</h3>
                        </div>
                        <p class="text-2xl font-bold text-secondary-900 dark:text-white mb-1">{{ app('tenant')->name }}</p>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">ID: #{{ app('tenant')->id }}</p>
                    </div>

                    <!-- Domain Info -->
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="h-10 w-10 bg-secondary-100 dark:bg-secondary-800/50 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-secondary-600 dark:text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-secondary-900 dark:text-white">Domain</h3>
                        </div>
                        <p class="text-sm font-mono text-secondary-900 dark:text-white mb-1 break-all">{{ app('tenant')->domain }}.{{ config('app.domain') }}</p>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">Subdomain: {{ app('tenant')->domain }}</p>
                    </div>

                    <!-- Account Info -->
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="h-10 w-10 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h3 class="ml-3 text-lg font-semibold text-secondary-900 dark:text-white">Account</h3>
                        </div>
                        <p class="text-lg font-semibold text-secondary-900 dark:text-white mb-1">{{ auth()->user()->email }}</p>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400">Tenant User ID: #{{ auth()->user()->id }}</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
        </div>
    </div>
</x-app-layout>
