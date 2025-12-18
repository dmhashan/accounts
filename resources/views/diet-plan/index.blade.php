<x-app-layout>
    <x-slot name="title">Diet Plan - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Diet Plan</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                        <div class="p-12 text-center">
                            <div class="mb-6">
                                <svg class="mx-auto h-24 w-24 text-secondary-400 dark:text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-secondary-900 dark:text-white mb-2">Diet Plan</h3>
                            <p class="text-lg text-secondary-600 dark:text-secondary-400 mb-4">Coming Soon</p>
                            <p class="text-secondary-500 dark:text-secondary-500 max-w-md mx-auto">
                                Your personalized diet plan will be available here. Stay tuned for updates!
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
