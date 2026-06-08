<template>
  <div v-if="summary.can_view" class="app-surface rounded-2xl border border-secondary-200/70 dark:border-secondary-700/70 p-4 sm:p-5 flex min-h-0 flex-col gap-4">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--text-muted)">
          Stock Availability
        </p>
        <p class="text-xs sm:text-sm mt-1" style="color: var(--text-muted)">
          Current product variation quantities.
        </p>
      </div>
      <div class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap" style="color: var(--text-strong); background: var(--surface-muted)">
        {{ formatNumber(summary.tracked_variations) }} tracked
      </div>
    </div>

    <div v-if="!loading" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 shrink-0">
      <div class="rounded-xl p-3 text-center" style="background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)">
        <p class="text-[11px] font-medium uppercase tracking-[0.06em]" style="color: var(--text-muted)">
          Tracked
        </p>
        <p class="mt-1 text-xl font-bold leading-none" style="color: var(--text-strong)">
          {{ formatNumber(summary.tracked_variations) }}
        </p>
      </div>
      <div class="rounded-xl p-3 text-center" style="background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)">
        <p class="text-[11px] font-medium uppercase tracking-[0.06em]" style="color: var(--text-muted)">
          Available Units
        </p>
        <p class="mt-1 text-xl font-bold leading-none" style="color: var(--text-strong)">
          {{ formatNumber(summary.available_units) }}
        </p>
      </div>
      <div class="rounded-xl p-3 text-center" :class="summary.low_stock_variations > 0 ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : ''" :style="summary.low_stock_variations === 0 ? 'background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)' : ''">
        <p class="text-[11px] font-medium uppercase tracking-[0.06em]" :class="summary.low_stock_variations > 0 ? 'text-red-500 dark:text-red-400' : ''" :style="summary.low_stock_variations === 0 ? 'color: var(--text-muted)' : ''">
          Low Stock
        </p>
        <p class="mt-1 text-xl font-bold leading-none" :class="summary.low_stock_variations > 0 ? 'text-red-600 dark:text-red-400' : ''" :style="summary.low_stock_variations === 0 ? 'color: var(--text-strong)' : ''">
          {{ formatNumber(summary.low_stock_variations) }}
        </p>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 shrink-0">
      <div v-for="i in 3" :key="i" class="app-skeleton h-16 rounded-xl" />
    </div>

    <div class="flex min-h-0 flex-col rounded-xl overflow-hidden" style="border: 1px solid var(--surface-border)">
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

      <ul v-else class="m-0 p-0 h-[300px] sm:h-[340px] overflow-auto divide-y divide-secondary-200 dark:divide-secondary-700">
        <li
          v-for="item in summary.variation_availability"
          :key="item.variation_id"
          class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-secondary-50/70 dark:hover:bg-secondary-800/40 transition-colors"
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
import { Package } from 'lucide-vue-next';
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
