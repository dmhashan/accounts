<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <template v-if="sale.id && !sale.is_paid">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
            :disabled="payNowLoading"
            @click="openPayNow"
          >
            Pay Now
          </button>
          <RouterLink
            v-if="permissions.edit"
            :to="`/sales/${route.params.id}/edit`"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
          >
            Edit Sale
          </RouterLink>
          <button
            v-if="permissions.delete"
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
            :disabled="deleting"
            @click="deleteSale"
          >
            Delete
          </button>
        </template>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll">
      <SaleInvoicePreviewCard v-if="sale.id" :sale="sale" />
    </div>

    <!-- Pay Now Modal -->
    <div v-if="payNowOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closePayNow" />
      <div class="relative z-10 w-full max-w-md rounded-2xl app-surface p-4 md:p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Pay Outstanding Sale
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
              Select the company account that received payment for sale #{{ sale.id }}.
            </p>
          </div>
          <button type="button" class="text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closePayNow">
            ✕
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div class="rounded-xl app-surface-soft px-3 py-2 text-sm text-secondary-700 dark:text-secondary-200">
            <p>Total: <span class="font-semibold">{{ money(sale.total_amount) }}</span></p>
            <p>Customer: <span class="font-semibold">{{ sale.customer_name || 'Walk-in' }}</span></p>
          </div>

          <AppFormField label="Company Account">
            <AppCompanyAccountSelect
              v-model="selectedAccountId"
              :accounts="companyAccounts"
              :member-id="sale.customer_member_id ?? undefined"
              :amount="Number(sale.total_amount || 0)"
            />
          </AppFormField>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <button type="button" class="px-4 py-2 text-sm rounded-xl border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closePayNow">
            Cancel
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white disabled:opacity-50"
            :disabled="payNowLoading || !selectedAccountId"
            @click="confirmPayNow"
          >
            {{ payNowLoading ? 'Processing...' : 'Confirm Payment' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppCompanyAccountSelect from '../components/forms/AppCompanyAccountSelect.vue';
import SaleInvoicePreviewCard from '../components/SaleInvoicePreviewCard.vue';

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const loading = ref(false);
const errorMessage = ref('');
const sale = ref({});
const deleting = ref(false);
const payNowOpen = ref(false);
const payNowLoading = ref(false);
const companyAccounts = ref([]);
const selectedAccountId = ref(null);
const permissions = ref({
    edit: Boolean(context.permissions?.salesEdit),
    delete: Boolean(context.permissions?.salesDelete),
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function openPayNow() {
    payNowOpen.value = true;
}

function closePayNow() {
    if (payNowLoading.value) return;
    payNowOpen.value = false;
}

async function confirmPayNow() {
    if (!selectedAccountId.value) return;
    payNowLoading.value = true;
    try {
        const isWallet = selectedAccountId.value === 'member_wallet';
        await apiRequest(`/api/sales/${route.params.id}/mark-as-paid`, {
            method: 'post',
            data: isWallet
                ? { payment_method: 'member_wallet' }
                : { account_id: Number(selectedAccountId.value) },
        });
        payNowOpen.value = false;
        await load();
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to mark sale as paid.');
    } finally {
        payNowLoading.value = false;
    }
}

async function deleteSale() {
    if (!confirm('Delete this sale?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/sales/${route.params.id}`, { method: 'DELETE' });
        router.push('/sales');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete sale.');
    } finally {
        deleting.value = false;
    }
}

async function loadMeta() {
    try {
        const response = await apiRequest('/api/sales/meta');
        companyAccounts.value = response.accounts || [];
        if (companyAccounts.value.length > 0) {
            selectedAccountId.value = companyAccounts.value[0].id;
        }
    } catch {
        // non-critical
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/sales/${route.params.id}`);
        sale.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load sale.';
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await Promise.all([load(), loadMeta()]);
});
</script>
