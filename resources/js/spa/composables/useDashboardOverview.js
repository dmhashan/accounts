import { onMounted, ref } from 'vue';
import { apiRequest } from './useApiClient';

function getTodayInputDate() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function formatRangeLabel(startDate, endDate, fallback = 'Custom Range') {
  if (!startDate || !endDate) return fallback;
  if (startDate === endDate) return startDate;
  return `${startDate} - ${endDate}`;
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

function createDefaultIncomeExpenseSummary() {
  return {
    can_view: true,
    start_date: '',
    end_date: '',
    range_label: '',
    income: 0,
    expense: 0,
    net_movement: 0,
    income_count: 0,
    expense_count: 0,
    transactions: [],
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
  const loading = ref(false);
  const errorMessage = ref('');

  const stockSummary = ref(createDefaultStockSummary());
  const incomeExpenseSummary = ref(createDefaultIncomeExpenseSummary());
  const todayAuthSummary = ref(createDefaultTodayAuthSummary());
  const selectedStartDate = ref(getTodayInputDate());
  const selectedEndDate = ref(getTodayInputDate());
  const selectedRangePreset = ref('today');
  const selectedRangeLabel = ref('Today');
  const selectedAccountIds = ref([]);

  async function loadDashboardSummary() {
    loading.value = true;
    errorMessage.value = '';

    try {
      const params = new URLSearchParams();
      params.set('start_date', selectedStartDate.value);
      params.set('end_date', selectedEndDate.value);
      if (selectedAccountIds.value && selectedAccountIds.value.length > 0) {
        params.set('account_ids', selectedAccountIds.value.join(','));
      }
      const response = await apiRequest(`/api/dashboard/overview?${params.toString()}`);
      stockSummary.value = {
        ...stockSummary.value,
        ...(response.stock_summary || {}),
      };
      incomeExpenseSummary.value = {
        ...incomeExpenseSummary.value,
        ...(response.income_expense_summary || {}),
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

    } catch (error) {
      errorMessage.value = error?.response?.data?.message || 'Failed to load dashboard summary.';
    } finally {
      loading.value = false;
    }
  }

  async function changeDateRange(range) {
    if (!range?.startDate || !range?.endDate) return;

    selectedStartDate.value = range.startDate;
    selectedEndDate.value = range.endDate;
    selectedRangePreset.value = range.id || 'custom';
    selectedRangeLabel.value = range.id === 'custom'
      ? formatRangeLabel(range.startDate, range.endDate)
      : range.label || formatRangeLabel(range.startDate, range.endDate);
    await loadDashboardSummary();
  }

  async function changeAccountFilters(ids) {
    selectedAccountIds.value = ids || [];
    await loadDashboardSummary();
  }

  onMounted(() => {
    loadDashboardSummary();
  });

  return {
    loading,
    errorMessage,
    stockSummary,
    incomeExpenseSummary,
    todayAuthSummary,
    selectedStartDate,
    selectedEndDate,
    selectedRangePreset,
    selectedRangeLabel,
    selectedAccountIds,
    loadDashboardSummary,
    changeDateRange,
    changeAccountFilters,
  };
}
