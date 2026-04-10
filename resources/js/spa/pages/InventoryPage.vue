<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <AppHeaderAction v-if="tabCta" :to="tabCta.to" :icon="tabCta.icon" :label="tabCta.label" />
            </template>

            <template #extra-slot>
                <div class="space-y-3">
                    <AppSearchField v-model="search" placeholder="Search current list" :disabled="loadingProducts || loadingStock" @search="triggerActiveSearch" />

                    <div class="inline-flex rounded-xl app-surface-soft p-1">
                        <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'products' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'products'">Products</button>
                        <button v-if="context.permissions?.inventoryStock" type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'stock' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'stock'">Stock</button>
                        <button v-if="context.permissions?.inventoryDisplay" type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'display' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'display'">Display</button>
                        <button v-if="context.permissions?.inventoryStock || context.permissions?.inventoryDisplay" type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'audit' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'audit'">Audit</button>
                    </div>
                </div>
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="activeTab === 'products'" class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="product in filteredProducts" :key="product.id" class="p-4">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ product.name }}</p>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">Variations: {{ product.variations_count }}</p>
                        <div class="mt-2 flex gap-3 text-sm">
                            <RouterLink :to="`/inventory/products/${product.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button type="button" class="text-red-600 dark:text-red-400" @click="removeProduct(product)">Delete</button>
                        </div>
                    </article>
                    <div v-if="filteredProducts.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No products found.</div>
                </div>

                    <div class="hidden md:block app-table-scroll">
                    <table class="w-full">
                            <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Variations</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">#{{ product.id }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ product.name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ product.variations_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <RouterLink :to="`/inventory/products/${product.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Edit</RouterLink>
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeProduct(product)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="filteredProducts.length === 0">
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
                    <article v-for="entry in filteredStockEntries" :key="entry.id" class="p-4 space-y-2">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ entry.product_name }} - {{ entry.variation_name }}</p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div><span class="text-secondary-500 dark:text-secondary-400">Total Stock:</span> {{ entry.quantity }}</div>
                            <div><span class="text-secondary-500 dark:text-secondary-400">On Display:</span> {{ entry.display_quantity }}</div>
                            <div><span class="text-secondary-500 dark:text-secondary-400">MFG:</span> {{ entry.manufacturing_date || '-' }}</div>
                            <div><span class="text-secondary-500 dark:text-secondary-400">EXP:</span> {{ entry.expiry_date || '-' }}</div>
                        </div>
                        <div class="text-xs text-secondary-700 dark:text-secondary-300">Local: {{ money(entry.local_selling_price) }} | Foreign: {{ money(entry.foreign_selling_price) }}</div>
                        <div class="flex gap-3 text-sm">
                            <RouterLink :to="`/inventory/stock/${entry.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button type="button" class="text-red-600 dark:text-red-400" @click="removeStock(entry)">Delete</button>
                        </div>
                    </article>
                    <div v-if="filteredStockEntries.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No stock entries found.</div>
                </div>

                    <div class="hidden md:block app-table-scroll">
                    <table class="w-full">
                            <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Variation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Total Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">On Display</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">MFG</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Expiry</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Local</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Foreign</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="entry in filteredStockEntries" :key="entry.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ entry.product_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ entry.variation_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ entry.quantity }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="text-secondary-700 dark:text-secondary-300">{{ entry.display_quantity }}</span>
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
                            <tr v-if="filteredStockEntries.length === 0">
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

        <!-- Display Tab -->
        <div v-if="activeTab === 'display'" class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <!-- Mobile cards -->
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                        <article v-for="entry in displayEntries" :key="entry.id" class="p-4 space-y-2">
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ entry.product_name }} - {{ entry.variation_name }}</p>
                            <div class="grid grid-cols-2 gap-2 text-xs text-secondary-600 dark:text-secondary-400">
                                <div><span>Total Stock:</span> {{ entry.quantity }}</div>
                                <div><span>Backroom:</span> {{ entry.quantity - entry.display_quantity }}</div>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <label class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0">Display Qty:</label>
                                <input
                                    v-model.number="displayDraft[entry.id]"
                                    type="number"
                                    min="0"
                                    :max="entry.quantity"
                                    class="w-20 rounded border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-2 py-1 text-sm text-secondary-900 dark:text-white"
                                />
                                <button
                                    type="button"
                                    class="px-3 py-1 text-xs bg-primary-600 hover:bg-primary-700 text-white rounded"
                                    :disabled="savingDisplay[entry.id]"
                                    @click="saveDisplayQty(entry)"
                                >{{ savingDisplay[entry.id] ? 'Saving…' : 'Save' }}</button>
                            </div>
                            <p v-if="displayErrors[entry.id]" class="text-xs text-red-500">{{ displayErrors[entry.id] }}</p>
                        </article>
                        <div v-if="displayEntries.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No stock entries found.</div>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden md:block app-table-scroll">
                        <table class="w-full">
                            <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Variation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Total Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Backroom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Display Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Expiry</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="entry in displayEntries" :key="entry.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                    <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ entry.product_name }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ entry.variation_name }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ entry.quantity }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ entry.quantity - entry.display_quantity }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <input
                                                v-model.number="displayDraft[entry.id]"
                                                type="number"
                                                min="0"
                                                :max="entry.quantity"
                                                class="w-24 rounded border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-2 py-1 text-sm text-secondary-900 dark:text-white"
                                            />
                                            <p v-if="displayErrors[entry.id]" class="text-xs text-red-500">{{ displayErrors[entry.id] }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ entry.expiry_date || '-' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            type="button"
                                            class="px-3 py-1 text-xs bg-primary-600 hover:bg-primary-700 text-white rounded"
                                            :disabled="savingDisplay[entry.id]"
                                            @click="saveDisplayQty(entry)"
                                        >{{ savingDisplay[entry.id] ? 'Saving…' : 'Release to Display' }}</button>
                                    </td>
                                </tr>
                                <tr v-if="displayEntries.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No stock entries found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="app-page-pagination">
                <AppPagination
                    :current-page="displayMeta.current_page"
                    :last-page="displayMeta.last_page"
                    :per-page="displayPerPage"
                    :total="displayMeta.total"
                    :disabled="loadingDisplay"
                    @page-change="handleDisplayPageChange"
                    @limit-change="handleDisplayLimitChange"
                />
            </div>
        </div>

        <!-- Audit Tab -->
        <div v-if="activeTab === 'audit'" class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <!-- Mobile cards -->
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                        <article v-for="log in auditLogs" :key="log.id" class="p-4 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase px-2 py-0.5 rounded" :class="auditActionClass(log.action)">{{ formatAuditAction(log.action) }}</span>
                                <span class="text-xs text-secondary-500 dark:text-secondary-400">{{ log.created_at }}</span>
                            </div>
                            <p class="text-sm text-secondary-900 dark:text-white">{{ log.user }} <span class="text-secondary-500">· Entry #{{ log.auditable_id }}</span></p>
                            <div class="grid grid-cols-2 gap-1 text-xs text-secondary-600 dark:text-secondary-400 pt-1">
                                <div v-if="log.before_data">
                                    <p class="font-medium text-secondary-500 mb-0.5">Before</p>
                                    <p v-for="(val, key) in log.before_data" :key="key">{{ key }}: {{ val }}</p>
                                </div>
                                <div v-if="log.after_data">
                                    <p class="font-medium text-secondary-500 mb-0.5">After</p>
                                    <p v-for="(val, key) in log.after_data" :key="key">{{ key }}: {{ val }}</p>
                                </div>
                            </div>
                        </article>
                        <div v-if="auditLogs.length === 0 && !loadingAudit" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No audit logs found.</div>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden md:block app-table-scroll">
                        <table class="w-full">
                            <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date / Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Entry #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Before</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top">
                                    <td class="px-6 py-4 text-xs text-secondary-500 dark:text-secondary-400 whitespace-nowrap">{{ log.created_at }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ log.user }}</td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-semibold uppercase px-2 py-0.5 rounded" :class="auditActionClass(log.action)">{{ formatAuditAction(log.action) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">#{{ log.auditable_id }}</td>
                                    <td class="px-6 py-4 text-xs text-secondary-600 dark:text-secondary-400 space-y-0.5">
                                        <p v-for="(val, key) in log.before_data" :key="key">{{ key }}: {{ val }}</p>
                                        <span v-if="!log.before_data">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-secondary-600 dark:text-secondary-400 space-y-0.5">
                                        <p v-for="(val, key) in log.after_data" :key="key">{{ key }}: {{ val }}</p>
                                        <span v-if="!log.after_data">—</span>
                                    </td>
                                </tr>
                                <tr v-if="auditLogs.length === 0 && !loadingAudit">
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No audit logs found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { PackagePlus, PackageSearch } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPagination from '../components/AppPagination.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const route = useRoute();
const context = useAppContext();

