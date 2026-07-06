<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #extra-slot>
        <div class="space-y-3">
          <!-- Filters -->
          <form class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 w-full xl:w-auto" @submit.prevent="loadStats">
            <AppFormField label="Range Type">
              <AppFormSelect v-model="filters.range_type" @change="handleRangeTypeChange">
                <option value="date">
                  Date
                </option>
                <option value="week">
                  Week
                </option>
                <option value="month">
                  Month
                </option>
                <option value="year">
                  Year
                </option>
                <option value="date_range">
                  Date Range
                </option>
              </AppFormSelect>
            </AppFormField>

            <AppFormField :label="rangeValueLabel">
              <AppFormInput
                v-if="filters.range_type !== 'year' && filters.range_type !== 'date_range'"
                v-model="filters.range_value"
                :type="filters.range_type"
                required
              />
              <AppFormInput
                v-else-if="filters.range_type === 'year'"
                v-model="filters.range_value"
                type="number"
                min="1970"
                max="9999"
                required
              />
              <div v-if="filters.range_type === 'date_range'" class="grid grid-cols-2 gap-2">
                <AppFormInput v-model="filters.start_date" type="date" required />
                <AppFormInput
                  v-model="filters.end_date"
                  type="date"
                  :min="filters.start_date"
                  required
                />
              </div>
            </AppFormField>

            <div class="block">
              <span class="text-xs font-medium text-secondary-600 dark:text-secondary-400">Period</span>
              <p class="mt-1 px-3 py-2 rounded-xl app-surface-soft text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">
                {{ stats.range_label || 'Select a period' }}
              </p>
            </div>

            <button
              type="submit"
              :disabled="statsLoading"
              class="self-end px-4 py-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold transition-all hover:brightness-110"
            >
              {{ statsLoading ? 'Loading...' : 'Apply' }}
            </button>
          </form>
        </div>
      </template>
    </AppPageHeader>

    <!-- Sales Stats Tab -->
    <div v-if="activeTab === 'stats'" class="app-page-scroll">
      <div v-if="statsError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ statsError }}
      </div>

      <div v-if="!stats.can_view" class="app-surface rounded-2xl p-5 md:p-6 text-sm text-secondary-500 dark:text-secondary-400">
        You do not have permission to view sales stats.
      </div>

      <template v-else>
        <template v-if="cashFlowFocus">
          <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
            <article class="app-surface rounded-xl p-4">
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                Income
              </p>
              <p class="mt-1 text-lg font-semibold text-green-700 dark:text-green-400 md:text-xl">
                {{ statsLoading ? '-' : formatMoney(stats.cash_flow_summary.income) }}
              </p>
            </article>
            <article class="app-surface rounded-xl p-4">
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                Expenses
              </p>
              <p class="mt-1 text-lg font-semibold text-red-700 dark:text-red-400 md:text-xl">
                {{ statsLoading ? '-' : formatMoney(stats.cash_flow_summary.expense) }}
              </p>
            </article>
            <article class="app-surface col-span-2 rounded-xl p-4 md:col-span-1">
              <p class="text-xs text-secondary-500 dark:text-secondary-400">
                Net Movement
              </p>
              <p class="mt-1 text-lg font-semibold md:text-xl" :class="Number(stats.cash_flow_summary.net_movement) >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                {{ statsLoading ? '-' : formatSignedMoney(stats.cash_flow_summary.net_movement) }}
              </p>
            </article>
          </div>

          <article class="app-surface overflow-hidden rounded-xl">
            <div class="border-b border-secondary-200 px-4 py-3 dark:border-secondary-700">
              <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                Account Transactions
              </p>
              <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                {{ stats.range_label || 'Selected period' }}
              </p>
            </div>
            <div v-if="statsLoading" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
              Loading transactions...
            </div>
            <div v-else-if="stats.account_transaction_list.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
              No account transactions found for the selected period.
            </div>
            <div v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
              <component
                :is="transaction.source_path ? 'RouterLink' : 'div'"
                v-for="transaction in stats.account_transaction_list"
                :key="transaction.id"
                :to="transaction.source_path || undefined"
                class="flex min-h-14 items-center gap-3 px-4 py-3 transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/50"
              >
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ transaction.source_label }}
                  </p>
                  <p class="mt-0.5 truncate text-xs text-secondary-500 dark:text-secondary-400">
                    {{ transaction.account_name }} · {{ transaction.transaction_date }}
                  </p>
                  <p v-if="transaction.notes" class="mt-0.5 truncate text-xs text-secondary-500 dark:text-secondary-400">
                    {{ transaction.notes }}
                  </p>
                </div>
                <p class="shrink-0 text-sm font-semibold tabular-nums" :class="Number(transaction.amount) >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                  {{ formatSignedMoney(transaction.amount) }}
                </p>
              </component>
            </div>
          </article>
        </template>

        <!-- Summary cards -->
        <div v-if="!cashFlowFocus" class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-4">
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Transactions
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatNumber(stats.transactions) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Gross Amount
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.gross_amount) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Paid Amount
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.paid_amount) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Outstanding
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.outstanding_amount) }}
            </p>
          </article>
        </div>

        <!-- Transaction List -->
        <article v-if="!cashFlowFocus" class="app-surface rounded-2xl overflow-hidden">
          <div v-if="statsLoading" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
            Loading transactions...
          </div>
          <div v-else-if="stats.transaction_list.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
            No transactions found for the selected period.
          </div>
          <template v-else>
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article v-for="transaction in stats.transaction_list" :key="transaction.sale_id" class="px-4 py-3 space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                      #{{ transaction.sale_id }} - {{ transaction.customer_name }}
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      {{ formatDateTime(transaction.created_at) }}
                    </p>
                  </div>
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">
                    {{ formatMoney(transaction.total_amount) }}
                  </p>
                </div>
                <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-secondary-600 dark:text-secondary-300">
                  <p>Type: <span class="font-medium text-secondary-900 dark:text-white">{{ customerTypeLabel(transaction.customer_type) }}</span></p>
                  <p>Payment: <span class="font-medium text-secondary-900 dark:text-white">{{ paymentMethodLabel(transaction.payment_method) }}</span></p>
                  <p>Items: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(transaction.item_quantity) }}</span></p>
                  <p>Lines: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(transaction.item_lines) }}</span></p>
                  <p>Paid: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(transaction.paid_amount) }}</span></p>
                  <p>Balance: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(transaction.balance) }}</span></p>
                </div>
              </article>
            </div>
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full min-w-[860px]">
                <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Sale
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Date &amp; Time
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Customer
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Type
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Payment
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Qty
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Lines
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Total
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Paid
                    </th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Balance
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr v-for="transaction in stats.transaction_list" :key="transaction.sale_id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                    <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">
                      #{{ transaction.sale_id }}
                    </td>
                    <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">
                      {{ formatDateTime(transaction.created_at) }}
                    </td>
                    <td class="px-3 py-2 text-sm text-secondary-900 dark:text-white">
                      {{ transaction.customer_name }}
                    </td>
                    <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ customerTypeLabel(transaction.customer_type) }}
                    </td>
                    <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ paymentMethodLabel(transaction.payment_method) }}
                    </td>
                    <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                      {{ formatNumber(transaction.item_quantity) }}
                    </td>
                    <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                      {{ formatNumber(transaction.item_lines) }}
                    </td>
                    <td class="px-3 py-2 text-sm text-right text-secondary-900 dark:text-white">
                      {{ formatMoney(transaction.total_amount) }}
                    </td>
                    <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                      {{ formatMoney(transaction.paid_amount) }}
                    </td>
                    <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                      {{ formatMoney(transaction.balance) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </article>
      </template>
    </div>

    <!-- Customer Wise Sale Tab -->
    <div v-else-if="activeTab === 'customers'" class="app-page-scroll">
      <div v-if="statsError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ statsError }}
      </div>

      <div v-if="!stats.can_view" class="app-surface rounded-2xl p-5 md:p-6 text-sm text-secondary-500 dark:text-secondary-400">
        You do not have permission to view sales stats.
      </div>

      <template v-else>
        <!-- Summary cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-4">
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Transactions
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatNumber(stats.transactions) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Gross Amount
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.gross_amount) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Paid Amount
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.paid_amount) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Outstanding
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.outstanding_amount) }}
            </p>
          </article>
        </div>

        <article class="app-surface rounded-2xl p-4 md:p-5">
          <div v-if="statsLoading" class="text-sm text-secondary-500 dark:text-secondary-400">
            Loading customer summary...
          </div>
          <div v-else-if="stats.customer_wise_sales.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">
            No customer sales found for the selected period.
          </div>
          <ul v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <li v-for="item in stats.customer_wise_sales" :key="item.customer_name" class="py-2.5 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm text-secondary-900 dark:text-white truncate">
                  {{ item.customer_name }}
                </p>
                <p class="text-xs text-secondary-500 dark:text-secondary-400">
                  {{ formatNumber(item.transactions) }} {{ transactionCountLabel(item.transactions) }}
                </p>
              </div>
              <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">
                {{ formatMoney(item.total_amount) }}
              </p>
            </li>
          </ul>
        </article>
      </template>
    </div>

    <!-- Product Wise Sale Tab -->
    <div v-else-if="activeTab === 'products'" class="app-page-scroll">
      <div v-if="statsError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ statsError }}
      </div>

      <div v-if="!stats.can_view" class="app-surface rounded-2xl p-5 md:p-6 text-sm text-secondary-500 dark:text-secondary-400">
        You do not have permission to view sales stats.
      </div>

      <template v-else>
        <!-- Summary cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-4">
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Transactions
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatNumber(stats.transactions) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Gross Amount
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.gross_amount) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Paid Amount
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.paid_amount) }}
            </p>
          </article>
          <article class="app-surface rounded-2xl p-4">
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              Outstanding
            </p>
            <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
              {{ statsLoading ? '-' : formatMoney(stats.outstanding_amount) }}
            </p>
          </article>
        </div>

        <article class="app-surface rounded-2xl p-4 md:p-5">
          <div v-if="statsLoading" class="text-sm text-secondary-500 dark:text-secondary-400">
            Loading product summary...
          </div>
          <div v-else-if="stats.product_wise_sales.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">
            No product sales found for the selected period.
          </div>
          <ul v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <li v-for="item in stats.product_wise_sales" :key="item.product_id" class="py-2.5 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm text-secondary-900 dark:text-white truncate">
                  {{ item.product_name }}
                </p>
                <p class="text-xs text-secondary-500 dark:text-secondary-400">
                  {{ formatNumber(item.quantity_sold) }} units · {{ formatNumber(item.transactions) }} {{ transactionCountLabel(item.transactions) }}
                </p>
              </div>
              <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">
                {{ formatMoney(item.total_amount) }}
              </p>
            </li>
          </ul>
        </article>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import { apiRequest } from '../composables/useApiClient';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';

