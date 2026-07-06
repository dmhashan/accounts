<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <div class="hidden items-center gap-1.5 sm:flex">
          <DashboardDateRangeSelector
            :start-date="selectedStartDate"
            :end-date="selectedEndDate"
            :selected-preset="selectedRangePreset"
            :range-label="selectedRangeLabel"
            :disabled="loading"
            @change="changeDateRange"
          />
          <button
            type="button"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-secondary-300 bg-white px-3 text-sm font-semibold text-secondary-700 transition-colors hover:bg-secondary-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-800"
            :disabled="loading"
            title="Refresh dashboard"
            aria-label="Refresh dashboard"
            @click="loadDashboardSummary"
          >
            <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
            <span>Refresh</span>
          </button>
        </div>
      </template>

      <template #extra-slot>
        <div class="flex items-center gap-1.5 sm:hidden">
          <DashboardDateRangeSelector
            class="min-w-0 flex-1"
            :start-date="selectedStartDate"
            :end-date="selectedEndDate"
            :selected-preset="selectedRangePreset"
            :range-label="selectedRangeLabel"
            :disabled="loading"
            @change="changeDateRange"
          />
          <button
            type="button"
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-secondary-300 bg-white text-secondary-700 transition-colors hover:bg-secondary-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-800"
            :disabled="loading"
            title="Refresh dashboard"
            aria-label="Refresh dashboard"
            @click="loadDashboardSummary"
          >
            <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
          </button>
        </div>
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="app-alert app-alert-error">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll flex flex-col min-h-0">
        <div class="app-widget-content-scroll flex min-h-0 flex-col gap-3 sm:gap-4">
          <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-12">
            <div class="min-h-0 md:col-span-6">
              <DashboardIncomeExpenseCard
                :loading="loading"
                :summary="incomeExpenseSummary"
                :selected-account-ids="selectedAccountIds"
                @change-filter="changeAccountFilters"
              />
            </div>

            <div class="min-h-0 md:col-span-6">
              <DashboardTodayAuthCard
                :loading="loading"
                :summary="todayAuthSummary"
              />
            </div>

            <div class="min-h-0 md:col-span-12">
              <DashboardStockSummaryCard
                :loading="loading"
                :summary="stockSummary"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { RefreshCw } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import DashboardDateRangeSelector from '../components/dashboard/DashboardDateRangeSelector.vue';
import DashboardIncomeExpenseCard from '../components/dashboard/DashboardIncomeExpenseCard.vue';
import DashboardStockSummaryCard from '../components/dashboard/DashboardStockSummaryCard.vue';
import DashboardTodayAuthCard from '../components/dashboard/DashboardTodayAuthCard.vue';
import { useDashboardOverview } from '../composables/useDashboardOverview';

const {
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
} = useDashboardOverview();
</script>
