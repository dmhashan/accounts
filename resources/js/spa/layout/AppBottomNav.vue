<template>
    <nav class="fixed bottom-0 inset-x-0 z-30 lg:hidden px-2 pb-[calc(0.4rem+env(safe-area-inset-bottom))] pt-1.5">
        <div class="app-glass rounded-2xl shadow-lg">
            <div class="grid grid-cols-5">
            <RouterLink
                v-for="item in displayedItems"
                :key="item.path"
                :to="item.path"
                class="px-1.5 py-2.5 text-center text-xs font-semibold transition-colors"
                :class="isActive(item.path)
                    ? 'text-primary-700 dark:text-primary-300'
                    : 'text-secondary-500 dark:text-secondary-400'"
            >
                <span class="mx-auto block max-w-full truncate rounded-lg px-1 py-0.5" :class="isActive(item.path) ? 'bg-primary-50 dark:bg-primary-900/30' : ''">{{ item.label }}</span>
            </RouterLink>

            <button type="button" class="px-1.5 py-2.5 text-center text-xs font-semibold text-secondary-500 dark:text-secondary-400" @click="$emit('open-menu')">
                <span class="mx-auto block max-w-full truncate rounded-lg px-1 py-0.5">More</span>
            </button>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useNavigation } from '../composables/useNavigation';

const route = useRoute();
const { quickItems } = useNavigation();

defineEmits(['open-menu']);

const displayedItems = computed(() => quickItems.value.slice(0, 4));

function isActive(path) {
    return route.path === path;
}
</script>
