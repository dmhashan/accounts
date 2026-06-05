<template>
  <RouterLink v-if="summary.can_view" to="/stats" class="group block h-full">
    <article class="app-surface rounded-2xl p-4 sm:p-5 border border-secondary-200/70 dark:border-secondary-700/70 h-full">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--text-muted)">
            Daily Sales
          </p>
          <template v-if="loading">
            <div class="app-skeleton mt-2.5 h-9 w-40 rounded-lg" />
          </template>
          <p v-else class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight leading-none" style="color: var(--text-strong)">
            {{ formatMoney(summary.gross_amount) }}
          </p>
          <p class="mt-2 text-xs sm:text-sm" style="color: var(--text-muted)">
            Gross sales for {{ summary.date || 'today' }}
          </p>
        </div>
        <div class="h-11 w-11 rounded-xl flex items-center justify-center shrink-0 bg-gradient-to-br from-primary-500 to-primary-700 shadow-sm shadow-primary-500/25 group-hover:scale-105 transition-transform">
          <TrendingUp class="h-5 w-5 text-white" :stroke-width="2" />
        </div>
      </div>

      <div class="mt-4 pt-3 border-t border-secondary-200/70 dark:border-secondary-700/70 flex items-center justify-between">
        <p class="text-xs" style="color: var(--text-muted)">
          Tap to open analytics
        </p>
        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 group-hover:translate-x-0.5 transition-transform">
          View
        </span>
      </div>
    </article>
  </RouterLink>
</template>

<script setup>
import { TrendingUp } from 'lucide-vue-next';

defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
  summary: {
    type: Object,
    default: () => ({
      can_view: true,
      date: '',
      gross_amount: 0,
    }),
  },
});

const moneyFormatter = new Intl.NumberFormat(undefined, {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

function formatMoney(value) {
  return moneyFormatter.format(Number(value || 0));
}
</script>
