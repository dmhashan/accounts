<template>
    <div class="app-empty-state">
        <div class="app-empty-state-icon">
            <component :is="icon" class="h-6 w-6" :stroke-width="1.75" />
        </div>
        <div class="space-y-1">
            <p class="app-empty-state-title">{{ title }}</p>
            <p v-if="description" class="app-empty-state-desc">{{ description }}</p>
        </div>
        <slot />
    </div>
</template>

<script setup>
import { Inbox } from 'lucide-vue-next';

defineProps({
    /** Lucide icon component to display. Defaults to Inbox. */
    icon: {
        type: [Object, Function],
        default: () => Inbox,
    },
    title: {
        type: String,
        default: 'Nothing here yet',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>