const route = useRoute();

const activeTab = ref(route.path === '/reports/customers' ? 'customers' : route.path === '/reports/products' ? 'products' : 'stats');
const cashFlowFocus = computed(() => route.query.focus === 'cash_flow' && activeTab.value === 'stats');

function switchTab(tab) {
    activeTab.value = tab;
}

watch(
    () => route.path,
    (path) => {
        const newTab = path === '/reports/customers' ? 'customers' : path === '/reports/products' ? 'products' : 'stats';
        if (activeTab.value !== newTab) switchTab(newTab);
    }
);

// ── Sales Stats ───────────────────────────────────────────────────────────────
const statsLoading = ref(false);
const statsError = ref('');

const filters = ref({
    range_type: route.query.range_type === 'date_range' ? 'date_range' : 'date',
    range_value: defaultRangeValue('date'),
    start_date: String(route.query.start_date || toDateInputValue()),
    end_date: String(route.query.end_date || toDateInputValue()),
});

const stats = ref(defaultStats());

const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const { formatDateTime } = useDateTimeFormat();

const rangeValueLabel = computed(() => {
    const labels = { week: 'Week', month: 'Month', year: 'Year', date_range: 'Date Range' };
    return labels[filters.value.range_type] || 'Date';
});

function defaultStats() {
    return {
        can_view: true,
        range_type: 'date',
        range_value: '',
        range_label: '',
        transactions: 0,
        gross_amount: 0,
        paid_amount: 0,
        outstanding_amount: 0,
        transaction_list: [],
        customer_wise_sales: [],
        product_wise_sales: [],
        cash_flow_summary: {
            income: 0,
            expense: 0,
            net_movement: 0,
            income_count: 0,
            expense_count: 0,
        },
        account_transaction_list: [],
    };
}

