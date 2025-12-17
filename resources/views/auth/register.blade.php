<x-guest-layout>
    <x-slot name="title">Register - {{ app('tenant')->name }}</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto h-16 w-16 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-secondary-900">Create Account</h2>
                    <p class="mt-2 text-sm text-secondary-600">{{ app('tenant')->name }}</p>
                </div>

                <form action="{{ route('register') }}" method="POST" class="space-y-5">
                    @csrf
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-secondary-700 mb-2">Full Name</label>
                        <input id="name" name="name" type="text" required autofocus
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('name') border-red-300 @enderror"
                               placeholder="John Doe" value="{{ old('name') }}">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">Email Address</label>
                        <input id="email" name="email" type="email" required
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('email') border-red-300 @enderror"
                               placeholder="you@example.com" value="{{ old('email') }}">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">Password</label>
                        <input id="password" name="password" type="password" required
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('password') border-red-300 @enderror"
                               placeholder="••••••••">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-2">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                               placeholder="••••••••">
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Create Account
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login.form') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 transition-colors">
                        Already have an account? <span class="underline">Sign in</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
