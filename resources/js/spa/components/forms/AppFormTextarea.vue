<template>
  <textarea
    :value="modelValue ?? ''"
    class="app-form-control w-full rounded-2xl border px-4 py-3 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60"
    @input="handleInput"
  />
</template>

<script setup>
const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    modelModifiers: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

function handleInput(event) {
    let value = event.target.value;

    if (props.modelModifiers.trim) {
        value = value.trim();
    }

    if (props.modelModifiers.number) {
        value = value === '' ? '' : Number(value);
    }

    emit('update:modelValue', value);
}
</script>
