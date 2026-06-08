<template>
  <select
    :value="modelValue"
    class="app-form-control h-12 w-full rounded-2xl border px-4 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60"
    @change="handleChange"
  >
    <slot />
  </select>
</template>

<script setup>
const props = defineProps({
    modelValue: { type: [String, Number, Boolean, Object, null], default: '' },
    modelModifiers: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

function handleChange(event) {
    const selectedOption = event.target.options[event.target.selectedIndex];
    const rawValue = selectedOption && Object.prototype.hasOwnProperty.call(selectedOption, '_value')
        ? selectedOption._value
        : event.target.value;

    if (props.modelModifiers.number) {
        if (rawValue === '' || rawValue === null || rawValue === undefined) {
            emit('update:modelValue', rawValue);
            return;
        }

        const parsedValue = Number(rawValue);
        emit('update:modelValue', Number.isNaN(parsedValue) ? rawValue : parsedValue);
        return;
    }

    emit('update:modelValue', rawValue);
}
</script>
