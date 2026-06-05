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
          <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <div class="xl:col-span-4 min-h-0">
              <DashboardDailySalesCard
                :loading="loading"
                :summary="dailySalesSummary"
              />
            </div>

            <div class="xl:col-span-8 min-h-0">
              <DashboardTodayAuthCard
                :loading="loading"
                :summary="todayAuthSummary"
                :selected-date="selectedAuthDate"
                @date-change="changeAuthDate"
              />
            </div>

            <div class="xl:col-span-12 min-h-0">
              <DashboardStockSummaryCard
                :loading="loading"
                :summary="stockSummary"
                :selected-date="selectedStockDate"
                @date-change="changeStockDate"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { Download } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import DashboardDailySalesCard from '../components/dashboard/DashboardDailySalesCard.vue';
import DashboardStockSummaryCard from '../components/dashboard/DashboardStockSummaryCard.vue';
import DashboardTodayAuthCard from '../components/dashboard/DashboardTodayAuthCard.vue';
import { useDashboardOverview } from '../composables/useDashboardOverview';

const {
  loading,
  errorMessage,
  exportingStockImage,
  stockSummary,
  dailySalesSummary,
  todayAuthSummary,
  selectedAuthDate,
  selectedStockDate,
  exportCurrentStockImage,
  changeAuthDate,
  changeStockDate,
} = useDashboardOverview();
</script>
