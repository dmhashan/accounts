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

        <div v-if="errorMessage" class="app-alert app-alert-error">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll flex flex-col min-h-0">
                <div class="app-widget-content-scroll flex min-h-0 flex-col gap-4">

                    <!-- Daily Sales KPI Card -->
                    <RouterLink v-if="dailySalesSummary.can_view" to="/stats" class="group block shrink-0">
                        <div class="app-kpi-card">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="app-section-label">Daily Sales</p>
                                    <template v-if="loading">
                                        <div class="app-skeleton mt-2 h-8 w-36 rounded-lg"></div>
                                    </template>
                                    <p v-else class="mt-1.5 text-3xl font-bold tracking-tight" style="color: var(--text-strong)">
                                        {{ formatMoney(dailySalesSummary.gross_amount) }}
                                    </p>
                                    <p class="mt-1 text-xs" style="color: var(--text-muted)">
                                        Gross Sales · {{ dailySalesSummary.date || 'Today' }}
                                    </p>
                                </div>
                                <div class="h-11 w-11 rounded-xl flex items-center justify-center shrink-0 bg-gradient-to-br from-primary-500 to-primary-700 shadow-sm shadow-primary-500/30 group-hover:scale-105 transition-transform">
                                    <TrendingUp class="h-5 w-5 text-white" :stroke-width="2" />
                                </div>
                            </div>
                        </div>
                    </RouterLink>

                    <!-- Stock Summary Section -->
                    <div v-if="stockSummary.can_view" class="flex min-h-0 flex-col gap-3 shrink-0">
                        <p class="app-section-label">Stock — Product Availability</p>

                        <!-- Stock metrics strip -->
                        <div v-if="!loading" class="grid grid-cols-3 gap-3 shrink-0">
                            <div class="rounded-xl p-3 text-center" style="background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)">
                                <p class="text-[11px] font-medium" style="color: var(--text-muted)">Tracked</p>
                                <p class="mt-0.5 text-lg font-bold" style="color: var(--text-strong)">{{ formatNumber(stockSummary.tracked_variations) }}</p>
                            </div>
                            <div class="rounded-xl p-3 text-center" style="background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)">
                                <p class="text-[11px] font-medium" style="color: var(--text-muted)">Available Units</p>
                                <p class="mt-0.5 text-lg font-bold" style="color: var(--text-strong)">{{ formatNumber(stockSummary.available_units) }}</p>
                            </div>
                            <div class="rounded-xl p-3 text-center" :class="stockSummary.low_stock_variations > 0 ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : ''" :style="stockSummary.low_stock_variations === 0 ? 'background: var(--surface-muted); border: 1px solid color-mix(in srgb, var(--surface-border) 88%, transparent)' : ''">
                                <p class="text-[11px] font-medium" :class="stockSummary.low_stock_variations > 0 ? 'text-red-500 dark:text-red-400' : ''" :style="stockSummary.low_stock_variations === 0 ? 'color: var(--text-muted)' : ''">Low Stock</p>
                                <p class="mt-0.5 text-lg font-bold" :class="stockSummary.low_stock_variations > 0 ? 'text-red-600 dark:text-red-400' : ''" :style="stockSummary.low_stock_variations === 0 ? 'color: var(--text-strong)' : ''">{{ formatNumber(stockSummary.low_stock_variations) }}</p>
                            </div>
                        </div>
                        <!-- Loading strip skeleton -->
                        <div v-else class="grid grid-cols-3 gap-3 shrink-0">
                            <div v-for="i in 3" :key="i" class="app-skeleton h-16 rounded-xl"></div>
                        </div>

                        <!-- Stock list -->
                        <div class="flex min-h-0 flex-col rounded-2xl overflow-hidden" style="border: 1px solid var(--surface-border)">
                            <!-- Skeleton rows -->
                            <template v-if="loading">
                                <div class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                    <div v-for="i in 5" :key="i" class="flex items-center justify-between px-4 py-3 gap-3">
                                        <div class="app-skeleton h-3.5 w-40 rounded"></div>
                                        <div class="app-skeleton h-3.5 w-12 rounded"></div>
                                    </div>
                                </div>
                            </template>

                            <AppEmptyState
                                v-else-if="stockSummary.variation_availability.length === 0"
                                :icon="Package"
                                title="No stock data"
                                description="No product variation availability recorded yet."
                            />

                            <ul v-else class="m-0 p-0 h-[calc(100dvh-440px)] overflow-auto divide-y divide-secondary-200 dark:divide-secondary-700">
                                <li
                                    v-for="item in stockSummary.variation_availability"
                                    :key="item.variation_id"
                                    class="px-4 py-2.5 flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span
                                            class="h-2 w-2 rounded-full shrink-0"
                                            :class="item.is_low_stock ? 'bg-red-500' : 'bg-green-500'"
                                        ></span>
                                        <p class="text-sm truncate" :class="item.is_low_stock ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-secondary-900 dark:text-white'">
                                            {{ item.label }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <AppBadge v-if="item.is_low_stock" color="red">Low</AppBadge>
                                        <p class="text-sm font-semibold tabular-nums" :class="item.is_low_stock ? 'text-red-600 dark:text-red-400' : 'text-secondary-900 dark:text-white'">
                                            {{ formatNumber(item.available_quantity) }}
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Download, Package, TrendingUp } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppBadge from '../components/AppBadge.vue';
import AppEmptyState from '../components/AppEmptyState.vue';
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
