<template>
    <section>
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Accounts</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Accounts Management</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Manage company cash and bank accounts, then move balances between them.</p>
                </div>
            </div>

            <div class="mt-4 inline-flex flex-wrap rounded-xl app-surface-soft p-1">
                <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'accounts' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'accounts'">Accounts</button>
                <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'transfers' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'transfers'">Transfers</button>
                <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'expenses' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'expenses'">Expenses</button>
                <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors" :class="activeTab === 'transactions' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'" @click="activeTab = 'transactions'">Transactions</button>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="activeTab === 'accounts'" class="space-y-4">
            <div class="flex justify-end">
                <RouterLink to="/accounts/new" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white text-sm font-semibold">Add Account</RouterLink>
            </div>

            <div class="app-surface rounded-2xl overflow-hidden">
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="account in accounts" :key="account.id" class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ account.name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ money(account.current_balance) }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">Current Balance</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 text-xs">
                            <div><span class="text-secondary-500 dark:text-secondary-400">Opening:</span> {{ money(account.opening_balance) }}</div>
                        </div>

                        <p v-if="account.description" class="text-xs text-secondary-600 dark:text-secondary-300">{{ account.description }}</p>

                        <div class="mt-2 flex gap-3 text-sm">
                            <RouterLink :to="`/accounts/${account.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button type="button" class="text-red-600 dark:text-red-400" @click="removeAccount(account)">Delete</button>
                        </div>
                    </article>
                    <div v-if="accounts.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No accounts found.</div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Opening</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Current</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="account in accounts" :key="account.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top">
                                <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ account.name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ money(account.opening_balance) }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ money(account.current_balance) }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <RouterLink :to="`/accounts/${account.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Edit</RouterLink>
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeAccount(account)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="accounts.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No accounts found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AppPagination
                :current-page="accountMeta.current_page"
                :last-page="accountMeta.last_page"
                :per-page="accountPerPage"
                :total="accountMeta.total"
                :disabled="loadingAccounts"
                @page-change="handleAccountPageChange"
                @limit-change="handleAccountLimitChange"
            />
        </div>

        <div v-if="activeTab === 'transfers'" class="space-y-4">
            <div class="flex justify-end">
                <RouterLink to="/accounts/transfers/new" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white text-sm font-semibold">New Transfer</RouterLink>
            </div>

            <div class="app-surface rounded-2xl overflow-hidden">
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="transfer in transfers" :key="transfer.id" class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ transfer.source_account_name }} to {{ transfer.destination_account_name }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">{{ transfer.transfer_date || '-' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ money(transfer.amount) }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">Transfer Amount</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div><span class="text-secondary-500 dark:text-secondary-400">From:</span> {{ transfer.source_account_name }}</div>
                            <div><span class="text-secondary-500 dark:text-secondary-400">To:</span> {{ transfer.destination_account_name }}</div>
                            <div class="col-span-2"><span class="text-secondary-500 dark:text-secondary-400">Reference:</span> {{ transfer.reference_number || '-' }}</div>
                        </div>

                        <p v-if="transfer.notes" class="text-xs text-secondary-600 dark:text-secondary-300">{{ transfer.notes }}</p>

                        <div class="mt-2 flex gap-3 text-sm">
                            <RouterLink :to="`/accounts/transfers/${transfer.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button type="button" class="text-red-600 dark:text-red-400" @click="removeTransfer(transfer)">Delete</button>
                        </div>
                    </article>
                    <div v-if="transfers.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No transfers found.</div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">From</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="transfer in transfers" :key="transfer.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top">
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ transfer.transfer_date || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ transfer.source_account_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ transfer.destination_account_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ transfer.reference_number || '-' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ money(transfer.amount) }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <RouterLink :to="`/accounts/transfers/${transfer.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Edit</RouterLink>
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeTransfer(transfer)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="transfers.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No transfers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AppPagination
                :current-page="transferMeta.current_page"
                :last-page="transferMeta.last_page"
                :per-page="transferPerPage"
                :total="transferMeta.total"
                :disabled="loadingTransfers"
                @page-change="handleTransferPageChange"
                @limit-change="handleTransferLimitChange"
            />
        </div>

        <div v-if="activeTab === 'expenses'" class="space-y-4">
            <div class="flex justify-end">
                <RouterLink to="/accounts/expenses/new" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white text-sm font-semibold">Record Expense</RouterLink>
            </div>

            <div class="app-surface rounded-2xl overflow-hidden">
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="expense in expenses" :key="expense.id" class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ expense.category }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">{{ expense.account_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-red-600 dark:text-red-400">-{{ money(expense.amount) }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ expense.expense_date || '-' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div v-if="expense.reference_number" class="col-span-2"><span class="text-secondary-500 dark:text-secondary-400">Reference:</span> {{ expense.reference_number }}</div>
                        </div>

                        <p v-if="expense.notes" class="text-xs text-secondary-600 dark:text-secondary-300">{{ expense.notes }}</p>

                        <div class="mt-2 flex gap-3 text-sm">
                            <RouterLink :to="`/accounts/expenses/${expense.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button type="button" class="text-red-600 dark:text-red-400" @click="removeExpense(expense)">Delete</button>
                        </div>
                    </article>
                    <div v-if="expenses.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No expenses recorded.</div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Account</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Notes</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top">
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ expense.expense_date || '-' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ expense.category }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ expense.account_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ expense.reference_number || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300 max-w-xs truncate">{{ expense.notes || '-' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-red-600 dark:text-red-400 text-right">-{{ money(expense.amount) }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <RouterLink :to="`/accounts/expenses/${expense.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Edit</RouterLink>
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeExpense(expense)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="expenses.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No expenses recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AppPagination
                :current-page="expenseMeta.current_page"
                :last-page="expenseMeta.last_page"
                :per-page="expensePerPage"
                :total="expenseMeta.total"
                :disabled="loadingExpenses"
                @page-change="handleExpensePageChange"
                @limit-change="handleExpenseLimitChange"
            />
        </div>

        <div v-if="activeTab === 'transactions'" class="space-y-4">
            <div class="app-surface rounded-2xl overflow-hidden">
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="tx in transactions" :key="tx.id" class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ tx.account_name }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">{{ tx.transaction_date || '-' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ money(tx.amount) }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ formatType(tx.type) }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div v-if="tx.sale_reference"><span class="text-secondary-500 dark:text-secondary-400">Sale Ref:</span> {{ tx.sale_reference }}</div>
                            <div v-if="tx.sale_customer"><span class="text-secondary-500 dark:text-secondary-400">Customer:</span> {{ tx.sale_customer }}</div>
                            <div v-if="tx.reference_number" class="col-span-2"><span class="text-secondary-500 dark:text-secondary-400">Reference:</span> {{ tx.reference_number }}</div>
                        </div>

                        <p v-if="tx.notes" class="text-xs text-secondary-600 dark:text-secondary-300">{{ tx.notes }}</p>
                    </article>
                    <div v-if="transactions.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No transactions found.</div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Account</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Sale Ref</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Reference</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top">
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ tx.transaction_date || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ tx.account_name }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ formatType(tx.type) }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ tx.sale_reference || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ tx.sale_customer || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ tx.reference_number || '-' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white text-right">{{ money(tx.amount) }}</td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No transactions found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AppPagination
                :current-page="transactionMeta.current_page"
                :last-page="transactionMeta.last_page"
                :per-page="transactionPerPage"
                :total="transactionMeta.total"
                :disabled="loadingTransactions"
                @page-change="handleTransactionPageChange"
                @limit-change="handleTransactionLimitChange"
            />
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppPagination from '../components/AppPagination.vue';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();

