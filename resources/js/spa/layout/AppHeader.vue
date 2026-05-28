<template>
  <header class="sticky top-0 z-20 px-3 pt-3 sm:px-4 md:px-6 md:pt-4">
    <div class="app-glass rounded-2xl px-3 py-2.5 sm:px-4 md:px-5 md:py-3 flex items-center justify-between gap-3">
      <div class="flex min-w-0 items-center gap-3 md:gap-4">
        <button type="button" class="lg:hidden p-2 rounded-xl text-secondary-600 dark:text-secondary-300 hover:bg-secondary-200/80 dark:hover:bg-secondary-700/80 transition-colors" @click="$emit('open-menu')">
          <Menu class="h-5 w-5" :stroke-width="2" />
        </button>

        <div class="flex items-center gap-2.5 min-w-0">
          <div v-if="context.tenant?.logo_url" class="w-7 h-7 rounded-lg overflow-hidden bg-secondary-100 dark:bg-secondary-800 shrink-0 border border-secondary-200/50 dark:border-secondary-700/50">
            <img :src="context.tenant.logo_url" :alt="context.tenant.name" class="w-full h-full object-contain" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] sm:text-xs uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400 truncate">
              {{ context.tenant?.name || 'Tenant App' }}
            </p>
            <h1 class="text-base sm:text-lg md:text-xl font-bold app-gradient-title truncate">
              {{ pageTitle }}
            </h1>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 sm:gap-3">
        <button type="button" class="inline-flex items-center gap-2 rounded-xl px-2.5 py-2 sm:px-3 text-secondary-600 dark:text-secondary-200 hover:bg-secondary-200/80 dark:hover:bg-secondary-700/70 transition-colors" @click="toggleTheme">
          <Sun class="hidden h-[18px] w-[18px] dark:block" :stroke-width="2" />
          <Moon class="block h-[18px] w-[18px] dark:hidden" :stroke-width="2" />
          <span class="hidden sm:inline text-xs font-semibold">{{ isDark ? 'Light' : 'Dark' }}</span>
        </button>

        <div class="text-right hidden sm:block">
          <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate max-w-[13rem]">
            {{ context.user?.name }}
          </p>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate max-w-[13rem]">
            {{ context.user?.email }}
          </p>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Menu, Sun, Moon } from 'lucide-vue-next';
import { useRoute } from 'vue-router';
import { useAppContext } from '../composables/useAppContext';

defineEmits(['open-menu']);
const route = useRoute();
const context = useAppContext();
const isDark = ref(document.documentElement.classList.contains('dark'));

const pageTitle = computed(() => {
    if (typeof route.meta?.title === 'string' && route.meta.title.trim() !== '') {
        return route.meta.title;
    }

    return route.path.replace('/', '').replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) || 'Dashboard';
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
</script>
