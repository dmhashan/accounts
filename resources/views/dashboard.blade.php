<x-app-layout>
    <x-slot name="title">Dashboard - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Component -->
            <x-header>
                <x-slot name="title">Dashboard</x-slot>
            </x-header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @php
                    $tenantPayload = [
                        'name' => app('tenant')->name,
                        'id' => app('tenant')->id,
                        'domain' => app('tenant')->domain,
                    ];

                    $userPayload = [
                        'name' => auth()->user()->name,
                        'id' => auth()->user()->id,
                        'email' => auth()->user()->email,
                    ];
                @endphp

                <div id="dashboard-overview"
                    data-tenant='@json($tenantPayload)'
                    data-user='@json($userPayload)'
                    data-app-domain="{{ config('app.domain') }}"
                    data-success-message="{{ session('success', '') }}">
                </div>
            </main>
        </div>
    </div>
</x-app-layout>