const activeTab = ref(route.query.tab === 'stock' ? 'stock' : route.query.tab === 'display' ? 'display' : route.query.tab === 'audit' ? 'audit' : 'products');
const errorMessage = ref('');

const products = ref([]);
const stockEntries = ref([]);
const displayEntries = ref([]);
const auditLogs = ref([]);
const search = ref('');
const loadingProducts = ref(false);
const loadingStock = ref(false);
const loadingDisplay = ref(false);
const loadingAudit = ref(false);
const productMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const stockMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const displayMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const productPerPage = ref(10);
const stockPerPage = ref(10);
const displayPerPage = ref(10);

// Per-entry display drafts keyed by stock entry id
const displayDraft = ref({});
const savingDisplay = ref({});
const displayErrors = ref({});

const tabCta = computed(() => {
    if (activeTab.value === 'stock') {
        return {
            to: '/inventory/stock/new',
            icon: PackageSearch,
            label: 'Add Stock Entry',
        };
    }

    if (activeTab.value === 'display' || activeTab.value === 'audit') {
        return null;
    }

    return {
        to: '/inventory/products/new',
        icon: PackagePlus,
        label: 'Add Product',
    };
});

watch(displayEntries, (entries) => {
    entries.forEach((entry) => {
        if (!(entry.id in displayDraft.value)) {
            displayDraft.value[entry.id] = entry.display_quantity;
        }
    });
});

