<x-guest-layout>
    <x-slot name="title">Welcome</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center">
                <div class="mx-auto mb-8">
                    <img src="{{ asset('images/black-text-logo.png') }}" alt="Platform Logo" class="h-24 mx-auto">
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
