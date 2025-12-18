<x-app-layout>
    <x-slot name="title">Members - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Members</x-slot>
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

                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Member Management</h2>
                    @if(auth()->user()->hasPermission('users.create'))
                        <a href="{{ route('members.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Member
                        </a>
                    @endif
                </div>

                <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Member ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Verified</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                @forelse($members as $member)
                                    <tr class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-secondary-900 dark:text-white">{{ $member->member_id }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-sm font-semibold text-white">{{ substr($member->name, 0, 2) }}</span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-secondary-900 dark:text-white">{{ $member->name }}</div>
                                                    <div class="text-xs text-secondary-500 dark:text-secondary-400">{{ ucfirst($member->gender) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-secondary-900 dark:text-white">{{ $member->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-secondary-900 dark:text-white">{{ $member->phone_number ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($member->is_active)
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                    Active
                                                </span>
                                            @else
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($member->is_verified)
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                    Verified
                                                </span>
                                            @else
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                                    Unverified
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                @if(auth()->user()->hasPermission('users.edit'))
                                                    <a href="{{ route('members.edit', $member) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                                        Edit
                                                    </a>
                                                    <form action="{{ route('members.toggle-status', $member) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-secondary-600 hover:text-secondary-900 dark:text-secondary-400 dark:hover:text-secondary-300">
                                                            {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('members.toggle-verification', $member) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                            {{ $member->is_verified ? 'Unverify' : 'Verify' }}
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(auth()->user()->hasPermission('users.delete'))
                                                    <form action="{{ route('members.destroy', $member) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this member?');">
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
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <svg class="h-12 w-12 text-secondary-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <p class="text-secondary-500 dark:text-secondary-400">No members found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