const normalizedSearch = computed(() => search.value.trim().toLowerCase());

const filteredProducts = computed(() => {
    if (!normalizedSearch.value) return products.value;

    return products.value.filter((product) => {
        return [product.id, product.name, product.variations_count]
            .some((value) => String(value || '').toLowerCase().includes(normalizedSearch.value));
    });
});

const filteredStockEntries = computed(() => {
    if (!normalizedSearch.value) return stockEntries.value;

    return stockEntries.value.filter((entry) => {
        return [
            entry.product_name,
            entry.variation_name,
            entry.quantity,
            entry.display_quantity,
            entry.manufacturing_date,
            entry.expiry_date,
        ].some((value) => String(value || '').toLowerCase().includes(normalizedSearch.value));
    });
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function formatAuditAction(action) {
    return {
        created: 'Created',
        updated: 'Updated',
        deleted: 'Deleted',
        display_released: 'Display Released',
        sale_deducted: 'Sale Deducted',
        sale_restored: 'Sale Restored',
    }[action] ?? action;
}

function auditActionClass(action) {
    const map = {
        created: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        updated: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        deleted: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        display_released: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        sale_deducted: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        sale_restored: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    };
    return map[action] ?? 'bg-secondary-100 text-secondary-800';
}

function triggerActiveSearch() {
    if (activeTab.value === 'products') {
        loadProducts(1);
        return;
    }

    if (activeTab.value === 'display') {
        loadDisplay(1);
        return;
    }

    loadStock(1);
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

async function loadDisplay(page = 1) {
    loadingDisplay.value = true;

    try {
        const response = await apiRequest('/api/inventory/display', {
            params: {
                page,
                per_page: displayPerPage.value,
            },
        });

        displayEntries.value = response.data || [];
        displayMeta.value = response.meta || displayMeta.value;
        displayPerPage.value = displayMeta.value.per_page || displayPerPage.value;

        // Initialise drafts for new entries
        displayEntries.value.forEach((entry) => {
            displayDraft.value[entry.id] = entry.display_quantity;
        });
    } finally {
        loadingDisplay.value = false;
    }
}

async function loadAuditLogs() {
    loadingAudit.value = true;

    try {
        const response = await apiRequest('/api/inventory/audit-logs');
        auditLogs.value = response.data || [];
    } catch {
        // ignore permission errors silently
    } finally {
        loadingAudit.value = false;
    }
}

async function loadAll() {
    errorMessage.value = '';

    const requests = [loadProducts(), loadStock()];

    if (context.permissions?.inventoryDisplay) {
        requests.push(loadDisplay());
    }

    try {
        await Promise.all(requests);
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

async function saveDisplayQty(entry) {
    displayErrors.value[entry.id] = '';
    savingDisplay.value[entry.id] = true;

    try {
        await apiRequest(`/api/inventory/stock/${entry.id}/release`, {
            method: 'post',
            data: { display_quantity: displayDraft.value[entry.id] },
        });

        // Update local entry
        entry.display_quantity = displayDraft.value[entry.id];
    } catch (error) {
        displayErrors.value[entry.id] = error?.response?.data?.message || 'Failed to update display quantity.';
    } finally {
        savingDisplay.value[entry.id] = false;
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

function handleDisplayPageChange(page) {
    loadDisplay(page);
}

function handleDisplayLimitChange(limit) {
    displayPerPage.value = Number(limit);
    loadDisplay(1);
}

watch(activeTab, (tab) => {
    if (tab === 'audit' && auditLogs.value.length === 0) {
        loadAuditLogs();
    }
    if (tab === 'display' && displayEntries.value.length === 0) {
        loadDisplay(1);
    }
});

onMounted(() => {
    loadAll();

    if (activeTab.value === 'audit') {
        loadAuditLogs();
    }
});
</script>
