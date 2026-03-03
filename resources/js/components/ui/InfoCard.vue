<template>
    <article class="group bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 shadow-sm hover:shadow-md transition-all duration-200">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-lg flex items-center justify-center" :class="iconToneClasses">
                <svg v-if="icon === 'tenant'" class="h-6 w-6" :class="iconColorClasses" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <svg v-else-if="icon === 'domain'" class="h-6 w-6" :class="iconColorClasses" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                <svg v-else class="h-6 w-6" :class="iconColorClasses" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h3 class="text-base md:text-lg font-semibold text-secondary-900 dark:text-white">{{ title }}</h3>
        </div>

        <p :class="valueClasses">{{ value }}</p>
        <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400 break-all">{{ subtitle }}</p>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: 'user',
    },
});

const iconToneClasses = computed(() => {
    if (props.icon === 'tenant') {
        return 'bg-primary-100 dark:bg-primary-900/50';
    }

    if (props.icon === 'domain') {
        return 'bg-secondary-100 dark:bg-secondary-800/50';
    }

    return 'bg-green-100 dark:bg-green-900/50';
});

const iconColorClasses = computed(() => {
    if (props.icon === 'tenant') {
        return 'text-primary-600 dark:text-primary-400';
    }

    if (props.icon === 'domain') {
        return 'text-secondary-600 dark:text-secondary-400';
    }

    return 'text-green-600 dark:text-green-400';
});

const valueClasses = computed(() => {
    if (props.icon === 'domain') {
        return 'text-sm md:text-base font-mono font-semibold text-secondary-900 dark:text-white break-all';
    }

    if (props.icon === 'account') {
        return 'text-lg md:text-xl font-semibold text-secondary-900 dark:text-white';
    }

    return 'text-2xl font-bold text-secondary-900 dark:text-white';
});
</script>