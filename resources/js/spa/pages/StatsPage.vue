<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #extra-slot>
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
                        :disabled="loading"
                        class="self-end px-4 py-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold transition-all hover:brightness-110"
                    >
                        {{ loading ? 'Loading...' : 'Apply' }}
                    </button>
                </form>
            </template>
        </AppPageHeader>

        <div class="app-page-scroll">
        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="!stats.can_view" class="app-surface rounded-2xl p-5 md:p-6 text-sm text-secondary-500 dark:text-secondary-400">
            You do not have permission to view sales stats.
        </div>

        <template v-else>
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4">
                <article class="app-surface rounded-2xl p-4">
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">Transactions</p>
                    <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ loading ? '-' : formatNumber(stats.transactions) }}</p>
                </article>
                <article class="app-surface rounded-2xl p-4">
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">Gross Amount</p>
                    <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ loading ? '-' : formatMoney(stats.gross_amount) }}</p>
                </article>
                <article class="app-surface rounded-2xl p-4">
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">Paid Amount</p>
                    <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ loading ? '-' : formatMoney(stats.paid_amount) }}</p>
                </article>
                <article class="app-surface rounded-2xl p-4">
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">Outstanding</p>
                    <p class="mt-1 text-lg md:text-xl font-semibold text-secondary-900 dark:text-white">{{ loading ? '-' : formatMoney(stats.outstanding_amount) }}</p>
                </article>
            </div>

            <article class="mt-4 app-surface rounded-2xl p-4 md:p-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">Sales Details</h3>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Switch between transaction, customer, and product views for the selected period.</p>
                    </div>

                    <div class="overflow-x-auto -mx-1 px-1">
                        <div role="tablist" aria-label="Sales detail sections" class="inline-flex min-w-full md:min-w-0 rounded-xl app-surface-soft p-1 gap-1">
                            <button
                                v-for="tab in detailTabs"
                                :key="tab.key"
                                type="button"
                                role="tab"
                                class="px-3 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors"
                                :class="activeDetailTab === tab.key
                                    ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm'
                                    : 'text-secondary-700 dark:text-secondary-300 hover:bg-white dark:hover:bg-secondary-700'"
                                :aria-selected="activeDetailTab === tab.key"
                                @click="activeDetailTab = tab.key"
                            >
                                {{ tab.label }}
                                <span
                                    class="ml-2 inline-flex min-w-[1.75rem] items-center justify-center rounded-full px-2 py-0.5 text-[11px]"
                                    :class="activeDetailTab === tab.key
                                        ? 'bg-white/20 text-white'
                                        : 'bg-white dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300'"
                                >
                                    {{ loading ? '-' : formatNumber(tab.count) }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="activeDetailTab === 'transactions'" role="tabpanel" aria-label="Transaction List" class="mt-4 border border-secondary-200 dark:border-secondary-700 rounded-lg overflow-hidden">
                    <div v-if="loading" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">Loading transactions...</div>

                    <div v-else-if="stats.transaction_list.length === 0" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">No transactions found for the selected period.</div>

                    <template v-else>
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article
                                v-for="transaction in stats.transaction_list"
                                :key="transaction.sale_id"
                                class="px-3 py-3 space-y-2"
                            >
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
                                    <tr
                                        v-for="transaction in stats.transaction_list"
                                        :key="transaction.sale_id"
                                        class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50"
                                    >
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
                </div>

                <div v-else-if="activeDetailTab === 'customers'" role="tabpanel" aria-label="Customer Wise Sale" class="mt-4 border border-secondary-200 dark:border-secondary-700 rounded-lg p-4 md:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">Customer Wise Sale</h3>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ loading ? '-' : `${formatNumber(stats.customer_wise_sales.length)} customers` }}</p>
                    </div>

                    <div v-if="loading" class="mt-3 text-sm text-secondary-500 dark:text-secondary-400">Loading customer summary...</div>

                    <div v-else-if="stats.customer_wise_sales.length === 0" class="mt-3 text-sm text-secondary-500 dark:text-secondary-400">No customer sales found for the selected period.</div>

                    <ul v-else class="mt-3 divide-y divide-secondary-200 dark:divide-secondary-700">
                        <li
                            v-for="item in stats.customer_wise_sales"
                            :key="item.customer_name"
                            class="py-2.5 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p class="text-sm text-secondary-900 dark:text-white truncate">{{ item.customer_name }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ formatNumber(item.transactions) }} {{ transactionCountLabel(item.transactions) }}</p>
                            </div>
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">{{ formatMoney(item.total_amount) }}</p>
                        </li>
                    </ul>
                </div>

                <div v-else role="tabpanel" aria-label="Product Wise Sale" class="mt-4 border border-secondary-200 dark:border-secondary-700 rounded-lg p-4 md:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">Product Wise Sale</h3>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ loading ? '-' : `${formatNumber(stats.product_wise_sales.length)} products` }}</p>
                    </div>

                    <div v-if="loading" class="mt-3 text-sm text-secondary-500 dark:text-secondary-400">Loading product summary...</div>

                    <div v-else-if="stats.product_wise_sales.length === 0" class="mt-3 text-sm text-secondary-500 dark:text-secondary-400">No product sales found for the selected period.</div>

                    <ul v-else class="mt-3 divide-y divide-secondary-200 dark:divide-secondary-700">
                        <li
                            v-for="item in stats.product_wise_sales"
                            :key="item.product_id"
                            class="py-2.5 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p class="text-sm text-secondary-900 dark:text-white truncate">{{ item.product_name }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ formatNumber(item.quantity_sold) }} units · {{ formatNumber(item.transactions) }} {{ transactionCountLabel(item.transactions) }}</p>
                            </div>
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white whitespace-nowrap">{{ formatMoney(item.total_amount) }}</p>
                        </li>
                    </ul>
                </div>
            </article>
        </template>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';

