<template>
    <section>
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Sales</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Sales History</h2>
                    <p class="text-secondary-600 dark:text-secondary-400 text-sm">Recent sales transactions loaded via REST API.</p>
                </div>
                <RouterLink v-if="permissions.create" to="/sales/new" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white font-semibold transition-all hover:brightness-110">New Sale</RouterLink>
            </div>

            <div class="mt-4 inline-flex rounded-xl app-surface-soft p-1">
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                    :class="activeTab === 'outstanding' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                    @click="switchTab('outstanding')"
                >
                    Outstanding Sales
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                    :class="activeTab === 'paid' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                    @click="switchTab('paid')"
                >
                    Paid Sales
                </button>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="app-surface rounded-2xl overflow-hidden">
            <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading sales...</div>

            <template v-else>
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="sale in sales" :key="sale.id" class="p-4 space-y-2">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">#{{ sale.id }} • {{ sale.customer_name || 'Walk-in' }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ sale.created_at }} • {{ sale.customer_type }}</p>
                                <p class="mt-1 text-xs font-semibold" :class="sale.is_paid ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button v-if="permissions.edit && !sale.is_paid" type="button" class="text-emerald-600 dark:text-emerald-400 text-sm" @click="openPayNowModal(sale)">Pay Now</button>
                                <RouterLink v-if="permissions.edit && !sale.is_paid" :to="`/sales/${sale.id}/edit`" type="button" class="text-primary-600 dark:text-primary-400 text-sm">Edit</RouterLink>
                                <button v-if="permissions.delete && !sale.is_paid" type="button" class="text-red-600 dark:text-red-400 text-sm" @click="removeSale(sale.id)">Delete</button>
                            </div>
                        </div>
                        <ul class="text-xs space-y-0.5">
                            <li v-for="(item, i) in sale.items" :key="i" class="text-secondary-700 dark:text-secondary-300">
                                {{ item.product_name }}<span v-if="item.variation_name"> – {{ item.variation_name }}</span>
                                <span class="text-secondary-500 dark:text-secondary-400"> × {{ item.quantity }}</span>
                            </li>
                        </ul>
                    </article>

                    <div v-if="sales.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No sales recorded.</div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Sale ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date &amp; Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="sale in sales" :key="sale.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">#{{ sale.id }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ sale.customer_name || 'Walk-in' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300 capitalize">{{ sale.customer_type }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                                    <ul class="space-y-0.5">
                                        <li v-for="(item, i) in sale.items" :key="i" class="whitespace-nowrap">
                                            {{ item.product_name }}<span v-if="item.variation_name"> – {{ item.variation_name }}</span>
                                            <span class="text-secondary-500 dark:text-secondary-400"> × {{ item.quantity }}</span>
                                        </li>
                                    </ul>
                                </td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">{{ sale.created_at }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="sale.is_paid ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button v-if="permissions.edit && !sale.is_paid" type="button" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 text-sm font-medium" @click="openPayNowModal(sale)">Pay Now</button>
                                    <RouterLink v-if="permissions.edit && !sale.is_paid" :to="`/sales/${sale.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-sm font-medium">Edit</RouterLink>
                                    <button v-if="permissions.delete && !sale.is_paid" type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium" @click="removeSale(sale.id)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="sales.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No sales recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
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

                    <div>
                        <label class="block text-sm text-secondary-700 dark:text-secondary-300 mb-1">Company Account</label>
                        <select v-model.number="selectedAccountId" class="w-full px-3 py-2 text-sm border border-secondary-300 dark:border-secondary-700 rounded-xl bg-white dark:bg-secondary-800">
                            <option :value="null">Select account</option>
                            <option v-for="account in companyAccounts" :key="account.id" :value="account.id">
                                {{ account.label || account.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-xl border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closePayNowModal">Cancel</button>
                    <button type="button" class="px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white disabled:opacity-50" :disabled="payingSale || !selectedAccountId || !selectedSale" @click="markSelectedSaleAsPaid">
                        {{ payingSale ? 'Processing...' : 'Confirm Payment' }}
                    </button>
                </div>
            </div>
        </div>

        <AppPagination
            :current-page="meta.current_page"
            :last-page="meta.last_page"
            :per-page="perPage"
            :total="meta.total"
            :disabled="loading"
            @page-change="handlePageChange"
            @limit-change="handleLimitChange"
        />
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppPagination from '../components/AppPagination.vue';
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';

const context = useAppContext();
const sales = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);
const activeTab = ref('outstanding');
const companyAccounts = ref([]);
const payNowModalOpen = ref(false);
const selectedSale = ref(null);
const selectedAccountId = ref(null);
const payingSale = ref(false);
const permissions = ref({
    create: Boolean(context.permissions?.salesCreate),
    edit: Boolean(context.permissions?.salesEdit),
    delete: Boolean(context.permissions?.salesDelete),
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
