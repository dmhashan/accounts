<x-app-layout>
    <x-slot name="title">Settings - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Settings</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Coming Soon Section -->
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-12">
                        <div class="text-center">
                            <div class="mx-auto h-24 w-24 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center mb-6">
                                <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-secondary-900 dark:text-white mb-3">Settings Coming Soon</h2>
                            <p class="text-lg text-secondary-600 dark:text-secondary-400 mb-8">
                                We're working on bringing you powerful settings and configuration options.
                            </p>
                            
                            <div class="inline-flex items-center px-6 py-3 bg-secondary-100 dark:bg-secondary-800 rounded-lg">
                                <svg class="animate-spin h-5 w-5 text-primary-600 dark:text-primary-400 mr-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">Under Development</span>
                            </div>
                        </div>

                        <!-- Features Grid -->
                        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Profile Settings</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Manage your personal information and preferences</p>
                            </div>

                            <div class="p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Security</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Configure security settings and authentication</p>
                            </div>

                            <div class="p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Notifications</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Customize your notification preferences</p>
                            </div>

                            <div class="p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Tenant Settings</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Configure tenant-specific options</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
