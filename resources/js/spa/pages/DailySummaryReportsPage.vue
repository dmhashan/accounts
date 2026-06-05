<template>
  <section class="app-page-frame">
    <AppPageHeader title="Daily Summary Reports">
      <template #extra-slot>
        <RouterLink
          to="/reports/daily-summary"
          class="px-4 py-2 rounded-xl app-surface-soft text-sm font-semibold text-secondary-700 dark:text-secondary-200 transition-colors hover:brightness-105"
        >
          Back to Summary
        </RouterLink>
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div v-if="error" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ error }}
      </div>

      <article class="app-surface rounded-2xl overflow-hidden">
        <header class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700">
          <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
            Generated Reports
          </h3>
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Signed daily summary reports. Adjusted figures are highlighted in the PDF.
          </p>
        </header>

        <div v-if="loading" class="px-4 py-6 text-sm text-secondary-500 dark:text-secondary-400">
          Loading reports...
        </div>
        <div v-else-if="reports.length === 0" class="px-4 py-6 text-sm text-secondary-500 dark:text-secondary-400">
          No reports have been generated yet.
        </div>
        <template v-else>
          <!-- Mobile -->
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="report in reports" :key="report.id" class="px-4 py-3 space-y-2">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                  {{ report.date_label }}
                </p>
                <span
                  v-if="report.change_count > 0"
                  class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-[11px] font-semibold text-red-700 dark:text-red-300"
                >
                  {{ report.change_count }} adjusted
                </span>
              </div>
              <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-secondary-600 dark:text-secondary-300">
                <p>Prepared by: <span class="font-medium text-secondary-900 dark:text-white">{{ report.prepared_by_name }}</span></p>
                <p>Generated: <span class="font-medium text-secondary-900 dark:text-white">{{ formatDateTime(report.created_at) }}</span></p>
                <p>Closing: <span class="font-medium text-secondary-900 dark:text-white">{{ formatMoney(report.totals?.closing_balance) }}</span></p>
              </div>
              <div class="pt-1">
                <a
                  v-if="report.has_pdf"
                  :href="`/api/reports/daily-summary/reports/${report.id}/pdf`"
                  target="_blank"
                  rel="noopener"
                  class="inline-block px-3 py-1.5 rounded-lg bg-primary-600 text-white text-xs font-semibold hover:brightness-110"
                >
                  View PDF
                </a>
              </div>
            </article>
          </div>
          <!-- Desktop -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[720px]">
              <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Report Date
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Prepared By
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Closing
                  </th>
                  <th class="px-3 py-2 text-center text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Adjustments
                  </th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Generated
                  </th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    PDF
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr v-for="report in reports" :key="report.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                  <td class="px-3 py-2 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ report.date_label }}
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ report.prepared_by_name }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right text-secondary-900 dark:text-white">
                    {{ formatMoney(report.totals?.closing_balance) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-center">
                    <span
                      v-if="report.change_count > 0"
                      class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-xs font-semibold text-red-700 dark:text-red-300"
                    >
                      {{ report.change_count }}
                    </span>
                    <span v-else class="text-xs text-secondary-400">—</span>
                  </td>
                  <td class="px-3 py-2 text-sm text-secondary-600 dark:text-secondary-300">
                    {{ formatDateTime(report.created_at) }}
                  </td>
                  <td class="px-3 py-2 text-sm text-right">
                    <a
                      v-if="report.has_pdf"
                      :href="`/api/reports/daily-summary/reports/${report.id}/pdf`"
                      target="_blank"
                      rel="noopener"
                      class="inline-block px-3 py-1.5 rounded-lg bg-primary-600 text-white text-xs font-semibold hover:brightness-110"
                    >
                      View
                    </a>
                    <span v-else class="text-xs text-secondary-400">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <footer v-if="meta.last_page > 1" class="flex items-center justify-between gap-3 px-4 py-3 border-t border-secondary-200 dark:border-secondary-700">
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Page {{ meta.current_page }} of {{ meta.last_page }}
          </p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              :disabled="meta.current_page <= 1 || loading"
              class="px-3 py-1.5 rounded-lg app-surface-soft text-xs font-semibold text-secondary-700 dark:text-secondary-200 disabled:opacity-50 hover:brightness-105"
              @click="loadReports(meta.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              :disabled="meta.current_page >= meta.last_page || loading"
              class="px-3 py-1.5 rounded-lg app-surface-soft text-xs font-semibold text-secondary-700 dark:text-secondary-200 disabled:opacity-50 hover:brightness-105"
              @click="loadReports(meta.current_page + 1)"
            >
              Next
            </button>
          </div>
        </footer>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';

const { formatDateTime } = useDateTimeFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function formatMoney(value) { return moneyFormatter.format(Number(value || 0)); }

const loading = ref(false);
const error = ref('');
const reports = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });

async function loadReports(page = 1) {
    loading.value = true;
    error.value = '';
    try {
        const response = await apiRequest('/api/reports/daily-summary/history', {
            params: { page, per_page: 20 },
        });
        reports.value = response?.data || [];
        meta.value = {
            current_page: response?.meta?.current_page || 1,
            last_page: response?.meta?.last_page || 1,
            total: response?.meta?.total || 0,
        };
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load reports.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadReports(1);
});
</script>
