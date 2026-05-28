<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="permissions.create"
          to="/sales/new"
          :icon="ReceiptText"
          label="New Sale"
        />
      </template>

      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search sale id, customer, item, or date"
          :disabled="loading"
          @search="loadSales(1)"
        />
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
                class="p-4 space-y-2.5 hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors cursor-pointer"
                @click="router.push('/sales/' + sale.id)"
              >
                <div class="flex justify-between items-start gap-3">
                  <div class="flex-1 text-left">
                    <div class="flex flex-wrap items-center gap-1.5">
                      <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                        #{{ sale.id }}
                      </p>
                      <p class="text-sm text-secondary-600 dark:text-secondary-300">
                        {{ sale.customer_name || 'Walk-in' }}
                      </p>
                      <span class="app-badge" :class="sale.is_paid ? 'app-badge-green' : 'app-badge-amber'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
                    </div>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                      {{ sale.created_at }} · {{ sale.customer_type }}
                    </p>
                  </div>
                </div>
                <ul class="text-xs space-y-0.5 pl-0">
                  <li v-for="(item, i) in sale.items" :key="i" class="text-secondary-600 dark:text-secondary-300">
                    {{ item.product_name }}<span v-if="item.variation_name"> – {{ item.variation_name }}</span>
                    <span class="text-secondary-400 dark:text-secondary-500"> × {{ item.quantity }}</span>
                  </li>
                </ul>
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
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400 whitespace-nowrap text-xs">
                      {{ sale.created_at }}
                    </td>
                    <td class="app-table-td">
                      <span class="app-badge" :class="sale.is_paid ? 'app-badge-green' : 'app-badge-amber'">{{ sale.is_paid ? 'Paid' : 'Outstanding' }}</span>
                    </td>
                  </tr>
                  <tr v-if="filteredSales.length === 0">
                    <td colspan="6">
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
import { ReceiptText } from 'lucide-vue-next';
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
