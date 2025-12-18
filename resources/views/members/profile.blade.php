<x-app-layout>
    <x-slot name="title">My Profile - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">My Profile</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                        <!-- Profile Header -->
                        <div class="bg-gradient-to-r from-primary-500 to-primary-700 px-6 py-8">
                            <div class="flex items-center">
                                <div class="h-20 w-20 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-3xl font-bold text-white">{{ substr($member->name, 0, 2) }}</span>
                                </div>
                                <div class="ml-6">
                                    <h2 class="text-2xl font-bold text-white">{{ $member->name }}</h2>
                                    <p class="text-primary-100">Member ID: {{ $member->member_id }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Details -->
                        <div class="p-6 space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-4">Personal Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">Email</label>
                                        <p class="text-secondary-900 dark:text-white">{{ $member->email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">Gender</label>
                                        <p class="text-secondary-900 dark:text-white">{{ ucfirst($member->gender) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">Phone Number</label>
                                        <p class="text-secondary-900 dark:text-white">{{ $member->phone_number ?? 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">NIC</label>
                                        <p class="text-secondary-900 dark:text-white">{{ $member->nic ?? 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">Date of Birth</label>
                                        <p class="text-secondary-900 dark:text-white">
                                            {{ $member->date_of_birth ? \Carbon\Carbon::parse($member->date_of_birth)->format('F j, Y') : 'Not provided' }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">Status</label>
                                        @if($member->is_active)
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                    @if($member->comment)
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-1">Notes</label>
                                            <p class="text-secondary-900 dark:text-white">{{ $member->comment }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-4 border-t border-secondary-200 dark:border-secondary-700">
                                <h3 class="text-sm font-medium text-secondary-500 dark:text-secondary-400 mb-2">Member Since</h3>
                                <p class="text-secondary-900 dark:text-white">{{ $member->created_at->format('F j, Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