const loading = ref(false);
const errorMessage = ref('');

const filters = ref({
    range_type: 'date',
    range_value: defaultRangeValue('date'),
});

const stats = ref(defaultStats());
const activeDetailTab = ref('transactions');

const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});
const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

const rangeValueLabel = computed(() => {
    if (filters.value.range_type === 'week') {
        return 'Week';
    }

    if (filters.value.range_type === 'month') {
        return 'Month';
    }

    if (filters.value.range_type === 'year') {
        return 'Year';
    }

    return 'Date';
});

const detailTabs = computed(() => {
    return [
        {
            key: 'transactions',
            label: 'Transaction List',
            count: stats.value.transaction_list.length,
        },
        {
            key: 'customers',
            label: 'Customer Wise Sale',
            count: stats.value.customer_wise_sales.length,
        },
        {
            key: 'products',
            label: 'Product Wise Sale',
            count: stats.value.product_wise_sales.length,
        },
    ];
});

function defaultStats() {
    return {
        can_view: true,
        range_type: 'date',
        range_value: '',
        range_label: '',
        start_date: '',
        end_date: '',
        transactions: 0,
        gross_amount: 0,
        paid_amount: 0,
        outstanding_amount: 0,
        transaction_list: [],
        customer_wise_sales: [],
        product_wise_sales: [],
    };
}

function formatNumber(value) {
    return numberFormatter.format(Number(value || 0));
}

function formatMoney(value) {
    return moneyFormatter.format(Number(value || 0));
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return dateTimeFormatter.format(date);
}

function paymentMethodLabel(value) {
    const labels = {
        cash: 'Cash',
        card: 'Card',
        bank: 'Bank Transfer',
        member_wallet: 'Member Wallet',
    };

    return labels[value] || 'Other';
}

function customerTypeLabel(value) {
    if (!value) {
        return 'N/A';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

function transactionCountLabel(count) {
    return Number(count || 0) === 1 ? 'transaction' : 'transactions';
}

function pad(value) {
    return String(value).padStart(2, '0');
}

function toDateInputValue(date = new Date()) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function toMonthInputValue(date = new Date()) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}`;
}

function toYearInputValue(date = new Date()) {
    return String(date.getFullYear());
}

function toIsoWeekInputValue(date = new Date()) {
    const current = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const day = current.getUTCDay() || 7;
    current.setUTCDate(current.getUTCDate() + 4 - day);

    const yearStart = new Date(Date.UTC(current.getUTCFullYear(), 0, 1));
    const weekNumber = Math.ceil((((current - yearStart) / 86400000) + 1) / 7);

    return `${current.getUTCFullYear()}-W${pad(weekNumber)}`;
}

function defaultRangeValue(rangeType) {
    if (rangeType === 'week') {
        return toIsoWeekInputValue();
    }

    if (rangeType === 'month') {
        return toMonthInputValue();
    }

    if (rangeType === 'year') {
        return toYearInputValue();
    }

    return toDateInputValue();
}

function handleRangeTypeChange() {
    filters.value.range_value = defaultRangeValue(filters.value.range_type);
}

async function loadStats() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/dashboard/stats', {
            params: {
                range_type: filters.value.range_type,
                range_value: filters.value.range_value,
            },
        });

        stats.value = {
            ...defaultStats(),
            ...(response || {}),
        };

        filters.value.range_type = stats.value.range_type || filters.value.range_type;
        filters.value.range_value = stats.value.range_value || filters.value.range_value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load sales stats.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadStats();
});
</script>
