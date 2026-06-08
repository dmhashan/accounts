<template>
  <div ref="selectorRoot" class="relative">
    <button
      type="button"
      class="inline-flex h-10 max-w-[220px] items-center gap-2 rounded-xl border border-secondary-300 bg-white px-3 text-sm font-semibold text-secondary-700 transition-colors hover:bg-secondary-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-800 sm:max-w-none"
      :disabled="disabled"
      :aria-expanded="open"
      aria-haspopup="menu"
      @click="open = !open"
    >
      <CalendarDays class="h-4 w-4 shrink-0" />
      <span class="truncate">{{ rangeLabel }}</span>
      <ChevronDown class="h-4 w-4 shrink-0" />
    </button>

    <div
      v-if="open"
      class="app-surface absolute right-0 top-full z-40 mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-xl border border-secondary-200 p-3 shadow-xl dark:border-secondary-700"
    >
      <div class="grid grid-cols-2 gap-1.5">
        <button
          v-for="preset in presets"
          :key="preset.id"
          type="button"
          class="rounded-lg border px-3 py-2 text-left text-xs font-medium transition-colors"
          :class="selectedPreset === preset.id
            ? 'border-primary-300 bg-primary-50 text-primary-700 dark:border-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
            : 'border-secondary-200 bg-white text-secondary-700 hover:bg-secondary-50 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-800'"
          @click="applyPreset(preset)"
        >
          {{ preset.label }}
        </button>
      </div>

      <div class="mt-3 border-t border-secondary-200 pt-3 dark:border-secondary-700">
        <p class="mb-2 text-xs font-semibold" style="color: var(--text-muted)">
          Custom date range
        </p>
        <div class="grid grid-cols-2 gap-2">
          <label class="min-w-0">
            <span class="sr-only">Start date</span>
            <input
              v-model="customStartDate"
              type="date"
              class="w-full rounded-lg border border-secondary-300 bg-white px-2 py-2 text-xs text-secondary-700 outline-none focus:border-primary-500 dark:border-secondary-600 dark:bg-secondary-800 dark:text-secondary-200"
              :max="customEndDate || today"
            />
          </label>
          <label class="min-w-0">
            <span class="sr-only">End date</span>
            <input
              v-model="customEndDate"
              type="date"
              class="w-full rounded-lg border border-secondary-300 bg-white px-2 py-2 text-xs text-secondary-700 outline-none focus:border-primary-500 dark:border-secondary-600 dark:bg-secondary-800 dark:text-secondary-200"
              :min="customStartDate"
              :max="today"
            />
          </label>
        </div>
        <button
          type="button"
          class="mt-2.5 w-full rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="!customRangeIsValid"
          @click="applyCustomRange"
        >
          Apply custom range
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { CalendarDays, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
  startDate: { type: String, required: true },
  endDate: { type: String, required: true },
  selectedPreset: { type: String, default: 'today' },
  rangeLabel: { type: String, default: 'Today' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['change']);

const selectorRoot = ref(null);
const open = ref(false);
const customStartDate = ref(props.startDate);
const customEndDate = ref(props.endDate);

function toInputDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function startOfWeek(date) {
  const result = new Date(date);
  const day = result.getDay();
  result.setDate(result.getDate() - (day === 0 ? 6 : day - 1));
  return result;
}

const now = new Date();
const currentDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
const today = toInputDate(currentDay);
const yesterday = new Date(currentDay);
yesterday.setDate(yesterday.getDate() - 1);
const thisWeekStart = startOfWeek(currentDay);
const lastWeekEnd = new Date(thisWeekStart);
lastWeekEnd.setDate(lastWeekEnd.getDate() - 1);
const lastWeekStart = new Date(lastWeekEnd);
lastWeekStart.setDate(lastWeekStart.getDate() - 6);
const thisMonthStart = new Date(currentDay.getFullYear(), currentDay.getMonth(), 1);
const lastMonthStart = new Date(currentDay.getFullYear(), currentDay.getMonth() - 1, 1);
const lastMonthEnd = new Date(currentDay.getFullYear(), currentDay.getMonth(), 0);

const presets = [
  { id: 'today', label: 'Today', startDate: today, endDate: today },
  { id: 'yesterday', label: 'Yesterday', startDate: toInputDate(yesterday), endDate: toInputDate(yesterday) },
  { id: 'this_week', label: 'This Week', startDate: toInputDate(thisWeekStart), endDate: today },
  { id: 'last_week', label: 'Last Week', startDate: toInputDate(lastWeekStart), endDate: toInputDate(lastWeekEnd) },
  { id: 'this_month', label: 'This Month', startDate: toInputDate(thisMonthStart), endDate: today },
  { id: 'last_month', label: 'Last Month', startDate: toInputDate(lastMonthStart), endDate: toInputDate(lastMonthEnd) },
];

const customRangeIsValid = computed(() => (
  customStartDate.value
  && customEndDate.value
  && customStartDate.value <= customEndDate.value
  && customEndDate.value <= today
));

function applyPreset(preset) {
  emit('change', preset);
  open.value = false;
}

function applyCustomRange() {
  if (!customRangeIsValid.value) return;

  emit('change', {
    id: 'custom',
    label: 'Custom Range',
    startDate: customStartDate.value,
    endDate: customEndDate.value,
  });
  open.value = false;
}

function handleDocumentClick(event) {
  if (open.value && !selectorRoot.value?.contains(event.target)) {
    open.value = false;
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape') open.value = false;
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick);
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick);
  window.removeEventListener('keydown', handleKeydown);
});

watch(
  () => [props.startDate, props.endDate],
  ([startDate, endDate]) => {
    customStartDate.value = startDate;
    customEndDate.value = endDate;
  },
);
</script>