const validTabs = ['accounts', 'transfers', 'expenses', 'transactions'];
const activeTab = ref(validTabs.includes(route.query.tab) ? route.query.tab : 'accounts');
const accounts = ref([]);
const transfers = ref([]);
const expenses = ref([]);
const transactions = ref([]);
const errorMessage = ref('');
const loadingAccounts = ref(false);
const loadingTransfers = ref(false);
const loadingExpenses = ref(false);
const loadingTransactions = ref(false);
const accountMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const transferMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const expenseMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const transactionMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const accountPerPage = ref(10);
const transferPerPage = ref(10);
const expensePerPage = ref(10);
const transactionPerPage = ref(10);

function money(value) {
    return Number(value || 0).toFixed(2);
}

function formatType(type) {
    return type ? type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-';
}


async function loadAccounts(page = 1) {
    loadingAccounts.value = true;

    try {
        const response = await apiRequest('/api/accounts', {
            params: {
                page,
                per_page: accountPerPage.value,
            },
        });

        accounts.value = response.data || [];
        accountMeta.value = response.meta || accountMeta.value;
        accountPerPage.value = accountMeta.value.per_page || accountPerPage.value;
    } finally {
        loadingAccounts.value = false;
    }
}

async function loadTransfers(page = 1) {
    loadingTransfers.value = true;

    try {
        const response = await apiRequest('/api/accounts/transfers', {
            params: {
                page,
                per_page: transferPerPage.value,
            },
        });

        transfers.value = response.data || [];
        transferMeta.value = response.meta || transferMeta.value;
        transferPerPage.value = transferMeta.value.per_page || transferPerPage.value;
    } finally {
        loadingTransfers.value = false;
    }
}

