<template>
    <section>
        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 lg:gap-6">
            <article class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 shadow-sm">
                <h3 class="text-sm font-medium text-secondary-500 dark:text-secondary-400">Stock Summary - Product Availability</h3>

                <template v-if="stockSummary.can_view">
                    <div class="mt-4 border border-secondary-200 dark:border-secondary-700 rounded-lg overflow-hidden">
                        <div v-if="loading" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">
                            Loading variation availability...
                        </div>

                        <div v-else-if="stockSummary.variation_availability.length === 0" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">
                            No variation stock found.
                        </div>

                        <ul v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <li
                                v-for="item in stockSummary.variation_availability"
                                :key="item.variation_id"
                                class="px-3 py-2.5 flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm truncate" :class="item.is_low_stock ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-secondary-900 dark:text-white'">
                                        {{ item.label }}
                                    </p>
                                </div>
                                <p class="text-sm font-semibold" :class="item.is_low_stock ? 'text-red-600 dark:text-red-400' : 'text-secondary-900 dark:text-white'">
                                    {{ formatNumber(item.available_quantity) }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </template>

                <p v-else class="mt-3 text-sm text-secondary-500 dark:text-secondary-400">You do not have permission to view stock summary.</p>
            </article>

            <article class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 shadow-sm">
                <h3 class="text-sm font-medium text-secondary-500 dark:text-secondary-400">Daily Sales Summary</h3>

                <template v-if="dailySalesSummary.can_view">
                    <p class="mt-2 text-2xl font-bold text-secondary-900 dark:text-white">{{ loading ? '-' : formatMoney(dailySalesSummary.gross_amount) }}</p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">Gross Sales · {{ dailySalesSummary.date || 'Today' }}</p>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-secondary-50 dark:bg-secondary-800/50 p-3">
                            <p class="text-xs text-secondary-500 dark:text-secondary-400">Transactions</p>
                            <p class="mt-1 text-sm font-semibold text-secondary-900 dark:text-white">{{ loading ? '-' : formatNumber(dailySalesSummary.transactions) }}</p>
                        </div>
                        <div class="rounded-lg bg-secondary-50 dark:bg-secondary-800/50 p-3">
                            <p class="text-xs text-secondary-500 dark:text-secondary-400">Paid Amount</p>
                            <p class="mt-1 text-sm font-semibold text-secondary-900 dark:text-white">{{ loading ? '-' : formatMoney(dailySalesSummary.paid_amount) }}</p>
                        </div>
                    </div>
                </template>

                <p v-else class="mt-3 text-sm text-secondary-500 dark:text-secondary-400">You do not have permission to view sales summary.</p>
            </article>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';

const context = useAppContext();
const loading = ref(false);
const errorMessage = ref('');

const stockSummary = ref({
    can_view: true,
    available_units: 0,
    tracked_variations: 0,
    low_stock_variations: 0,
    low_stock_threshold: 5,
    variation_availability: [],
});

const dailySalesSummary = ref({
    can_view: true,
    date: '',
    transactions: 0,
    gross_amount: 0,
    paid_amount: 0,
});

const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function formatNumber(value) {
    return numberFormatter.format(Number(value || 0));
}

function formatMoney(value) {
    return moneyFormatter.format(Number(value || 0));
}

async function loadDashboardSummary() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/dashboard/overview');
        stockSummary.value = {
            ...stockSummary.value,
            ...(response.stock_summary || {}),
        };
        dailySalesSummary.value = {
            ...dailySalesSummary.value,
            ...(response.daily_sales_summary || {}),
        };
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load dashboard summary.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadDashboardSummary();
});
</script>
