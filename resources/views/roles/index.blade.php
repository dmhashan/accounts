<x-app-layout>
    <x-slot name="title">Roles - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Roles & Permissions</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="mb-6">
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white mb-2">Role Management</h2>
                    <p class="text-secondary-600 dark:text-secondary-400">Manage roles and their permissions</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($roles as $role)
                        <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white flex items-center">
                                        {{ $role->name }}
                                        @if(!$role->is_editable)
                                            <span class="ml-2 px-2 py-1 text-xs bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-400 rounded">
                                                Predefined
                                            </span>
                                        @endif
                                    </h3>
                                    @if($role->description)
                                        <p class="text-sm text-secondary-600 dark:text-secondary-400 mt-1">{{ $role->description }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-3 mb-4">
                                <div class="flex items-center text-sm">
                                    <svg class="h-5 w-5 text-secondary-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span class="text-secondary-600 dark:text-secondary-400">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <svg class="h-5 w-5 text-secondary-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <span class="text-secondary-600 dark:text-secondary-400">{{ $role->permissions_count }} {{ Str::plural('permission', $role->permissions_count) }}</span>
                                </div>
                            </div>

                            <a href="{{ route('roles.show', $role) }}" class="block w-full px-4 py-2 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/50 rounded-lg text-center font-medium transition-colors">
                                Manage Permissions
                            </a>
                        </div>
                    @endforeach
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
