<template>
    <div>
        <div v-if="open" class="fixed inset-0 bg-black/50 z-40 lg:hidden" @click="$emit('close')"></div>

        <aside class="fixed inset-y-0 left-0 z-50 w-80 max-w-[90vw] bg-white dark:bg-secondary-900 border-r border-secondary-200 dark:border-secondary-700 transform transition-transform duration-300 lg:hidden"
            :class="open ? 'translate-x-0' : '-translate-x-full'">
            <div class="h-16 px-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-secondary-900 dark:text-white truncate">{{ context.tenant?.name || 'Tenant App' }}</h2>
                </div>
                <button type="button" class="p-2 rounded-lg text-secondary-500 hover:bg-secondary-100 dark:hover:bg-secondary-700" @click="$emit('close')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-4 border-b border-secondary-200 dark:border-secondary-700">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ context.user?.name }}</p>
                <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ context.user?.email }}</p>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <RouterLink
                    v-for="item in menuItems"
                    :key="item.path"
                    :to="item.path"
                    class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive(item.path)
                        ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white'
                        : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                    @click="$emit('close')"
                >
                    {{ item.label }}
                </RouterLink>
            </nav>

            <div class="p-4 border-t border-secondary-200 dark:border-secondary-700">
                <form :action="context.legacyUrls?.logout" method="POST">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button type="submit" class="w-full rounded-lg px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </aside>
    </div>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useNavigation } from '../composables/useNavigation';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const route = useRoute();
const { context, menuItems } = useNavigation();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function isActive(path) {
    return route.path === path;
}
</script>