async function loadTransactions(page = 1) {
    loadingTransactions.value = true;

    try {
        const response = await apiRequest('/api/accounts/transactions', {
            params: {
                page,
                per_page: transactionPerPage.value,
            },
        });

        transactions.value = response.data || [];
        transactionMeta.value = response.meta || transactionMeta.value;
        transactionPerPage.value = transactionMeta.value.per_page || transactionPerPage.value;
    } finally {
        loadingTransactions.value = false;
    }
}

async function loadExpenses(page = 1) {
    loadingExpenses.value = true;

    try {
        const response = await apiRequest('/api/accounts/expenses', {
            params: {
                page,
                per_page: expensePerPage.value,
            },
        });

        expenses.value = response.data || [];
        expenseMeta.value = response.meta || expenseMeta.value;
        expensePerPage.value = expenseMeta.value.per_page || expensePerPage.value;
    } finally {
        loadingExpenses.value = false;
    }
}

async function loadAll() {
    errorMessage.value = '';

    try {
        await Promise.all([loadAccounts(), loadTransfers(), loadExpenses(), loadTransactions()]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load account data.';
    }
}

async function removeAccount(account) {
    if (!window.confirm(`Delete account "${account.name}"?`)) {
        return;
    }

    try {
        await apiRequest(`/api/accounts/${account.id}`, { method: 'delete' });
        await loadAccounts(accountMeta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete account.';
    }
}

async function removeTransfer(transfer) {
    if (!window.confirm('Delete this transfer?')) {
        return;
    }

    try {
        await apiRequest(`/api/accounts/transfers/${transfer.id}`, { method: 'delete' });
        await Promise.all([
            loadAccounts(accountMeta.value.current_page),
            loadTransfers(transferMeta.value.current_page),
        ]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete transfer.';
    }
}

async function removeExpense(expense) {
    if (!window.confirm(`Delete expense "${expense.category}"?`)) {
        return;
    }

    try {
        await apiRequest(`/api/accounts/expenses/${expense.id}`, { method: 'delete' });
        await Promise.all([
            loadAccounts(accountMeta.value.current_page),
            loadExpenses(expenseMeta.value.current_page),
        ]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete expense.';
    }
}

function handleAccountPageChange(page) {
    loadAccounts(page);
}

function handleAccountLimitChange(limit) {
    accountPerPage.value = Number(limit);
    loadAccounts(1);
}

function handleTransferPageChange(page) {
    loadTransfers(page);
}

function handleTransferLimitChange(limit) {
    transferPerPage.value = Number(limit);
    loadTransfers(1);
}

function handleExpensePageChange(page) {
    loadExpenses(page);
}

function handleExpenseLimitChange(limit) {
    expensePerPage.value = Number(limit);
    loadExpenses(1);
}

function handleTransactionPageChange(page) {
    loadTransactions(page);
}

function handleTransactionLimitChange(limit) {
    transactionPerPage.value = Number(limit);
    loadTransactions(1);
}

watch(
    () => route.query.tab,
    (tab) => {
        activeTab.value = validTabs.includes(tab) ? tab : 'accounts';
    }
);

onMounted(() => {
    loadAll();
});
</script>