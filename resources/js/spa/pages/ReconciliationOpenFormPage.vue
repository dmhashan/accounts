<template>
  <section class="app-page-frame">
    <AppPageHeader show-back />

    <div class="app-page-scroll">
      <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <div v-if="loading" class="app-surface rounded-2xl p-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
        Loading form…
      </div>

      <form v-else class="space-y-4" @submit.prevent="submit">
        <!-- Accounts section -->
        <div v-if="accounts.length" class="app-surface rounded-2xl p-5 md:p-6 space-y-4">
          <h2 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide">
            Account Balances
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <AppFormField
              v-for="account in accounts"
              :key="account.id"
              :label="account.name"
              required
            >
              <AppFormInput
                v-model.number="accountEntries[account.id]"
                type="number"
                min="0"
                step="0.01"
                required
                :placeholder="`Enter ${account.name} balance`"
              />
              <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                System current balance: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ account.current_value }}</span>
              </p>
            </AppFormField>
          </div>
        </div>

        <!-- Stock section -->
        <div v-if="products.length" class="app-surface rounded-2xl p-5 md:p-6 space-y-4">
          <!-- Tab switcher -->
          <div class="flex items-center gap-1 border-b border-secondary-200 dark:border-secondary-700 -mx-5 md:-mx-6 px-5 md:px-6">
            <button
              type="button"
              class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors"
              :class="stockTab === 'stock'
                ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                : 'border-transparent text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300'"
              @click="stockTab = 'stock'"
            >
              Stock
            </button>
            <button
              type="button"
              class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors"
              :class="stockTab === 'display'
                ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                : 'border-transparent text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300'"
              @click="stockTab = 'display'"
            >
              Display
            </button>
          </div>

          <!-- Stock tab -->
          <div v-if="stockTab === 'stock'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <AppFormField
              v-for="product in products"
              :key="`stock_${product.type}_${product.id}`"
              :label="product.name"
              required
            >
              <AppFormInput
                v-model.number="stockEntries[`${product.type}_${product.id}`]"
                type="number"
                min="0"
                required
                :placeholder="`Enter ${product.name} quantity`"
              />
              <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                System stock: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ product.current_value }}</span>
              </p>
            </AppFormField>
          </div>

          <!-- Display tab -->
          <div v-if="stockTab === 'display'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <AppFormField
              v-for="product in products"
              :key="`display_${product.type}_${product.id}`"
              :label="product.name"
              required
            >
              <AppFormInput
                v-model.number="displayEntries[`${product.type}_${product.id}`]"
                type="number"
                min="0"
                required
                :placeholder="`Enter ${product.name} display quantity`"
              />
              <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                System display: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ product.current_display_value }}</span>
              </p>
            </AppFormField>
          </div>
        </div>

        <div v-if="!loading && accounts.length === 0 && products.length === 0" class="app-surface rounded-2xl p-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
          No reconciliation items configured for your role. Contact your admin.
        </div>

        <div v-if="accounts.length || products.length" class="flex items-center justify-end gap-3">
          <RouterLink to="/reconciliation" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300">
            Cancel
          </RouterLink>
          <button
            type="submit"
            class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium"
            :disabled="submitting"
          >
            {{ submitting ? 'Opening…' : 'Open Session' }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';

const router       = useRouter();
const loading      = ref(true);
const submitting   = ref(false);
const errorMessage = ref('');
const stockTab     = ref('stock');

const accounts      = ref([]);
const products      = ref([]);
const accountEntries = ref({}); // { [account_id]: value }
const stockEntries   = ref({}); // { [`${type}_${id}`]: value }
const displayEntries = ref({}); // { [`${type}_${id}`]: value } for display_quantity

async function loadFormConfig() {
    loading.value = true;
    const data = await apiRequest('/api/reconciliation/form-config');
    accounts.value = data?.accounts ?? [];
    products.value = data?.products ?? [];
    // Initialise entry maps to empty string so v-model works
    accounts.value.forEach(a => { accountEntries.value[a.id] = a.current_value ?? ''; });
    products.value.forEach(p  => {
        stockEntries.value[`${p.type}_${p.id}`]   = p.current_value ?? '';
        displayEntries.value[`${p.type}_${p.id}`] = p.current_display_value ?? '';
    });
    loading.value = false;
}

async function submit() {
    errorMessage.value = '';
    submitting.value   = true;

    const entries = [
        ...accounts.value.map(a => ({
            type:          'account',
            reference_id:  a.id,
            entered_value: accountEntries.value[a.id],
        })),
        ...products.value.map(p => ({
            type:          p.type,
            reference_id:  p.id,
            entered_value: stockEntries.value[`${p.type}_${p.id}`],
        })),
        ...products.value.map(p => ({
            type:          p.type === 'stock_variation' ? 'stock_variation_display' : 'stock_display',
            reference_id:  p.id,
            entered_value: displayEntries.value[`${p.type}_${p.id}`],
        })),
    ];

    try {
        const res = await apiRequest('/api/reconciliation/open', {
            method: 'POST',
            data:   { entries },
        });
        if (res?.session) {
            router.push('/reconciliation');
        }
    } catch (err) {
        const errData = err?.response?.data;
        if (errData?.errors) {
            const messages = Object.values(errData.errors).flat();
            errorMessage.value = messages.join(' ');
        } else {
            errorMessage.value = errData?.message ?? 'Failed to open session.';
        }
    } finally {
        submitting.value = false;
    }
}

onMounted(loadFormConfig);
</script>
