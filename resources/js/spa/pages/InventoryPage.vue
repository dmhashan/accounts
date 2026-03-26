<template>
    <section class="app-page-frame">
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Inventory</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Inventory Management</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Manage products and stock entries via REST API.</p>
                </div>
                <RouterLink :to="tabCta.to" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white font-semibold transition-all hover:brightness-110">
                    {{ tabCta.label }}
                </RouterLink>
            </div>

            <div class="mt-4 inline-flex rounded-xl app-surface-soft p-1">
                <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'products' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'products'">Products</button>
                <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'stock' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'stock'">Stock</button>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="activeTab === 'products'" class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="product in products" :key="product.id" class="p-4">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ product.name }}</p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">Variations: {{ product.variations_count }}</p>
                        <div class="mt-2 flex gap-3 text-sm">
                            <RouterLink :to="`/inventory/products/${product.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button type="button" class="text-red-600 dark:text-red-400" @click="removeProduct(product)">Delete</button>
                        </div>
                    </article>
                    <div v-if="products.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No products found.</div>
                </div>

                    <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Variations</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="product in products" :key="product.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">#{{ product.id }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ product.name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ product.variations_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <RouterLink :to="`/inventory/products/${product.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Edit</RouterLink>
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeProduct(product)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No products found.</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="app-page-pagination">
                <AppPagination
                    :current-page="productMeta.current_page"
                    :last-page="productMeta.last_page"
                    :per-page="productPerPage"
                    :total="productMeta.total"
                    :disabled="loadingProducts"
                    @page-change="handleProductPageChange"
                    @limit-change="handleProductLimitChange"
                />
            </div>
        </div>

        <div v-if="activeTab === 'stock'" class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="entry in stockEntries" :key="entry.id" class="p-4 space-y-2">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ entry.product_name }} - {{ entry.variation_name }}</p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div><span class="text-secondary-500 dark:text-secondary-400">Qty:</span> {{ entry.quantity }}</div>
                            <div><span class="text-secondary-500 dark:text-secondary-400">Available:</span> {{ entry.available }}</div>
                            <div><span class="text-secondary-500 dark:text-secondary-400">MFG:</span> {{ entry.manufacturing_date || '-' }}</div>
                            <div><span class="text-secondary-500 dark:text-secondary-400">EXP:</span> {{ entry.expiry_date || '-' }}</div>
                        </div>
                        <div class="text-xs text-secondary-700 dark:text-secondary-300">Local: {{ money(entry.local_selling_price) }} | Foreign: {{ money(entry.foreign_selling_price) }}</div>
                        <div class="flex gap-3 text-sm">
                            <RouterLink :to="`/inventory/stock/${entry.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button type="button" class="text-red-600 dark:text-red-400" @click="removeStock(entry)">Delete</button>
                        </div>
                    </article>
                    <div v-if="stockEntries.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No stock entries found.</div>
                </div>

                    <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Variation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Available</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">MFG</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Expiry</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Local</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Foreign</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="entry in stockEntries" :key="entry.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ entry.product_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ entry.variation_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ entry.quantity }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="text-secondary-700 dark:text-secondary-300">{{ entry.available }}</span>
                                    <span v-if="entry.is_low_stock" class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">Low</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ entry.manufacturing_date || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ entry.expiry_date || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ money(entry.local_selling_price) }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ money(entry.foreign_selling_price) }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <RouterLink :to="`/inventory/stock/${entry.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Edit</RouterLink>
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeStock(entry)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="stockEntries.length === 0">
                                <td colspan="9" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No stock entries found.</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="app-page-pagination">
                <AppPagination
                    :current-page="stockMeta.current_page"
                    :last-page="stockMeta.last_page"
                    :per-page="stockPerPage"
                    :total="stockMeta.total"
                    :disabled="loadingStock"
                    @page-change="handleStockPageChange"
                    @limit-change="handleStockLimitChange"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import AppPagination from '../components/AppPagination.vue';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();

const activeTab = ref(route.query.tab === 'stock' ? 'stock' : 'products');
const errorMessage = ref('');

const products = ref([]);
const stockEntries = ref([]);
const loadingProducts = ref(false);
const loadingStock = ref(false);
const productMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const stockMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const productPerPage = ref(10);
const stockPerPage = ref(10);

const tabCta = computed(() => {
    if (activeTab.value === 'stock') {
        return {
            to: '/inventory/stock/new',
            label: 'Add Stock Entry',
        };
    }

    return {
        to: '/inventory/products/new',
        label: 'Add Product',
    };
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function loadProducts(page = 1) {
    loadingProducts.value = true;

    try {
        const response = await apiRequest('/api/inventory/products', {
            params: {
                page,
                per_page: productPerPage.value,
            },
        });

        products.value = response.data || [];
        productMeta.value = response.meta || productMeta.value;
        productPerPage.value = productMeta.value.per_page || productPerPage.value;
    } finally {
        loadingProducts.value = false;
    }
}

async function loadStock(page = 1) {
    loadingStock.value = true;

    try {
        const response = await apiRequest('/api/inventory/stock', {
            params: {
                page,
                per_page: stockPerPage.value,
            },
        });

        stockEntries.value = response.data || [];
        stockMeta.value = response.meta || stockMeta.value;
        stockPerPage.value = stockMeta.value.per_page || stockPerPage.value;
    } finally {
        loadingStock.value = false;
    }
}

async function loadAll() {
    errorMessage.value = '';

    try {
        await Promise.all([loadProducts(), loadStock()]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load inventory data.';
    }
}

async function removeProduct(product) {
    if (!window.confirm(`Delete product "${product.name}"?`)) {
        return;
    }

    try {
        await apiRequest(`/api/inventory/products/${product.id}`, { method: 'delete' });
        await Promise.all([loadProducts(productMeta.value.current_page), loadStock(stockMeta.value.current_page)]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete product.';
    }
}

async function removeStock(entry) {
    if (!window.confirm('Delete this stock entry?')) {
        return;
    }

    try {
        await apiRequest(`/api/inventory/stock/${entry.id}`, { method: 'delete' });
        await loadStock(stockMeta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete stock entry.';
    }
}

function handleProductPageChange(page) {
    loadProducts(page);
}

function handleProductLimitChange(limit) {
    productPerPage.value = Number(limit);
    loadProducts(1);
}

function handleStockPageChange(page) {
    loadStock(page);
}

function handleStockLimitChange(limit) {
    stockPerPage.value = Number(limit);
    loadStock(1);
}

onMounted(() => {
    loadAll();
});
</script>
