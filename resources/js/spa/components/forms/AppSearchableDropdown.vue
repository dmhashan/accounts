<template>
  <div ref="dropdownRef" class="relative w-full">
    <AppFormField :label="label" :error="error">
      <button
        type="button"
        class="w-full px-3 py-2 text-sm border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-left flex items-center justify-between"
        @click="toggleDropdown"
        :disabled="disabled"
      >
        <span class="truncate">{{ selectedLabel || placeholder }}</span>
        <span class="text-secondary-500">▾</span>
      </button>
      <div v-if="dropdownOpen" class="absolute z-20 mt-1 w-full bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-700 rounded-lg shadow-lg overflow-hidden">
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
    </AppFormField>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import AppFormField from './AppFormField.vue';
import AppFormInput from './AppFormInput.vue';

const props = defineProps({
  modelValue: [String, Number, Object, null],
  options: {
    type: Array,
    required: true,
  },
  label: String,
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
  error: String,
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

const selectedLabel = computed(() => {
  if (props.modelValue == null) return '';
  const found = props.options.find(opt => props.optionKey(opt) === props.modelValue || opt === props.modelValue);
  return found ? props.optionLabel(found) : '';
});

const filteredOptions = computed(() => {
  if (!props.searchable || !search.value.trim()) return props.options;
  const term = search.value.trim().toLowerCase();
  return props.options.filter(opt => props.optionLabel(opt).toLowerCase().includes(term));
});

function selectOption(option) {
  emit('update:modelValue', props.optionKey(option));
  dropdownOpen.value = false;
  search.value = '';
}

function toggleDropdown() {
  if (props.disabled) return;
  dropdownOpen.value = !dropdownOpen.value;
  if (dropdownOpen.value && props.searchable) {
    search.value = '';
  }
}

function handleDocumentClick(event) {
  if (!dropdownOpen.value) return;
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    dropdownOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick);
});
onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick);
});

watch(() => props.modelValue, () => {
  if (!props.options.some(opt => props.optionKey(opt) === props.modelValue)) {
    emit('update:modelValue', null);
  }
});
</script>
