<template>
  <div
    v-if="visible"
    class="mb-6 rounded-xl border px-4 py-3 md:px-5 md:py-4 flex items-start gap-3"
    :class="toneClasses"
    role="alert"
  >
    <CircleCheck class="h-5 w-5 mt-0.5 shrink-0" aria-hidden="true" />
    <p class="text-sm md:text-base font-medium leading-relaxed">
      {{ message }}
    </p>
    <button
      type="button"
      class="ml-auto shrink-0 rounded-md p-1.5 hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
      aria-label="Dismiss notification"
      @click="visible = false"
    >
      <X class="h-4 w-4" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { CircleCheck, X } from 'lucide-vue-next';

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