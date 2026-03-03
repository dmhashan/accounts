<template>
    <nav class="fixed bottom-0 inset-x-0 z-30 lg:hidden border-t border-secondary-200 dark:border-secondary-700 bg-white/95 dark:bg-secondary-900/95 backdrop-blur">
        <div class="grid grid-cols-5">
            <RouterLink
                v-for="item in displayedItems"
                :key="item.path"
                :to="item.path"
                class="px-2 py-2.5 text-center text-xs font-medium transition-colors"
                :class="isActive(item.path)
                    ? 'text-primary-700 dark:text-primary-300'
                    : 'text-secondary-500 dark:text-secondary-400'"
            >
                <span class="block truncate">{{ item.label }}</span>
            </RouterLink>

            <button type="button" class="px-2 py-2.5 text-center text-xs font-medium text-secondary-500 dark:text-secondary-400" @click="$emit('open-menu')">
                <span class="block truncate">More</span>
            </button>
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
