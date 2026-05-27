<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="$emit('cancel')" />
      <div class="relative z-10 w-full max-w-sm rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-xl">
        <div class="flex items-center justify-between p-5 border-b border-secondary-200 dark:border-secondary-700">
          <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
            {{ title }}
          </h3>
          <button
            type="button"
            class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200"
            :disabled="loading"
            @click="$emit('cancel')"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <div class="px-5 py-4">
          <slot>
            <p class="text-sm text-secondary-700 dark:text-secondary-300">
              {{ message }}
            </p>
          </slot>
        </div>

        <div class="flex justify-end gap-3 px-5 pb-5">
          <button
            type="button"
            class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 disabled:opacity-50"
            :disabled="loading"
            @click="$emit('cancel')"
          >
            {{ cancelLabel }}
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded-lg text-sm font-medium text-white disabled:opacity-50"
            :class="variantClass"
            :disabled="loading"
            @click="$emit('confirm')"
          >
            {{ loading ? loadingLabel : confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirm' },
    cancelLabel: { type: String, default: 'Cancel' },
    loadingLabel: { type: String, default: 'Please wait...' },
    variant: { type: String, default: 'danger' }, // 'danger' | 'warning' | 'primary'
    loading: { type: Boolean, default: false },
});

defineEmits(['confirm', 'cancel']);

const variantClass = computed(() => ({
    danger:  'bg-red-600 hover:bg-red-700',
    warning: 'bg-amber-600 hover:bg-amber-700',
    primary: 'bg-primary-600 hover:bg-primary-700',
}[props.variant] ?? 'bg-red-600 hover:bg-red-700'));
</script>
