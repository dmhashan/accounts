<template>
  <article v-if="summary.can_view" class="app-surface rounded-2xl border border-secondary-200/70 p-4 dark:border-secondary-700/70 sm:p-5">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--text-muted)">
          Cash Flow
        </p>
        <p class="mt-1 truncate text-xs sm:text-sm" style="color: var(--text-muted)">
          {{ summary.range_label || 'Selected period' }}
        </p>
      </div>
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-100 dark:bg-secondary-800">
        <ChartNoAxesCombined class="h-5 w-5 text-secondary-700 dark:text-secondary-300" :stroke-width="2" />
      </div>
    </div>

    <div v-if="loading" class="mt-4 grid grid-cols-2 gap-2">
      <div v-for="i in 4" :key="i" class="app-skeleton h-16 rounded-xl" />
    </div>

    <div v-else class="mt-4 grid grid-cols-2 gap-2">
      <div class="rounded-xl border border-green-200 bg-green-50/70 p-3 dark:border-green-900/50 dark:bg-green-900/20">
        <p class="text-[11px] font-medium text-green-700 dark:text-green-400">
          Income
        </p>
        <p class="mt-1 truncate text-base font-bold text-green-700 dark:text-green-400">
          {{ money(summary.income) }}
        </p>
        <p class="mt-1 text-[10px] text-green-600/80 dark:text-green-400/80">
          {{ formatCount(summary.income_count) }} entries
        </p>
      </div>

      <div class="rounded-xl border border-red-200 bg-red-50/70 p-3 dark:border-red-900/50 dark:bg-red-900/20">
        <p class="text-[11px] font-medium text-red-700 dark:text-red-400">
          Expenses
        </p>
        <p class="mt-1 truncate text-base font-bold text-red-700 dark:text-red-400">
          {{ money(summary.expense) }}
        </p>
        <p class="mt-1 text-[10px] text-red-600/80 dark:text-red-400/80">
          {{ formatCount(summary.expense_count) }} entries
        </p>
      </div>

      <div class="col-span-2 flex items-center justify-between rounded-xl p-3" style="background: var(--surface-muted); border: 1px solid var(--surface-border)">
        <div>
          <p class="text-[11px] font-medium" style="color: var(--text-muted)">
            Net Movement
          </p>
          <p class="mt-1 text-lg font-bold" :class="netAmountClass">
            {{ signedMoney(summary.net_movement) }}
          </p>
        </div>
        <RouterLink to="/accounts/transactions" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
          All transactions
        </RouterLink>
      </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl" style="border: 1px solid var(--surface-border)">
      <div class="flex items-center justify-between px-3 py-2" style="background: var(--surface-muted)">
        <p class="text-[11px] font-semibold uppercase tracking-[0.06em]" style="color: var(--text-muted)">
          Recent Activity
        </p>
        <RouterLink to="/expenses" class="text-[11px] font-semibold text-primary-600 hover:underline dark:text-primary-400">
          Expenses
        </RouterLink>
      </div>

      <div v-if="loading" class="divide-y divide-secondary-200 dark:divide-secondary-700">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 px-3 py-2.5">
          <div class="app-skeleton h-8 w-8 rounded-lg" />
          <div class="min-w-0 flex-1 space-y-1">
            <div class="app-skeleton h-3 w-24 rounded" />
            <div class="app-skeleton h-2.5 w-16 rounded" />
          </div>
          <div class="app-skeleton h-3 w-14 rounded" />
        </div>
      </div>

      <AppEmptyState
        v-else-if="summary.recent_transactions.length === 0"
        :icon="ReceiptText"
        title="No cash flow"
        description="No income or expense transactions were recorded in this period."
      />

      <ul v-else class="m-0 divide-y divide-secondary-200 p-0 dark:divide-secondary-700">
        <li v-for="transaction in summary.recent_transactions" :key="transaction.id">
          <component
            :is="transaction.source_path ? RouterLink : 'div'"
            :to="transaction.source_path || undefined"
            class="flex items-center gap-2.5 px-3 py-2.5 transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/50"
          >
            <div
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
              :class="transaction.amount >= 0
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
            >
              <ArrowDownLeft v-if="transaction.amount >= 0" class="h-4 w-4" />
              <ArrowUpRight v-else class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-xs font-semibold" style="color: var(--text-strong)">
                {{ transaction.source_label }}
              </p>
              <p class="truncate text-[10px]" style="color: var(--text-muted)">
                {{ transaction.account_name }} · {{ transaction.transaction_date }}
              </p>
            </div>
            <p
              class="shrink-0 text-xs font-bold tabular-nums"
              :class="transaction.amount >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'"
            >
              {{ signedMoney(transaction.amount) }}
            </p>
          </component>
        </li>
      </ul>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { ArrowDownLeft, ArrowUpRight, ChartNoAxesCombined, ReceiptText } from 'lucide-vue-next';
import AppEmptyState from '../AppEmptyState.vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  summary: {
    type: Object,
    default: () => ({
      can_view: true,
      range_label: '',
      income: 0,
      expense: 0,
      net_movement: 0,
      income_count: 0,
      expense_count: 0,
      recent_transactions: [],
    }),
  },
});

const moneyFormatter = new Intl.NumberFormat(undefined, {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});
const numberFormatter = new Intl.NumberFormat();

const netAmountClass = computed(() => Number(props.summary.net_movement || 0) >= 0
  ? 'text-green-700 dark:text-green-400'
  : 'text-red-700 dark:text-red-400');

function money(value) {
  return moneyFormatter.format(Math.abs(Number(value || 0)));
}

function signedMoney(value) {
  const amount = Number(value || 0);
  return `${amount >= 0 ? '+' : '-'}${money(amount)}`;
}

function formatCount(value) {
  return numberFormatter.format(Number(value || 0));
}
</script>
