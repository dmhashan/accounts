<template>
  <section class="app-page-frame">
    <AppPageHeader show-back />

    <div class="app-page-scroll">
      <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AppFormField label="Member" optional class="md:col-span-2">
            <AppSearchableDropdown
              v-model="form.member_id"
              :options="members"
              :option-label="option => option.label"
              :option-key="option => option.id"
              placeholder="Select member..."
              search-placeholder="Search member..."
              no-results-text="No members found."
              @update:model-value="onMemberSelect"
            />
          </AppFormField>

          <div v-if="selectedMember" class="md:col-span-2 rounded-lg border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/20 px-4 py-3">
            <p class="text-sm font-semibold text-primary-800 dark:text-primary-200">
              {{ selectedMember.name }}
            </p>
            <p class="text-xs text-primary-600 dark:text-primary-400">
              {{ selectedMember.phone_number }}
              <span v-if="selectedMember.payment_plan"> &bull; {{ selectedMember.payment_plan }}</span>
              <span v-if="selectedMember.price > 0"> &bull; {{ money(selectedMember.price) }}</span>
            </p>
          </div>

          <AppFormField label="Account" required>
            <AppCompanyAccountSelect
              v-model="selectedAccountValue"
              :accounts="accounts"
              :member-id="form.member_id ?? undefined"
              :amount="parseFloat(form.amount) || 0"
            />
          </AppFormField>

          <AppFormField label="Payment Plan" optional>
            <select
              v-model="form.payment_plan_id"
              class="w-full px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              @change="onPlanSelect"
            >
              <option :value="null">
                No plan
              </option>
              <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                {{ plan.name }} — {{ formatDuration(plan.duration_days) }} ({{ money(plan.price) }})
              </option>
            </select>
          </AppFormField>

          <AppFormField label="Amount" required>
            <AppFormInput
              v-model="form.amount"
              type="number"
              min="0.01"
              step="0.01"
              required
            />
          </AppFormField>

          <AppFormField label="Payment Date" required>
            <AppFormDateInput
              v-model="form.payment_date"
              required
              @change="onPaymentDateChange"
            />
          </AppFormField>

          <AppFormField label="Membership Start Date" optional>
            <AppFormDateInput v-model="form.start_date" @change="onStartDateChange" />
          </AppFormField>

          <AppFormField label="Membership End Date" optional>
            <AppFormDateInput v-model="form.end_date" />
          </AppFormField>

          <AppFormField label="Reference" help="Receipt ID, transaction reference, etc." optional>
            <AppFormInput
              v-model="form.reference_number"
              type="text"
              maxlength="255"
              placeholder="Receipt ID, transaction reference, etc."
            />
          </AppFormField>

          <AppFormField label="Notes" class="md:col-span-2" optional>
            <AppFormTextarea v-model="form.notes" rows="3" maxlength="1000" />
          </AppFormField>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <RouterLink to="/payments" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300">
            Cancel
          </RouterLink>
          <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm disabled:opacity-50" :disabled="submitDisabled">
            {{ submitting ? 'Saving...' : (isEdit ? 'Update Payment' : 'Record Payment') }}
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
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormDateInput from '../components/forms/AppFormDateInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';
import AppCompanyAccountSelect from '../components/forms/AppCompanyAccountSelect.vue';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');
const members = ref([]);
const accounts = ref([]);
const plans = ref([]);
// Combined account selector: holds account ID (number) or 'member_wallet'
const selectedAccountValue = ref(null);

const form = ref({
    member_id: null,
    payment_plan_id: null,
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    start_date: '',
    end_date: '',
    reference_number: '',
    notes: '',
});

const selectedMember = computed(() => {
    if (!form.value.member_id) return null;
    return members.value.find(m => m.id === form.value.member_id) || null;
});

const submitDisabled = computed(() => {
    if (submitting.value || !form.value.amount) return true;
    if (!selectedAccountValue.value) return true;
    return false;
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function formatDuration(days) {
    if (days === 1)   return '1 day';
    if (days === 30)  return '1 month';
    if (days === 90)  return '3 months';
    if (days === 180) return '6 months';
    if (days === 365) return '1 year';
    return `${days} days`;
}

function calcEndDate(startDate, durationDays) {
    if (!startDate || !durationDays) return '';
    const d = new Date(startDate);
    d.setDate(d.getDate() + durationDays - 1);
    return d.toISOString().slice(0, 10);
}

function onPlanSelect() {
    const plan = plans.value.find(p => p.id === form.value.payment_plan_id);
    if (plan) {
        if (!form.value.amount) form.value.amount = String(plan.price);
        if (!form.value.start_date) form.value.start_date = form.value.payment_date || new Date().toISOString().slice(0, 10);
        form.value.end_date = calcEndDate(form.value.start_date, plan.duration_days);
    }
}

function onPaymentDateChange() {
    if (!form.value.start_date) {
        form.value.start_date = form.value.payment_date;
        onStartDateChange();
    }
}

function onStartDateChange() {
    const plan = plans.value.find(p => p.id === form.value.payment_plan_id);
    if (plan) {
        form.value.end_date = calcEndDate(form.value.start_date, plan.duration_days);
    }
}

async function onMemberSelect(memberId) {
    const member = members.value.find(m => m.id === memberId);
    if (member && member.price > 0 && !form.value.amount) {
        form.value.amount = String(member.price);
    }
    // Reset account selection on member change
    if (selectedAccountValue.value === 'member_wallet') {
        selectedAccountValue.value = accounts.value[0]?.id ?? null;
    }
}

async function loadMeta() {
    const response = await apiRequest('/api/payments/meta');
    members.value = response.members || [];
    accounts.value = response.accounts || [];
    plans.value = (response.plans || []).filter(p => p.is_active !== false);
    if (!isEdit.value && accounts.value.length > 0) {
        selectedAccountValue.value = accounts.value[0].id;
    }
}

async function loadPayment() {
    if (!isEdit.value) return;

    const response = await apiRequest(`/api/payments/${route.params.id}`);
    const payment = response.data || {};

    form.value = {
            member_id: payment.member_id ?? null,
            payment_plan_id: payment.payment_plan_id ?? null,
            amount: payment.amount !== null ? String(payment.amount) : '',
            payment_date: payment.payment_date || new Date().toISOString().slice(0, 10),
            start_date: payment.start_date || '',
            end_date: payment.end_date || '',
            reference_number: payment.reference_number || '',
            notes: payment.notes || '',
        };
        selectedAccountValue.value = payment.company_account_id ?? null;
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const isWallet = selectedAccountValue.value === 'member_wallet';
        const payload = {
            member_id: form.value.member_id,
            company_account_id: isWallet ? null : selectedAccountValue.value,
            payment_method: isWallet ? 'member_wallet' : 'cash',
            payment_plan_id: form.value.payment_plan_id || null,
            amount: form.value.amount,
            payment_date: form.value.payment_date,
            start_date: form.value.start_date || null,
            end_date: form.value.end_date || null,
            reference_number: form.value.reference_number || null,
            notes: form.value.notes || null,
        };

        if (isEdit.value) {
            await apiRequest(`/api/payments/${route.params.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/payments', { method: 'post', data: payload });
        }

        router.push('/payments');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save payment.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadPayment();
    } catch {
        errorMessage.value = 'Failed to load data.';
    }
});
</script>
