<template>
    <div>
        <!-- Avatar block -->
        <div class="flex flex-col items-center pb-6 pt-4">
            <div class="w-24 h-24 rounded-full bg-gray-900 flex items-center justify-center text-3xl font-bold text-white select-none shadow-md border-4 border-white">
                {{ initials }}
            </div>
            <h2 class="mt-4 text-xl font-bold text-gray-900 tracking-tight">{{ meta.name }}</h2>
            <p class="text-sm text-gray-400 mt-0.5">@{{ meta.username }}</p>
            <span v-if="meta.member_role" class="mt-3 text-xs font-semibold text-gray-600 bg-white border border-gray-200 px-3 py-1 rounded-full shadow-sm">
                {{ meta.member_role }}
            </span>
        </div>

        <!-- Personal info -->
        <section class="mb-4">
            <div class="flex items-center justify-between px-1 mb-3">
                <h3 class="text-base font-bold text-gray-900">Personal info</h3>
            </div>
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">Name</p>
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ meta.name }}</p>
                    </div>
                </div>
                <div v-if="meta.email" class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">E-mail</p>
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ meta.email }}</p>
                    </div>
                </div>
                <div v-if="meta.phone_number" class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">Phone number</p>
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ meta.phone_number }}</p>
                    </div>
                </div>
                <div v-if="meta.gender" class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8" fill="none"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12v8m-3-3h6"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">Gender</p>
                        <p class="text-sm font-semibold text-gray-900">{{ capitalize(meta.gender) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8" fill="none"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="1.8"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">Member since</p>
                        <p class="text-sm font-semibold text-gray-900">{{ meta.joined_date ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Account quick stats -->
        <section class="mb-4">
            <h3 class="text-base font-bold text-gray-900 px-1 mb-3">Account info</h3>
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">Workout Plans</p>
                        <p class="text-sm font-semibold text-gray-900">{{ workoutsData.length }} assigned</p>
                    </div>
                    <span v-if="workoutsData.length" class="text-xs font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">Active</span>
                </div>
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">Transactions</p>
                        <p class="text-sm font-semibold text-gray-900">{{ salesData.length }} total</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sign out -->
        <section class="mb-6">
            <button
                type="button"
                class="w-full py-3.5 rounded-2xl border border-red-200 bg-red-50 text-red-600 text-sm font-bold hover:bg-red-100 active:bg-red-200 transition-colors"
                @click="$emit('logout')"
            >
                Sign out
            </button>
        </section>
    </div>
</template>

<script setup>
defineProps({
    meta:         { type: Object, default: () => ({}) },
    initials:     { type: String, default: '' },
    workoutsData: { type: Array,  default: () => [] },
    salesData:    { type: Array,  default: () => [] },
});

defineEmits(['logout']);

function capitalize(val) {
    if (!val) return '-';
    return val.charAt(0).toUpperCase() + val.slice(1);
}
</script>
