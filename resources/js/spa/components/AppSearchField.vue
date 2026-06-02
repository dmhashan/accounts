<template>
  <form class="flex w-full items-center gap-2" @submit.prevent="$emit('search')">
    <div class="relative min-w-0 flex-1">
      <input
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        type="text"
        enterkeyhint="search"
        class="h-12 w-full rounded-full border border-secondary-300 dark:border-secondary-700 bg-white dark:bg-secondary-900 pl-5 pr-14 text-sm text-secondary-900 dark:text-white placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/40"
        @input="$emit('update:modelValue', $event.target.value)"
      />

      <button
        type="submit"
        :disabled="disabled"
        class="absolute right-1 top-1/2 -translate-y-1/2 inline-flex h-10 w-10 items-center justify-center rounded-full text-secondary-500 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        <Search class="h-5 w-5" />
      </button>
    </div>

    <slot name="filter-trigger" />
  </form>
</template>

<script setup>
import { Search } from 'lucide-vue-next';
defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Search' },
    disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'search']);
</script>
