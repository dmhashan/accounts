<template>
  <div class="min-w-0" :class="wrapperClass">
    <label
      v-if="label"
      :for="forId || null"
      class="mb-1.5 block text-xs font-medium text-secondary-700 dark:text-secondary-300"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
      <span v-else-if="optional" class="text-secondary-400 dark:text-secondary-500"> (optional)</span>
    </label>

    <slot />

    <p v-if="help" class="mt-1.5 text-xs text-secondary-500 dark:text-secondary-400">
      {{ help }}
    </p>
  </div>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    forId: { type: String, default: '' },
    help: { type: String, default: '' },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    wrapperClass: { type: [String, Array, Object], default: '' },
});
</script>
