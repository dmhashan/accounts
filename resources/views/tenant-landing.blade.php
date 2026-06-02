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
        $heroBackground = asset('images/background.jpg');
    @endphp

    <div class="min-h-screen bg-stone-950 text-white">
        <header class="sticky top-0 z-30 border-b border-white/10 bg-stone-950/85 backdrop-blur">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="#top" class="flex min-w-0 items-center gap-3">
                    @if ($logoUrl)
                        <img
                            src="{{ $logoUrl }}"
                            alt="{{ $facilityName }} logo"
                            class="h-11 w-11 rounded-xl border border-white/10 object-cover shadow-lg shadow-black/20"
                        >
                    @endif

                    <div class="min-w-0">
                        <p class="truncate text-sm uppercase tracking-[0.35em] text-amber-300/80">Fitness Center</p>
                        <p class="truncate text-base font-semibold text-white sm:text-lg">{{ $facilityName }}</p>
                    </div>
                </a>

                <a
                    href="#connect"
                    class="inline-flex items-center justify-center rounded-full border border-amber-400/70 bg-amber-400 px-4 py-2 text-sm font-semibold text-stone-950 transition hover:border-amber-300 hover:bg-amber-300 sm:px-5"
                >
                    Contact Us
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
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(251,191,36,0.18),_transparent_38%),linear-gradient(to_top,_rgba(12,10,9,0.92),_rgba(12,10,9,0.4))]"></div>

                <div class="relative mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 md:py-16 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,420px)] lg:px-8 lg:py-20">
                    <div class="max-w-3xl self-center">
                        <p class="mb-4 text-sm font-medium uppercase tracking-[0.4em] text-amber-300/85">Train with purpose</p>
                        <h1 class="max-w-3xl text-4xl font-black uppercase tracking-tight text-white sm:text-5xl lg:text-7xl">{{ $facilityName }}</h1>
                        <p class="mt-5 max-w-2xl text-lg text-stone-200 sm:text-xl">Premium Fitness Facility. Built for Results.</p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @if ($phoneHref)
                                <a
                                    href="{{ $phoneHref }}"
                                    class="inline-flex min-h-14 items-center justify-center rounded-full border border-white/20 bg-white/10 px-6 text-base font-semibold text-white backdrop-blur transition hover:bg-white/15"
                                >
                                    Call Now
                                </a>
                            @endif

                            @if (!$phoneHref)
                                <a
                                    href="#connect"
                                    class="inline-flex min-h-14 items-center justify-center rounded-full bg-amber-400 px-6 text-base font-semibold text-stone-950 transition hover:bg-amber-300"
                                >
                                    Contact Us
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="self-end rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-xl sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-amber-300/85">Quick access</p>
                        <div class="mt-5 space-y-4">
                            <div class="rounded-2xl border border-white/10 bg-stone-950/45 p-4">
                                <p class="text-xs uppercase tracking-[0.3em] text-stone-400">Visit</p>
                                <p class="mt-2 text-base font-medium text-white">{{ $address !== '' ? $address : 'Contact us for the latest address details.' }}</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-stone-950/45 p-4">
                                    <p class="text-xs uppercase tracking-[0.3em] text-stone-400">Call</p>
                                    <p class="mt-2 break-words text-base font-medium text-white">{{ $phone !== '' ? $phone : 'Available on request' }}</p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-stone-950/45 p-4">
                                    <p class="text-xs uppercase tracking-[0.3em] text-stone-400">Email</p>
                                    <p class="mt-2 break-words text-base font-medium text-white">{{ $email !== '' ? $email : 'Reach out via phone for quick support' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="connect" class="border-t border-white/10 bg-stone-900">
                <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:px-8 lg:py-16">
                    <div class="rounded-[2rem] border border-white/10 bg-stone-950/80 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-amber-300/85">Visit the facility</p>
                        <h2 class="mt-4 text-3xl font-bold text-white sm:text-4xl">Find us fast. Train without delay.</h2>
                        <p class="mt-4 text-base leading-7 text-stone-300">Everything important is here at a glance: your route, contact options, and the quickest way to reach the gym from any device.</p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs uppercase tracking-[0.3em] text-stone-400">Address</p>
                                <p class="mt-3 text-base font-medium text-white">{{ $address !== '' ? $address : 'Insert street address, city, zip code' }}</p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs uppercase tracking-[0.3em] text-stone-400">Contact</p>
                                <div class="mt-3 space-y-3 text-base font-medium text-white">
                                    <p>{{ $phone !== '' ? $phone : 'Insert phone number' }}</p>
                                    <p class="break-words text-stone-300">{{ $email !== '' ? $email : 'Insert email address' }}</p>
                                </div>
                                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                    @if ($phoneHref)
                                        <a
                                            href="{{ $phoneHref }}"
                                            class="inline-flex min-h-12 items-center justify-center rounded-full bg-amber-400 px-5 text-sm font-semibold text-stone-950 transition hover:bg-amber-300"
                                        >
                                            Tap to Call
                                        </a>
                                    @endif

                                    @if ($emailHref)
                                        <a
                                            href="{{ $emailHref }}"
                                            class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/15 px-5 text-sm font-semibold text-white transition hover:bg-white/10"
                                        >
                                            Send Email
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-white/10 bg-[linear-gradient(160deg,_rgba(251,191,36,0.08),_rgba(28,25,23,0.92))] p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-amber-300/85">Location</p>
                        <h3 class="mt-4 text-2xl font-bold text-white sm:text-3xl">Pure address details</h3>
                        <p class="mt-4 text-base leading-7 text-stone-300">Use the address below exactly as listed to visit the facility.</p>
                        <div class="mt-8 rounded-2xl border border-white/10 bg-stone-950/60 p-5 sm:p-6">
                            <p class="text-xs uppercase tracking-[0.3em] text-stone-400">Facility address</p>
                            <p class="mt-4 whitespace-pre-line text-lg font-medium leading-8 text-white">{{ $address !== '' ? $address : 'Insert street address, city, zip code' }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/10 bg-stone-950">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-6 text-sm text-stone-400 sm:px-6 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <p>&copy; 2026 {{ $facilityName }}. All Rights Reserved.</p>
                <p>Powered by <a href="https://beforward.lk" target="_blank" rel="noreferrer" class="text-stone-200 transition hover:text-amber-300">beforward.lk</a></p>
            </div>
        </footer>
    </div>
</x-guest-layout>
