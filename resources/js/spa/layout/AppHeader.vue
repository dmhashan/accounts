<template>
    <header class="h-16 border-b border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 px-4 md:px-6 flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <button type="button" class="lg:hidden p-2 rounded-lg text-secondary-500 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors" @click="$emit('open-menu')">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="min-w-0">
            <h1 class="text-lg md:text-xl font-bold text-secondary-900 dark:text-white truncate">{{ pageTitle }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" @click="toggleTheme" class="p-2 rounded-lg text-secondary-500 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors">
                <svg class="h-5 w-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="h-5 w-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>

            <div class="text-right hidden sm:block">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ context.user?.name }}</p>
                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ context.user?.email }}</p>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAppContext } from '../composables/useAppContext';

const route = useRoute();
const context = useAppContext();

defineEmits(['open-menu']);

const pageTitle = computed(() => {
    return route.path.replace('/', '').replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) || 'Dashboard';
});

function toggleTheme() {
    const root = document.documentElement;
    if (root.classList.contains('dark')) {
        root.classList.remove('dark');
        localStorage.theme = 'light';
    } else {
        root.classList.add('dark');
        localStorage.theme = 'dark';
    }
}
</script>
