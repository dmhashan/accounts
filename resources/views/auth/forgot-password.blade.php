<x-guest-layout>
    <x-slot name="title">Forgot Password - {{ app('tenant')->name }}</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto h-16 w-16 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-secondary-900">Forgot Password?</h2>
                    <p class="mt-2 text-sm text-secondary-600">No worries! Enter your email address and we'll send you a link to reset your password.</p>
                </div>

                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">
                        <p class="text-sm">{{ session('success') }}</p>
                        @if(session('resetLink'))
                            <div class="mt-3 p-3 bg-white rounded border border-green-300">
                                <p class="text-xs text-secondary-600 mb-2"><strong>Development Only - Reset Link:</strong></p>
                                <a href="{{ session('resetLink') }}" class="text-xs text-primary-600 hover:text-primary-700 break-all">
                                    {{ session('resetLink') }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('password.email') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">Email Address</label>
                        <input id="email" name="email" type="email" required autofocus
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                               placeholder="you@example.com" value="{{ old('email') }}">
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Send Reset Link
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login.form') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 transition-colors">
                        <span class="flex items-center justify-center">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Login
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
