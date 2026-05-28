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
        <!-- Account Balances -->
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
              />
              <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                {{ account.hint1Label }}: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ account.hint1 }}</span>
                <template v-if="account.hint2 != null">
                  &nbsp;|&nbsp;Expected: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ account.hint2 }}</span>
                </template>
              </p>
            </AppFormField>
          </div>
        </div>

        <!-- Stock section with Stock / Display tabs -->
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
                {{ product.hint1Label }}: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ product.hint1 }}</span>
                <template v-if="product.hint2 != null">
                  &nbsp;|&nbsp;Expected: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ product.hint2 }}</span>
                </template>
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
                {{ product.displayHint1Label }}: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ product.displayHint1 }}</span>
                <template v-if="product.displayHint2 != null">
                  &nbsp;|&nbsp;Expected: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ product.displayHint2 }}</span>
                </template>
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
            {{ submitting
              ? (isOpen ? 'Opening…' : 'Saving…')
              : (isOpen ? 'Open Session' : 'Review Comparison →') }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';

const route  = useRoute();
const router = useRouter();

const isOpen    = computed(() => !route.params.id);
const sessionId = computed(() => route.params.id ?? null);

const loading      = ref(true);
const submitting   = ref(false);
const errorMessage = ref('');
const stockTab     = ref('stock');

// Normalised item shape:
//  accounts:  { id, name, hint1Label, hint1, hint2 }
//  products:  { id, name, type, hint1Label, hint1, hint2, displayHint1Label, displayHint1, displayHint2 }
const accounts       = ref([]);
const products       = ref([]);
const accountEntries = ref({});
const stockEntries   = ref({});  // key: `${type}_${id}`
const displayEntries = ref({});  // key: `${type}_${id}` (stock/stock_variation prefix)

function displayTypeFor(type) {
    return type === 'stock_variation' ? 'stock_variation_display' : 'stock_display';
}

async function loadFormConfig() {
    loading.value = true;

    if (isOpen.value) {
        const data        = await apiRequest('/api/reconciliation/form-config');
        const rawAccounts = data?.accounts ?? [];
        const rawProducts = data?.products ?? [];

        accounts.value = rawAccounts.map(a => ({
            id:         a.id,
            name:       a.name,
            hint1Label: 'System balance',
            hint1:      a.current_value,
            hint2:      null,
        }));

        products.value = rawProducts.map(p => ({
            id:               p.id,
            name:             p.name,
            type:             p.type,
            hint1Label:       'System stock',
            hint1:            p.current_value,
            hint2:            null,
            displayHint1Label: 'System display',
            displayHint1:     p.current_display_value,
            displayHint2:     null,
        }));

        accounts.value.forEach(a => {
            accountEntries.value[a.id] = a.hint1 ?? '';
        });
        products.value.forEach(p => {
            const key = `${p.type}_${p.id}`;
            stockEntries.value[key]   = p.hint1 ?? '';
            displayEntries.value[key] = p.displayHint1 ?? '';
        });
    } else {
        const data  = await apiRequest(`/api/reconciliation/sessions/${sessionId.value}/preview`);
        const items = data?.items ?? [];

        const accountItems = items.filter(i => i.type === 'account');
        const stockItems   = items.filter(i => i.type === 'stock' || i.type === 'stock_variation');
        const displayMap   = Object.fromEntries(
            items
                .filter(i => i.type === 'stock_display' || i.type === 'stock_variation_display')
                .map(i => [
                    `${i.type === 'stock_variation_display' ? 'stock_variation' : 'stock'}_${i.reference_id}`,
                    i,
                ])
        );

        accounts.value = accountItems.map(i => ({
            id:         i.reference_id,
            name:       i.label,
            hint1Label: 'Opening',
            hint1:      i.opening_value,
            hint2:      i.expected_close,
        }));

        products.value = stockItems.map(i => {
            const key         = `${i.type}_${i.reference_id}`;
            const displayItem = displayMap[key] ?? null;
            return {
                id:               i.reference_id,
                name:             i.label,
                type:             i.type,
                hint1Label:       'Opening',
                hint1:            i.opening_value,
                hint2:            i.expected_close,
                displayHint1Label: 'Opening',
                displayHint1:     displayItem?.opening_value ?? null,
                displayHint2:     displayItem?.expected_close ?? null,
            };
        });

        accounts.value.forEach(a => {
            const raw = accountItems.find(i => i.reference_id === a.id);
            accountEntries.value[a.id] = raw?.actual_close ?? raw?.expected_close ?? '';
        });
        products.value.forEach(p => {
            const key         = `${p.type}_${p.id}`;
            const stockItem   = stockItems.find(i => i.type === p.type && i.reference_id === p.id);
            const displayItem = displayMap[key] ?? null;
            stockEntries.value[key]   = stockItem?.actual_close ?? stockItem?.expected_close ?? '';
            displayEntries.value[key] = displayItem?.actual_close ?? displayItem?.expected_close ?? '';
        });
    }

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
            type:          displayTypeFor(p.type),
            reference_id:  p.id,
            entered_value: displayEntries.value[`${p.type}_${p.id}`],
        })),
    ];

    try {
        if (isOpen.value) {
            const res = await apiRequest('/api/reconciliation/open', {
                method: 'POST',
                data:   { entries },
            });
            if (res?.session) {
                router.push('/reconciliation');
            }
        } else {
            await apiRequest(`/api/reconciliation/sessions/${sessionId.value}/save-close`, {
                method: 'POST',
                data:   { entries },
            });
            router.push(`/reconciliation/comparison/${sessionId.value}`);
        }
    } catch (err) {
        const errData = err?.response?.data;
        if (errData?.errors) {
            errorMessage.value = Object.values(errData.errors).flat().join(' ');
        } else {
            errorMessage.value = errData?.message ?? (isOpen.value ? 'Failed to open session.' : 'Failed to save entries.');
        }
    } finally {
        submitting.value = false;
    }
}

onMounted(loadFormConfig);
</script>
