<template>
    <section class="app-page-frame">
        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <article v-if="hasAnySummaryPermission" class="app-surface rounded-2xl p-5 md:p-6 min-h-[20rem] max-h-[30rem] flex flex-col">
                    <h3 class="shrink-0 text-sm font-medium text-secondary-500 dark:text-secondary-400">Dashboard Summary</h3>

                    <div class="mt-4 app-widget-content-scroll space-y-4">
                        <section v-if="dailySalesSummary.can_view" class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4">
                            <h4 class="text-xs font-semibold uppercase tracking-[0.08em] text-secondary-500 dark:text-secondary-400">Daily Sales Summary</h4>
                            <p class="mt-2 text-3xl font-bold text-secondary-900 dark:text-white">{{ loading ? '-' : formatMoney(dailySalesSummary.gross_amount) }}</p>
                            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">Gross Sales · {{ dailySalesSummary.date || 'Today' }}</p>

                            <RouterLink
                                to="/stats"
                                class="mt-4 inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-semibold bg-gradient-to-r from-primary-500 to-primary-700 text-white transition-all hover:brightness-110"
                            >
                                Open Sales Stats
                            </RouterLink>
                        </section>

                        <section v-if="stockSummary.can_view" class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-4">
                            <h4 class="text-xs font-semibold uppercase tracking-[0.08em] text-secondary-500 dark:text-secondary-400">Stock Summary - Product Availability</h4>

                            <div class="mt-3 rounded-xl border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                                <div v-if="loading" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">
                                    Loading variation availability...
                                </div>

                                <div v-else-if="stockSummary.variation_availability.length === 0" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">
                                    No variation stock found.
                                </div>

                                <ul v-else class="max-h-[12rem] overflow-auto divide-y divide-secondary-200 dark:divide-secondary-700">
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
                        </section>
                    </div>
                </article>

                <div v-else class="app-surface rounded-2xl p-5 md:p-6 text-sm text-secondary-500 dark:text-secondary-400">
                    You do not have permission to view dashboard summary widgets.
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiRequest } from '../composables/useApiClient';

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
    gross_amount: 0,
});

const hasAnySummaryPermission = computed(
    () => Boolean(dailySalesSummary.value.can_view || stockSummary.value.can_view)
);

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
