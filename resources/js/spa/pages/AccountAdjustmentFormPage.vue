<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <AppHeaderAction
          to="/accounting/adjustments"
          :icon="ArrowRightLeft"
          label="Adjustments"
          variant="secondary"
        />
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppFormField label="Account to Adjust">
            <AppFormSelect v-model="form.company_account_id">
              <option value="">
                Select account
              </option>
              <option v-for="account in accounts" :key="account.id" :value="String(account.id)">
                {{ accountOptionLabel(account) }}
              </option>
            </AppFormSelect>
            <p v-if="selectedAccount" class="mt-2 text-xs text-secondary-500 dark:text-secondary-400">
              Current balance: {{ money(selectedAccount.current_balance) }}
            </p>
          </AppFormField>

          <AppFormField label="Adjustment Type">
            <AppFormSelect v-model="form.type">
              <option value="credit">
                Credit (Increase Balance)
              </option>
              <option value="debit">
                Debit (Decrease Balance)
              </option>
            </AppFormSelect>
          </AppFormField>

          <AppFormField label="Amount">
            <AppFormInput
              v-model="form.amount"
              type="number"
              min="0.01"
              step="0.01"
              required
            />
          </AppFormField>

          <AppFormField label="Adjustment Date">
            <AppFormDateInput v-model="form.adjustment_date" required />
          </AppFormField>

          <AppFormField label="Reason" class="md:col-span-2">
            <AppFormTextarea
              v-model="form.reason"
              rows="4"
              maxlength="1000"
              required
            />
            <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
              Please explain why this adjustment is being made.
            </p>
          </AppFormField>
        </div>

        <div class="mt-5 flex items-center justify-between gap-2">
          <div>
            <button
              v-if="isEdit"
              type="button"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm disabled:opacity-50"
              :disabled="submitting || deleting"
              @click="destroy"
            >
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
          <div class="flex items-center gap-2">
            <RouterLink to="/accounting/adjustments" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm">
              Cancel
            </RouterLink>
            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm" :disabled="submitting || deleting">
              {{ submitting ? 'Saving...' : (isEdit ? 'Update Adjustment' : 'Create Adjustment') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowRightLeft } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormDateInput from '../components/forms/AppFormDateInput.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const deleting = ref(false);
const errorMessage = ref('');
const accounts = ref([]);

const form = ref({
    company_account_id: '',
    type: 'credit',
    amount: '',
    reason: '',
    adjustment_date: new Date().toISOString().slice(0, 10),
});

const selectedAccount = computed(() => accounts.value.find((account) => String(account.id) === String(form.value.company_account_id)) || null);

function money(value) {
    return Number(value || 0).toFixed(2);
}

function accountOptionLabel(account) {
    return `${account.name} • ${money(account.current_balance)}`;
}

async function loadMeta() {
    const response = await apiRequest('/api/accounts/meta');
    accounts.value = response.accounts || [];
}

async function loadAdjustment() {
    if (!isEdit.value) {
        return;
    }

    const response = await apiRequest(`/api/accounts/adjustments/${route.params.id}`);
    const adj = response.data || {};

    form.value = {
        company_account_id: adj.company_account_id ? String(adj.company_account_id) : '',
        type: adj.type || 'credit',
        amount: adj.amount !== null ? String(adj.amount) : '',
        reason: adj.reason || '',
        adjustment_date: adj.adjustment_date || new Date().toISOString().slice(0, 10),
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            company_account_id: form.value.company_account_id,
            type: form.value.type,
            amount: form.value.amount,
            reason: form.value.reason,
            adjustment_date: form.value.adjustment_date,
        };

        if (isEdit.value) {
            await apiRequest(`/api/accounts/adjustments/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
        } else {
            await apiRequest('/api/accounts/adjustments', {
                method: 'post',
                data: payload,
            });
        }

        router.push('/accounting/adjustments');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save adjustment.';
    } finally {
        submitting.value = false;
    }
}

async function destroy() {
    if (!confirm('Are you sure you want to delete this adjustment? This action cannot be undone.')) {
        return;
    }

    deleting.value = true;
    errorMessage.value = '';

    try {
        await apiRequest(`/api/accounts/adjustments/${route.params.id}`, {
            method: 'delete',
        });
        router.push('/accounting/adjustments');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete adjustment.';
    } finally {
        deleting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadAdjustment();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load metadata.';
    }
});
</script>
