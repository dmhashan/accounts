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
    @endphp

    <div class="min-h-screen bg-gradient-to-b from-gray-900 to-gray-800 flex items-center justify-center px-4">
        <div class="w-full max-w-xl bg-white rounded-2xl shadow-xl p-8 md:p-10 text-center">
            @if ($logoUrl)
                <img
                    src="{{ $logoUrl }}"
                    alt="{{ $tenant->name }} logo"
                    class="mx-auto mb-5 h-16 w-16 rounded-xl object-cover ring-1 ring-gray-200"
                >
            @endif

            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Welcome</h1>
            <p class="text-xl text-primary-600 font-semibold mb-4">{{ $tenant->name }}</p>

            <div class="mb-8 space-y-2 text-sm text-gray-600">
                @if (!empty($tenant->address))
                    <p>{{ $tenant->address }}</p>
                @endif

                @if (!empty($tenant->phone) || !empty($tenant->email))
                    <p>
                        @if (!empty($tenant->phone))
                            <span>{{ $tenant->phone }}</span>
                        @endif
                        @if (!empty($tenant->phone) && !empty($tenant->email))
                            <span class="mx-2 text-gray-400">|</span>
                        @endif
                        @if (!empty($tenant->email))
                            <span>{{ $tenant->email }}</span>
                        @endif
                    </p>
                @endif
            </div>

            <div class="flex justify-center">
                <a
                    href="{{ route('login.form') }}"
                    class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-medium"
                >
                    Login
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
