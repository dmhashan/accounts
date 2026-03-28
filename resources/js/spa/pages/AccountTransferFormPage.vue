<template>
    <section>
        <div class="app-surface app-page-header-compact">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Accounts</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">{{ isEdit ? 'Edit Transfer' : 'New Transfer' }}</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Move balances from one company account to another with an auditable transfer record.</p>
                </div>
                <RouterLink :to="{ path: '/accounts', query: { tab: 'transfers' } }" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm font-semibold transition-all hover:bg-secondary-50 dark:hover:bg-secondary-800">
                    ← Back to Transfers
                </RouterLink>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Source Account</label>
                    <select v-model="form.source_account_id" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                        <option value="">Select source account</option>
                        <option v-for="account in accounts" :key="account.id" :value="String(account.id)">{{ accountOptionLabel(account) }}</option>
                    </select>
                    <p v-if="sourceAccount" class="mt-2 text-xs text-secondary-500 dark:text-secondary-400">Available balance: {{ money(sourceAccount.current_balance) }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Destination Account</label>
                    <select v-model="form.destination_account_id" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                        <option value="">Select destination account</option>
                        <option v-for="account in accounts" :key="account.id" :value="String(account.id)" :disabled="String(account.id) === String(form.source_account_id)">{{ accountOptionLabel(account) }}</option>
                    </select>
                    <p v-if="destinationAccount" class="mt-2 text-xs text-secondary-500 dark:text-secondary-400">Current balance: {{ money(destinationAccount.current_balance) }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Amount</label>
                    <input v-model="form.amount" type="number" min="0.01" step="0.01" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Transfer Date</label>
                    <input v-model="form.transfer_date" type="date" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Reference Number</label>
                    <input v-model="form.reference_number" type="text" maxlength="255" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300">Notes</label>
                        <button type="button" @click="insertDenominationTemplate" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">Insert Cash Count Template</button>
                    </div>
                    <textarea v-model="form.notes" rows="4" maxlength="1000" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"></textarea>
                    <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">Format: denomination - count, e.g. 5000 - 3, 2000 - 5, etc.</p>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <RouterLink :to="{ path: '/accounts', query: { tab: 'transfers' } }" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm">Cancel</RouterLink>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update Transfer' : 'Create Transfer') }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');
const accounts = ref([]);

const form = ref({
    source_account_id: '',
    destination_account_id: '',
    amount: '',
    transfer_date: new Date().toISOString().slice(0, 10),
    reference_number: '',
    notes: '',
});

const sourceAccount = computed(() => accounts.value.find((account) => String(account.id) === String(form.value.source_account_id)) || null);
const destinationAccount = computed(() => accounts.value.find((account) => String(account.id) === String(form.value.destination_account_id)) || null);

function money(value) {
    return Number(value || 0).toFixed(2);
}

function accountOptionLabel(account) {
    return `${account.name} • ${money(account.current_balance)}`;
}

function insertDenominationTemplate() {
    const template = `5000 x 0
2000 x 0
1000 x 0
500 x 0
100 x 0
50 x 0
20 x 0`;
    form.value.notes = form.value.notes ? form.value.notes + '\n' + template : template;
}

async function loadMeta() {
    const response = await apiRequest('/api/accounts/meta');
    accounts.value = response.accounts || [];
}

async function loadTransfer() {
    if (!isEdit.value) {
        return;
    }

    const response = await apiRequest(`/api/accounts/transfers/${route.params.id}`);
    const transfer = response.data || {};

    form.value = {
        source_account_id: transfer.source_account_id ? String(transfer.source_account_id) : '',
        destination_account_id: transfer.destination_account_id ? String(transfer.destination_account_id) : '',
        amount: transfer.amount != null ? String(transfer.amount) : '',
        transfer_date: transfer.transfer_date || new Date().toISOString().slice(0, 10),
        reference_number: transfer.reference_number || '',
        notes: transfer.notes || '',
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            source_account_id: form.value.source_account_id,
            destination_account_id: form.value.destination_account_id,
            amount: form.value.amount,
            transfer_date: form.value.transfer_date,
            reference_number: form.value.reference_number || null,
            notes: form.value.notes || null,
        };

        if (isEdit.value) {
            await apiRequest(`/api/accounts/transfers/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
        } else {
            await apiRequest('/api/accounts/transfers', {
                method: 'post',
                data: payload,
            });
        }

        router.push({ path: '/accounts', query: { tab: 'transfers' } });
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save transfer.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadTransfer();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load transfer data.';
    }
});
</script>