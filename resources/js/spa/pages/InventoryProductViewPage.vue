<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/inventory/products/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Product
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteProduct"
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
        <h1 class="text-xl font-bold text-secondary-900 dark:text-white mb-4">
          {{ product.name }}
        </h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Variations
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ product.variations_count ?? (product.variations?.length ?? 0) }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="product.variations && product.variations.length > 0" class="app-surface rounded-2xl overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-secondary-200 dark:border-secondary-700">
          <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
            Variations
          </h2>
        </div>

        <!-- Mobile -->
        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
          <div v-for="v in product.variations" :key="v.id" class="p-4">
            <p class="text-sm font-medium text-secondary-900 dark:text-white">
              {{ v.name }}
            </p>
            <p v-if="v.sku" class="text-xs text-secondary-500 dark:text-secondary-400">
              SKU: {{ v.sku }}
            </p>
          </div>
        </div>

        <!-- Desktop -->
        <table class="hidden md:table w-full text-sm">
          <thead class="bg-secondary-50 dark:bg-secondary-800/50 border-b border-secondary-200 dark:border-secondary-700">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                Variation
              </th>
              <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                SKU
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <tr v-for="v in product.variations" :key="v.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40">
              <td class="px-4 py-3 font-medium text-secondary-900 dark:text-white">
                {{ v.name }}
              </td>
              <td class="px-4 py-3 text-secondary-600 dark:text-secondary-400">
                {{ v.sku || '—' }}
              </td>
            </tr>
          </tbody>
        </table>
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
const product = ref({});
const deleting = ref(false);

async function deleteProduct() {
    if (!confirm(`Delete product "${product.value.name}"?`)) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/inventory/products/${route.params.id}`, { method: 'DELETE' });
        router.push('/inventory');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete product.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/inventory/products/${route.params.id}`);
        product.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load product.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
