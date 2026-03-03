<template>
    <div
        v-if="visible"
        class="mb-6 rounded-xl border px-4 py-3 md:px-5 md:py-4 flex items-start gap-3"
        :class="toneClasses"
        role="alert"
    >
        <svg class="h-5 w-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
            />
        </svg>
        <p class="text-sm md:text-base font-medium leading-relaxed">{{ message }}</p>
        <button
            type="button"
            class="ml-auto shrink-0 rounded-md p-1.5 hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
            @click="visible = false"
            aria-label="Dismiss notification"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    message: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'success',
    },
});

const visible = ref(Boolean(props.message));

const toneClasses = computed(() => {
    if (props.tone === 'success') {
        return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200';
    }

    return 'bg-secondary-50 dark:bg-secondary-900/20 border-secondary-200 dark:border-secondary-700 text-secondary-800 dark:text-secondary-100';
});
</script>