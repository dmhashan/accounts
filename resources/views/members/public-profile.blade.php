<x-app-layout>
    <x-slot name="title">Member Public Profile - {{ app('tenant')->name }}</x-slot>
    <div class="flex justify-center min-h-screen bg-background-light dark:bg-background-dark py-6 px-2 sm:px-0">
        <div class="w-full max-w-2xl bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-4 sm:p-8">
            <h2 class="text-xl sm:text-2xl font-bold text-secondary-900 dark:text-white mb-2 text-center">{{ $publicData['name'] }}</h2>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-2 mb-6">
                <span class="text-secondary-500 dark:text-secondary-400">Hi Welcome to {{ app('tenant')->name }}</span>
            </div>

            <!-- Tabs -->
            <div x-data="{ tab: 'profile' }" class="w-full">
                <div class="flex border-b border-secondary-200 dark:border-secondary-700 mb-4">
                    <button type="button" class="flex-1 py-2 text-center text-sm font-semibold focus:outline-none transition-colors"
                        :class="tab === 'profile' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-300' : 'text-secondary-700 dark:text-secondary-300'"
                        @click="tab = 'profile'">Profile</button>
                    <button type="button" class="flex-1 py-2 text-center text-sm font-semibold focus:outline-none transition-colors"
                        :class="tab === 'workout' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-300' : 'text-secondary-700 dark:text-secondary-300'"
                        @click="tab = 'workout'">Workout</button>
                    <button type="button" class="flex-1 py-2 text-center text-sm font-semibold focus:outline-none transition-colors"
                        :class="tab === 'finance' ? 'border-b-2 border-primary-600 text-primary-700 dark:text-primary-300' : 'text-secondary-700 dark:text-secondary-300'"
                        @click="tab = 'finance'">Finance</button>
                </div>

                <!-- Profile Tab -->
                <div x-show="tab === 'profile'" class="space-y-3" x-cloak>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Full Name</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ $publicData['name'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Username</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ $publicData['username'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Gender</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ ucfirst($publicData['gender'] ?? '-') }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-secondary-500 dark:text-secondary-400">Joined</span>
                            <span class="block text-base font-medium text-secondary-900 dark:text-white">{{ $publicData['joined_date'] ? $publicData['joined_date']->format('Y-m-d') : '-' }}</span>
                        </div>
                    </div>
                </div>

<!-- Workout Tab -->
                <div x-show="tab === 'workout'" class="space-y-2" x-cloak>
                    @if($assignedWorkouts->isEmpty())
                        <div class="text-secondary-500 dark:text-secondary-400 text-center py-6">No assigned workout plans.</div>
                    @else
                        <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                            @foreach($assignedWorkouts as $assignment)
                                <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                    <div>
                                        <div class="font-semibold text-secondary-900 dark:text-white">{{ $assignment->assignedProgram->title ?? 'N/A' }}</div>
                                        <div class="text-xs text-secondary-500 dark:text-secondary-400">Effective: {{ $assignment->effective_date ? $assignment->effective_date->format('Y-m-d') : '-' }}</div>
                                    </div>
                                    <span class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0">{{ $assignment->assignedProgram->duration_weeks ?? '-' }} weeks</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Finance Tab -->
                <div x-show="tab === 'finance'" class="space-y-3" x-cloak>
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-semibold text-secondary-900 dark:text-white">Total Outstanding:</span>
                        <span class="text-lg font-bold text-red-600">{{ number_format($totalOutstanding, 2) }}</span>
                    </div>
                    @if($sales->isEmpty())
                        <div class="text-secondary-500 dark:text-secondary-400 text-center py-6">No finance records found.</div>
                    @else
                        <div class="divide-y divide-secondary-100 dark:divide-secondary-800">
                            @foreach($sales as $sale)
                                <div class="py-3 flex flex-col gap-1 @if(!$sale->is_paid) bg-red-50 dark:bg-red-900/30 border-l-4 border-red-400 @endif rounded">
                                    <div class="flex flex-wrap justify-between items-center">
                                        <span class="font-semibold text-secondary-900 dark:text-white">Invoice #{{ $sale->id }}</span>
                                        <span class="text-xs text-secondary-500 dark:text-secondary-400">{{ $sale->created_at->format('Y-m-d') }}</span>
                                    </div>
                                    <div class="flex flex-wrap justify-between items-center">
                                        <span>Total: <span class="font-semibold">{{ number_format($sale->total_amount, 2) }}</span></span>
                                        <span>Paid: <span class="font-semibold">{{ number_format($sale->paid_amount, 2) }}</span></span>
                                        <span>Outstanding: <span class="font-semibold @if(!$sale->is_paid) text-red-600 @endif">{{ number_format($sale->balance, 2) }}</span></span>
                                    </div>
                                    @if(!$sale->is_paid)
                                        <span class="text-xs text-red-600 font-semibold">Unpaid</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Alpine.js for tabs -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>
