<template>
    <textarea
        :value="modelValue ?? ''"
        class="w-full rounded-2xl border border-secondary-300 bg-white px-4 py-3 text-sm text-secondary-900 shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition placeholder:text-secondary-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white dark:placeholder:text-secondary-500"
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