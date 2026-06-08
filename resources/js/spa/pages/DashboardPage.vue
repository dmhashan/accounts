<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <DashboardDateRangeSelector
          :start-date="selectedStartDate"
          :end-date="selectedEndDate"
          :selected-preset="selectedRangePreset"
          :range-label="selectedRangeLabel"
          :disabled="loading"
          @change="changeDateRange"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="app-alert app-alert-error">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll flex flex-col min-h-0">
        <div class="app-widget-content-scroll flex min-h-0 flex-col gap-4">
          <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <div class="xl:col-span-4 min-h-0">
              <DashboardIncomeExpenseCard
                :loading="loading"
                :summary="incomeExpenseSummary"
              />
            </div>

            <div class="xl:col-span-8 min-h-0">
              <DashboardTodayAuthCard
                :loading="loading"
                :summary="todayAuthSummary"
              />
            </div>

            <div class="xl:col-span-12 min-h-0">
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
  changeDateRange,
} = useDashboardOverview();
</script>
