<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <button
          v-if="selectedSaleIds.length > 0 && permissions.edit"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="bulkPayLoading"
          @click="openBulkPayModal"
        >
          <CreditCard class="w-4 h-4" />
          <span>Pay Selected ({{ selectedSaleIds.length }}) • {{ money(selectedTotalAmount) }}</span>
        </button>
        <AppHeaderAction
          v-if="permissions.create"
          to="/sales/new"
          :icon="ReceiptText"
          label="New Sale"
        />
      </template>

      <template #extra-slot>
        <div class="space-y-3">
          <AppSearchField
            v-model="search"
            placeholder="Search sale id, customer, item, or date"
            :disabled="loading"
            @search="loadSales(1)"
          />

          <div
            v-if="selectedSaleIds.length > 0"
            class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs sm:text-sm font-medium text-emerald-800 dark:text-emerald-300"
          >
            <span>Selected <strong>{{ selectedSaleIds.length }}</strong> sale{{ selectedSaleIds.length === 1 ? '' : 's' }} (Total: <strong>{{ money(selectedTotalAmount) }}</strong>)</span>
            <button
              type="button"
              class="text-emerald-700 dark:text-emerald-400 hover:underline font-semibold text-xs ml-2"
              @click="clearSelection"
            >
              Clear selection
            </button>
          </div>
        </div>
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="app-alert app-alert-error">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <div v-for="i in 5" :key="i" class="p-4 space-y-2">
              <div class="flex items-center gap-3">
                <div class="app-skeleton h-3.5 w-28 rounded" />
                <div class="app-skeleton h-3.5 w-20 rounded" />
              </div>
              <div class="app-skeleton h-3 w-48 rounded" />
            </div>
          </div>

          <template v-else>
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article
                v-for="sale in filteredSales"
                :key="sale.id"
                class="p-4 flex items-start gap-3 hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors cursor-pointer"
                @click="router.push('/sales/' + sale.id)"
              >
                <div class="pt-0.5" @click.stop>
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500 cursor-pointer disabled:opacity-40"
                    :checked="selectedSaleIds.includes(sale.id)"
                    :disabled="sale.is_paid || !permissions.edit"
                    @change="toggleSelectSale(sale.id)"
                  />
                </div>
                <div class="flex-1 space-y-2.5 min-w-0">
                  <div class="flex justify-between items-start gap-3">
                    <div class="flex-1 text-left min-w-0">
                      <div class="flex flex-wrap items-center gap-1.5">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                          #{{ sale.id }}
                        </p>
                        <p class="text-sm text-secondary-600 dark:text-secondary-300 truncate">
                          {{ sale.customer_name || 'Walk-in' }}
                        </p>
                        <span class="app-badge" :class="sale.is_paid ? 'app-badge-green' : 'app-badge-amber'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
                      </div>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                        {{ sale.created_at }} · {{ sale.customer_type }}
                      </p>
                    </div>
                    <div class="text-right shrink-0">
                      <p class="text-sm font-bold text-secondary-900 dark:text-white">
                        {{ money(sale.total_amount) }}
                      </p>
                    </div>
                  </div>
                  <ul class="text-xs space-y-0.5 pl-0">
                    <li v-for="(item, i) in sale.items" :key="i" class="text-secondary-600 dark:text-secondary-300">
                      {{ item.product_name }}<span v-if="item.variation_name"> – {{ item.variation_name }}</span>
                      <span class="text-secondary-400 dark:text-secondary-500"> × {{ item.quantity }}</span>
                    </li>
                  </ul>
                </div>
              </article>

              <AppEmptyState
                v-if="filteredSales.length === 0"
                :icon="ReceiptText"
                title="No sales recorded"
                description="Sales will appear here once recorded."
              />
            </div>

            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="w-10 px-3 py-3 text-center">
                      <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500 cursor-pointer disabled:opacity-40"
                        :checked="isAllSelected"
                        :disabled="selectableSales.length === 0 || !permissions.edit"
                        @change="toggleSelectAll"
                      />
                    </th>
                    <th class="app-table-th">
                      Sale ID
                    </th>
                    <th class="app-table-th">
                      Customer
                    </th>
                    <th class="app-table-th">
                      Type
                    </th>
                    <th class="app-table-th">
                      Items
                    </th>
                    <th class="app-table-th text-right">
                      Amount
                    </th>
                    <th class="app-table-th">
                      Date &amp; Time
                    </th>
                    <th class="app-table-th">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr
                    v-for="sale in filteredSales"
                    :key="sale.id"
                    class="app-table-row cursor-pointer"
                    @click="router.push('/sales/' + sale.id)"
                  >
                    <td class="w-10 px-3 py-3 text-center" @click.stop>
                      <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500 cursor-pointer disabled:opacity-40"
                        :checked="selectedSaleIds.includes(sale.id)"
                        :disabled="sale.is_paid || !permissions.edit"
                        @change="toggleSelectSale(sale.id)"
                      />
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400">
                      #{{ sale.id }}
                    </td>
                    <td class="app-table-td font-medium">
                      {{ sale.customer_name || 'Walk-in' }}
                    </td>
                    <td class="app-table-td text-secondary-600 dark:text-secondary-300 capitalize">
                      {{ sale.customer_type }}
                    </td>
                    <td class="app-table-td text-secondary-600 dark:text-secondary-300">
                      <ul class="space-y-0.5">
                        <li v-for="(item, i) in sale.items" :key="i" class="whitespace-nowrap text-xs">
                          {{ item.product_name }}<span v-if="item.variation_name"> – {{ item.variation_name }}</span>
                          <span class="text-secondary-400 dark:text-secondary-500"> × {{ item.quantity }}</span>
                        </li>
                      </ul>
                    </td>
                    <td class="app-table-td font-semibold text-right">
                      {{ money(sale.total_amount) }}
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400 whitespace-nowrap text-xs">
                      {{ sale.created_at }}
                    </td>
                    <td class="app-table-td">
                      <span class="app-badge" :class="sale.is_paid ? 'app-badge-green' : 'app-badge-amber'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
                    </td>
                  </tr>
                  <tr v-if="filteredSales.length === 0">
                    <td colspan="8">
                      <AppEmptyState :icon="ReceiptText" title="No sales recorded" description="Sales will appear here once recorded." />
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
          :per-page="perPage"
          :total="meta.total"
          :disabled="loading"
          @page-change="handlePageChange"
          @limit-change="handleLimitChange"
        />
      </div>
    </div>

    <!-- Bulk Pay Now Modal -->
    <div v-if="bulkPayOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closeBulkPayModal" />
      <div class="relative z-10 w-full max-w-md rounded-2xl app-surface p-4 md:p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Bulk Pay Outstanding Sales
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
              Select the payment method to mark {{ selectedSaleIds.length }} sale{{ selectedSaleIds.length === 1 ? '' : 's' }} as paid.
            </p>
          </div>
          <button type="button" class="text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeBulkPayModal">
            ✕
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div class="rounded-xl app-surface-soft px-3.5 py-2.5 text-sm text-secondary-700 dark:text-secondary-200">
            <p class="flex justify-between">
              <span>Selected Sales:</span>
              <span class="font-semibold">{{ selectedSaleIds.length }}</span>
            </p>
            <p class="flex justify-between mt-1">
              <span>Total Amount:</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ money(selectedTotalAmount) }}</span>
            </p>
          </div>

          <AppFormField label="Payment Method">
            <AppPaymentMethodSelect
              v-model="selectedAccountId"
              :methods="paymentMethods"
              :member-id="commonMemberId ?? undefined"
              :amount="selectedTotalAmount"
            />
          </AppFormField>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 text-sm rounded-xl border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800"
            @click="closeBulkPayModal"
          >
            Cancel
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white disabled:opacity-50"
            :disabled="bulkPayLoading || !selectedAccountId"
            @click="confirmBulkPay"
          >
            {{ bulkPayLoading ? 'Processing...' : 'Confirm Bulk Payment' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPagination from '../components/AppPagination.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppEmptyState from '../components/AppEmptyState.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppPaymentMethodSelect from '../components/forms/AppPaymentMethodSelect.vue';
import { CreditCard, ReceiptText } from 'lucide-vue-next';
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();

const context = useAppContext();
const sales = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const search = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);
const activeTab = ref(route.path === '/sales/paid' ? 'paid' : 'outstanding');
const permissions = ref({
    create: Boolean(context.permissions?.salesCreate),
    edit: Boolean(context.permissions?.salesEdit),
    delete: Boolean(context.permissions?.salesDelete),
});

const selectedSaleIds = ref([]);
const bulkPayOpen = ref(false);
const bulkPayLoading = ref(false);
const paymentMethods = ref([]);
const companyAccounts = ref([]);
const selectedAccountId = ref(null);
const metaLoaded = ref(false);

function money(value) {
    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const filteredSales = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return sales.value;
    }

    return sales.value.filter((sale) => {
        const items = Array.isArray(sale.items)
            ? sale.items.map((item) => `${item.product_name || ''} ${item.variation_name || ''}`).join(' ')
            : '';

        return [
            sale.id,
            sale.customer_name,
            sale.customer_type,
            sale.created_at,
            items,
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

const selectableSales = computed(() => {
    return filteredSales.value.filter((sale) => !sale.is_paid);
});

const isAllSelected = computed(() => {
    if (selectableSales.value.length === 0) return false;
    return selectableSales.value.every((sale) => selectedSaleIds.value.includes(sale.id));
});

const selectedSales = computed(() => {
    return sales.value.filter((sale) => selectedSaleIds.value.includes(sale.id));
});

const selectedTotalAmount = computed(() => {
    return selectedSales.value.reduce((sum, sale) => sum + Number(sale.total_amount || 0), 0);
});

const commonMemberId = computed(() => {
    if (selectedSales.value.length === 0) return null;
    const firstMemberId = selectedSales.value[0].customer_member_id;
    if (!firstMemberId) return null;
    const allSame = selectedSales.value.every((sale) => sale.customer_member_id === firstMemberId);
    return allSame ? firstMemberId : null;
});

function toggleSelectSale(id) {
    const index = selectedSaleIds.value.indexOf(id);
    if (index === -1) {
        selectedSaleIds.value.push(id);
    } else {
        selectedSaleIds.value.splice(index, 1);
    }
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        const selectableIds = selectableSales.value.map((sale) => sale.id);
        selectedSaleIds.value = selectedSaleIds.value.filter((id) => !selectableIds.includes(id));
    } else {
        const selectableIds = selectableSales.value.map((sale) => sale.id);
        const newIds = new Set([...selectedSaleIds.value, ...selectableIds]);
        selectedSaleIds.value = Array.from(newIds);
    }
}

function clearSelection() {
    selectedSaleIds.value = [];
}

async function loadMeta() {
    if (metaLoaded.value) return;
    try {
        const response = await apiRequest('/api/sales/meta');
        companyAccounts.value = response.accounts || [];
        paymentMethods.value = response.payment_methods || [];
        if (paymentMethods.value.length > 0 && !selectedAccountId.value) {
            selectedAccountId.value = paymentMethods.value[0].id;
        }
        metaLoaded.value = true;
    } catch {
        // non-critical
    }
}

async function openBulkPayModal() {
    await loadMeta();
    bulkPayOpen.value = true;
}

function closeBulkPayModal() {
    if (bulkPayLoading.value) return;
    bulkPayOpen.value = false;
}

async function confirmBulkPay() {
    if (!selectedAccountId.value || selectedSaleIds.value.length === 0) return;
    bulkPayLoading.value = true;
    try {
        const isWallet = selectedAccountId.value === 'member_wallet';
        const payload = {
            sale_ids: selectedSaleIds.value,
        };
        if (isWallet) {
            payload.payment_method = 'member_wallet';
        } else {
            payload.payment_method_id = Number(selectedAccountId.value);
        }

        const res = await apiRequest('/api/sales/bulk-mark-as-paid', {
            method: 'post',
            data: payload,
        });

        bulkPayOpen.value = false;
        selectedSaleIds.value = [];
        await loadSales(meta.value.current_page || 1);

        if (res?.message) {
            alert(res.message);
        }
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to process bulk payment.');
    } finally {
        bulkPayLoading.value = false;
    }
}

async function loadSales(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/sales', {
            params: {
                page,
                per_page: perPage.value,
                status: activeTab.value,
            },
        });

        sales.value = response.data || [];
        meta.value = response.meta || meta.value;
        perPage.value = meta.value.per_page || perPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load sales.';
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    clearSelection();
    loadSales(page);
}

function handleLimitChange(limit) {
    clearSelection();
    perPage.value = Number(limit);
    loadSales(1);
}

function switchTab(tab) {
    if (activeTab.value === tab) {
        return;
    }

    clearSelection();
    activeTab.value = tab;
    loadSales(1);
}

watch(
    () => route.path,
    (path) => {
        const newTab = path === '/sales/paid' ? 'paid' : 'outstanding';
        if (activeTab.value !== newTab) switchTab(newTab);
    }
);

onMounted(() => {
    loadSales();
});
</script>
