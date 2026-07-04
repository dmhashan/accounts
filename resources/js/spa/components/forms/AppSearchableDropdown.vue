<template>
  <div ref="dropdownRef" class="relative w-full" :class="wrapperClass">
    <AppFormField
      :label="label"
      :for-id="id"
      :required="required"
      :optional="optional"
    >
      <button
        :id="id || null"
        ref="triggerRef"
        type="button"
        :class="buttonClasses"
        :disabled="disabled"
        :aria-required="required || null"
        @click="toggleDropdown"
      >
        <span class="flex-1 truncate" :class="selectedLabel ? '' : 'text-secondary-400 dark:text-secondary-500'">{{ selectedLabel || placeholder }}</span>
        <span
          v-if="clearable && selectedLabel && !disabled"
          class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full text-secondary-400 transition-colors hover:bg-secondary-100 hover:text-secondary-600 dark:hover:bg-secondary-700 dark:hover:text-secondary-200"
          aria-label="Clear selection"
          @click.stop="clearValue"
        >✕</span>
        <span class="flex-shrink-0 text-secondary-500">▾</span>
      </button>
    </AppFormField>

    <Teleport to="body">
      <div
        v-if="dropdownOpen"
        ref="panelRef"
        class="app-overlay-panel fixed z-[9999] overflow-hidden rounded-2xl"
        :style="dropdownStyle"
      >
        <div v-if="searchable" class="p-2 border-b border-secondary-200 dark:border-secondary-700">
          <AppFormInput
            v-model="search"
            type="text"
            :placeholder="searchPlaceholder"
            @keydown.stop
          />
        </div>
        <div class="max-h-64 overflow-y-auto py-1">
          <button
            v-for="option in filteredOptions"
            :key="optionKey(option)"
            type="button"
            class="w-full px-3 py-2 text-left text-sm hover:bg-secondary-100 dark:hover:bg-secondary-800"
            @click="selectOption(option)"
          >
            {{ optionLabel(option) }}
          </button>
          <p v-if="filteredOptions.length === 0" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">
            {{ noResultsText }}
          </p>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import AppFormField from './AppFormField.vue';
import AppFormInput from './AppFormInput.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: { type: [String, Number, Boolean, Object, null], default: null },
  options: {
    type: Array,
    required: true,
  },
  id: { type: String, default: '' },
  label: { type: String, default: '' },
  required: { type: Boolean, default: false },
  optional: { type: Boolean, default: false },
  placeholder: {
    type: String,
    default: 'Select...'
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  optionLabel: {
    type: Function,
    default: (option) => option.label || option.name || option.toString(),
  },
  optionKey: {
    type: Function,
    default: (option) => option?.id ?? option?.value ?? option,
  },
  searchable: {
    type: Boolean,
    default: true,
  },
  clearable: {
    type: Boolean,
    default: false,
  },
  error: { type: String, default: null },
  disabled: Boolean,
  wrapperClass: { type: [String, Array, Object], default: '' },
  buttonClass: { type: [String, Array, Object], default: '' },
  noResultsText: {
    type: String,
    default: 'No results found.'
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const dropdownOpen = ref(false);
const search = ref('');
const dropdownRef = ref(null);
const triggerRef = ref(null);
const panelRef = ref(null);
const dropdownStyle = ref({});

const DEFAULT_BUTTON_CLASS = 'app-form-control flex h-12 w-full items-center gap-2 rounded-2xl border px-4 text-left text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60';

const buttonClasses = computed(() => [
  props.buttonClass || DEFAULT_BUTTON_CLASS,
  dropdownOpen.value ? 'border-primary-500 ring-4 ring-primary-500/10' : '',
]);

const selectedLabel = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) return '';
  const found = props.options.find(opt => isSameValue(props.optionKey(opt), props.modelValue) || isSameValue(opt, props.modelValue));
  return found ? props.optionLabel(found) : '';
});

const meaningfulOptions = computed(() => {
  return props.options.filter(opt => {
    const key = props.optionKey(opt);
    return key !== null && key !== undefined && key !== '';
  });
});

const filteredOptions = computed(() => {
  if (!props.searchable || !search.value.trim()) return meaningfulOptions.value;
  const term = search.value.trim().toLowerCase();
  return meaningfulOptions.value.filter(opt => String(props.optionLabel(opt)).toLowerCase().includes(term));
});

function computeDropdownStyle() {
  if (!triggerRef.value) return;
  const rect = triggerRef.value.getBoundingClientRect();
  dropdownStyle.value = {
    top: `${rect.bottom + 4}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`,
  };
}

function selectOption(option) {
  const value = props.optionKey(option);
  emit('update:modelValue', value);
  emit('change', value);
  dropdownOpen.value = false;
  search.value = '';
}

function clearValue() {
  emit('update:modelValue', null);
  emit('change', null);
}

function toggleDropdown() {
  if (props.disabled) return;
  dropdownOpen.value = !dropdownOpen.value;
  if (dropdownOpen.value) {
    if (props.searchable) search.value = '';
    computeDropdownStyle();
  }
}

function handleDocumentClick(event) {
  if (!dropdownOpen.value) return;
  if (
    dropdownRef.value && !dropdownRef.value.contains(event.target) &&
    triggerRef.value && !triggerRef.value.contains(event.target) &&
    panelRef.value && !panelRef.value.contains(event.target)
  ) {
    dropdownOpen.value = false;
  }
}

function handleScrollOrResize() {
  if (dropdownOpen.value) {
    computeDropdownStyle();
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick, true);
  window.addEventListener('scroll', handleScrollOrResize, true);
  window.addEventListener('resize', handleScrollOrResize);
});
onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick, true);
  window.removeEventListener('scroll', handleScrollOrResize, true);
  window.removeEventListener('resize', handleScrollOrResize);
});

watch(() => props.modelValue, () => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return;
  }

  if (!props.options.some(opt => isSameValue(props.optionKey(opt), props.modelValue))) {
    emit('update:modelValue', null);
  }
});

function isSameValue(left, right) {
  if (left === right) return true;

  if (left === null || left === undefined || right === null || right === undefined) {
    return false;
  }

  if (typeof left === 'object' || typeof right === 'object') {
    return false;
  }

  return String(left) === String(right);
}
</script>
