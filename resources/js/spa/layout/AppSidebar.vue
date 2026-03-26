<template>
    <aside class="hidden lg:flex lg:w-[19rem] lg:flex-col lg:pl-4 lg:py-4 xl:pl-5">
        <div class="app-surface flex h-[calc(100vh-2rem)] flex-col rounded-3xl overflow-hidden">
            <div class="px-5 py-5 border-b border-secondary-200/70 dark:border-secondary-700/70">
                <p class="text-[11px] uppercase tracking-[0.14em] text-secondary-500 dark:text-secondary-400">Workspace</p>
                <h2 class="mt-1 text-lg font-bold text-secondary-900 dark:text-white truncate">{{ context.tenant?.name || 'Tenant App' }}</h2>
            </div>

            <nav class="flex-1 overflow-y-auto p-3 space-y-1.5">
                <RouterLink v-for="item in menuItems" :key="item.path" :to="item.path"
                    class="app-nav-link"
                    :class="isActive(item.path) ? 'app-nav-link-active' : ''">
                    <span class="truncate">{{ item.label }}</span>
                </RouterLink>
            </nav>

            <div class="m-3 p-3 rounded-2xl app-surface-soft">
                <p class="text-xs text-secondary-600 dark:text-secondary-300">Signed in as</p>
                <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ context.user?.name }}</p>
                <p class="text-[11px] text-secondary-500 dark:text-secondary-400 truncate">{{ context.user?.email }}</p>
            </div>

            <div class="p-3 border-t border-secondary-200/70 dark:border-secondary-700/70">
                <form :action="context.legacyUrls?.logout" method="POST" class="space-y-2">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        Logout
                    </button>
                </form>
            </div>
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
