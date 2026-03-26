<template>
    <div>
        <div v-if="open" class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm z-40 lg:hidden" @click="$emit('close')"></div>

        <aside class="fixed inset-y-0 left-0 z-50 w-80 max-w-[92vw] app-surface border-r-0 transform transition-transform duration-300 lg:hidden flex flex-col"
            :class="open ? 'translate-x-0' : '-translate-x-full'">
            <div class="h-16 px-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Navigation</p>
                    <h2 class="text-lg font-bold text-secondary-900 dark:text-white truncate">{{ context.tenant?.name || 'Tenant App' }}</h2>
                </div>
                <button type="button" class="p-2 rounded-xl text-secondary-500 hover:bg-secondary-100 dark:hover:bg-secondary-700" @click="$emit('close')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 space-y-1.5">
                <RouterLink
                    v-for="item in menuItems"
                    :key="item.path"
                    :to="item.path"
                    class="app-nav-link"
                    :class="isActive(item.path) ? 'app-nav-link-active' : ''"
                    @click="$emit('close')"
                >
                    <component :is="item.icon" class="h-[18px] w-[18px] flex-shrink-0" :stroke-width="2" />
                    {{ item.label }}
                </RouterLink>
            </nav>

            <div class="p-3 border-t border-secondary-200/70 dark:border-secondary-700/70 [padding-bottom:calc(0.75rem+env(safe-area-inset-bottom))]">
                <!-- Expanded profile options -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div v-if="profileOpen" class="mb-2 rounded-2xl overflow-hidden border border-secondary-200/70 dark:border-secondary-700/70">
                        <button @click="toggleTheme" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-700/50 transition-colors">
                            <svg v-if="isDark" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            {{ isDark ? 'Light Mode' : 'Dark Mode' }}
                        </button>
                        <div class="border-t border-secondary-200/50 dark:border-secondary-700/50"></div>
                        <form :action="context.legacyUrls?.logout" method="POST">
                            <input type="hidden" name="_token" :value="csrfToken">
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </Transition>

                <!-- Profile button -->
                <button @click="profileOpen = !profileOpen" class="w-full flex items-center gap-3 p-3 rounded-2xl hover:bg-secondary-100 dark:hover:bg-secondary-700/50 transition-colors text-left">
                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-primary-500 to-red-700 flex-shrink-0 flex items-center justify-center text-white text-xs font-bold">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ context.user?.name }}</p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ context.user?.email }}</p>
                    </div>
                    <svg class="h-4 w-4 text-secondary-400 flex-shrink-0 transition-transform duration-200" :class="profileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </aside>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
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
const isDark = ref(document.documentElement.classList.contains('dark'));
const profileOpen = ref(false);

const initials = computed(() => {
    const name = context.user?.name || '';
    return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() || '').join('') || '?';
});

function toggleTheme() {
    const root = document.documentElement;
    if (root.classList.contains('dark')) {
        root.classList.remove('dark');
        localStorage.theme = 'light';
        isDark.value = false;
    } else {
        root.classList.add('dark');
        localStorage.theme = 'dark';
        isDark.value = true;
    }
}

function isActive(path) {
    return route.path === path;
}
</script>
