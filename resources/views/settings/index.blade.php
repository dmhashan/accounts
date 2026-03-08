<x-app-layout>
    <x-slot name="title">Settings - {{ $tenant->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Settings</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    @if (session('success'))
                        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 md:p-8">
                        <h2 class="text-xl font-semibold text-secondary-900 dark:text-white mb-2">Tenant Landing Page</h2>
                        <p class="text-sm text-secondary-600 dark:text-secondary-400 mb-6">
                            Turn on custom landing page to load HTML from <span class="font-medium">public/tenant-pages</span>. If the tenant file does not exist, it will be created automatically.
                        </p>

                        <form action="{{ route('settings.landing-page.update') }}" method="POST" class="space-y-6">
                            @csrf

                            <label for="use_custom_landing_page" class="flex items-center justify-between rounded-lg border border-secondary-200 dark:border-secondary-700 px-4 py-4 cursor-pointer">
                                <div>
                                    <p class="font-medium text-secondary-900 dark:text-white">Enable custom tenant landing page</p>
                                    <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-1">OFF = default simple landing page | ON = load tenant HTML file</p>
                                </div>

                                <div class="relative inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        name="use_custom_landing_page"
                                        id="use_custom_landing_page"
                                        value="1"
                                        class="peer sr-only"
                                        {{ old('use_custom_landing_page', $tenant->use_custom_landing_page) ? 'checked' : '' }}
                                    >
                                    <div class="h-6 w-11 rounded-full bg-secondary-300 dark:bg-secondary-700 peer-checked:bg-primary-600 transition-colors"></div>
                                    <div class="absolute left-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
                                </div>
                            </label>

                            @error('use_custom_landing_page')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror

                            <div>
                                <label for="wallet_credit_limit" class="block text-sm font-medium text-secondary-900 dark:text-white mb-2">
                                    Member Wallet Credit Limit
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="wallet_credit_limit"
                                    id="wallet_credit_limit"
                                    value="{{ old('wallet_credit_limit', $tenant->wallet_credit_limit) }}"
                                    class="w-full md:max-w-sm rounded-lg border border-secondary-300 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white"
                                >
                                <p class="text-xs text-secondary-600 dark:text-secondary-400 mt-1">
                                    Members can spend below zero up to this configured limit.
                                </p>
                                @error('wallet_credit_limit')
                                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 text-sm font-medium transition-colors"
                            >
                                Save Settings
                            </button>
                        </form>

                        <div class="mt-6 rounded-lg bg-secondary-50 dark:bg-secondary-800 px-4 py-3 text-sm text-secondary-700 dark:text-secondary-300">
                            Tenant file name: <span class="font-medium">{{ \Illuminate\Support\Str::slug($tenant->name) }}.html</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
