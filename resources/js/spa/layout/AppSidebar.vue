<template>
    <aside class="w-72 border-r border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 hidden lg:flex lg:flex-col">
        <div class="h-16 px-6 border-b border-secondary-200 dark:border-secondary-700 flex items-center">
            <h2 class="text-lg font-bold text-secondary-900 dark:text-white truncate">{{ context.tenant?.name || 'Tenant App' }}</h2>
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            <RouterLink v-for="item in menuItems" :key="item.path" :to="item.path"
                class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                :class="isActive(item.path)
                    ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white'
                    : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'">
                {{ item.label }}
            </RouterLink>
        </nav>

        <div class="p-4 border-t border-secondary-200 dark:border-secondary-700">
            <form :action="context.legacyUrls?.logout" method="POST" class="space-y-2">
                <input type="hidden" name="_token" :value="csrfToken">
                <button type="submit" class="w-full rounded-lg px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    Logout
                </button>
            </form>
        </div>
    </aside>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useNavigation } from '../composables/useNavigation';

const route = useRoute();
const { context, menuItems } = useNavigation();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function isActive(path) {
    return route.path === path;
}
</script>
