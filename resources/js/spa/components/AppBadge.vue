<template>
    <span class="app-badge" :class="colorClass">
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    /**
     * Color variant of the badge.
     * Options: green | red | blue | amber | purple | orange | indigo | primary | secondary
     */
    color: {
        type: String,
        default: 'secondary',
    },
});

const colorClass = computed(() => `app-badge-${props.color}`);
</script>
