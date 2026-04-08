<template>
    <nav class="fixed bottom-0 inset-x-0 z-20 bg-white border-t border-gray-100 safe-area-bottom">
        <div class="max-w-lg mx-auto flex">
            <button
                v-for="tab in navTabs"
                :key="tab.key"
                type="button"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-3 transition-colors focus:outline-none"
                :class="activeNav === tab.key ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
                @click="$emit('navigate', tab.key)"
            >
                <component :is="tab.icon" class="w-5 h-5" />
                <span class="text-[10px] font-semibold leading-none">{{ tab.label }}</span>
                <span v-if="activeNav === tab.key" class="w-5 h-0.5 rounded-full bg-red-500 mt-0.5"></span>
                <span v-else class="w-5 h-0.5 mt-0.5"></span>
            </button>
        </div>
    </nav>
</template>

<script setup>
import { h } from 'vue';

defineProps({
    activeNav: { type: String, default: 'home' },
});

defineEmits(['navigate']);

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

const IconProfile = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }),
    ]),
};

const navTabs = [
    { key: 'home',         label: 'Home',         icon: IconHome },
    { key: 'workout',      label: 'Workout',       icon: IconWorkout },
    { key: 'transactions', label: 'Payments',  icon: IconTransactions },
    { key: 'profile',      label: 'Profile',       icon: IconProfile },
];
</script>
