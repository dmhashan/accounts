<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors disabled:opacity-50"
          :disabled="exporting || loading"
          @click="exportCsv"
        >
          <Download class="w-4 h-4" />
          <span class="hidden sm:inline">{{ exporting ? 'Exporting...' : 'Export CSV' }}</span>
        </button>
      </template>

      <template #extra-slot>
        <AppSearchField
          v-model="filters.member_search"
          placeholder="Search by member name or ID"
          :disabled="loading"
          @search="load(1)"
        />
      </template>
    </AppPageHeader>

    <!-- Filter bar -->
    <div class="mb-3 flex flex-wrap gap-2 shrink-0">
      <select
        v-model="filters.event_type"
        class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
        @change="load(1)"
      >
        <option value="">
          All events
        </option>
        <option value="session_start">
          Session start
        </option>
        <option value="session_resume">
          Session resume
        </option>
        <option value="otp_requested">
          OTP requested
        </option>
        <option value="otp_verified">
          OTP verified
        </option>
        <option value="tab_view">
          Tab view
        </option>
        <option value="workout_opened">
          Workout opened
        </option>
        <option value="sale_opened">
          Sale opened
        </option>
        <option value="logout">
          Logout
        </option>
      </select>

      <select
        v-model="filters.device_type"
        class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
        @change="load(1)"
      >
        <option value="">
          All devices
        </option>
        <option value="mobile">
          Mobile
        </option>
        <option value="tablet">
          Tablet
        </option>
        <option value="desktop">
          Desktop
        </option>
      </select>

      <input
        v-model="filters.date_from"
        type="date"
        class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
        placeholder="From date"
        @change="load(1)"
      />
      <input
        v-model="filters.date_to"
        type="date"
        class="rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
        placeholder="To date"
        @change="load(1)"
      />

      <button
        v-if="hasActiveFilters"
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        @click="clearFilters"
      >
        Clear filters
      </button>
    </div>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Loading activity logs...
          </div>

          <template v-else-if="logs.length === 0">
            <div class="p-10 text-center text-secondary-500 dark:text-secondary-400 text-sm">
              No activity logs found for the selected filters.
            </div>
          </template>

          <template v-else>
            <!-- Mobile cards -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article v-for="log in logs" :key="log.id" class="p-4 space-y-2">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                      <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="eventBadgeClass(log.event_type)">
                        {{ formatEventType(log.event_type) }}
                      </span>
                      <span v-if="log.device_type" class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300">
                        {{ capitalize(log.device_type) }}
                      </span>
                    </div>
                    <p v-if="log.member_name" class="text-sm font-medium text-secondary-900 dark:text-white">
                      {{ log.member_name }}
                      <span class="text-xs text-secondary-400 dark:text-secondary-500 font-normal ml-1">{{ log.member_ref_id }}</span>
                    </p>
                    <div class="text-xs text-secondary-400 dark:text-secondary-500 flex flex-wrap gap-x-3 gap-y-0.5">
                      <span v-if="log.browser">{{ log.browser }}</span>
                      <span v-if="log.os">{{ log.os }}</span>
                      <span v-if="log.ip_address">{{ log.ip_address }}</span>
                      <span>{{ formatDate(log.created_at) }}</span>
                    </div>
                  </div>
                  <button
                    type="button"
                    class="shrink-0 text-xs text-primary-600 dark:text-primary-400 hover:underline"
                    @click="toggleDetail(log.id)"
                  >
                    {{ expandedId === log.id ? 'Hide' : 'Details' }}
                  </button>
                </div>
                <!-- Expanded detail -->
                <div v-if="expandedId === log.id" class="rounded-lg bg-secondary-50 dark:bg-secondary-800/40 p-3 text-xs space-y-1 text-secondary-600 dark:text-secondary-400 break-all">
                  <p v-if="log.screen_width">
                    <strong>Screen:</strong> {{ log.screen_width }}×{{ log.screen_height }}
                  </p>
                  <p><strong>Session:</strong> {{ log.session_id }}</p>
                  <p v-if="log.metadata">
                    <strong>Metadata:</strong> {{ JSON.stringify(log.metadata) }}
                  </p>
                </div>
              </article>
            </div>

            <!-- Desktop table -->
            <table class="hidden md:table w-full text-sm">
              <thead class="border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300 whitespace-nowrap">
                    Date / Time
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Member
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Event
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Device
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Browser / OS
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    IP Address
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300 hidden xl:table-cell">
                    Screen
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <template v-for="log in logs" :key="log.id">
                  <tr
                    class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors cursor-pointer"
                    @click="toggleDetail(log.id)"
                  >
                    <td class="px-4 py-3 text-secondary-500 dark:text-secondary-400 whitespace-nowrap text-xs">
                      {{ formatDate(log.created_at) }}
                    </td>
                    <td class="px-4 py-3">
                      <span v-if="log.member_name" class="font-medium text-secondary-900 dark:text-white">{{ log.member_name }}</span>
                      <span v-if="log.member_ref_id" class="ml-1 text-xs text-secondary-400 dark:text-secondary-500">{{ log.member_ref_id }}</span>
                      <span v-if="!log.member_name" class="text-secondary-400 dark:text-secondary-500 text-xs italic">—</span>
                    </td>
                    <td class="px-4 py-3">
                      <span class="px-2 py-0.5 text-xs font-semibold rounded-full whitespace-nowrap" :class="eventBadgeClass(log.event_type)">
                        {{ formatEventType(log.event_type) }}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <span v-if="log.device_type" class="px-2 py-0.5 text-xs rounded-full" :class="deviceBadgeClass(log.device_type)">
                        {{ capitalize(log.device_type) }}
                      </span>
                      <span v-else class="text-secondary-400 dark:text-secondary-500 text-xs">—</span>
                    </td>
                    <td class="px-4 py-3 text-secondary-600 dark:text-secondary-400 text-xs whitespace-nowrap">
                      <span v-if="log.browser">{{ log.browser }}</span>
                      <span v-if="log.browser && log.os" class="text-secondary-300 dark:text-secondary-600 mx-1">/</span>
                      <span v-if="log.os">{{ log.os }}</span>
                      <span v-if="!log.browser && !log.os">—</span>
                    </td>
                    <td class="px-4 py-3 text-secondary-500 dark:text-secondary-400 text-xs font-mono">
                      {{ log.ip_address || '—' }}
                    </td>
                    <td class="px-4 py-3 text-secondary-400 dark:text-secondary-500 text-xs hidden xl:table-cell whitespace-nowrap">
                      {{ (log.screen_width && log.screen_height) ? `${log.screen_width}×${log.screen_height}` : '—' }}
                    </td>
                  </tr>
                  <!-- Expanded detail row -->
                  <tr v-if="expandedId === log.id" class="bg-secondary-50 dark:bg-secondary-800/30">
                    <td colspan="7" class="px-6 py-3">
                      <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-secondary-600 dark:text-secondary-400">
                        <span><strong class="text-secondary-700 dark:text-secondary-300">Session:</strong> {{ log.session_id }}</span>
                        <span v-if="log.metadata"><strong class="text-secondary-700 dark:text-secondary-300">Metadata:</strong> {{ JSON.stringify(log.metadata) }}</span>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </template>
        </div>

        <!-- Pagination -->
        <AppPagination
          v-if="pagination.last_page > 1"
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          class="mt-4"
          @page-change="load"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Download } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppPagination from '../components/AppPagination.vue';
