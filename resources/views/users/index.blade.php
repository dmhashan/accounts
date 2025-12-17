<x-app-layout>
    <x-slot name="title">Users - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Users</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg px-6 py-4 flex items-center">
                        <svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 rounded-lg px-6 py-4 flex items-center">
                        <svg class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">User Management</h2>
                    @if(auth()->user()->hasPermission('users.create'))
                        <a href="{{ route('users.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add User
                        </a>
                    @endif
                </div>

                <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                @forelse($users as $user)
                                    <tr class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-sm font-semibold text-white">{{ substr($user->name, 0, 2) }}</span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-secondary-900 dark:text-white">{{ $user->name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-secondary-900 dark:text-white">{{ $user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($user->role)
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $user->role->slug === 'admin' ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300' : 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300' }}">
                                                    {{ $user->role->name }}
                                                </span>
                                            @else
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300">
                                                    No Role
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                @if(auth()->user()->hasPermission('users.edit'))
                                                    <a href="{{ route('users.edit', $user) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                                        Edit
                                                    </a>
                                                @endif
                                                @if(auth()->user()->hasPermission('users.delete') && $user->id !== auth()->id())
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($users->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
