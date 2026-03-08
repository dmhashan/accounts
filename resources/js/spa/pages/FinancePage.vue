<template>
    <section class="space-y-6">
        <div>
            <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Accounts</h2>
            <p class="text-sm text-secondary-600 dark:text-secondary-400">Manage company account transactions.</p>
        </div>

        <div v-if="pageError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ pageError }}
        </div>

        <div v-if="pageSuccess" class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-300">
            {{ pageSuccess }}
        </div>

        <div v-if="!canManageAccounts" class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-6">
            <p class="text-sm text-secondary-700 dark:text-secondary-300">You do not have permission to access company account tools.</p>
        </div>

        <div v-else class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-6 space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Accounts</h3>
                <p class="text-sm text-secondary-600 dark:text-secondary-400">Create accounts and record credit/debit transactions.</p>
            </div>

            <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-4 space-y-3">
                <p class="text-sm font-semibold text-secondary-900 dark:text-white">Create Account</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input
                        v-model="createAccountForm.account_name"
                        type="text"
                        placeholder="Account name"
                        class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                    >
                    <input
                        v-model.number="createAccountForm.initial_balance"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Initial balance"
                        class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                    >
                    <button
                        type="button"
                        class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="accountCreating"
                        @click="submitCreateAccount"
                    >
                        {{ accountCreating ? 'Creating...' : 'Create Account' }}
                    </button>
                </div>
                <input
                    v-model="createAccountForm.description"
                    type="text"
                    placeholder="Description (optional)"
                    class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                >
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-1 rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">Accounts</p>
                    </div>

                    <div v-if="accountsLoading" class="p-4 text-sm text-secondary-500 dark:text-secondary-400">Loading accounts...</div>
                    <div v-else-if="accounts.length === 0" class="p-4 text-sm text-secondary-500 dark:text-secondary-400">No company accounts available.</div>
                    <div v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
                        <button
                            v-for="account in accounts"
                            :key="account.id"
                            type="button"
                            class="w-full text-left px-4 py-3 transition-colors"
                            :class="selectedAccountId === account.id
                                ? 'bg-primary-50 dark:bg-primary-900/20'
                                : 'hover:bg-secondary-50 dark:hover:bg-secondary-800/40'"
                            @click="selectedAccountId = account.id"
                        >
                            <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">{{ account.account_name }}</p>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">Balance: {{ money(account.current_balance) }}</p>
                        </button>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-4">
                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-4 space-y-3">
                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">Record Transaction</p>

                        <div v-if="!selectedAccountId" class="text-sm text-secondary-500 dark:text-secondary-400">Select an account first.</div>
                        <template v-else>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <select
                                    v-model="accountTransactionForm.transaction_type"
                                    class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                                >
                                    <option value="credit">Credit</option>
                                    <option value="debit">Debit</option>
                                </select>
                                <input
                                    v-model.number="accountTransactionForm.amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="Amount"
                                    class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                                >
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="accountTransactionSubmitting"
                                    @click="submitAccountTransaction"
                                >
                                    {{ accountTransactionSubmitting ? 'Saving...' : 'Save Transaction' }}
                                </button>
                            </div>
                            <input
                                v-model="accountTransactionForm.description"
                                type="text"
                                placeholder="Description (optional)"
                                class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white"
                            >
                        </template>
                    </div>

                    <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                        <div class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50 flex items-center justify-between">
                            <p class="text-sm font-semibold text-secondary-900 dark:text-white">Recent Transactions</p>
                            <span v-if="selectedAccountDetails" class="text-xs text-secondary-500 dark:text-secondary-400">Current: {{ money(selectedAccountDetails.current_balance) }}</span>
                        </div>

                        <div v-if="accountDetailsLoading" class="p-4 text-sm text-secondary-500 dark:text-secondary-400">Loading transactions...</div>
                        <div v-else-if="!selectedAccountDetails" class="p-4 text-sm text-secondary-500 dark:text-secondary-400">Select an account to view transactions.</div>
                        <template v-else>
                            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                                <article
                                    v-for="transaction in selectedAccountDetails.transactions"
                                    :key="transaction.id"
                                    class="p-4 space-y-2"
                                >
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-secondary-900 dark:text-white capitalize">{{ transaction.transaction_type }}</p>
                                        <p class="text-sm text-secondary-900 dark:text-white">{{ money(transaction.amount) }}</p>
                                    </div>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400">Before: {{ money(transaction.balance_before) }} | After: {{ money(transaction.balance_after) }}</p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ formatDate(transaction.transaction_date) }}</p>
                                    <p v-if="transaction.description" class="text-xs text-secondary-600 dark:text-secondary-300">{{ transaction.description }}</p>
                                </article>

                                <div v-if="selectedAccountDetails.transactions.length === 0" class="p-4 text-sm text-secondary-500 dark:text-secondary-400">No transactions yet.</div>
                            </div>

                            <div class="hidden md:block overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Amount</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Before</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">After</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                        <tr v-for="transaction in selectedAccountDetails.transactions" :key="transaction.id">
                                            <td class="px-4 py-3 text-sm text-secondary-900 dark:text-white capitalize">{{ transaction.transaction_type }}</td>
                                            <td class="px-4 py-3 text-sm text-secondary-900 dark:text-white">{{ money(transaction.amount) }}</td>
                                            <td class="px-4 py-3 text-sm text-secondary-700 dark:text-secondary-300">{{ money(transaction.balance_before) }}</td>
                                            <td class="px-4 py-3 text-sm text-secondary-700 dark:text-secondary-300">{{ money(transaction.balance_after) }}</td>
                                            <td class="px-4 py-3 text-sm text-secondary-700 dark:text-secondary-300">{{ formatDate(transaction.transaction_date) }}</td>
                                            <td class="px-4 py-3 text-sm text-secondary-700 dark:text-secondary-300">{{ transaction.description || '--' }}</td>
                                        </tr>
                                        <tr v-if="selectedAccountDetails.transactions.length === 0">
                                            <td colspan="6" class="px-4 py-6 text-center text-sm text-secondary-500 dark:text-secondary-400">No transactions yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();

