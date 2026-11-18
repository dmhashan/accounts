<template>
  <div class="mb-4" aria-label="Inventory product filters">
    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-secondary-600 dark:text-secondary-300">
      Filter products
    </p>
    <div class="flex gap-2 overflow-x-auto pb-2" role="group" aria-label="Product category and status">
      <button
        v-for="item in filters"
        :key="item.value"
        type="button"
        class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
        :class="[
          item.category ? [getColorClasses(item.category.color).bg, getColorClasses(item.category.color).text, getColorClasses(item.category.color).border] : 'border-secondary-200 text-secondary-700 hover:bg-secondary-100 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800',
          modelValue === item.value ? 'ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-secondary-900' : 'hover:opacity-80',
        ]"
        :aria-pressed="modelValue === item.value"
        @click="$emit('update:modelValue', item.value)"
      >
        <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />
        {{ item.label }}
      </button>
    </div>
    <p class="mt-1 text-xs text-secondary-600 dark:text-secondary-300">
      All, categories and None show active products; Disabled shows inactive products.
    </p>
    <p v-if="loading" role="status" class="mt-2 text-sm text-secondary-600 dark:text-secondary-300">
      Loading categories…
    </p>
    <div v-if="error" role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">
      {{ error }}
      <button type="button" class="ml-2 min-h-11 font-semibold underline" @click="$emit('retry')">
        Retry
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Apple, Beef, Boxes, CircleSlash, Coffee, CupSoda, Droplets, Dumbbell, Gift, Heart, LayoutGrid, Package, Pill, Shirt, ShoppingBag, Sparkles, Tag, Ban, Utensils, Wine } from 'lucide-vue-next';
import { getColorClasses } from '../utils/paymentMethodHelper';

const props = defineProps({
    modelValue: { type: String, default: 'all' },
    categories: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});
defineEmits(['update:modelValue', 'retry']);

const icons = { Apple, Beef, Boxes, Coffee, CupSoda, Droplets, Dumbbell, Gift, Heart, Package, Pill, Shirt, ShoppingBag, Sparkles, Tag, Utensils, Wine };
const filters = computed(() => [
    { value: 'all', label: 'All', icon: LayoutGrid },
    ...props.categories.map((category) => ({
        value: `category:${category.id}`,
        label: category.name,
        icon: Object.hasOwn(icons, category.icon) ? icons[category.icon] : Tag,
        category,
    })),
    { value: 'none', label: 'None', icon: CircleSlash },
    { value: 'disabled', label: 'Disabled', icon: Ban },
]);
</script>