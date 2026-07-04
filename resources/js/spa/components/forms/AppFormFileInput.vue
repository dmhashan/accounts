<template>
  <input
    type="file"
    class="block h-12 w-full rounded-2xl border border-secondary-300 bg-white px-4 py-2.5 text-sm text-secondary-600 file:mr-3 file:rounded-xl file:border-0 file:bg-primary-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300"
    @change="handleChange"
  />
</template>

<script setup>
defineProps({
    modelValue: { type: [Array, Object], default: null },
});

const emit = defineEmits(['update:modelValue', 'change']);

function handleChange(event) {
    const files = Array.from(event.target.files || []);

    emit('update:modelValue', event.target.multiple ? files : files[0] || null);
    emit('change', event);
}
</script>
