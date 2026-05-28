<template>
  <section class="flex h-full min-h-0 flex-col overflow-y-auto pb-8">
    <AppPageHeader show-back />

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="save">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
        <AppFormField label="Product" required>
          <AppFormSelect v-model.number="form.product_id" required>
            <option :value="null">
              Select product
            </option>
            <option v-for="product in productsMeta" :key="product.id" :value="product.id">
              {{ product.name }}
            </option>
          </AppFormSelect>
        </AppFormField>

        <AppFormField label="Variation" required>
          <AppFormSelect v-model.number="form.product_variation_id" required>
            <option :value="null">
              Select variation
            </option>
            <option v-for="variation in filteredVariations" :key="variation.id" :value="variation.id">
              {{ variation.label }}
            </option>
          </AppFormSelect>
        </AppFormField>

        <AppFormField label="Quantity" required>
          <AppFormInput
            v-model.number="form.quantity"
            type="number"
            min="0"
            required
          />
        </AppFormField>

        <AppFormField label="Display Quantity" optional>
          <AppFormInput
            v-model.number="form.display_quantity"
            type="number"
            min="0"
            :max="form.quantity"
          />
          <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
            How many to release to the display shelf immediately (max: {{ form.quantity }}).
          </p>
        </AppFormField>

        <AppFormField label="Purchasing Price" required>
          <AppFormInput
            v-model.number="form.purchasing_price"
            type="number"
            min="0"
            step="0.01"
            required
          />
        </AppFormField>

        <AppFormField label="Local Selling Price" required>
          <AppFormInput
            v-model.number="form.local_selling_price"
            type="number"
            min="0"
            step="0.01"
            required
          />
        </AppFormField>

        <AppFormField label="Foreign Selling Price" required>
          <AppFormInput
            v-model.number="form.foreign_selling_price"
            type="number"
            min="0"
            step="0.01"
            required
          />
        </AppFormField>

        <AppFormField label="Manufacturing Date" optional>
          <AppFormInput v-model="form.manufacturing_date" type="date" />
        </AppFormField>

        <AppFormField label="Expiry Date" optional>
          <AppFormInput v-model="form.expiry_date" type="date" />
        </AppFormField>
      </div>

      <div class="mt-4 flex flex-col sm:flex-row gap-2">
        <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm">
          {{ isEdit ? 'Update Stock Entry' : 'Create Stock Entry' }}
        </button>
        <RouterLink
          to="/inventory?tab=stock"
          class="inline-flex items-center justify-center px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-200"
        >
          Cancel
        </RouterLink>
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => Boolean(route.params.id));

const errorMessage = ref('');
const productsMeta = ref([]);
const variationsMeta = ref([]);

const form = ref({
    product_id: null,
    product_variation_id: null,
    quantity: 1,
    display_quantity: 0,
    manufacturing_date: '',
    expiry_date: '',
    purchasing_price: 0,
    local_selling_price: 0,
    foreign_selling_price: 0,
});

const filteredVariations = computed(() => {
    if (!form.value.product_id) {
        return variationsMeta.value;
    }

    return variationsMeta.value.filter((variation) => variation.product_id === form.value.product_id);
});

watch(
    () => form.value.product_id,
    (productId) => {
        if (!productId) {
            form.value.product_variation_id = null;
            return;
        }

        const valid = filteredVariations.value.some((variation) => variation.id === form.value.product_variation_id);
        if (!valid) {
            form.value.product_variation_id = null;
        }
    }
);

async function loadMeta() {
    const response = await apiRequest('/api/inventory/meta');
    productsMeta.value = response.products || [];
    variationsMeta.value = response.variations || [];
}

async function loadStockEntry() {
    if (!isEdit.value) {
        return;
    }

    const response = await apiRequest(`/api/inventory/stock/${route.params.id}`);
    form.value = {
        product_id: response.data.product_id,
        product_variation_id: response.data.product_variation_id,
        quantity: response.data.quantity,
        display_quantity: response.data.display_quantity,
        manufacturing_date: response.data.manufacturing_date || '',
        expiry_date: response.data.expiry_date || '',
        purchasing_price: response.data.purchasing_price,
        local_selling_price: response.data.local_selling_price,
        foreign_selling_price: response.data.foreign_selling_price,
    };
}

async function load() {
    errorMessage.value = '';

    try {
        await loadMeta();
        await loadStockEntry();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load stock form data.';
    }
}

async function save() {
    errorMessage.value = '';

    try {
        const payload = {
            product_id: Number(form.value.product_id),
            product_variation_id: Number(form.value.product_variation_id),
            quantity: Number(form.value.quantity),
            display_quantity: Number(form.value.display_quantity ?? 0),
            manufacturing_date: form.value.manufacturing_date || null,
            expiry_date: form.value.expiry_date || null,
            purchasing_price: Number(form.value.purchasing_price),
            local_selling_price: Number(form.value.local_selling_price),
            foreign_selling_price: Number(form.value.foreign_selling_price),
        };

        if (isEdit.value) {
            await apiRequest(`/api/inventory/stock/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
        } else {
            await apiRequest('/api/inventory/stock', {
                method: 'post',
                data: payload,
            });
        }

        router.push('/inventory?tab=stock');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save stock entry.';
    }
}

onMounted(() => {
    load();
});
</script>
