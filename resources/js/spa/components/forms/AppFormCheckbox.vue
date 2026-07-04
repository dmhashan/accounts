<template>
  <label
    class="inline-flex items-center justify-center gap-2 rounded-xl border border-secondary-200 bg-white font-semibold text-secondary-700 transition dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-200"
    :class="[
      compact ? 'h-9 px-3 text-xs' : 'px-3 py-2 text-sm',
      disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800',
    ]"
  >
    <input
      type="checkbox"
      class="h-4 w-4 rounded border-secondary-300 text-primary-600 disabled:opacity-50"
      :checked="checked"
      :disabled="disabled"
      :value="value"
      @change="handleChange"
    />
    <span v-if="label">{{ label }}</span>
    <slot v-else />
  </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [Boolean, Array], default: false },
    value: { type: [String, Number, Boolean, Object], default: true },
    label: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

const checked = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue.includes(props.value);
    }

    return Boolean(props.modelValue);
});

function handleChange(event) {
    if (Array.isArray(props.modelValue)) {
        const values = [...props.modelValue];
        const index = values.indexOf(props.value);

        if (event.target.checked && index === -1) {
            values.push(props.value);
        } else if (!event.target.checked && index !== -1) {
            values.splice(index, 1);
        }

        emit('update:modelValue', values);
        emit('change', event);
        return;
    }

    emit('update:modelValue', event.target.checked);
    emit('change', event);
}
</script>
