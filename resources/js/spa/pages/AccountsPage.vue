<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="tabCta"
          :to="tabCta.to"
          :icon="tabCta.icon"
          :label="tabCta.label"
        />
      </template>

      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search current list"
          :disabled="loadingAccounts || loadingTransfers || loadingTransactions"
          @search="triggerActiveSearch"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div v-if="activeTab === 'accounts'" class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article
              v-for="account in filteredAccounts"
              :key="account.id"
              class="p-4 space-y-2 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
              @click="router.push('/accounts/' + account.id)"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ account.name }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ money(account.current_balance) }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    Current Balance
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-2 text-xs">
                <div><span class="text-secondary-500 dark:text-secondary-400">Opening:</span> {{ money(account.opening_balance) }}</div>
              </div>

              <p v-if="account.description" class="text-xs text-secondary-600 dark:text-secondary-300">
                {{ account.description }}
              </p>
            </article>
            <div v-if="filteredAccounts.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
              No accounts found.
            </div>
          </div>

          <div class="hidden md:block app-table-scroll">
            <table class="w-full">
              <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Name
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Opening
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Current
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr
                  v-for="account in filteredAccounts"
                  :key="account.id"
                  class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top cursor-pointer"
                  @click="router.push('/accounts/' + account.id)"
                >
                  <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ account.name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ money(account.opening_balance) }}
                  </td>
                  <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ money(account.current_balance) }}
                  </td>
                </tr>
                <tr v-if="filteredAccounts.length === 0">
                  <td colspan="3" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">
                    No accounts found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="app-page-pagination">
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
    </div>

    <div v-if="activeTab === 'transfers'" class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article
              v-for="transfer in filteredTransfers"
              :key="transfer.id"
              class="p-4 space-y-2 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
              @click="router.push('/accounts/transfers/' + transfer.id)"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ transfer.source_account_name }} to {{ transfer.destination_account_name }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">
                    {{ transfer.transfer_date || '-' }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ money(transfer.amount) }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    Transfer Amount
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-secondary-500 dark:text-secondary-400">From:</span> {{ transfer.source_account_name }}</div>
                <div><span class="text-secondary-500 dark:text-secondary-400">To:</span> {{ transfer.destination_account_name }}</div>
                <div class="col-span-2">
                  <span class="text-secondary-500 dark:text-secondary-400">Reference:</span> {{ transfer.reference_number || '-' }}
                </div>
              </div>

              <p v-if="transfer.notes" class="text-xs text-secondary-600 dark:text-secondary-300">
                {{ transfer.notes }}
              </p>
            </article>
            <div v-if="filteredTransfers.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
              No transfers found.
            </div>
          </div>

          <div class="hidden md:block app-table-scroll">
            <table class="w-full">
              <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    From
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    To
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Reference
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Amount
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr
                  v-for="transfer in filteredTransfers"
                  :key="transfer.id"
                  class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top cursor-pointer"
                  @click="router.push('/accounts/transfers/' + transfer.id)"
                >
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ transfer.transfer_date || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">
                    {{ transfer.source_account_name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">
                    {{ transfer.destination_account_name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ transfer.reference_number || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ money(transfer.amount) }}
                  </td>
                </tr>
                <tr v-if="filteredTransfers.length === 0">
                  <td colspan="5" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">
                    No transfers found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="app-page-pagination">
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
    </div>

    <div v-if="activeTab === 'transactions'" class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="tx in filteredTransactions" :key="tx.id" class="p-4 space-y-2">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ tx.account_name }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">
                    {{ tx.transaction_date || '-' }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ money(tx.amount) }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    {{ formatType(tx.type) }}
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2 text-xs">
                <div v-if="tx.model_name" class="col-span-2">
                  <span class="text-secondary-500 dark:text-secondary-400">Record:</span>
                  <RouterLink :to="sourceRecordPath(tx)" class="ml-1 text-primary-600 dark:text-primary-400 hover:underline">
                    {{ sourceRecordLabel(tx) }}
                  </RouterLink>
                </div>
                <div v-if="tx.customer">
                  <span class="text-secondary-500 dark:text-secondary-400">Customer:</span> {{ tx.customer }}
                </div>
                <div v-if="tx.reference_number" class="col-span-2">
                  <span class="text-secondary-500 dark:text-secondary-400">Reference:</span> {{ tx.reference_number }}
                </div>
              </div>

              <p v-if="tx.notes" class="text-xs text-secondary-600 dark:text-secondary-300">
                {{ tx.notes }}
              </p>
            </article>
            <div v-if="filteredTransactions.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
              No transactions found.
            </div>
          </div>

          <div class="hidden md:block app-table-scroll">
            <table class="w-full">
              <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Account
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Record
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Customer
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Reference
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Amount
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr v-for="tx in filteredTransactions" :key="tx.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 align-top">
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ tx.transaction_date || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">
                    {{ tx.account_name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ formatType(tx.type) }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <RouterLink v-if="tx.model_name" :to="sourceRecordPath(tx)" class="text-primary-600 dark:text-primary-400 hover:underline">
                      {{ sourceRecordLabel(tx) }}
                    </RouterLink>
                    <span v-else>-</span>
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ tx.customer || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ tx.reference_number || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white text-right">
                    {{ money(tx.amount) }}
                  </td>
                </tr>
                <tr v-if="filteredTransactions.length === 0">
                  <td colspan="7" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">
                    No transactions found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="app-page-pagination">
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
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowRightLeft, Landmark } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPagination from '../components/AppPagination.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();

const activeTab = ref(route.path === '/accounts/transfers' ? 'transfers' : route.path === '/accounts/transactions' ? 'transactions' : 'accounts');
const accounts = ref([]);
const transfers = ref([]);
const transactions = ref([]);
const search = ref('');
const errorMessage = ref('');
const loadingAccounts = ref(false);
const loadingTransfers = ref(false);
const loadingTransactions = ref(false);
const accountMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const transferMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const transactionMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const accountPerPage = ref(10);
const transferPerPage = ref(10);
const transactionPerPage = ref(10);

const tabCta = computed(() => {
    if (activeTab.value === 'transactions') {
        return null;
    }

    if (activeTab.value === 'transfers') {
        return {
            to: '/accounts/transfers/new',
            icon: ArrowRightLeft,
            label: 'New Transfer',
        };
    }

    return {
        to: '/accounts/new',
        icon: Landmark,
        label: 'Add Account',
    };
});

const normalizedSearch = computed(() => search.value.trim().toLowerCase());

const filteredAccounts = computed(() => {
    if (!normalizedSearch.value) return accounts.value;

    return accounts.value.filter((account) => {
        return [account.name, account.description, account.opening_balance, account.current_balance]
            .some((value) => String(value || '').toLowerCase().includes(normalizedSearch.value));
    });
});

const filteredTransfers = computed(() => {
    if (!normalizedSearch.value) return transfers.value;

    return transfers.value.filter((transfer) => {
        return [
            transfer.source_account_name,
            transfer.destination_account_name,
            transfer.reference_number,
            transfer.transfer_date,
            transfer.notes,
            transfer.amount,
        ].some((value) => String(value || '').toLowerCase().includes(normalizedSearch.value));
    });
});


const filteredTransactions = computed(() => {
    if (!normalizedSearch.value) return transactions.value;

    return transactions.value.filter((transaction) => {
        return [
            transaction.account_name,
            transaction.transaction_date,
            transaction.reference_number,
            transaction.source_reference,
            transaction.customer,
            transaction.type,
            transaction.amount,
        ].some((value) => String(value || '').toLowerCase().includes(normalizedSearch.value));
    });
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function formatType(type) {
    return type ? type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-';
}

function sourceRecordPath(tx) {
    if (tx.model_name === 'sale') return `/sales/${tx.reference_id}`;
    if (tx.model_name === 'expense') return `/expenses/${tx.reference_id}`;
    if (tx.model_name === 'payment') return `/payments/${tx.reference_id}`;
    if (tx.model_name === 'wallet_topup' && tx.reference_id) return `/wallet-topups/${tx.reference_id}`;
    return '#';
}

function sourceRecordLabel(tx) {
    if (tx.model_name === 'sale') return tx.source_reference ? `Sale • ${tx.source_reference}` : `Sale #${tx.reference_id}`;
    if (tx.model_name === 'expense') return tx.source_reference ? `Expense • ${tx.source_reference}` : `Expense #${tx.reference_id}`;
    if (tx.model_name === 'payment') return `Payment #${tx.reference_id}`;
    if (tx.model_name === 'wallet_topup') return (tx.customer && tx.transaction_date) ? `${tx.customer} • ${tx.transaction_date}` : tx.customer ? tx.customer : `Wallet Topup #${tx.reference_id}`;
    return '-';
}

function triggerActiveSearch() {
    if (activeTab.value === 'accounts') {
        loadAccounts(1);
        return;
    }

    if (activeTab.value === 'transfers') {
        loadTransfers(1);
        return;
    }

    loadTransactions(1);
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

async function loadAll() {
    errorMessage.value = '';

    try {
        await Promise.all([loadAccounts(), loadTransfers(), loadTransactions()]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load account data.';
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


function handleTransactionPageChange(page) {
    loadTransactions(page);
}

function handleTransactionLimitChange(limit) {
    transactionPerPage.value = Number(limit);
    loadTransactions(1);
}

watch(
    () => route.path,
    (path) => {
        const newTab = path === '/accounts/transfers' ? 'transfers' : path === '/accounts/transactions' ? 'transactions' : 'accounts';
        if (activeTab.value !== newTab) activeTab.value = newTab;
    }
);

onMounted(() => {
    loadAll();
});
</script>