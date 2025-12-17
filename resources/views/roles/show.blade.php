<x-app-layout>
    <x-slot name="title">{{ $role->name }} Permissions - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">{{ $role->name }} Permissions</x-slot>
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

                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-secondary-900 dark:text-white flex items-center">
                                    {{ $role->name }}
                                    @if(!$role->is_editable)
                                        <span class="ml-3 px-3 py-1 text-xs bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-400 rounded-full">
                                            Predefined Role
                                        </span>
                                    @endif
                                </h2>
                                @if($role->description)
                                    <p class="text-secondary-600 dark:text-secondary-400 mt-2">{{ $role->description }}</p>
                                @endif
                            </div>
                            <a href="{{ route('roles.index') }}" class="px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white">
                                Back
                            </a>
                        </div>
                    </div>

                    <form action="{{ route('roles.permissions.update', $role) }}" method="POST">
                        @csrf
                        
                        <div class="space-y-6">
                            @foreach($allPermissions as $feature => $permissions)
                                <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                                    <div class="px-6 py-4 bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">{{ $feature }}</h3>
                                    </div>
                                    <div class="p-6">
                                        <div class="space-y-4">
                                            @foreach($permissions as $permission)
                                                <label class="flex items-start cursor-pointer group">
                                                    <div class="flex items-center h-5">
                                                        <input type="checkbox" 
                                                            name="permissions[]" 
                                                            value="{{ $permission->id }}"
                                                            {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}
                                                            {{ !$role->is_editable ? 'disabled' : '' }}
                                                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-secondary-300 rounded">
                                                    </div>
                                                    <div class="ml-3 flex-1">
                                                        <div class="text-sm font-medium text-secondary-700 dark:text-secondary-300 group-hover:text-secondary-900 dark:group-hover:text-white">
                                                            {{ $permission->name }}
                                                        </div>
                                                        @if($permission->description)
                                                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">{{ $permission->description }}</p>
                                                        @endif
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($role->is_editable && auth()->user()->hasPermission('roles.permissions'))
                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                                    Update Permissions
                                </button>
                            </div>
                        @else
                            <div class="mt-6 p-4 bg-secondary-50 dark:bg-background-dark rounded-lg border border-secondary-200 dark:border-secondary-700">
                                <p class="text-sm text-secondary-600 dark:text-secondary-400 flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    This is a predefined role. Permissions cannot be modified.
                                </p>
                            </div>
                        @endif
                    </form>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
