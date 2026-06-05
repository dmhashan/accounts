import { onMounted, onUnmounted, ref } from 'vue';
import { apiRequest } from './useApiClient';
import { useDateTimeFormat } from './useDateTimeFormat';

const AUTO_REFRESH_MS = 60000;

function getTodayInputDate() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function createDefaultStockSummary() {
  return {
    can_view: true,
    available_units: 0,
    tracked_variations: 0,
    low_stock_variations: 0,
    low_stock_threshold: 5,
    variation_availability: [],
  };
}

function createDefaultDailySalesSummary() {
  return {
    can_view: true,
    date: '',
    gross_amount: 0,
  };
}

function createDefaultTodayAuthSummary() {
  return {
    can_view: true,
    date: '',
    counts: {
      total: 0,
      success: 0,
      payment_expired: 0,
      other_failed: 0,
    },
    lists: {
      success_attempts: [],
      payment_expired: [],
      other_failed: [],
    },
  };
}

export function useDashboardOverview() {
  const { formatDateTime } = useDateTimeFormat();

  const loading = ref(false);
  const errorMessage = ref('');
  const exportingStockImage = ref(false);

  const stockSummary = ref(createDefaultStockSummary());
  const dailySalesSummary = ref(createDefaultDailySalesSummary());
  const todayAuthSummary = ref(createDefaultTodayAuthSummary());
  const selectedAuthDate = ref(getTodayInputDate());
  const selectedStockDate = ref(getTodayInputDate());

  const numberFormatter = new Intl.NumberFormat();
  let autoRefreshTimer = null;

  function formatNumber(value) {
    return numberFormatter.format(Number(value || 0));
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
      const titleSectionHeight = 120;
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
      context.fillText(`Generated: ${formatDateTime(new Date())}`, paddingX, 100);

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
      const params = new URLSearchParams();
      params.set('auth_date', selectedAuthDate.value);
      params.set('stock_date', selectedStockDate.value);
      const response = await apiRequest(`/api/dashboard/overview?${params.toString()}`);
      stockSummary.value = {
        ...stockSummary.value,
        ...(response.stock_summary || {}),
      };
      dailySalesSummary.value = {
        ...dailySalesSummary.value,
        ...(response.daily_sales_summary || {}),
      };
      todayAuthSummary.value = {
        ...todayAuthSummary.value,
        ...(response.today_auth_summary || {}),
        counts: {
          ...todayAuthSummary.value.counts,
          ...(response.today_auth_summary?.counts || {}),
        },
        lists: {
          ...todayAuthSummary.value.lists,
          ...(response.today_auth_summary?.lists || {}),
        },
      };

      if (response.stock_summary?.selected_date) {
        selectedStockDate.value = response.stock_summary.selected_date;
      }

      if (response.today_auth_summary?.selected_date) {
        selectedAuthDate.value = response.today_auth_summary.selected_date;
      }
    } catch (error) {
      errorMessage.value = error?.response?.data?.message || 'Failed to load dashboard summary.';
    } finally {
      loading.value = false;
    }
  }

  async function changeAuthDate(date) {
    if (!date || date === selectedAuthDate.value) {
      return;
    }

    selectedAuthDate.value = date;
    await loadDashboardSummary();
  }

  async function changeStockDate(date) {
    if (!date || date === selectedStockDate.value) {
      return;
    }

    selectedStockDate.value = date;
    await loadDashboardSummary();
  }

  onMounted(() => {
    loadDashboardSummary();
    autoRefreshTimer = window.setInterval(() => {
      if (!loading.value) {
        loadDashboardSummary();
      }
    }, AUTO_REFRESH_MS);
  });

  onUnmounted(() => {
    if (autoRefreshTimer) {
      window.clearInterval(autoRefreshTimer);
      autoRefreshTimer = null;
    }
  });

  return {
    loading,
    errorMessage,
    exportingStockImage,
    stockSummary,
    dailySalesSummary,
    todayAuthSummary,
    selectedAuthDate,
    selectedStockDate,
    exportCurrentStockImage,
    loadDashboardSummary,
    changeAuthDate,
    changeStockDate,
  };
}