const canManageAccounts = computed(() => Boolean(context.permissions?.settings));

const pageError = ref('');
const pageSuccess = ref('');

const accounts = ref([]);
const selectedAccountId = ref(null);
const selectedAccountDetails = ref(null);
const accountsLoading = ref(false);
const accountDetailsLoading = ref(false);
const accountCreating = ref(false);
const accountTransactionSubmitting = ref(false);

const createAccountForm = ref({
    account_name: '',
    description: '',
    initial_balance: 0,
});

const accountTransactionForm = ref({
    transaction_type: 'credit',
    amount: null,
    description: '',
});

watch(selectedAccountId, () => {
    loadSelectedAccountDetails();
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function formatDate(value) {
    if (!value) {
        return '--';
    }

    return new Date(value).toLocaleString();
}

function setPageMessage({ error = '', success = '' } = {}) {
    pageError.value = error;
    pageSuccess.value = success;
}

async function loadAccounts() {
    if (!canManageAccounts.value) {
        return;
    }

    accountsLoading.value = true;

    try {
        const response = await apiRequest('/api/company-accounts', {
            params: { per_page: 50 },
        });

        accounts.value = response.data || [];

        if (accounts.value.length > 0) {
            const hasSelected = accounts.value.some((account) => account.id === selectedAccountId.value);
            if (!hasSelected) {
                selectedAccountId.value = accounts.value[0].id;
            }
        } else {
            selectedAccountId.value = null;
            selectedAccountDetails.value = null;
        }
    } catch (error) {
        setPageMessage({ error: error?.response?.data?.message || 'Failed to load company accounts.' });
    } finally {
        accountsLoading.value = false;
    }
}

async function loadSelectedAccountDetails() {
    if (!canManageAccounts.value || !selectedAccountId.value) {
        selectedAccountDetails.value = null;
        return;
    }

    accountDetailsLoading.value = true;

    try {
        const response = await apiRequest(`/api/company-accounts/${selectedAccountId.value}`);
        selectedAccountDetails.value = response.data || null;
    } catch (error) {
        selectedAccountDetails.value = null;
        setPageMessage({ error: error?.response?.data?.message || 'Failed to load account transaction history.' });
    } finally {
        accountDetailsLoading.value = false;
    }
}

async function submitCreateAccount() {
    if (!createAccountForm.value.account_name.trim()) {
        setPageMessage({ error: 'Account name is required.' });
        return;
    }

    accountCreating.value = true;
    setPageMessage();

    try {
        const response = await apiRequest('/api/company-accounts', {
            method: 'post',
            data: {
                account_name: createAccountForm.value.account_name.trim(),
                description: createAccountForm.value.description || null,
                initial_balance: Number(createAccountForm.value.initial_balance || 0),
            },
        });

        createAccountForm.value.account_name = '';
        createAccountForm.value.description = '';
        createAccountForm.value.initial_balance = 0;

        await loadAccounts();

        if (response?.data?.id) {
            selectedAccountId.value = response.data.id;
            await loadSelectedAccountDetails();
        }

        setPageMessage({ success: 'Company account created successfully.' });
    } catch (error) {
        setPageMessage({ error: error?.response?.data?.message || 'Failed to create company account.' });
    } finally {
        accountCreating.value = false;
    }
}

async function submitAccountTransaction() {
    if (!selectedAccountId.value) {
        setPageMessage({ error: 'Select a company account first.' });
        return;
    }

    if (Number(accountTransactionForm.value.amount || 0) <= 0) {
        setPageMessage({ error: 'Transaction amount must be greater than zero.' });
        return;
    }

    accountTransactionSubmitting.value = true;
    setPageMessage();

    try {
        await apiRequest(`/api/company-accounts/${selectedAccountId.value}/transactions`, {
            method: 'post',
            data: {
                transaction_type: accountTransactionForm.value.transaction_type,
                amount: Number(accountTransactionForm.value.amount),
                description: accountTransactionForm.value.description || null,
            },
        });

        accountTransactionForm.value.amount = null;
        accountTransactionForm.value.description = '';

        await Promise.all([loadAccounts(), loadSelectedAccountDetails()]);
        setPageMessage({ success: 'Company account transaction recorded successfully.' });
    } catch (error) {
        setPageMessage({ error: error?.response?.data?.message || 'Failed to record company account transaction.' });
    } finally {
        accountTransactionSubmitting.value = false;
    }
}

onMounted(async () => {
    await loadAccounts();
});
</script>
