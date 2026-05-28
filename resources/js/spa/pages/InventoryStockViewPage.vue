<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/inventory/stock/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Stock Entry
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteEntry"
        >
          Delete
        </button>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll space-y-5">
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
          <div>
            <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
              {{ entry.product_name }}
            </h1>
            <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
              {{ entry.variation_name }}
            </p>
          </div>
          <span v-if="entry.is_low_stock" class="self-start px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">Low Stock</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Total Stock
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ entry.quantity }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              On Display
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ entry.display_quantity }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Manufacturing Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ entry.manufacturing_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Expiry Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ entry.expiry_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Local Selling Price
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ money(entry.local_selling_price) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Foreign Selling Price
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ money(entry.foreign_selling_price) }}
            </p>
          </div>
          <div v-if="entry.purchasing_price != null">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Purchasing Price
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ money(entry.purchasing_price) }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const entry = ref({});
const deleting = ref(false);

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function deleteEntry() {
    if (!confirm('Delete this stock entry?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/inventory/stock/${route.params.id}`, { method: 'DELETE' });
        router.push('/inventory/stock');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete stock entry.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/inventory/stock/${route.params.id}`);
        entry.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load stock entry.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
