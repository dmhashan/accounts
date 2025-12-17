<x-app-layout>
    <x-slot name="title">Reports - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Reports</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Coming Soon Section -->
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-12">
                        <div class="text-center">
                            <div class="mx-auto h-24 w-24 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center mb-6">
                                <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-secondary-900 dark:text-white mb-3">Reports Coming Soon</h2>
                            <p class="text-lg text-secondary-600 dark:text-secondary-400 mb-8">
                                Advanced analytics and reporting features are in development.
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">User Activity</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Track user engagement and activity patterns</p>
                            </div>

                            <div class="p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Permission Reports</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Analyze role and permission usage</p>
                            </div>

                            <div class="p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Performance Metrics</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Monitor system performance and trends</p>
                            </div>

                            <div class="p-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Audit Logs</h3>
                                </div>
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">Review system activity and changes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
