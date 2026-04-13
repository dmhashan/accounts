<template>
    <nav class="fixed bottom-0 inset-x-0 z-20 bg-white border-t border-gray-100 safe-area-bottom">
        <div class="max-w-lg mx-auto flex">
            <button
                v-for="tab in navTabs"
                :key="tab.key"
                type="button"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-3 transition-colors focus:outline-none"
                :class="isActive(tab) ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
                @click="router.push(tab.path)"
            >
                <component :is="tab.icon" class="w-5 h-5" />
                <span class="text-[10px] font-semibold leading-none">{{ tab.label }}</span>
                <span v-if="isActive(tab)" class="w-5 h-0.5 rounded-full bg-red-500 mt-0.5"></span>
                <span v-else class="w-5 h-0.5 mt-0.5"></span>
            </button>
        </div>
    </nav>
</template>

<script setup>
import { h } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route  = useRoute();
const router = useRouter();

function isActive(tab) {
    return route.path === tab.path;
}

const IconHome = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' }),
    ]),
};

const IconWorkout = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M13 10V3L4 14h7v7l9-11h-7z' }),
    ]),
};

const IconTransactions = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' }),
    ]),
};

const IconBell = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' }),
    ]),
};

const IconProfile = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }),
    ]),
};

const navTabs = [
    { key: 'home',          path: '/',              label: 'Home',     icon: IconHome },
    { key: 'workout',       path: '/workout',        label: 'Workout',  icon: IconWorkout },
    { key: 'notifications', path: '/notifications',  label: 'Notifications', icon: IconBell },
    { key: 'transactions',  path: '/transactions',   label: 'Payments', icon: IconTransactions },
    { key: 'profile',       path: '/profile',        label: 'Profile',  icon: IconProfile },
];
</script>
