<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <AppHeaderAction
                    v-if="stockSummary.can_view"
                    :icon="Download"
                    :label="exportingStockImage ? 'Exporting...' : 'Export Stock Image'"
                    variant="secondary"
                    :disabled="loading || exportingStockImage || stockSummary.variation_availability.length === 0"
                    @click="exportCurrentStockImage"
                />
            </template>
        </AppPageHeader>

        <div v-if="errorMessage"
            class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll flex flex-col min-h-0">
                <div class="mt-4 app-widget-content-scroll flex min-h-0 flex-col gap-4">
                    <RouterLink v-if="dailySalesSummary.can_view" to="/stats"
                        class="group block shrink-0 rounded-xl border border-secondary-200 p-4 transition-all hover:border-primary-300 hover:bg-primary-50/40 dark:border-secondary-700 dark:hover:border-primary-600 dark:hover:bg-primary-900/20">
                        <div class="flex flex-col items-center justify-center text-center">
                            <h4
                                class="text-xs font-semibold uppercase tracking-[0.08em] text-secondary-500 dark:text-secondary-400">
                                Daily Sales Summary</h4>
                            <p class="mt-2 text-3xl font-bold text-secondary-900 dark:text-white">{{ loading ? '-' :
                                formatMoney(dailySalesSummary.gross_amount) }}</p>
                            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">Gross Sales · {{
                                dailySalesSummary.date || 'Today' }}</p>
                        </div>
                    </RouterLink>
                    <h4
                        class="text-xs font-semibold uppercase tracking-[0.08em] text-secondary-500 dark:text-secondary-400">
                        Stock Summary - Product Availability</h4>

                    <div
                        class="flex min-h-0 flex-col rounded-xl border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                        <div v-if="loading" class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">
                            Loading variation availability...
                        </div>

                        <div v-else-if="stockSummary.variation_availability.length === 0"
                            class="px-3 py-3 text-sm text-secondary-500 dark:text-secondary-400">
                            No variation stock found.
                        </div>

                        <ul v-else
                            class="m-0 h-[calc(100dvh-380px)] p-0 overflow-auto divide-y divide-secondary-200 dark:divide-secondary-700">
                            <li v-for="item in stockSummary.variation_availability" :key="item.variation_id"
                                class="px-3 py-2.5 last:pb-0 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm truncate"
                                        :class="item.is_low_stock ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-secondary-900 dark:text-white'">
                                        {{ item.label }}
                                    </p>
                                </div>
                                <p class="text-sm font-semibold"
                                    :class="item.is_low_stock ? 'text-red-600 dark:text-red-400' : 'text-secondary-900 dark:text-white'">
                                    {{ formatNumber(item.available_quantity) }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Download } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';

const loading = ref(false);
const errorMessage = ref('');
const exportingStockImage = ref(false);

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

function downloadBlob(blob, filename) {
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(downloadUrl);
}

async function exportCurrentStockImage() {
    const variations = stockSummary.value.variation_availability || [];
    if (variations.length === 0) {
        errorMessage.value = 'No stock data available to export.';
        return;
    }

    exportingStockImage.value = true;
    errorMessage.value = '';

    try {
        const canvas = document.createElement('canvas');
        const width = 1280;
        const paddingX = 56;
        const titleSectionHeight = 120; // title (68) + subtitle (100) padded top area
        const summaryHeight = 56;
        const summaryGap = 20;
        const headerHeight = 44;
        const rowHeight = 44;
        const footerHeight = 56;
        const rowsHeight = variations.length * rowHeight;
        const height = titleSectionHeight + summaryHeight + summaryGap + headerHeight + rowsHeight + footerHeight;

        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d');
        if (!context) {
            throw new Error('Unable to initialize image export renderer.');
        }

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, width, height);

        context.fillStyle = '#0f172a';
        context.font = '700 36px sans-serif';
        context.fillText('Current Stock Summary', paddingX, 68);

        context.fillStyle = '#64748b';
        context.font = '500 20px sans-serif';
        context.fillText(`Generated: ${new Date().toLocaleString()}`, paddingX, 100);

        const summaryY = titleSectionHeight;
        context.fillStyle = '#e2e8f0';
        context.fillRect(paddingX, summaryY, width - paddingX * 2, summaryHeight);
        context.fillStyle = '#0f172a';
        context.font = '600 20px sans-serif';
        const summaryText = `Tracked: ${formatNumber(stockSummary.value.tracked_variations)}   Available Units: ${formatNumber(stockSummary.value.available_units)}   Low Stock: ${formatNumber(stockSummary.value.low_stock_variations)} (threshold: ${formatNumber(stockSummary.value.low_stock_threshold)})`;
        context.fillText(summaryText, paddingX + 16, summaryY + 34);

        const tableTop = summaryY + summaryHeight + summaryGap;
        context.fillStyle = '#0f172a';
        context.fillRect(paddingX, tableTop, width - paddingX * 2, headerHeight);

        context.fillStyle = '#ffffff';
        context.font = '700 18px sans-serif';
        context.fillText('Variation', paddingX + 16, tableTop + 28);
        context.fillText('Available Qty', width - paddingX - 340, tableTop + 28);
        context.fillText('Status', width - paddingX - 150, tableTop + 28);

        const qtyX = width - paddingX - 340;
        const statusX = width - paddingX - 150;

        variations.forEach((item, index) => {
            const y = tableTop + headerHeight + (index * rowHeight);
            context.fillStyle = index % 2 === 0 ? '#ffffff' : '#f8fafc';
            context.fillRect(paddingX, y, width - paddingX * 2, rowHeight);

            context.fillStyle = '#cbd5e1';
            context.fillRect(paddingX, y + rowHeight - 1, width - paddingX * 2, 1);

            context.fillStyle = item.is_low_stock ? '#dc2626' : '#0f172a';
            context.font = item.is_low_stock ? '700 18px sans-serif' : '500 18px sans-serif';
            context.fillText(String(item.label || ''), paddingX + 16, y + 28, qtyX - paddingX - 30);

            context.textAlign = 'left';
            context.fillStyle = item.is_low_stock ? '#dc2626' : '#0f172a';
            context.font = '600 18px sans-serif';
            context.fillText(formatNumber(item.available_quantity), qtyX, y + 28);

            context.fillStyle = item.is_low_stock ? '#dc2626' : '#16a34a';
            context.font = '700 16px sans-serif';
            context.fillText(item.is_low_stock ? 'LOW' : 'OK', statusX, y + 28);
        });

        const footerY = tableTop + headerHeight + rowsHeight;
        context.fillStyle = '#f1f5f9';
        context.fillRect(paddingX, footerY, width - paddingX * 2, footerHeight);
        context.fillStyle = '#64748b';
        context.font = '500 16px sans-serif';
        context.fillText('Exported from Dashboard', paddingX + 16, footerY + footerHeight / 2 + 6);

        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob((generatedBlob) => {
                if (!generatedBlob) {
                    reject(new Error('Image export failed.'));
                    return;
                }
                resolve(generatedBlob);
            }, 'image/png');
        });

        const fileName = `current-stock-${new Date().toISOString().slice(0, 10)}.png`;
        downloadBlob(blob, fileName);
    } catch (error) {
        errorMessage.value = error?.message || 'Failed to export stock image.';
    } finally {
        exportingStockImage.value = false;
    }
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
