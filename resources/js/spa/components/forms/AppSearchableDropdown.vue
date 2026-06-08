<template>
  <div ref="dropdownRef" class="relative w-full">
    <AppFormField :label="label" :error="error">
      <button
        ref="triggerRef"
        type="button"
        class="app-form-control w-full px-3 py-2 text-sm border rounded-lg text-left flex items-center gap-1"
        :disabled="disabled"
        @click="toggleDropdown"
      >
        <span class="flex-1 truncate" :class="selectedLabel ? '' : 'text-secondary-400 dark:text-secondary-500'">{{ selectedLabel || placeholder }}</span>
        <span
          v-if="clearable && selectedLabel && !disabled"
          class="flex-shrink-0 flex items-center justify-center w-4 h-4 rounded-full text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors"
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
        class="app-overlay-panel fixed z-[9999] rounded-lg overflow-hidden"
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
            class="w-full px-3 py-2 text-sm text-left hover:bg-secondary-100 dark:hover:bg-secondary-800"
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

const props = defineProps({
  modelValue: { type: [String, Number, Object], default: null },
  options: {
    type: Array,
    required: true,
  },
  label: { type: String, default: '' },
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
    default: (option) => option.id || option.value || option,
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
  noResultsText: {
    type: String,
    default: 'No results found.'
  }
});

const emit = defineEmits(['update:modelValue']);

const dropdownOpen = ref(false);
const search = ref('');
const dropdownRef = ref(null);
const triggerRef = ref(null);
const panelRef = ref(null);
const dropdownStyle = ref({});

const selectedLabel = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) return '';
  const found = props.options.find(opt => props.optionKey(opt) === props.modelValue || opt === props.modelValue);
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
  return meaningfulOptions.value.filter(opt => props.optionLabel(opt).toLowerCase().includes(term));
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
  emit('update:modelValue', props.optionKey(option));
  dropdownOpen.value = false;
  search.value = '';
}

function clearValue() {
  emit('update:modelValue', null);
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
  if (!props.options.some(opt => props.optionKey(opt) === props.modelValue)) {
    emit('update:modelValue', null);
  }
});
</script>
