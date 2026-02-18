<x-guest-layout>
    <x-slot name="title">{{ $tenant->name }} - Welcome</x-slot>

    <div class="min-h-screen bg-gradient-to-b from-gray-900 to-gray-800 flex items-center justify-center px-4">
        <div class="w-full max-w-xl bg-white rounded-2xl shadow-xl p-8 md:p-10 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Welcome</h1>
            <p class="text-xl text-primary-600 font-semibold mb-8">{{ $tenant->name }}</p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a
                    href="{{ route('login.form') }}"
                    class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-medium"
                >
                    Login
                </a>
                <a
                    href="{{ route('register.form') }}"
                    class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-primary-700 bg-primary-100 hover:bg-primary-200 transition-colors font-medium"
                >
                    Register
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
