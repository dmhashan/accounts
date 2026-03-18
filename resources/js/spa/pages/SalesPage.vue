<template>
    <section>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 md:mb-6">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Sales History</h2>
                <p class="text-secondary-600 dark:text-secondary-400 text-sm">Recent sales transactions loaded via REST API.</p>
            </div>
            <RouterLink to="/sales/new" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">New Sale</RouterLink>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading sales...</div>

            <template v-else>
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="sale in sales" :key="sale.id" class="p-4 space-y-2">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">#{{ sale.id }} • {{ sale.customer_name || 'Walk-in' }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ sale.created_at }} • {{ sale.customer_type }}</p>
                            </div>
                            <div class="flex gap-2">
                                <RouterLink :to="`/sales/${sale.id}/edit`" type="button" class="text-primary-600 dark:text-primary-400 text-sm">Edit</RouterLink>
                                <button type="button" class="text-red-600 dark:text-red-400 text-sm" @click="removeSale(sale.id)">Delete</button>
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
                                <td class="px-6 py-4 text-right space-x-2">
                                    <RouterLink :to="`/sales/${sale.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-sm font-medium">Edit</RouterLink>
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium" @click="removeSale(sale.id)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="sales.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No sales recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
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
import { apiRequest } from '../composables/useApiClient';

const sales = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);

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
    loadSales(page);
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadSales(1);
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
    loadSales();
});
</script>
