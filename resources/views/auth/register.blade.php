<x-guest-layout>
    <x-slot name="title">Register - {{ app('tenant')->name }}</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-3xl w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto mb-6">
                        <img src="{{ asset('images/black-text-logo.png') }}" alt="{{ app('tenant')->name }}" class="h-16 mx-auto">
                    </div>
                    <h2 class="text-3xl font-bold text-secondary-900">Create Account</h2>
                    <p class="mt-2 text-sm text-secondary-600">Join us today</p>
                </div>

                <form action="{{ route('register') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-medium text-secondary-700 mb-2">Full Name *</label>
                            <input id="name" name="name" type="text" required autofocus
                                   class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('name') border-red-300 @enderror"
                                   placeholder="John Doe" value="{{ old('name') }}">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="gender" class="block text-sm font-medium text-secondary-700 mb-2">Gender *</label>
                            <select name="gender" id="gender" required
                                class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 @error('gender') border-red-300 @enderror">
                                <option value="">Select gender</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-secondary-700 mb-2">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}"
                                class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 @error('date_of_birth') border-red-300 @enderror">
                            @error('date_of_birth')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">Email Address *</label>
                            <input id="email" name="email" type="email" required
                                   class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('email') border-red-300 @enderror"
                                   placeholder="you@example.com" value="{{ old('email') }}">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-secondary-700 mb-2">Phone Number</label>
                            <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}"
                                class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 @error('phone_number') border-red-300 @enderror"
                                placeholder="+1234567890">
                            @error('phone_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nic" class="block text-sm font-medium text-secondary-700 mb-2">NIC Number</label>
                            <input type="text" name="nic" id="nic" value="{{ old('nic') }}"
                                class="w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 @error('nic') border-red-300 @enderror"
                                placeholder="123456789V">
                            @error('nic')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">Password *</label>
                            <input id="password" name="password" type="password" required
                                   class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors @error('password') border-red-300 @enderror"
                                   placeholder="••••••••">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-secondary-700 mb-2">Confirm Password *</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                   class="block w-full px-4 py-3 border border-secondary-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-primary-50 rounded-lg">
                        <p class="text-sm text-primary-700">
                            <strong>Note:</strong> Your account will be created and pending verification by our team.
                        </p>
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
