<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <AppHeaderAction v-if="permissions.create" to="/sales/new" :icon="ReceiptText" label="New Sale" />
            </template>

            <template #extra-slot>
                <div class="space-y-3">
                    <AppSearchField v-model="search" placeholder="Search sale id, customer, item, or date" :disabled="loading" @search="loadSales(1)" />

                    <div class="inline-flex rounded-xl app-surface-soft p-1">
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                            :class="activeTab === 'outstanding' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                            @click="switchTab('outstanding')"
                        >
                            Outstanding Sales
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                            :class="activeTab === 'paid' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                            @click="switchTab('paid')"
                        >
                            Paid Sales
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
                                <div class="app-skeleton h-3.5 w-28 rounded"></div>
                                <div class="app-skeleton h-3.5 w-20 rounded"></div>
                            </div>
                            <div class="app-skeleton h-3 w-48 rounded"></div>
                        </div>
                    </div>

                <template v-else>
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="sale in filteredSales" :key="sale.id" class="p-4 space-y-2.5 hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors">
                        <div class="flex justify-between items-start gap-3">
                            <button type="button" class="flex-1 text-left" @click="openPreviewModal(sale)">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">#{{ sale.id }}</p>
                                    <p class="text-sm text-secondary-600 dark:text-secondary-300">{{ sale.customer_name || 'Walk-in' }}</p>
                                    <span class="app-badge" :class="sale.is_paid ? 'app-badge-green' : 'app-badge-amber'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
                                </div>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">{{ sale.created_at }} · {{ sale.customer_type }}</p>
                            </button>
                            <div class="flex gap-3 shrink-0">
                                <button type="button" class="app-table-action" @click="openPreviewModal(sale)">View</button>
                                <button v-if="permissions.edit && !sale.is_paid" type="button" class="app-table-action-green" @click="openPayNowModal(sale)">Pay</button>
                                <RouterLink v-if="permissions.edit && !sale.is_paid" :to="`/sales/${sale.id}/edit`" class="app-table-action-primary">Edit</RouterLink>
                                <button v-if="permissions.delete && !sale.is_paid" type="button" class="app-table-action-red" @click="removeSale(sale.id)">Delete</button>
                            </div>
                        </div>
                        <ul class="text-xs space-y-0.5 pl-0">
                            <li v-for="(item, i) in sale.items" :key="i" class="text-secondary-600 dark:text-secondary-300">
                                {{ item.product_name }}<span v-if="item.variation_name"> – {{ item.variation_name }}</span>
                                <span class="text-secondary-400 dark:text-secondary-500"> × {{ item.quantity }}</span>
                            </li>
                        </ul>
                    </article>

                    <AppEmptyState v-if="filteredSales.length === 0" :icon="ReceiptText" title="No sales recorded" description="Sales will appear here once recorded." />
                </div>

                <div class="hidden md:block app-table-scroll">
                    <table class="w-full">
                        <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="app-table-th">Sale ID</th>
                                <th class="app-table-th">Customer</th>
                                <th class="app-table-th">Type</th>
                                <th class="app-table-th">Items</th>
                                <th class="app-table-th">Date &amp; Time</th>
                                <th class="app-table-th">Status</th>
                                <th class="app-table-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="sale in filteredSales" :key="sale.id" class="app-table-row">
                                <td class="app-table-td text-secondary-500 dark:text-secondary-400">#{{ sale.id }}</td>
                                <td class="app-table-td font-medium">{{ sale.customer_name || 'Walk-in' }}</td>
                                <td class="app-table-td text-secondary-600 dark:text-secondary-300 capitalize">{{ sale.customer_type }}</td>
                                <td class="app-table-td text-secondary-600 dark:text-secondary-300">
                                    <ul class="space-y-0.5">
                                        <li v-for="(item, i) in sale.items" :key="i" class="whitespace-nowrap text-xs">
                                            {{ item.product_name }}<span v-if="item.variation_name"> – {{ item.variation_name }}</span>
                                            <span class="text-secondary-400 dark:text-secondary-500"> × {{ item.quantity }}</span>
                                        </li>
                                    </ul>
                                </td>
                                <td class="app-table-td text-secondary-500 dark:text-secondary-400 whitespace-nowrap text-xs">{{ sale.created_at }}</td>
                                <td class="app-table-td">
                                    <span class="app-badge" :class="sale.is_paid ? 'app-badge-green' : 'app-badge-amber'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
                                </td>
                                <td class="app-table-td text-right space-x-3">
                                    <button type="button" class="app-table-action" @click="openPreviewModal(sale)">View</button>
                                    <button v-if="permissions.edit && !sale.is_paid" type="button" class="app-table-action-green" @click="openPayNowModal(sale)">Pay Now</button>
                                    <RouterLink v-if="permissions.edit && !sale.is_paid" :to="`/sales/${sale.id}/edit`" class="app-table-action-primary">Edit</RouterLink>
                                    <button v-if="permissions.delete && !sale.is_paid" type="button" class="app-table-action-red" @click="removeSale(sale.id)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="filteredSales.length === 0">
                                <td colspan="7">
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

        <!-- Invoice Preview Modal -->
        <div v-if="previewModalOpen" class="fixed inset-0 z-40 flex items-start justify-center p-3 sm:p-6 overflow-y-auto" @keydown.escape.window="closePreviewModal">
            <div class="fixed inset-0 bg-black/45" @click="closePreviewModal"></div>
            <div class="relative z-10 w-full max-w-2xl my-4">
                <div class="flex items-center justify-end mb-2">
                    <button
                        type="button"
                        class="p-2 rounded-lg bg-white dark:bg-secondary-800 text-secondary-500 hover:text-secondary-700 dark:text-secondary-400 dark:hover:text-secondary-200 shadow"
                        @click="closePreviewModal"
                        aria-label="Close"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <SaleInvoicePreviewCard v-if="previewSale" :sale="previewSale" />
            </div>
        </div>

        <div v-if="payNowModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45" @click="closePayNowModal"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl app-surface p-4 md:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Pay Outstanding Sale</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Select the company account that received payment for sale #{{ selectedSale?.id }}.</p>
                    </div>
                    <button type="button" class="text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closePayNowModal">✕</button>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="rounded-xl app-surface-soft px-3 py-2 text-sm text-secondary-700 dark:text-secondary-200">
                        <p>Total: <span class="font-semibold">{{ money(selectedSale?.total_amount) }}</span></p>
                        <p>Customer: <span class="font-semibold">{{ selectedSale?.customer_name || 'Walk-in' }}</span></p>
                    </div>

                    <AppFormField label="Company Account">
                        <AppFormSelect v-model.number="selectedAccountId">
                            <option :value="null">Select account</option>
                            <option v-for="account in companyAccounts" :key="account.id" :value="account.id">
                                {{ account.label || account.name }}
                            </option>
                        </AppFormSelect>
                    </AppFormField>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-xl border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closePayNowModal">Cancel</button>
                    <button type="button" class="px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white disabled:opacity-50" :disabled="payingSale || !selectedAccountId || !selectedSale" @click="markSelectedSaleAsPaid">
                        {{ payingSale ? 'Processing...' : 'Confirm Payment' }}
                    </button>
                </div>
            </div>
        </div>

    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AppPagination from '../components/AppPagination.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppEmptyState from '../components/AppEmptyState.vue';
