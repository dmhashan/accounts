<x-guest-layout>
    <x-slot name="title">{{ $tenant->name }} - Terms &amp; Conditions Unavailable</x-slot>

    @php
        $logoUrl = null;
        if (!empty($tenant->logo_path)) {
            try {
                $logoUrl = app(\App\Services\MediaStorageService::class)->url($tenant->logo_path);
            } catch (\Throwable $e) {
                $logoUrl = null;
            }
        }
        $facilityName = $tenant->name ?: 'Fitness Center';
        $memberPortalUrl = app(\App\Services\MemberPortalUrlService::class)->urlForTenant($tenant);
    @endphp

    <div class="min-h-screen bg-secondary-50 dark:bg-background-dark text-secondary-900 dark:text-secondary-100 flex flex-col">
        <!-- Header -->
        <header class="bg-white/80 dark:bg-secondary-900/80 backdrop-blur sticky top-0 z-30 border-b border-secondary-200 dark:border-secondary-800">
            <div class="mx-auto max-w-4xl px-4 py-4 sm:px-6 flex items-center justify-between">
                <a href="/" class="flex items-center gap-3">
                    @if ($logoUrl)
                        <img
                            src="{{ $logoUrl }}"
                            alt="{{ $facilityName }} logo"
                            class="h-10 w-10 rounded-xl object-cover border border-secondary-200 dark:border-secondary-700"
                        >
                    @endif
                    <div>
                        <p class="text-xs uppercase tracking-widest text-secondary-500 dark:text-secondary-400 font-medium">Fitness Center</p>
                        <p class="text-base font-bold text-secondary-900 dark:text-white leading-none mt-0.5">{{ $facilityName }}</p>
                    </div>
                </a>

                <a
                    href="{{ $memberPortalUrl }}"
                    class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-full bg-primary-600 hover:bg-primary-700 text-white transition-colors"
                >
                    Member Portal
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 mx-auto w-full max-w-4xl px-4 py-16 sm:px-6 flex items-center justify-center">
            <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-sm border border-secondary-200 dark:border-secondary-800 p-8 sm:p-12 text-center max-w-md w-full">
                <div class="w-16 h-16 rounded-full bg-secondary-100 dark:bg-secondary-800 text-secondary-500 dark:text-secondary-400 mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-secondary-900 dark:text-white mb-2">
                    Terms &amp; Conditions Unavailable
                </h1>
                <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-6 leading-relaxed">
                    Member terms and conditions are currently turned off or unavailable for {{ $facilityName }}. Please contact gym management directly for further details.
                </p>
                <a
                    href="/"
                    class="inline-flex items-center justify-center px-5 py-2.5 text-xs sm:text-sm font-semibold rounded-xl bg-secondary-100 hover:bg-secondary-200 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-secondary-800 dark:text-secondary-200 transition-colors"
                >
                    Back to Home
                </a>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-secondary-500 dark:text-secondary-400">
                <p>&copy; {{ date('Y') }} {{ $facilityName }}. All rights reserved.</p>
                <p>Powered by <a href="https://beforward.lk" target="_blank" rel="noreferrer" class="font-medium text-primary-600 hover:underline">beforward.lk</a></p>
            </div>
        </footer>
    </div>
</x-guest-layout>
