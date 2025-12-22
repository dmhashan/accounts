<x-guest-layout>
    <x-slot name="title">Reset Password - {{ app('tenant')->name }}</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto h-16 w-16 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-secondary-900">Reset Password</h2>
                    <p class="mt-2 text-sm text-secondary-600">Enter your new password below.</p>
                </div>

                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">
                    
                    <div>
                        <label for="email_display" class="block text-sm font-medium text-secondary-700 mb-2">Email Address</label>
                        <input id="email_display" type="email" disabled
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg bg-secondary-50 text-secondary-500"
                               value="{{ $email }}">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">New Password</label>
                        <input id="password" name="password" type="password" required
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                               placeholder="••••••••">
                        @include('components.password-criteria', ['passwordId' => 'password', 'confirmId' => 'password_confirmation'])
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-2">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                               placeholder="••••••••">
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Reset Password
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login.form') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 transition-colors">
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
