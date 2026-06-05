<template>
  <section class="app-page-frame">
    <AppPageHeader title="Daily Summary">
      <template #extra-slot>
        <form class="flex flex-wrap items-end gap-3" @submit.prevent="loadSummary">
          <AppFormField label="Date" class="w-full sm:w-auto">
            <AppFormInput
              v-model="selectedDate"
              type="date"
              :max="today"
              required
            />
          </AppFormField>

          <button
            type="submit"
            :disabled="loading"
            class="self-end px-4 py-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold transition-all hover:brightness-110"
          >
            {{ loading ? 'Loading...' : 'Apply' }}
          </button>

          <button
            v-if="selectedDate !== today"
            type="button"
            class="self-end px-4 py-2 rounded-xl app-surface-soft text-sm font-semibold text-secondary-700 dark:text-secondary-200 transition-colors hover:brightness-105"
            @click="goToToday"
          >
            Today
          </button>
        </form>
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div v-if="error" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ error }}
      </div>

      <p v-if="summary.date_label" class="mb-4 text-sm text-secondary-500 dark:text-secondary-400">
        Showing summary for <span class="font-semibold text-secondary-800 dark:text-secondary-100">{{ summary.date_label }}</span>
        <span v-if="summary.is_today" class="ml-1">(today)</span>
      </p>

      <!-- Headline summary cards -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-4">
        <article class="app-surface rounded-2xl p-4">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Opening Balance
          </p>
          <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
            {{ loading ? '-' : formatMoney(summary.totals.opening_balance) }}
          </p>
        </article>
        <article class="app-surface rounded-2xl p-4">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Income
          </p>
          <p class="mt-1 text-lg md:text-xl font-semibold text-emerald-600 dark:text-emerald-400">
            {{ loading ? '-' : '+' + formatMoney(summary.totals.income) }}
          </p>
        </article>
        <article class="app-surface rounded-2xl p-4">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Expenses
          </p>
          <p class="mt-1 text-lg md:text-xl font-semibold text-red-600 dark:text-red-400">
            {{ loading ? '-' : '-' + formatMoney(summary.totals.expense) }}
          </p>
        </article>
        <article class="app-surface rounded-2xl p-4 ring-1 ring-primary-200 dark:ring-primary-900/40">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Closing Balance
          </p>
          <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
            {{ loading ? '-' : formatMoney(summary.totals.closing_balance) }}
          </p>
        </article>
      </div>

      <!-- Accounts breakdown -->
      <article class="app-surface rounded-2xl overflow-hidden mb-4">
        <header class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700">
          <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
            Account Balances
          </h3>
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Opening balance, daily movement and closing balance per account.
          </p>
        </header>

        <div v-if="loading" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
          Loading accounts...
        </div>
        <div v-else-if="summary.accounts.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
          No accounts found.
        </div>
        <template v-else>
          <!-- Mobile -->
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="account in summary.accounts" :key="account.id" class="px-4 py-3 space-y-2">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                  {{ account.name }}
                </p>
                <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">
                  {{ formatMoney(account.closing_balance) }}
                </p>
              </div>
              <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-secondary-600 dark:text-secondary-300">
                <p>Opening: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(account.opening_balance) }}</span></p>
                <p>Closing: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(account.closing_balance) }}</span></p>
                <p>Income: <span class="font-medium text-emerald-600 dark:text-emerald-400">+{{ formatMoney(account.income) }}</span></p>
                <p>Expense: <span class="font-medium text-red-600 dark:text-red-400">-{{ formatMoney(account.expense) }}</span></p>
              </div>
            </article>
          </div>
          <!-- Desktop -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[640px]">
              <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Account
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Opening
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Income
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Expense
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Closing
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr v-for="account in summary.accounts" :key="account.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm text-secondary-900 dark:text-white">
                    {{ account.name }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                    {{ formatMoney(account.opening_balance) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-emerald-600 dark:text-emerald-400">
                    +{{ formatMoney(account.income) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-red-600 dark:text-red-400">
                    -{{ formatMoney(account.expense) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(account.closing_balance) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-secondary-50 dark:bg-background-dark border-t border-secondary-200 dark:border-secondary-700">
                <tr>
                  <td class="px-3 py-2 text-sm font-semibold text-secondary-900 dark:text-white">
                    Total
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(summary.totals.opening_balance) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-emerald-600 dark:text-emerald-400">
                    +{{ formatMoney(summary.totals.income) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-red-600 dark:text-red-400">
                    -{{ formatMoney(summary.totals.expense) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(summary.totals.closing_balance) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </template>
      </article>

      <!-- Income & Expense side by side -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <DailySummaryFlow
          title="Income"
          tone="income"
          :total="summary.income.total"
          :breakdown="summary.income.breakdown"
          :transactions="summary.income.transactions"
          :loading="loading"
          :format-money="formatMoney"
        />
        <DailySummaryFlow
          title="Expenses"
          tone="expense"
          :total="summary.expense.total"
          :breakdown="summary.expense.breakdown"
          :transactions="summary.expense.transactions"
          :loading="loading"
          :format-money="formatMoney"
        />
      </div>

      <!-- Stock movement -->
      <article class="app-surface rounded-2xl overflow-hidden">
        <header class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
              Stock Movement
            </h3>
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Opening units, received, sold and closing units per product.
            </p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Stock on hand (cost)
            </p>
            <p class="text-sm font-semibold text-secondary-900 dark:text-white">
              {{ loading ? '-' : formatMoney(summary.stock.on_hand_value) }}
            </p>
          </div>
        </header>

        <div v-if="loading" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
          Loading stock...
        </div>
        <div v-else-if="summary.stock.movements.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
          No stock or stock movement for this day.
        </div>
        <template v-else>
          <!-- Mobile -->
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="item in summary.stock.movements" :key="item.product_id" class="px-4 py-3 space-y-2">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                  {{ item.product_name }}
                </p>
                <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">
                  {{ formatNumber(item.closing) }} units
                </p>
              </div>
              <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-secondary-600 dark:text-secondary-300">
                <p>Opening: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(item.opening) }}</span></p>
                <p>Closing: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(item.closing) }}</span></p>
                <p>Received: <span class="font-medium text-emerald-600 dark:text-emerald-400">+{{ formatNumber(item.received) }}</span></p>
                <p>Sold: <span class="font-medium text-red-600 dark:text-red-400">-{{ formatNumber(item.sold) }}</span></p>
                <p class="col-span-2">
                  Revenue: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(item.revenue) }}</span>
                </p>
              </div>
            </article>
          </div>
          <!-- Desktop -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[720px]">
              <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Product
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Opening
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Received
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Sold
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Closing
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Revenue
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr v-for="item in summary.stock.movements" :key="item.product_id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm text-secondary-900 dark:text-white">
                    {{ item.product_name }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                    {{ formatNumber(item.opening) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-emerald-600 dark:text-emerald-400">
                    +{{ formatNumber(item.received) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-red-600 dark:text-red-400">
                    -{{ formatNumber(item.sold) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatNumber(item.closing) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                    {{ formatMoney(item.revenue) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-secondary-50 dark:bg-background-dark border-t border-secondary-200 dark:border-secondary-700">
                <tr>
                  <td class="px-3 py-2 text-sm font-semibold text-secondary-900 dark:text-white">
                    Total
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatNumber(summary.stock.totals.opening) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-emerald-600 dark:text-emerald-400">
                    +{{ formatNumber(summary.stock.totals.received) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-red-600 dark:text-red-400">
                    -{{ formatNumber(summary.stock.totals.sold) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatNumber(summary.stock.totals.closing) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(summary.stock.totals.revenue) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </template>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import DailySummaryFlow from '../components/reports/DailySummaryFlow.vue';
import { apiRequest } from '../composables/useApiClient';

const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function pad(v) { return String(v).padStart(2, '0'); }
function toDateInputValue(date = new Date()) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

const today = toDateInputValue();
const selectedDate = ref(today);
const loading = ref(false);
const error = ref('');

function defaultSummary() {
    return {
        date: '',
        date_label: '',
        is_today: false,
        accounts: [],
        income: { total: 0, breakdown: [], transactions: [] },
        expense: { total: 0, breakdown: [], transactions: [] },
        stock: {
            on_hand_value: 0,
            totals: { opening: 0, received: 0, sold: 0, closing: 0, revenue: 0 },
            movements: [],
        },
        totals: {
            opening_balance: 0,
            income: 0,
            expense: 0,
            closing_balance: 0,
            net_movement: 0,
            stock_on_hand: 0,
        },
    };
}

const summary = ref(defaultSummary());

function formatNumber(value) { return numberFormatter.format(Number(value || 0)); }
function formatMoney(value) { return moneyFormatter.format(Number(value || 0)); }

function goToToday() {
    selectedDate.value = today;
    loadSummary();
}

async function loadSummary() {
    loading.value = true;
    error.value = '';
    try {
        const response = await apiRequest('/api/reports/daily-summary', {
            params: { date: selectedDate.value },
        });
        summary.value = { ...defaultSummary(), ...(response || {}) };
        if (summary.value.date) selectedDate.value = summary.value.date;
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load daily summary.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadSummary();
});
</script>
