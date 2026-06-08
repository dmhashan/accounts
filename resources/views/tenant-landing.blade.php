<x-guest-layout>
    <x-slot name="title">{{ $tenant->name }} - Welcome</x-slot>

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
        $address = trim((string) ($tenant->address ?? ''));
        $phone = trim((string) ($tenant->phone ?? ''));
        $email = trim((string) ($tenant->email ?? ''));
        $phoneHref = $phone !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $phone) : null;
        $emailHref = $email !== '' ? 'mailto:' . $email : null;
        $memberPortalUrl = app(\App\Services\MemberPortalUrlService::class)->urlForTenant($tenant);
        $heroBackgroundPath = public_path('images/background.jpg');
        $heroBackground = asset('images/background.jpg');

        if (file_exists($heroBackgroundPath)) {
            $heroBackground .= '?v=' . filemtime($heroBackgroundPath);
        }
    @endphp

    <div class="tenant-landing min-h-screen">
        <header class="tenant-landing-header sticky top-0 z-30 border-b backdrop-blur">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="#top" class="flex min-w-0 items-center gap-3">
                    @if ($logoUrl)
                        <img
                            src="{{ $logoUrl }}"
                            alt="{{ $facilityName }} logo"
                            class="app-logo-tile h-11 w-11 rounded-xl object-cover"
                        >
                    @endif

                    <div class="min-w-0">
                        <p class="tenant-landing-eyebrow truncate text-sm uppercase tracking-[0.35em]">Fitness Center</p>
                        <p class="tenant-landing-strong truncate text-base font-semibold sm:text-lg">{{ $facilityName }}</p>
                    </div>
                </a>

                <a
                    href="{{ $memberPortalUrl }}"
                    class="tenant-landing-primary-action inline-flex items-center justify-center rounded-full border px-4 py-2 text-sm font-semibold transition sm:px-5"
                >
                    Member Portal
                </a>
            </div>
        </header>

        <main>
            <section
                id="top"
                class="relative isolate flex min-h-[calc(100vh-73px)] items-end overflow-hidden"
            >
                <div
                    class="absolute inset-0 bg-cover bg-center"
                    style="background-image: linear-gradient(135deg, rgba(12, 10, 9, 0.8), rgba(12, 10, 9, 0.45)), url('{{ $heroBackground }}');"
                ></div>
                <div class="tenant-landing-hero-overlay absolute inset-0"></div>

                <div class="relative mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 md:py-16 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] lg:px-8 lg:py-20">
                    <div class="max-w-3xl self-center">
                        <p class="tenant-landing-eyebrow mb-4 text-sm font-medium uppercase tracking-[0.4em]">Train with purpose</p>
                        <h1 class="tenant-landing-strong max-w-3xl text-4xl font-black uppercase tracking-tight sm:text-5xl lg:text-7xl">{{ $facilityName }}</h1>
                        <p class="tenant-landing-muted mt-5 max-w-2xl text-lg sm:text-xl">Premium Fitness Facility. Built for Results.</p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @if ($phoneHref)
                                <a
                                    href="{{ $phoneHref }}"
                                    class="tenant-landing-secondary-action inline-flex min-h-14 items-center justify-center rounded-full border px-6 text-base font-semibold backdrop-blur transition"
                                >
                                    Call Now
                                </a>
                            @endif

                            @if (!$phoneHref)
                                <a
                                    href="{{ $memberPortalUrl }}"
                                    class="tenant-landing-primary-action inline-flex min-h-14 items-center justify-center rounded-full border px-6 text-base font-semibold transition"
                                >
                                    Member Portal
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="tenant-landing-glass self-end rounded-3xl border p-5 backdrop-blur-xl sm:p-6">
                        <p class="tenant-landing-eyebrow text-xs font-semibold uppercase tracking-[0.35em]">Quick access</p>
                        <div class="mt-5 space-y-4">
                            <div class="tenant-landing-info rounded-2xl border p-4">
                                <p class="tenant-landing-subtle text-xs uppercase tracking-[0.3em]">Visit</p>
                                <p class="tenant-landing-strong mt-2 text-base font-medium">{{ $address !== '' ? $address : 'Contact us for the latest address details.' }}</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                <div class="tenant-landing-info rounded-2xl border p-4">
                                    <p class="tenant-landing-subtle text-xs uppercase tracking-[0.3em]">Call</p>
                                    <p class="tenant-landing-strong mt-2 break-words text-base font-medium">{{ $phone !== '' ? $phone : 'Available on request' }}</p>
                                </div>

                                <div class="tenant-landing-info rounded-2xl border p-4">
                                    <p class="tenant-landing-subtle text-xs uppercase tracking-[0.3em]">Email</p>
                                    <p class="tenant-landing-strong mt-2 break-words text-base font-medium">{{ $email !== '' ? $email : 'Reach out via phone for quick support' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="connect" class="tenant-landing-section border-t">
                <div class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                    <div class="tenant-landing-panel rounded-[2rem] border p-6 sm:p-8">
                        <p class="tenant-landing-eyebrow text-sm font-semibold uppercase tracking-[0.35em]">Visit the facility</p>
                        <h2 class="tenant-landing-strong mt-4 text-3xl font-bold sm:text-4xl">Find us fast. Train without delay.</h2>
                        <p class="tenant-landing-muted mt-4 text-base leading-7">Everything important is here at a glance: your route, contact options, and the quickest way to reach the gym from any device.</p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="tenant-landing-info rounded-2xl border p-5">
                                <p class="tenant-landing-subtle text-xs uppercase tracking-[0.3em]">Address</p>
                                <p class="tenant-landing-strong mt-3 text-base font-medium">{{ $address !== '' ? $address : 'Insert street address, city, zip code' }}</p>
                            </div>

                            <div class="tenant-landing-info rounded-2xl border p-5">
                                <p class="tenant-landing-subtle text-xs uppercase tracking-[0.3em]">Contact</p>
                                <div class="tenant-landing-strong mt-3 space-y-3 text-base font-medium">
                                    <p>{{ $phone !== '' ? $phone : 'Insert phone number' }}</p>
                                    <p class="tenant-landing-muted break-words">{{ $email !== '' ? $email : 'Insert email address' }}</p>
                                </div>
                                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                    @if ($phoneHref)
                                        <a
                                            href="{{ $phoneHref }}"
                                            class="tenant-landing-primary-action inline-flex min-h-12 items-center justify-center rounded-full border px-5 text-sm font-semibold transition"
                                        >
                                            Tap to Call
                                        </a>
                                    @endif

                                    @if ($emailHref)
                                        <a
                                            href="{{ $emailHref }}"
                                            class="tenant-landing-secondary-action inline-flex min-h-12 items-center justify-center rounded-full border px-5 text-sm font-semibold transition"
                                        >
                                            Send Email
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="tenant-landing-footer border-t">
            <div class="tenant-landing-subtle mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-6 text-sm sm:px-6 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <p>&copy; 2026 {{ $facilityName }}. All Rights Reserved.</p>
                <p>Powered by <a href="https://beforward.lk" target="_blank" rel="noreferrer" class="tenant-landing-powered transition">beforward.lk</a></p>
            </div>
        </footer>
    </div>
</x-guest-layout>
