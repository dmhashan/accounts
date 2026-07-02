<template>
  <nav class="app-bottom-nav lg:hidden">
    <div class="app-glass app-bottom-nav-panel">
      <div class="flex items-end">
        <!-- Left 2 items -->
        <RouterLink
          v-for="item in leftItems"
          :key="item.path"
          :to="item.path"
          class="flex flex-1 flex-col items-center gap-0.5 py-2.5 px-1 transition-colors"
          :class="isActive(item.path) ? 'text-primary-600 dark:text-primary-400' : 'text-secondary-500 dark:text-secondary-400'"
        >
          <span class="rounded-lg px-2.5 py-1 transition-colors" :class="isActive(item.path) ? 'bg-primary-50 dark:bg-primary-900/30' : ''">
            <component :is="item.icon" class="h-5 w-5" :stroke-width="2" />
          </span>
          <span class="text-[10px] font-semibold leading-none">{{ item.shortLabel || item.label }}</span>
        </RouterLink>

        <!-- More button — center, elevated, gradient -->
        <button
          type="button"
          class="flex flex-shrink-0 flex-col items-center gap-1 px-2 pb-2 -translate-y-3"
          style="width: 20%"
          @click="$emit('open-menu')"
        >
          <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-600/40">
            <Menu class="h-6 w-6 text-white" :stroke-width="2.25" />
          </div>
          <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400 leading-none">More</span>
        </button>

        <!-- Right 2 items -->
        <RouterLink
          v-for="item in rightItems"
          :key="item.path"
          :to="item.path"
          class="flex flex-1 flex-col items-center gap-0.5 py-2.5 px-1 transition-colors"
          :class="isActive(item.path) ? 'text-primary-600 dark:text-primary-400' : 'text-secondary-500 dark:text-secondary-400'"
        >
          <span class="rounded-lg px-2.5 py-1 transition-colors" :class="isActive(item.path) ? 'bg-primary-50 dark:bg-primary-900/30' : ''">
            <component :is="item.icon" class="h-5 w-5" :stroke-width="2" />
          </span>
          <span class="text-[10px] font-semibold leading-none">{{ item.shortLabel || item.label }}</span>
        </RouterLink>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { Menu } from 'lucide-vue-next';
import { useRoute } from 'vue-router';
import { useNavigation } from '../composables/useNavigation';

defineEmits(['open-menu']);
const route = useRoute();
const { quickItems } = useNavigation();

const leftItems  = computed(() => quickItems.value.slice(0, 2));
const rightItems = computed(() => quickItems.value.slice(2, 4));

function isActive(path) {
    return route.path === path;
}
</script>
