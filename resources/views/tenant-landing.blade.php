<x-guest-layout>
    <x-slot name="title">{{ $tenant->name }}</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-primary-500 to-primary-700 px-8 py-12 text-center">
                    <div class="mx-auto h-20 w-20 bg-white rounded-full flex items-center justify-center mb-4 shadow-lg">
                        <span class="text-3xl font-bold text-primary-600">{{ substr($tenant->name, 0, 1) }}</span>
                    </div>
                    <h1 class="text-4xl font-extrabold text-white mb-2">{{ $tenant->name }}</h1>
                    <p class="text-primary-100">Welcome to our platform</p>
                </div>
                
                <!-- Content Section -->
                <div class="px-8 py-10">
                    <div class="space-y-4">
                        <a href="{{ route('login.form') }}" 
                           class="block w-full py-3.5 px-4 text-center text-base font-semibold rounded-lg text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            Sign In
                        </a>
                        
                        <a href="{{ route('register.form') }}" 
                           class="block w-full py-3.5 px-4 text-center text-base font-semibold rounded-lg text-primary-600 bg-primary-50 hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                            Create Account
                        </a>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-secondary-200">
                        <p class="text-xs text-center text-secondary-500">
                            Subdomain: <span class="font-mono text-secondary-700">{{ $tenant->domain }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
