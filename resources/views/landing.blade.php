<x-guest-layout>
    <x-slot name="title">Welcome</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center">
                <div class="mx-auto h-24 w-24 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center mb-8 shadow-lg">
                    <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-secondary-900 mb-4">Welcome to Our Platform</h1>
                <p class="text-lg text-secondary-600 mb-8">Multi-tenant application platform. Please use a tenant subdomain to access your account.</p>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <p class="text-sm text-secondary-500">Example: <span class="font-mono text-primary-600">company-1.{{ config('app.domain') }}</span></p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
