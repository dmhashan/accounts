<x-app-layout>
    <x-slot name="title">Workout Schedule - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Workout Schedule</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                        <div class="p-12 text-center">
                            <div class="mb-6">
                                <svg class="mx-auto h-24 w-24 text-secondary-400 dark:text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-secondary-900 dark:text-white mb-2">Workout Schedule</h3>
                            <p class="text-lg text-secondary-600 dark:text-secondary-400 mb-4">Coming Soon</p>
                            <p class="text-secondary-500 dark:text-secondary-500 max-w-md mx-auto">
                                Your personalized workout schedule will be available here. Stay tuned for updates!
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