import SaleInvoicePreviewCard from '../components/SaleInvoicePreviewCard.vue';
import { ReceiptText } from 'lucide-vue-next';
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';

const context = useAppContext();
const sales = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const search = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);
const activeTab = ref('outstanding');
const companyAccounts = ref([]);
const payNowModalOpen = ref(false);
const selectedSale = ref(null);
const selectedAccountId = ref(null);
const payingSale = ref(false);
const previewModalOpen = ref(false);
const previewSale = ref(null);
const permissions = ref({
    create: Boolean(context.permissions?.salesCreate),
    edit: Boolean(context.permissions?.salesEdit),
    delete: Boolean(context.permissions?.salesDelete),
});

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

function money(value) {
    const amount = Number(value || 0);
    return amount.toFixed(2);
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

async function loadMeta() {
    try {
        const response = await apiRequest('/api/sales/meta');
        companyAccounts.value = response.accounts || [];

        if (companyAccounts.value.length > 0 && !selectedAccountId.value) {
            selectedAccountId.value = companyAccounts.value[0].id;
        }
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load sale metadata.';
    }
}

function handlePageChange(page) {
    loadSales(page);
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadSales(1);
}

function switchTab(tab) {
    if (activeTab.value === tab) {
        return;
    }

    activeTab.value = tab;
    loadSales(1);
}

function openPreviewModal(sale) {
    previewSale.value = sale;
    previewModalOpen.value = true;
}

function closePreviewModal() {
    previewModalOpen.value = false;
    previewSale.value = null;
}

function openPayNowModal(sale) {
    selectedSale.value = sale;

    if (!selectedAccountId.value && companyAccounts.value.length > 0) {
        selectedAccountId.value = companyAccounts.value[0].id;
    }

    payNowModalOpen.value = true;
}

function closePayNowModal(force = false) {
    if (payingSale.value && !force) {
        return;
    }

    payNowModalOpen.value = false;
    selectedSale.value = null;
}

async function markSelectedSaleAsPaid() {
    if (!selectedSale.value || !selectedAccountId.value) {
        return;
    }

    payingSale.value = true;
    errorMessage.value = '';

    try {
        await apiRequest(`/api/sales/${selectedSale.value.id}/mark-as-paid`, {
            method: 'post',
            data: {
                account_id: Number(selectedAccountId.value),
            },
        });

        closePayNowModal(true);
        await loadSales(meta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to mark sale as paid.';
    } finally {
        payingSale.value = false;
    }
}

async function removeSale(id) {
    if (!window.confirm('Delete this sale?')) {
        return;
    }

    try {
        await apiRequest(`/api/sales/${id}`, { method: 'delete' });
        await loadSales(meta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete sale.';
    }
}

onMounted(() => {
    loadMeta();
    loadSales();
});
</script>
