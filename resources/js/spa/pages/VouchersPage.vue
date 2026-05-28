<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="canManage"
          :icon="Ticket"
          label="New Voucher"
          @click="openCreateModal"
        />
      </template>
      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search vouchers…"
          :disabled="loading"
          @search="load(1)"
        >
          <template #filter-trigger>
            <select
              v-model="filterStatus"
              class="h-12 rounded-full border border-secondary-300 dark:border-secondary-700 bg-white dark:bg-secondary-900 px-4 text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500/40 cursor-pointer shrink-0"
              @change="load(1)"
            >
              <option value="">
                All Statuses
              </option>
              <option value="active">
                Active
              </option>
              <option value="inactive">
                Inactive
              </option>
              <option value="redeemed">
                Redeemed
              </option>
            </select>
          </template>
        </AppSearchField>
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="app-alert app-alert-error">
      {{ errorMessage }}
    </div>
    <div v-if="successMessage" class="app-alert app-alert-success">
      {{ successMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <div v-for="i in 5" :key="i" class="p-4 space-y-2">
              <div class="flex items-center gap-3">
                <div class="app-skeleton h-3.5 w-36 rounded" />
                <div class="app-skeleton h-3.5 w-16 rounded-full" />
              </div>
              <div class="app-skeleton h-3 w-64 rounded" />
            </div>
          </div>

          <template v-else-if="vouchers.length === 0">
            <AppEmptyState :icon="Ticket" title="No vouchers found" description="Create a voucher to get started." />
          </template>

          <template v-else>
            <!-- Mobile cards -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article
                v-for="v in vouchers"
                :key="v.id"
                class="p-4 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
                @click="router.push('/vouchers/' + v.id)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                      <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                        {{ v.name }}
                      </p>
                      <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="statusClass(v.status)">{{ capitalize(v.status) }}</span>
                    </div>
                    <p class="text-xs font-mono text-secondary-400 dark:text-secondary-500 break-all mb-1">
                      {{ v.uuid }}
                    </p>
                    <div class="flex flex-wrap items-center gap-x-3 text-xs text-secondary-500 dark:text-secondary-400">
                      <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">{{ formatMoney(v.amount) }}</span>
                      <template v-if="v.valid_from || v.valid_until">
                        <span>{{ v.valid_from || '∞' }} → {{ v.valid_until || '∞' }}</span>
                      </template>
                    </div>
                  </div>
                  <ChevronRight class="h-4 w-4 text-secondary-400 shrink-0 mt-1" :stroke-width="2" />
                </div>
              </article>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="app-table-th">
                      Name
                    </th>
                    <th class="app-table-th">
                      UUID
                    </th>
                    <th class="app-table-th text-right">
                      Amount
                    </th>
                    <th class="app-table-th">
                      Status
                    </th>
                    <th class="app-table-th">
                      Valid From
                    </th>
                    <th class="app-table-th">
                      Valid Until
                    </th>
                    <th class="app-table-th">
                      Created
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr
                    v-for="v in vouchers"
                    :key="v.id"
                    class="app-table-row cursor-pointer"
                    @click="router.push('/vouchers/' + v.id)"
                  >
                    <td class="app-table-td font-semibold">
                      {{ v.name }}
                    </td>
                    <td class="app-table-td">
                      <span class="font-mono text-xs text-secondary-500 dark:text-secondary-400 select-all">{{ v.uuid }}</span>
                    </td>
                    <td class="app-table-td text-right font-semibold text-emerald-600 dark:text-emerald-400">
                      {{ formatMoney(v.amount) }}
                    </td>
                    <td class="app-table-td">
                      <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="statusClass(v.status)">{{ capitalize(v.status) }}</span>
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400">
                      {{ v.valid_from || '—' }}
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400">
                      {{ v.valid_until || '—' }}
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400 whitespace-nowrap">
                      {{ formatDate(v.created_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>

      <div class="app-page-pagination">
        <AppPagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :per-page="meta.per_page"
          :total="meta.total"
          :disabled="loading"
          @page-change="load"
        />
      </div>
    </div>

    <!-- ── Create Modal ── -->
    <div v-if="formModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeFormModal" />
      <div class="relative z-10 w-full max-w-md rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3 mb-4">
          <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
            New Voucher
          </h3>
          <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeFormModal">
            ✕
          </button>
        </div>

        <div v-if="formError" class="mb-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
          {{ formError }}
        </div>

        <form class="space-y-3" @submit.prevent="submitForm">
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Voucher Name <span class="text-red-500">*</span></label>
            <input
              v-model="form.name"
              type="text"
              required
              maxlength="255"
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="e.g. Welcome Gift"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Amount <span class="text-red-500">*</span></label>
            <input
              v-model="form.amount"
              type="number"
              min="0.01"
              step="0.01"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              placeholder="0.00"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Status <span class="text-red-500">*</span></label>
            <select
              v-model="form.status"
              required
              class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="active">
                Active
              </option>
              <option value="inactive">
                Inactive
              </option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Valid From <span class="text-xs text-secondary-400">(optional)</span></label>
              <input
                v-model="form.valid_from"
                type="date"
                class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Valid Until <span class="text-xs text-secondary-400">(optional)</span></label>
              <input
                v-model="form.valid_until"
                type="date"
                class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
          </div>
          <div class="flex items-center justify-end gap-2 pt-1">
            <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeFormModal">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50" :disabled="formSubmitting">
              {{ formSubmitting ? 'Saving…' : 'Create Voucher' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ChevronRight, Ticket } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppPagination from '../components/AppPagination.vue';
import AppEmptyState from '../components/AppEmptyState.vue';

const context = useAppContext();
const router = useRouter();
const canManage = computed(() => Boolean(context.permissions?.vouchersManage));

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const vouchers = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });

const search = ref('');
const filterStatus = ref('');

async function load(page = 1) {
    loading.value = true;
    errorMessage.value = '';
    try {
        const params = new URLSearchParams({ page, per_page: meta.value.per_page });
        if (filterStatus.value) params.append('status', filterStatus.value);
        if (search.value.trim()) params.append('search', search.value.trim());

        const res = await apiRequest(`/api/vouchers?${params.toString()}`);
        vouchers.value = res.data || [];
        meta.value = res.meta || meta.value;
    } catch {
        errorMessage.value = 'Failed to load vouchers.';
    } finally {
        loading.value = false;
    }
}

// ── Create modal ──────────────────────────────────────────────────────
const formModalOpen = ref(false);
const formSubmitting = ref(false);
const formError = ref('');
const form = ref({ name: '', amount: '', status: 'active', valid_from: '', valid_until: '' });

function openCreateModal() {
    form.value = { name: '', amount: '', status: 'active', valid_from: '', valid_until: '' };
    formError.value = '';
    formModalOpen.value = true;
}

function closeFormModal() {
    formModalOpen.value = false;
}

async function submitForm() {
    formSubmitting.value = true;
    formError.value = '';
    try {
        await apiRequest('/api/vouchers', {
            method: 'post',
            data: {
                name: form.value.name,
                amount: form.value.amount,
                status: form.value.status,
                valid_from: form.value.valid_from || null,
                valid_until: form.value.valid_until || null,
            },
        });
        successMessage.value = 'Voucher created successfully.';
        closeFormModal();
        await load(1);
        setTimeout(() => { successMessage.value = ''; }, 4000);
    } catch (err) {
        formError.value = err?.response?.data?.message || 'Failed to create voucher.';
    } finally {
        formSubmitting.value = false;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────
const moneyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function formatMoney(v) {
    return moneyFormatter.format(Number(v) || 0);
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function capitalize(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

function statusClass(status) {
    if (status === 'active')   return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    if (status === 'inactive') return 'bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-400';
    if (status === 'redeemed') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    return '';
}

onMounted(() => {
    load(1);
});
</script>
