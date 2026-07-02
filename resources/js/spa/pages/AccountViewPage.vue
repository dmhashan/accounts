<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/settings/accounts/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Account
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteAccount"
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
          {{ account.name }}
        </h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Opening Balance
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ money(account.opening_balance) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Current Balance
            </p>
            <p class="font-bold text-lg text-secondary-900 dark:text-white">
              {{ money(account.current_balance) }}
            </p>
          </div>
        </div>

        <div v-if="account.description" class="mt-4">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Description
          </p>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ account.description }}
          </p>
        </div>
      </div>

      <div class="app-surface rounded-2xl overflow-hidden">
        <div class="px-4 py-3 md:px-6 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
            Pending Payment Settlements
          </h2>
          <button
            type="button"
            class="text-xs text-primary-600 dark:text-primary-400 hover:underline disabled:opacity-50"
            :disabled="settlementsLoading"
            @click="loadSettlements"
          >
            Refresh
          </button>
        </div>

        <div v-if="settlementsError" class="px-4 py-3 text-sm text-red-600 dark:text-red-400">
          {{ settlementsError }}
        </div>
        <div v-else-if="settlementsLoading" class="px-4 py-6 text-sm text-secondary-500 dark:text-secondary-400">
          Loading settlements...
        </div>
        <div v-else-if="pendingSettlements.length === 0" class="px-4 py-6 text-sm text-secondary-500 dark:text-secondary-400">
          No pending payment settlements.
        </div>
        <div v-else class="app-table-scroll">
          <table class="w-full">
            <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                  Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                  Method
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                  Record
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                  Gross
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                  Fee
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                  Net
                </th>
                <th class="px-6 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
              <tr v-for="settlement in pendingSettlements" :key="settlement.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">
                  {{ settlement.payment_date || '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">
                  {{ settlement.payment_method_name }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <RouterLink
                    v-if="settlement.source_path"
                    :to="settlement.source_path"
                    class="text-primary-600 dark:text-primary-400 hover:underline"
                  >
                    {{ settlement.source_label }}
                  </RouterLink>
                  <span v-else>{{ settlement.source_label }}</span>
                  <p v-if="settlement.customer" class="text-xs text-secondary-500 dark:text-secondary-400">
                    {{ settlement.customer }}
                  </p>
                </td>
                <td class="px-6 py-4 text-sm text-right text-secondary-700 dark:text-secondary-300">
                  {{ money(settlement.gross_amount) }}
                </td>
                <td class="px-6 py-4 text-sm text-right text-secondary-700 dark:text-secondary-300">
                  {{ money(settlement.deduction_amount) }}
                </td>
                <td class="px-6 py-4 text-sm text-right font-semibold text-secondary-900 dark:text-white">
                  {{ money(settlement.net_amount) }}
                </td>
                <td class="px-6 py-4 text-right">
                  <button
                    type="button"
                    class="px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold disabled:opacity-50"
                    :disabled="confirming"
                    @click="openConfirmSettlement(settlement)"
                  >
                    Confirm
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="confirmSettlement" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-secondary-200 dark:border-secondary-700">
          <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
            Confirm Settlement
          </h3>
          <button type="button" class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200" @click="confirmSettlement = null">
            x
          </button>
        </div>

        <form class="p-5 space-y-4" @submit.prevent="submitConfirmSettlement">
          <div class="rounded-xl bg-secondary-50 dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-700 dark:text-secondary-200">
            <p>{{ confirmSettlement.payment_method_name }} - {{ confirmSettlement.source_label }}</p>
            <p>Net: <span class="font-semibold">{{ money(confirmSettlement.net_amount) }}</span></p>
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Received Date</label>
            <input
              v-model="confirmForm.transaction_date"
              type="date"
              class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Reference</label>
            <input
              v-model="confirmForm.confirmation_reference"
              type="text"
              maxlength="255"
              class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Notes</label>
            <textarea
              v-model="confirmForm.confirmation_notes"
              rows="3"
              maxlength="1000"
              class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
            />
          </div>

          <div class="flex justify-end gap-2">
            <button type="button" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm" @click="confirmSettlement = null">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50" :disabled="confirming">
              {{ confirming ? 'Confirming...' : 'Confirm Payment' }}
            </button>
          </div>
        </form>
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
const account = ref({});
const deleting = ref(false);
const settlementsLoading = ref(false);
const settlementsError = ref('');
const pendingSettlements = ref([]);
const confirmSettlement = ref(null);
const confirming = ref(false);
const confirmForm = ref({
    transaction_date: new Date().toISOString().slice(0, 10),
    confirmation_reference: '',
    confirmation_notes: '',
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function deleteAccount() {
    if (!confirm(`Delete account "${account.value.name}"?`)) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/accounts/${route.params.id}`, { method: 'DELETE' });
        router.push('/settings/accounts');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete account.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/accounts/${route.params.id}`);
        account.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load account.';
    } finally {
        loading.value = false;
    }
}

async function loadSettlements() {
    settlementsLoading.value = true;
    settlementsError.value = '';
    try {
        const response = await apiRequest(`/api/accounts/${route.params.id}/payment-settlements`, {
            params: { status: 'pending', per_page: 50 },
        });
        pendingSettlements.value = response.data || [];
    } catch {
        settlementsError.value = 'Failed to load payment settlements.';
    } finally {
        settlementsLoading.value = false;
    }
}

function openConfirmSettlement(settlement) {
    confirmSettlement.value = settlement;
    confirmForm.value = {
        transaction_date: new Date().toISOString().slice(0, 10),
        confirmation_reference: settlement.reference_number || '',
        confirmation_notes: '',
    };
}

async function submitConfirmSettlement() {
    if (!confirmSettlement.value) return;
    confirming.value = true;
    try {
        await apiRequest(`/api/accounts/payment-settlements/${confirmSettlement.value.id}/confirm`, {
            method: 'post',
            data: confirmForm.value,
        });
        confirmSettlement.value = null;
        await Promise.all([load(), loadSettlements()]);
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to confirm settlement.');
    } finally {
        confirming.value = false;
    }
}

onMounted(() => {
    load();
    loadSettlements();
});
</script>
