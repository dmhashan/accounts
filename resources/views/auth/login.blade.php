<x-guest-layout>
    <x-slot name="title">Login - {{ app('tenant')->name }}</x-slot>
    @php
        $baseInputClass = 'block h-12 w-full rounded-2xl border border-secondary-300 bg-white px-4 text-sm text-secondary-900 shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition placeholder:text-secondary-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10';
        $tenantLogoUrl = app('tenant')->logo_path
            ? app(\App\Services\MediaStorageService::class)->url(app('tenant')->logo_path)
            : asset('images/black-text-logo.png');
    @endphp
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto">
                        <img src="{{ $tenantLogoUrl }}" alt="{{ app('tenant')->name }} logo" class="h-32 mx-auto object-contain">
                    </div>
                    <h2 class="text-3xl font-bold text-secondary-900">Welcome Back</h2>
                    <p class="mt-2 text-sm text-secondary-600">Sign in to your account</p>
                </div>

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <label for="login" class="block text-sm font-medium text-secondary-700 mb-2">Username or Email</label>
                        <input id="login" name="login" type="text" required autofocus
                               class="{{ $baseInputClass }} @error('login') border-red-300 focus:border-red-300 focus:ring-red-100 @enderror"
                               placeholder="john.doe or you@example.com" value="{{ old('login') }}">
                        @error('login')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">Password</label>
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
                    <div class="flex-1 border-t border-secondary-200"></div>
                    <span class="px-3 text-xs uppercase tracking-wider text-secondary-500">or continue with</span>
                    <div class="flex-1 border-t border-secondary-200"></div>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('auth.social.redirect', 'google') }}"
                        class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-secondary-300 text-secondary-900 font-semibold rounded-2xl hover:bg-secondary-50 transition-colors">
                        Continue with Google
                    </a>

                    <a href="{{ route('auth.social.redirect', 'apple') }}"
                        class="w-full inline-flex items-center justify-center py-3.5 px-4 bg-secondary-900 hover:bg-secondary-800 text-white font-semibold rounded-2xl transition-colors">
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
