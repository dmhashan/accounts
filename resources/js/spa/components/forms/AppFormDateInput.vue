<template>
  <VueDatePicker
    :model-value="modelValue || null"
    model-type="yyyy-MM-dd"
    auto-apply
    :time-config="{ enableTimePicker: false }"
    :disabled="disabled"
    :min-date="min || undefined"
    :max-date="max || undefined"
    teleport
    :dark="isDark"
    @update:model-value="handleUpdate"
  >
    <template #trigger>
      <div class="relative cursor-pointer">
        <input
          type="text"
          :value="displayValue"
          :placeholder="placeholder ?? formatHint"
          :disabled="disabled"
          :class="inputClass ?? DEFAULT_CLASS"
          :style="{ paddingRight: '2.25rem' }"
          readonly
        />
        <Calendar
          class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-secondary-400 dark:text-secondary-500"
        />
      </div>
    </template>
  </VueDatePicker>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, inject } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { Calendar } from 'lucide-vue-next';
import { useDateTimeFormat } from '../../composables/useDateTimeFormat';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: undefined },
    required:   { type: Boolean, default: false },
    disabled:   { type: Boolean, default: false },
    min:        { type: String, default: undefined },
    max:        { type: String, default: undefined },
    inputClass: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue', 'change']);

const DEFAULT_CLASS = 'app-form-control h-12 w-full rounded-2xl border px-4 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60';

const ctx = inject('appContext', {});
const { formatDate } = useDateTimeFormat();

const displayValue = computed(() => props.modelValue ? formatDate(props.modelValue, '') : '');
const formatHint = ctx.settings?.dateFormat ?? 'D MMM YYYY';

// Sync dark mode with the document root class
const isDark = ref(document.documentElement.classList.contains('dark'));
let _observer = null;
onMounted(() => {
    _observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    _observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onUnmounted(() => _observer?.disconnect());

function handleUpdate(value) {
    const iso = value ?? '';
    emit('update:modelValue', iso);
    emit('change', { target: { value: iso } });
}
</script>
