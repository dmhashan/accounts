<x-app-layout>
    <x-slot name="title">Member Portal - {{ app('tenant')->name }}</x-slot>
    <script>
        @php
            $__tenant = app('tenant');
            $__logoUrl = $__tenant->logo_path
                ? app(\App\Services\MediaStorageService::class)->url($__tenant->logo_path)
                : null;
        @endphp
        window.__tenantName    = @json($__tenant->name);
        window.__tenantLogoUrl = @json($__logoUrl);
        window.__tenantAddress = @json($__tenant->address);
        window.__tenantEmail   = @json($__tenant->email);
        window.__tenantPhone   = @json($__tenant->phone);
    </script>
    <div id="public-profile-app"></div>
    @vite('resources/js/public-profile.js')
</x-app-layout>
