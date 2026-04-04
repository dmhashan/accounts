<x-app-layout>
    <x-slot name="title">Member Public Profile - {{ app('tenant')->name }}</x-slot>
    <script>
        window.__profileWorkouts = @json($workoutsData);
        window.__profileSales = @json($salesData);
        window.__profileMeta = {
            name: @json($publicData['name']),
            username: @json($publicData['username']),
            gender: @json($publicData['gender'] ?? null),
            joined_date: @json($publicData['joined_date'] ? $publicData['joined_date']->format('Y-m-d') : null),
            member_role: @json($publicData['member_role'] ?? null),
            email: @json($publicData['email'] ?? null),
            phone_number: @json($publicData['phone_number'] ?? null),
            tenant_name: @json(app('tenant')->name),
            total_outstanding: @json(number_format($totalOutstanding, 2)),
        };
    </script>
    <div id="public-profile-app"></div>
    @vite('resources/js/public-profile.js')
</x-app-layout>