import { apiRequest } from '../composables/useApiClient.js';

const logs        = ref([]);
const loading     = ref(false);
const exporting   = ref(false);
const errorMessage = ref('');
const expandedId  = ref(null);

const pagination = ref({ current_page: 1, last_page: 1 });

const filters = ref({
    member_search: '',
    event_type:    '',
    device_type:   '',
    date_from:     '',
    date_to:       '',
});

const hasActiveFilters = computed(() =>
    Object.values(filters.value).some(v => v !== '')
);

onMounted(() => load(1));

async function load(page = 1) {
    loading.value    = true;
    errorMessage.value = '';
    try {
        const params = { page, ...activeFilters() };
        const res = await apiRequest('/api/member-activity', { params });
        logs.value       = res.data ?? [];
        pagination.value = { current_page: res.current_page, last_page: res.last_page };
    } catch {
        errorMessage.value = 'Failed to load activity logs.';
    } finally {
        loading.value = false;
    }
}

function activeFilters() {
    return Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== '')
    );
}

function clearFilters() {
    filters.value = { member_search: '', event_type: '', device_type: '', date_from: '', date_to: '' };
    load(1);
}

async function exportCsv() {
    exporting.value = true;
    try {
        const params = new URLSearchParams(activeFilters()).toString();
        const url = `/api/member-activity/export${params ? '?' + params : ''}`;
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } finally {
        exporting.value = false;
    }
}

function toggleDetail(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

// ── Formatting helpers ─────────────────────────────────────
function formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

const EVENT_LABELS = {
    session_start:  'Session Start',
    session_resume: 'Session Resume',
    otp_requested:  'OTP Requested',
    otp_verified:   'OTP Verified',
    tab_view:       'Tab View',
    workout_opened: 'Workout Opened',
    sale_opened:    'Sale Opened',
    logout:         'Logout',
};

function formatEventType(type) {
    return EVENT_LABELS[type] ?? capitalize(type.replace(/_/g, ' '));
}

function eventBadgeClass(type) {
    const map = {
        session_start:  'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
        session_resume: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
        otp_requested:  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        otp_verified:   'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        tab_view:       'bg-secondary-100 text-secondary-700 dark:bg-secondary-700 dark:text-secondary-300',
        workout_opened: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        sale_opened:    'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        logout:         'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return map[type] ?? 'bg-secondary-100 text-secondary-600 dark:bg-secondary-700 dark:text-secondary-300';
}

function deviceBadgeClass(type) {
    const map = {
        mobile:  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        tablet:  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        desktop: 'bg-secondary-100 text-secondary-700 dark:bg-secondary-700 dark:text-secondary-300',
    };
    return map[type] ?? 'bg-secondary-100 text-secondary-600';
}
</script>
