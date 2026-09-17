<x-guest-layout>
    <x-slot name="title">{{ $tenant->name }} - Terms &amp; Conditions</x-slot>

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
        <main class="flex-1 mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 sm:py-12">
            <article class="bg-white dark:bg-secondary-900 rounded-2xl shadow-sm border border-secondary-200 dark:border-secondary-800 p-6 sm:p-10">
                <header class="border-b border-secondary-200 dark:border-secondary-800 pb-6 mb-8">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 mb-3">
                        Official Facility Policy
                    </span>
                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-secondary-900 dark:text-white">
                        Gym Member Terms &amp; Conditions
                    </h1>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-2">
                        Please review the terms, rules, and conditions applicable to members of {{ $facilityName }}.
                    </p>
                </header>

                <div class="prose dark:prose-invert max-w-none text-secondary-800 dark:text-secondary-200 text-sm sm:text-base leading-relaxed space-y-4">
                    {!! $content !!}
                </div>
            </article>
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
