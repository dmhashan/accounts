<template>
    <div class="flex w-full items-center gap-2">
        <div class="relative min-w-0 flex-1">
            <input
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                type="text"
                class="h-12 w-full rounded-full border border-secondary-300 dark:border-secondary-700 bg-white dark:bg-secondary-900 pl-5 pr-14 text-sm text-secondary-900 dark:text-white placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/40"
                @input="$emit('update:modelValue', $event.target.value)"
                @keydown.enter.prevent
                @keyup.enter="$emit('search')"
            >

            <button
                type="button"
                :disabled="disabled"
                class="absolute right-1 top-1/2 -translate-y-1/2 inline-flex h-10 w-10 items-center justify-center rounded-full text-secondary-500 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                @click="$emit('search')"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </div>

        <slot name="filter-trigger" />
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Search' },
    disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'search']);
</script>
