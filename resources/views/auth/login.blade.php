<x-guest-layout>
    <x-slot name="title">Login - {{ app('tenant')->name }}</x-slot>
    @php
        $baseInputClass = 'app-form-control block h-12 w-full rounded-2xl border px-4 text-sm outline-none';
        $tenantLogoUrl = app('tenant')->logo_path
            ? app(\App\Services\MediaStorageService::class)->url(app('tenant')->logo_path)
            : asset('images/black-text-logo.png');
    @endphp
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-md w-full">
            <div class="app-auth-card rounded-2xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="amx-auto">
                        <img src="{{ $tenantLogoUrl }}" alt="{{ app('tenant')->name }} logo" class="h-32 mx-auto object-contain">
                    </div>
                    <h2 class="text-3xl font-bold" style="color: var(--text-strong)">Welcome Back</h2>
                    <p class="mt-2 text-sm" style="color: var(--text-muted)">Sign in to your account</p>
                </div>

                @if(session('error'))
                    <div class="app-alert app-alert-error mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <label for="login" class="app-auth-label block text-sm font-medium mb-2">Username or Email</label>
                        <input id="login" name="login" type="text" required autofocus
                               class="{{ $baseInputClass }} @error('login') border-red-300 focus:border-red-300 focus:ring-red-100 @enderror"
                               placeholder="john.doe or you@example.com" value="{{ old('login') }}">
                        @error('login')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="app-auth-label block text-sm font-medium mb-2">Password</label>
                        <input id="password" name="password" type="password" required
                               class="{{ $baseInputClass }} @error('password') border-red-300 focus:border-red-300 focus:ring-red-100 @enderror"
                               placeholder="••••••••">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Sign In
                    </button>
                </form>

                <div class="my-6 flex items-center">
                    <div class="app-auth-divider flex-1 border-t"></div>
                    <span class="px-3 text-xs uppercase tracking-wider" style="color: var(--text-muted)">or continue with</span>
                    <div class="app-auth-divider flex-1 border-t"></div>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('auth.social.redirect', 'google') }}"
                        class="app-auth-secondary-action w-full inline-flex items-center justify-center py-3.5 px-4 border font-semibold rounded-2xl transition-colors">
                        Continue with Google
                    </a>

                    <a href="{{ route('auth.social.redirect', 'apple') }}"
                        class="app-auth-primary-neutral-action w-full inline-flex items-center justify-center py-3.5 px-4 text-white font-semibold rounded-2xl transition-colors">
                        Continue with Apple
                    </a>
                </div>

                <div class="mt-6 text-center">
                    <a href="{{ route('register.form') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 transition-colors">
                        Don't have an account? <span class="underline">Create one</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
