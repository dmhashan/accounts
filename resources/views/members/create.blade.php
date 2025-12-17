<x-app-layout>
    <x-slot name="title">Add Member - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Add Member</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                        <form action="{{ route('members.store') }}" method="POST">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Full Name *</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="gender" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Gender *</label>
                                    <select name="gender" id="gender" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        <option value="">Select gender</option>
                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('gender')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Date of Birth</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('date_of_birth')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Email *</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Phone Number</label>
                                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('phone_number')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="nic" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">NIC Number</label>
                                    <input type="text" name="nic" id="nic" value="{{ old('nic') }}"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('nic')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="comment" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Comment</label>
                                    <textarea name="comment" id="comment" rows="4"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">{{ old('comment') }}</textarea>
                                    @error('comment')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">
                                    <strong>Note:</strong> A user account will be automatically created for this member with login access to the system.
                                </p>
                            </div>

                            <div class="flex items-center justify-end space-x-4 pt-6">
                                <a href="{{ route('members.index') }}" class="px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white">
                                    Cancel
                                </a>
                                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                                    Create Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
