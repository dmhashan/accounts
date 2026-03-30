<template>
    <component
        :is="componentTag"
        v-bind="componentAttrs"
        :class="classes"
        :aria-label="label"
        :title="label"
        @click="handleClick"
    >
        <component :is="icon" class="h-4 w-4 shrink-0" />
        <span class="hidden sm:inline">{{ label }}</span>
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
    to: {
        type: [String, Object],
        default: null,
    },
    icon: {
        type: [Object, Function],
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    variant: {
        type: String,
        default: 'primary',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'button',
    },
});

const emit = defineEmits(['click']);

const componentTag = computed(() => (props.to ? RouterLink : 'button'));

const componentAttrs = computed(() => {
    if (props.to) {
        return {
            to: props.to,
        };
    }

    return {
        type: props.type,
        disabled: props.disabled,
    };
});

const classes = computed(() => {
    const base = 'inline-flex items-center justify-center gap-2 h-10 w-10 sm:h-auto sm:w-auto sm:px-4 sm:py-2.5 rounded-full sm:rounded-xl text-sm font-semibold transition-all disabled:opacity-60 disabled:cursor-not-allowed';

    if (props.variant === 'secondary') {
        return `${base} border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800`;
    }

    return `${base} bg-gradient-to-r from-primary-500 to-primary-700 text-white hover:brightness-110`;
});

function handleClick(event) {
    if (!props.to) {
        emit('click', event);
    }
}
</script>