function formatNumber(value) { return numberFormatter.format(Number(value || 0)); }
function formatMoney(value) { return moneyFormatter.format(Number(value || 0)); }
function formatSignedMoney(value) {
    const amount = Number(value || 0);
    return `${amount >= 0 ? '+' : '-'}${formatMoney(Math.abs(amount))}`;
}

function paymentMethodLabel(value) {
    return { cash: 'Cash', card: 'Card', bank: 'Bank Transfer', member_wallet: 'Member Wallet' }[value] || (value || 'Other');
}

function customerTypeLabel(value) {
    if (!value) return 'N/A';
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function transactionCountLabel(count) {
    return Number(count || 0) === 1 ? 'transaction' : 'transactions';
}

function pad(v) { return String(v).padStart(2, '0'); }

function toDateInputValue(date = new Date()) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function toMonthInputValue(date = new Date()) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}`;
}

function toYearInputValue(date = new Date()) { return String(date.getFullYear()); }

function toIsoWeekInputValue(date = new Date()) {
    const current = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const day = current.getUTCDay() || 7;
    current.setUTCDate(current.getUTCDate() + 4 - day);
    const yearStart = new Date(Date.UTC(current.getUTCFullYear(), 0, 1));
    const weekNumber = Math.ceil((((current - yearStart) / 86400000) + 1) / 7);
    return `${current.getUTCFullYear()}-W${pad(weekNumber)}`;
}

function defaultRangeValue(rangeType) {
    if (rangeType === 'week') return toIsoWeekInputValue();
    if (rangeType === 'month') return toMonthInputValue();
    if (rangeType === 'year') return toYearInputValue();
    return toDateInputValue();
}

function handleRangeTypeChange() {
    if (filters.value.range_type === 'date_range') {
        filters.value.start_date = filters.value.start_date || toDateInputValue();
        filters.value.end_date = filters.value.end_date || filters.value.start_date;
        return;
    }

    filters.value.range_value = defaultRangeValue(filters.value.range_type);
}

async function loadStats() {
    statsLoading.value = true;
    statsError.value = '';
    try {
        const response = await apiRequest('/api/dashboard/stats', {
            params: {
                range_type: filters.value.range_type,
                range_value: filters.value.range_type === 'date_range' ? undefined : filters.value.range_value,
                start_date: filters.value.range_type === 'date_range' ? filters.value.start_date : undefined,
                end_date: filters.value.range_type === 'date_range' ? filters.value.end_date : undefined,
            },
        });
        stats.value = { ...defaultStats(), ...(response || {}) };
        filters.value.range_type = stats.value.range_type || filters.value.range_type;
        filters.value.range_value = stats.value.range_value || filters.value.range_value;
        filters.value.start_date = stats.value.start_date || filters.value.start_date;
        filters.value.end_date = stats.value.end_date || filters.value.end_date;
    } catch (error) {
        statsError.value = error?.response?.data?.message || 'Failed to load sales stats.';
    } finally {
        statsLoading.value = false;
    }
}

onMounted(() => {
    loadStats();
});
</script>
