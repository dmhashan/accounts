<x-app-layout>
    <x-slot name="title">Member Portal - {{ app('tenant')->name }}</x-slot>
    <script>
        window.__tenantName = @json(app('tenant')->name);
    </script>
    <div id="public-profile-app"></div>
    @vite('resources/js/public-profile.js')
</x-app-layout>
