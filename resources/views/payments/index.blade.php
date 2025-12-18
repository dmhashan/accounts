<x-app-layout>
    <x-slot name="title">Payments - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Payments</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                        <div class="p-12 text-center">
                            <div class="mb-6">
                                <svg class="mx-auto h-24 w-24 text-secondary-400 dark:text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-secondary-900 dark:text-white mb-2">Payments</h3>
                            <p class="text-lg text-secondary-600 dark:text-secondary-400 mb-4">Coming Soon</p>
                            <p class="text-secondary-500 dark:text-secondary-500 max-w-md mx-auto">
                                Your payment history and subscription details will be available here. Stay tuned for updates!
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
