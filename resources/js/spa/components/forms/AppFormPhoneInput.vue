<template>
  <div
    class="app-phone-input-container w-full"
    :class="[disabled ? 'opacity-60 cursor-not-allowed' : '']"
  >
    <VueTelInput
      :model-value="modelValue"
      :disabled="disabled"
      :dropdown-options="{ showSearchBox: true, showDialCodeInSelection: true }"
      :input-options="{ showDialCode: false, placeholder: 'Enter phone number' }"
      default-country="LK"
      mode="international"
      class="custom-vti h-12 rounded-2xl bg-white dark:bg-secondary-800 transition focus-within:border-primary-500 focus-within:ring-4 focus-within:ring-primary-500/10 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
      :class="[
        error ? 'border-red-500 focus-within:border-red-500 focus-within:ring-red-500/10' : 'border-secondary-300 dark:border-secondary-700'
      ]"
      @on-input="onInput"
    />
  </div>
</template>

<script setup>
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';

defineProps({
    modelValue: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

function onInput(formattedNumber, phoneObject) {
    if (!formattedNumber || !phoneObject) {
        emit('update:modelValue', '');
        return;
    }

    if (phoneObject.valid && phoneObject.number?.e164) {
        emit('update:modelValue', phoneObject.number.e164);
        return;
    }

    const digits = formattedNumber.replace(/[^\d+]/g, '');
    let clean;
    if (digits.startsWith('+')) {
        clean = digits;
    } else if (digits.startsWith('0')) {
        clean = '+94' + digits.substring(1);
    } else if (digits.startsWith('94')) {
        clean = '+' + digits;
    } else {
        clean = '+94' + digits;
    }

    emit('update:modelValue', clean);
}
</script>

<style>
/* Custom overrides to match the premium theme and support dark mode */
.custom-vti {
  border-width: 1px !important;
  border-style: solid !important;
  border-color: #cbd5e1 !important; /* Default border color */
  display: flex !important;
  align-items: center !important;
}

.dark .custom-vti {
  border-color: #334155 !important;
}

.custom-vti.border-red-500 {
  border-color: #ef4444 !important;
}

.custom-vti .vti__dropdown {
  background-color: transparent !important;
  border-radius: 1rem 0 0 1rem !important;
  padding: 0 0.5rem !important;
  cursor: pointer !important;
}

.custom-vti .vti__dropdown:hover {
  background-color: #f1f5f9 !important;
}

.dark .custom-vti .vti__dropdown:hover {
  background-color: rgba(255, 255, 255, 0.05) !important;
}

.custom-vti .vti__dropdown-list {
  background-color: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 1rem !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
  z-index: 50 !important;
  margin-top: 0.5rem !important;
  max-height: 250px !important;
}

.dark .custom-vti .vti__dropdown-list {
  background-color: #1e293b !important;
  border-color: #334155 !important;
}

.custom-vti .vti__dropdown-item {
  padding: 8px 12px !important;
  color: #0f172a !important;
}

.dark .custom-vti .vti__dropdown-item {
  color: #ffffff !important;
}

.custom-vti .vti__dropdown-item.highlighted {
  background-color: #f1f5f9 !important;
}

.dark .custom-vti .vti__dropdown-item.highlighted {
  background-color: #334155 !important;
}

.custom-vti .vti__input {
  background-color: transparent !important;
  color: #0f172a !important;
  font-size: 0.875rem !important;
  padding-left: 0.75rem !important;
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
}

.dark .custom-vti .vti__input {
  color: #ffffff !important;
}

.custom-vti .vti__search_box {
  border: 1px solid #e2e8f0 !important;
  border-radius: 0.5rem !important;
  margin: 8px !important;
  padding: 6px 10px !important;
  width: calc(100% - 16px) !important;
  box-sizing: border-box !important;
}

.dark .custom-vti .vti__search_box {
  background-color: #0f172a !important;
  border-color: #334155 !important;
  color: #ffffff !important;
}
</style>
