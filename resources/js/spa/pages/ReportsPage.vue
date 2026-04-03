<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #extra-slot>
                <div class="space-y-3">
                    <div class="inline-flex flex-wrap rounded-xl app-surface-soft p-1">
                        <button
                            v-for="tab in mainTabs"
                            :key="tab.key"
                            type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                            :class="activeTab === tab.key ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                            @click="switchTab(tab.key)"
                        >
                            {{ tab.label }}
                            <span
                                class="ml-2 inline-flex min-w-[1.5rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[11px]"
                                :class="activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-white dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300'"
                            >{{ statsLoading ? '-' : formatNumber(tab.count) }}</span>
                        </button>
                    </div>

                    <!-- Filters -->
                    <form class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 w-full xl:w-auto" @submit.prevent="loadStats">
                        <AppFormField label="Range Type">
                            <AppFormSelect v-model="filters.range_type" @change="handleRangeTypeChange">
                                <option value="date">Date</option>
                                <option value="week">Week</option>
                                <option value="month">Month</option>
                                <option value="year">Year</option>
                            </AppFormSelect>
                        </AppFormField>

                        <AppFormField :label="rangeValueLabel">
                            <AppFormInput
                                v-if="filters.range_type !== 'year'"
                                v-model="filters.range_value"
                                :type="filters.range_type"
                                required
                            />
                            <AppFormInput
                                v-else
                                v-model="filters.range_value"
                                type="number"
                                min="1970"
                                max="9999"
                                required
                            />
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
                <!-- Summary cards -->
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-4">
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Transactions</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatNumber(stats.transactions) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Gross Amount</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.gross_amount) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Paid Amount</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.paid_amount) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Outstanding</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.outstanding_amount) }}</p>
                    </article>
                </div>

                <!-- Transaction List -->
                <article class="app-surface rounded-2xl overflow-hidden">
                    <div v-if="statsLoading" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">Loading transactions...</div>
                    <div v-else-if="stats.transaction_list.length === 0" class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">No transactions found for the selected period.</div>
                    <template v-else>
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article v-for="transaction in stats.transaction_list" :key="transaction.sale_id" class="px-4 py-3 space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">#{{ transaction.sale_id }} - {{ transaction.customer_name }}</p>
                                        <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ formatDateTime(transaction.created_at) }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">{{ formatMoney(transaction.total_amount) }}</p>
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
                                        <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Sale</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date &amp; Time</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Customer</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Type</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Payment</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Qty</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Lines</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Total</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Paid</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Balance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                    <tr v-for="transaction in stats.transaction_list" :key="transaction.sale_id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                        <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">#{{ transaction.sale_id }}</td>
                                        <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">{{ formatDateTime(transaction.created_at) }}</td>
                                        <td class="px-3 py-2 text-sm text-secondary-900 dark:text-white">{{ transaction.customer_name }}</td>
                                        <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">{{ customerTypeLabel(transaction.customer_type) }}</td>
                                        <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">{{ paymentMethodLabel(transaction.payment_method) }}</td>
                                        <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">{{ formatNumber(transaction.item_quantity) }}</td>
                                        <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">{{ formatNumber(transaction.item_lines) }}</td>
                                        <td class="px-3 py-2 text-sm text-right text-secondary-900 dark:text-white">{{ formatMoney(transaction.total_amount) }}</td>
                                        <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">{{ formatMoney(transaction.paid_amount) }}</td>
                                        <td class="px-3 py-2 text-sm text-right text-secondary-700 dark:text-secondary-300">{{ formatMoney(transaction.balance) }}</td>
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
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Transactions</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatNumber(stats.transactions) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Gross Amount</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.gross_amount) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Paid Amount</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.paid_amount) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Outstanding</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.outstanding_amount) }}</p>
                    </article>
                </div>

                <article class="app-surface rounded-2xl p-4 md:p-5">
                    <div v-if="statsLoading" class="text-sm text-secondary-500 dark:text-secondary-400">Loading customer summary...</div>
                    <div v-else-if="stats.customer_wise_sales.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">No customer sales found for the selected period.</div>
                    <ul v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
                        <li v-for="item in stats.customer_wise_sales" :key="item.customer_name" class="py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm text-secondary-900 dark:text-white truncate">{{ item.customer_name }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ formatNumber(item.transactions) }} {{ transactionCountLabel(item.transactions) }}</p>
                            </div>
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">{{ formatMoney(item.total_amount) }}</p>
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
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Transactions</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatNumber(stats.transactions) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Gross Amount</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.gross_amount) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Paid Amount</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.paid_amount) }}</p>
                    </article>
                    <article class="app-surface rounded-2xl p-4">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Outstanding</p>
                        <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ statsLoading ? '-' : formatMoney(stats.outstanding_amount) }}</p>
                    </article>
                </div>

                <article class="app-surface rounded-2xl p-4 md:p-5">
                    <div v-if="statsLoading" class="text-sm text-secondary-500 dark:text-secondary-400">Loading product summary...</div>
                    <div v-else-if="stats.product_wise_sales.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">No product sales found for the selected period.</div>
                    <ul v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
                        <li v-for="item in stats.product_wise_sales" :key="item.product_id" class="py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm text-secondary-900 dark:text-white truncate">{{ item.product_name }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ formatNumber(item.quantity_sold) }} units · {{ formatNumber(item.transactions) }} {{ transactionCountLabel(item.transactions) }}</p>
                            </div>
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">{{ formatMoney(item.total_amount) }}</p>
                        </li>
                    </ul>
                </article>
            </template>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();

const VALID_TABS = ['stats', 'customers', 'products'];
const activeTab = ref(VALID_TABS.includes(route.query.tab) ? route.query.tab : 'stats');

const mainTabs = computed(() => [
    { key: 'stats', label: 'Transaction List', count: stats.value.transaction_list.length },
    { key: 'customers', label: 'Customer Wise Sale', count: stats.value.customer_wise_sales.length },
    { key: 'products', label: 'Product Wise Sale', count: stats.value.product_wise_sales.length },
]);

function switchTab(tab) {
    activeTab.value = tab;
    router.replace({ query: { tab } });
}

// ── Sales Stats ───────────────────────────────────────────────────────────────
const statsLoading = ref(false);
const statsError = ref('');

const filters = ref({
    range_type: 'date',
    range_value: defaultRangeValue('date'),
});

const stats = ref(defaultStats());

const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const dateTimeFormatter = new Intl.DateTimeFormat(undefined, { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const rangeValueLabel = computed(() => {
    const labels = { week: 'Week', month: 'Month', year: 'Year' };
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
    };
}

function formatNumber(value) { return numberFormatter.format(Number(value || 0)); }
function formatMoney(value) { return moneyFormatter.format(Number(value || 0)); }

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : dateTimeFormatter.format(date);
}

function paymentMethodLabel(value) {
    return { cash: 'Cash', card: 'Card', bank: 'Bank Transfer', member_wallet: 'Member Wallet' }[value] || 'Other';
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
    filters.value.range_value = defaultRangeValue(filters.value.range_type);
}

async function loadStats() {
    statsLoading.value = true;
    statsError.value = '';
    try {
        const response = await apiRequest('/api/dashboard/stats', {
            params: { range_type: filters.value.range_type, range_value: filters.value.range_value },
        });
        stats.value = { ...defaultStats(), ...(response || {}) };
        filters.value.range_type = stats.value.range_type || filters.value.range_type;
        filters.value.range_value = stats.value.range_value || filters.value.range_value;
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
