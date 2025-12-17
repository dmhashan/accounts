<x-app-layout>
    <x-slot name="title">Create User - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Create User</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-2xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                        <form action="{{ route('users.store') }}" method="POST">
                            @csrf

                            <div class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Name</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="role_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role</label>
                                    <select name="role_id" id="role_id" required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Select a role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                                    <input type="password" name="password" id="password" required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                </div>

                                <div class="flex items-center justify-end space-x-4 pt-4">
                                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white">
                                        Cancel
                                    </a>
                                    <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                                        Create User
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
