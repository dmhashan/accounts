<template>
  <section class="app-page-frame">
    <AppPageHeader title="Daily Summary">
      <template #extra-slot>
        <div class="flex flex-wrap items-end gap-3">
          <form class="flex flex-wrap items-end gap-3" @submit.prevent="loadSummary">
            <AppFormField label="Date" class="w-full sm:w-auto">
              <AppFormInput
                v-model="selectedDate"
                type="date"
                :max="today"
                :disabled="editMode"
                required
              />
            </AppFormField>

            <button
              type="submit"
              :disabled="loading || editMode"
              class="self-end px-4 py-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold transition-all hover:brightness-110"
            >
              {{ loading ? 'Loading...' : 'Apply' }}
            </button>

            <button
              v-if="selectedDate !== today && !editMode"
              type="button"
              class="self-end px-4 py-2 rounded-xl app-surface-soft text-sm font-semibold text-secondary-700 dark:text-secondary-200 transition-colors hover:brightness-105"
              @click="goToToday"
            >
              Today
            </button>
          </form>

          <div class="flex items-end gap-2">
            <template v-if="!editMode">
              <RouterLink
                to="/reports/daily-summary/history"
                class="self-end px-4 py-2 rounded-xl app-surface-soft text-sm font-semibold text-secondary-700 dark:text-secondary-200 transition-colors hover:brightness-105"
              >
                History
              </RouterLink>
              <button
                v-if="canEdit"
                type="button"
                :disabled="loading"
                class="self-end px-4 py-2 rounded-xl border border-primary-300 dark:border-primary-700 text-primary-700 dark:text-primary-300 text-sm font-semibold transition-colors hover:bg-primary-50 dark:hover:bg-primary-900/20 disabled:opacity-60"
                @click="enterEditMode"
              >
                Edit &amp; Prepare
              </button>
            </template>
            <template v-else>
              <button
                type="button"
                class="self-end px-4 py-2 rounded-xl app-surface-soft text-sm font-semibold text-secondary-700 dark:text-secondary-200 transition-colors hover:brightness-105"
                @click="exitEditMode"
              >
                Cancel
              </button>
              <button
                type="button"
                class="self-end px-4 py-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white text-sm font-semibold transition-all hover:brightness-110"
                @click="prepareOpen = true"
              >
                Prepare &amp; Sign
                <span v-if="changeCount > 0" class="ml-1.5 inline-flex items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-bold leading-5 text-white">{{ changeCount }}</span>
              </button>
            </template>
          </div>
        </div>
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div v-if="error" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ error }}
      </div>

      <!-- Generated success banner -->
      <div v-if="generatedReport" class="mb-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="text-sm text-emerald-800 dark:text-emerald-200">
            <span class="font-semibold">Report generated.</span>
            Prepared by {{ generatedReport.prepared_by_name }}. A copy has been emailed to the administrators.
          </div>
          <div class="flex items-center gap-2">
            <a
              :href="`/api/reports/daily-summary/reports/${generatedReport.id}/pdf`"
              target="_blank"
              rel="noopener"
              class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:brightness-110"
            >
              View PDF
            </a>
            <RouterLink
              to="/reports/daily-summary/history"
              class="px-3 py-1.5 rounded-lg app-surface-soft text-xs font-semibold text-secondary-700 dark:text-secondary-200 hover:brightness-105"
            >
              All Reports
            </RouterLink>
          </div>
        </div>
      </div>

      <div v-if="editMode" class="mb-4 rounded-xl border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/20 px-4 py-3 text-sm text-primary-800 dark:text-primary-200">
        <span class="font-semibold">Edit mode.</span>
        Adjust any figure below. Changed values are tracked and highlighted in red on the generated PDF. When done, click <span class="font-semibold">Prepare &amp; Sign</span>.
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
            {{ loading ? '-' : formatMoney(displayTotals.opening_balance) }}
          </p>
        </article>
        <article class="app-surface rounded-2xl p-4">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Income
          </p>
          <p class="mt-1 text-lg md:text-xl font-semibold text-emerald-600 dark:text-emerald-400">
            {{ loading ? '-' : '+' + formatMoney(displayTotals.income) }}
          </p>
        </article>
        <article class="app-surface rounded-2xl p-4">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Expenses
          </p>
          <p class="mt-1 text-lg md:text-xl font-semibold text-red-600 dark:text-red-400">
            {{ loading ? '-' : '-' + formatMoney(displayTotals.expense) }}
          </p>
        </article>
        <article class="app-surface rounded-2xl p-4 ring-1 ring-primary-200 dark:ring-primary-900/40">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Closing Balance
          </p>
          <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">
            {{ loading ? '-' : formatMoney(displayTotals.closing_balance) }}
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
        <div v-else-if="displayAccounts.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
          No accounts found.
        </div>
        <template v-else>
          <!-- Mobile -->
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="account in displayAccounts" :key="account.id" class="px-4 py-3 space-y-2">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                  {{ account.name }}
                </p>
                <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">
                  {{ formatMoney(accountClosing(account)) }}
                </p>
              </div>
              <div v-if="editMode" class="grid grid-cols-3 gap-2">
                <label class="text-xs text-secondary-500 dark:text-secondary-400">Opening
                  <input
                    v-model.number="account.opening_balance"
                    type="number"
                    step="0.01"
                    class="edit-input mt-0.5"
                  />
                </label>
                <label class="text-xs text-secondary-500 dark:text-secondary-400">Income
                  <input
                    v-model.number="account.income"
                    type="number"
                    step="0.01"
                    class="edit-input mt-0.5"
                  />
                </label>
                <label class="text-xs text-secondary-500 dark:text-secondary-400">Expense
                  <input
                    v-model.number="account.expense"
                    type="number"
                    step="0.01"
                    class="edit-input mt-0.5"
                  />
                </label>
              </div>
              <div v-else class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-secondary-600 dark:text-secondary-300">
                <p>Opening: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(account.opening_balance) }}</span></p>
                <p>Closing: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(accountClosing(account)) }}</span></p>
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
                <tr v-for="account in displayAccounts" :key="account.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm text-secondary-900 dark:text-white">
                    {{ account.name }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                    <input
                      v-if="editMode"
                      v-model.number="account.opening_balance"
                      type="number"
                      step="0.01"
                      class="edit-input text-right"
                    />
                    <span v-else>{{ formatMoney(account.opening_balance) }}</span>
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-emerald-600 dark:text-emerald-400">
                    <input
                      v-if="editMode"
                      v-model.number="account.income"
                      type="number"
                      step="0.01"
                      class="edit-input text-right"
                    />
                    <span v-else>+{{ formatMoney(account.income) }}</span>
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-red-600 dark:text-red-400">
                    <input
                      v-if="editMode"
                      v-model.number="account.expense"
                      type="number"
                      step="0.01"
                      class="edit-input text-right"
                    />
                    <span v-else>-{{ formatMoney(account.expense) }}</span>
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(accountClosing(account)) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-secondary-50 dark:bg-background-dark border-t border-secondary-200 dark:border-secondary-700">
                <tr>
                  <td class="px-3 py-2 text-sm font-semibold text-secondary-900 dark:text-white">
                    Total
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(displayTotals.opening_balance) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-emerald-600 dark:text-emerald-400">
                    +{{ formatMoney(displayTotals.income) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-red-600 dark:text-red-400">
                    -{{ formatMoney(displayTotals.expense) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatMoney(displayTotals.closing_balance) }}
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
        <div v-else-if="displayStock.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
          No stock or stock movement for this day.
        </div>
        <template v-else>
          <!-- Mobile -->
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="item in displayStock" :key="item.product_id" class="px-4 py-3 space-y-2">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                  {{ item.product_name }}
                </p>
                <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">
                  {{ formatNumber(stockClosing(item)) }} units
                </p>
              </div>
              <div v-if="editMode" class="grid grid-cols-3 gap-2">
                <label class="text-xs text-secondary-500 dark:text-secondary-400">Opening
                  <input
                    v-model.number="item.opening"
                    type="number"
                    step="1"
                    class="edit-input mt-0.5"
                  />
                </label>
                <label class="text-xs text-secondary-500 dark:text-secondary-400">Received
                  <input
                    v-model.number="item.received"
                    type="number"
                    step="1"
                    class="edit-input mt-0.5"
                  />
                </label>
                <label class="text-xs text-secondary-500 dark:text-secondary-400">Sold
                  <input
                    v-model.number="item.sold"
                    type="number"
                    step="1"
                    class="edit-input mt-0.5"
                  />
                </label>
              </div>
              <div v-else class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-secondary-600 dark:text-secondary-300">
                <p>Opening: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(item.opening) }}</span></p>
                <p>Closing: <span class="font-medium text-secondary-900 dark:text-white">{{ formatNumber(stockClosing(item)) }}</span></p>
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
                <tr v-for="item in displayStock" :key="item.product_id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm text-secondary-900 dark:text-white">
                    {{ item.product_name }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">
                    <input
                      v-if="editMode"
                      v-model.number="item.opening"
                      type="number"
                      step="1"
                      class="edit-input text-right"
                    />
                    <span v-else>{{ formatNumber(item.opening) }}</span>
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-emerald-600 dark:text-emerald-400">
                    <input
                      v-if="editMode"
                      v-model.number="item.received"
                      type="number"
                      step="1"
                      class="edit-input text-right"
                    />
                    <span v-else>+{{ formatNumber(item.received) }}</span>
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-red-600 dark:text-red-400">
                    <input
                      v-if="editMode"
                      v-model.number="item.sold"
                      type="number"
                      step="1"
                      class="edit-input text-right"
                    />
                    <span v-else>-{{ formatNumber(item.sold) }}</span>
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatNumber(stockClosing(item)) }}
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
                    {{ formatNumber(displayStockTotals.opening) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-emerald-600 dark:text-emerald-400">
                    +{{ formatNumber(displayStockTotals.received) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-red-600 dark:text-red-400">
                    -{{ formatNumber(displayStockTotals.sold) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                    {{ formatNumber(displayStockTotals.closing) }}
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

    <DailySummaryPrepareModal
      v-if="prepareOpen"
      :change-count="changeCount"
      :submitting="generating"
      :submit-error="generateError"
      @close="prepareOpen = false"
      @submit="submitGenerate"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import DailySummaryFlow from '../components/reports/DailySummaryFlow.vue';
import DailySummaryPrepareModal from '../components/reports/DailySummaryPrepareModal.vue';
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

// Edit / prepare state
const editMode = ref(false);
const editAccounts = ref([]);
const editStock = ref([]);
const prepareOpen = ref(false);
const generating = ref(false);
const generateError = ref('');
const generatedReport = ref(null);

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

function num(value) { return Number(value) || 0; }
function accountClosing(a) { return num(a.opening_balance) + num(a.income) - num(a.expense); }
function stockClosing(s) { return num(s.opening) + num(s.received) - num(s.sold); }

const canEdit = computed(() => summary.value.accounts.length > 0 || summary.value.stock.movements.length > 0);

const displayAccounts = computed(() => (editMode.value ? editAccounts.value : summary.value.accounts));
const displayStock = computed(() => (editMode.value ? editStock.value : summary.value.stock.movements));

const displayTotals = computed(() => {
    if (!editMode.value) return summary.value.totals;
    let opening = 0; let income = 0; let expense = 0;
    for (const a of editAccounts.value) {
        opening += num(a.opening_balance);
        income += num(a.income);
        expense += num(a.expense);
    }
    return { opening_balance: opening, income, expense, closing_balance: opening + income - expense };
});

const displayStockTotals = computed(() => {
    if (!editMode.value) return summary.value.stock.totals;
    let opening = 0; let received = 0; let sold = 0;
    for (const s of editStock.value) {
        opening += num(s.opening);
        received += num(s.received);
        sold += num(s.sold);
    }
    return { opening, received, sold, closing: opening + received - sold };
});

const changeCount = computed(() => {
    if (!editMode.value) return 0;
    let n = 0;
    const accMap = Object.fromEntries(summary.value.accounts.map(a => [a.id, a]));
    for (const a of editAccounts.value) {
        const o = accMap[a.id];
        if (!o) continue;
        if (Math.abs(num(a.opening_balance) - num(o.opening_balance)) > 0.001) n += 1;
        if (Math.abs(num(a.income) - num(o.income)) > 0.001) n += 1;
        if (Math.abs(num(a.expense) - num(o.expense)) > 0.001) n += 1;
    }
    const stMap = Object.fromEntries(summary.value.stock.movements.map(s => [s.product_id, s]));
    for (const s of editStock.value) {
        const o = stMap[s.product_id];
        if (!o) continue;
        if (Math.abs(num(s.opening) - num(o.opening)) > 0.001) n += 1;
        if (Math.abs(num(s.received) - num(o.received)) > 0.001) n += 1;
        if (Math.abs(num(s.sold) - num(o.sold)) > 0.001) n += 1;
    }
    return n;
});

function enterEditMode() {
    editAccounts.value = summary.value.accounts.map(a => ({
        id: a.id,
        name: a.name,
        opening_balance: num(a.opening_balance),
        income: num(a.income),
        expense: num(a.expense),
    }));
    editStock.value = summary.value.stock.movements.map(s => ({
        product_id: s.product_id,
        product_name: s.product_name,
        opening: num(s.opening),
        received: num(s.received),
        sold: num(s.sold),
        revenue: num(s.revenue),
    }));
    generatedReport.value = null;
    generateError.value = '';
    editMode.value = true;
}

function exitEditMode() {
    editMode.value = false;
    editAccounts.value = [];
    editStock.value = [];
}

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

async function submitGenerate(meta) {
    generating.value = true;
    generateError.value = '';
    try {
        const response = await apiRequest('/api/reports/daily-summary/generate', {
            method: 'post',
            data: {
                date: selectedDate.value,
                prepared_by_name: meta.prepared_by_name,
                signature: meta.signature,
                selfie: meta.selfie,
                accounts: editAccounts.value.map(a => ({
                    id: a.id,
                    opening_balance: num(a.opening_balance),
                    income: num(a.income),
                    expense: num(a.expense),
                })),
                stock: editStock.value.map(s => ({
                    product_id: s.product_id,
                    opening: num(s.opening),
                    received: num(s.received),
                    sold: num(s.sold),
                })),
            },
        });
        generatedReport.value = response?.report || response;
        prepareOpen.value = false;
        editMode.value = false;
        editAccounts.value = [];
        editStock.value = [];
    } catch (err) {
        generateError.value = err?.response?.data?.message || 'Failed to generate report.';
    } finally {
        generating.value = false;
    }
}

onMounted(() => {
    loadSummary();
});
</script>

<style scoped>
.edit-input {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid rgb(203 213 225);
    background-color: white;
    padding: 0.25rem 0.5rem;
    font-size: 0.8125rem;
    color: rgb(15 23 42);
}
.edit-input:focus {
    outline: 2px solid rgb(99 102 241);
    outline-offset: -1px;
}
:global(.dark) .edit-input {
    border-color: rgb(71 85 105);
    background-color: rgb(15 23 42);
    color: white;
}
</style>
