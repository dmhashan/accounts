<template>
  <div v-if="summary.can_view" class="app-surface flex min-h-0 flex-col gap-3 rounded-xl border border-secondary-200/70 p-3.5 dark:border-secondary-700/70 sm:gap-4 sm:p-4 xl:p-5">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--text-muted)">
          Stock Availability
        </p>
        <p class="text-xs sm:text-sm mt-1" style="color: var(--text-muted)">
          Current product variation quantities.
        </p>
      </div>
      <RouterLink to="/inventory/stock" class="inline-flex min-h-9 items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 text-xs font-semibold transition-colors hover:bg-secondary-100 dark:hover:bg-secondary-800" style="color: var(--text-strong); background: var(--surface-muted)">
        {{ formatNumber(summary.tracked_variations) }} tracked
        <ChevronRight class="h-3.5 w-3.5" />
      </RouterLink>
    </div>

    <div v-if="!loading" class="grid shrink-0 grid-cols-3 gap-1.5 sm:gap-2.5">
      <div class="min-w-0 rounded-lg p-2 text-center sm:p-3" style="background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)">
        <p class="text-[11px] font-medium uppercase tracking-[0.06em]" style="color: var(--text-muted)">
          <span class="sm:hidden">Items</span><span class="hidden sm:inline">Tracked</span>
        </p>
        <p class="mt-1 truncate text-lg font-bold leading-none sm:text-xl" style="color: var(--text-strong)">
          {{ formatNumber(summary.tracked_variations) }}
        </p>
      </div>
      <div class="min-w-0 rounded-lg p-2 text-center sm:p-3" style="background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)">
        <p class="text-[11px] font-medium uppercase tracking-[0.06em]" style="color: var(--text-muted)">
          <span class="sm:hidden">Units</span><span class="hidden sm:inline">Available Units</span>
        </p>
        <p class="mt-1 truncate text-lg font-bold leading-none sm:text-xl" style="color: var(--text-strong)">
          {{ formatNumber(summary.available_units) }}
        </p>
      </div>
      <div class="min-w-0 rounded-lg p-2 text-center sm:p-3" :class="summary.low_stock_variations > 0 ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : ''" :style="summary.low_stock_variations === 0 ? 'background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)' : ''">
        <p class="text-[11px] font-medium uppercase tracking-[0.06em]" :class="summary.low_stock_variations > 0 ? 'text-red-500 dark:text-red-400' : ''" :style="summary.low_stock_variations === 0 ? 'color: var(--text-muted)' : ''">
          Low Stock
        </p>
        <p class="mt-1 truncate text-lg font-bold leading-none sm:text-xl" :class="summary.low_stock_variations > 0 ? 'text-red-600 dark:text-red-400' : ''" :style="summary.low_stock_variations === 0 ? 'color: var(--text-strong)' : ''">
          {{ formatNumber(summary.low_stock_variations) }}
        </p>
      </div>
    </div>

    <div v-else class="grid shrink-0 grid-cols-3 gap-1.5 sm:gap-2.5">
      <div v-for="i in 3" :key="i" class="app-skeleton h-16 rounded-xl" />
    </div>

    <div class="flex min-h-0 flex-col overflow-hidden rounded-lg" style="border: 1px solid var(--surface-border)">
      <template v-if="loading">
        <div class="divide-y divide-secondary-200 dark:divide-secondary-700">
          <div v-for="i in 5" :key="i" class="flex items-center justify-between px-4 py-3 gap-3">
            <div class="app-skeleton h-3.5 w-40 rounded" />
            <div class="app-skeleton h-3.5 w-12 rounded" />
          </div>
        </div>
      </template>

      <AppEmptyState
        v-else-if="summary.variation_availability.length === 0"
        :icon="Package"
        title="No stock data"
        description="No product variation availability recorded yet."
      />

      <ul v-else class="m-0 max-h-[320px] overflow-auto divide-y divide-secondary-200 p-0 dark:divide-secondary-700 sm:h-[340px] sm:max-h-none">
        <li
          v-for="item in summary.variation_availability"
          :key="item.variation_id"
          class="flex min-h-12 items-center justify-between gap-3 px-3 py-2.5 transition-colors hover:bg-secondary-50/70 dark:hover:bg-secondary-800/40 sm:px-4 sm:py-3"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <span
              class="h-2 w-2 rounded-full shrink-0"
              :class="item.is_low_stock ? 'bg-red-500' : 'bg-green-500'"
            />
            <p class="text-sm truncate" :class="item.is_low_stock ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-secondary-900 dark:text-white'">
              {{ item.label }}
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <AppBadge v-if="item.is_low_stock" color="red">
              Low
            </AppBadge>
            <p class="text-sm font-semibold tabular-nums" :class="item.is_low_stock ? 'text-red-600 dark:text-red-400' : 'text-secondary-900 dark:text-white'">
              {{ formatNumber(item.available_quantity) }}
            </p>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ChevronRight, Package } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import AppBadge from '../AppBadge.vue';
import AppEmptyState from '../AppEmptyState.vue';

defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
  summary: {
    type: Object,
    default: () => ({
      can_view: true,
      available_units: 0,
      tracked_variations: 0,
      low_stock_variations: 0,
      variation_availability: [],
    }),
  },
});

const numberFormatter = new Intl.NumberFormat();

function formatNumber(value) {
  return numberFormatter.format(Number(value || 0));
}

</script>
