<template>
  <button
    type="button"
    role="switch"
    :aria-checked="Boolean(modelValue)"
    :disabled="disabled"
    class="flex h-12 w-full items-center justify-between rounded-2xl border border-secondary-300 bg-white px-4 text-sm font-medium text-secondary-700 transition disabled:cursor-not-allowed disabled:opacity-60 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-200"
    @click="toggle"
  >
    <span>{{ modelValue ? trueLabel : falseLabel }}</span>
    <span
      class="relative inline-flex h-5 w-9 rounded-full transition"
      :class="modelValue ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-700'"
    >
      <span
        class="inline-block h-5 w-5 rounded-full bg-white shadow transition"
        :class="modelValue ? 'translate-x-4' : 'translate-x-0'"
      />
    </span>
  </button>
</template>

<script setup>
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    trueLabel: { type: String, default: 'Yes' },
    falseLabel: { type: String, default: 'No' },
});

const emit = defineEmits(['update:modelValue', 'change']);

function toggle(event) {
    if (props.disabled) return;

    const value = !props.modelValue;
    emit('update:modelValue', value);
    emit('change', event);
}
</script>
