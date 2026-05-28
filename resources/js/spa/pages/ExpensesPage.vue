<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction :to="'/expenses/new'" :icon="ReceiptText" label="Record Expense" />
      </template>

      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search expenses"
          :disabled="loading"
          @search="loadExpenses(1)"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
            <article
              v-for="expense in expenses"
              :key="expense.id"
              class="p-4 space-y-2 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
              @click="router.push('/expenses/' + expense.id)"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ expense.category }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">
                    {{ expense.account_name }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                    -{{ money(expense.amount) }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    {{ expense.expense_date || '-' }}
                  </p>
                </div>
              </div>

              <div v-if="expense.reference_number" class="text-xs">
                <span class="text-secondary-500 dark:text-secondary-400">Reference:</span> {{ expense.reference_number }}
              </div>

              <p v-if="expense.notes" class="text-xs text-secondary-600 dark:text-secondary-300">
                {{ expense.notes }}
              </p>
            </article>
            <div v-if="expenses.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
              No expenses recorded.
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
                    Category
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Account
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Reference
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Notes
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                    Amount
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr
                  v-for="expense in expenses"
                  :key="expense.id"
                  class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 cursor-pointer align-top"
                  @click="router.push('/expenses/' + expense.id)"
                >
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ expense.expense_date || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">
                    {{ expense.category }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ expense.account_name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                    {{ expense.reference_number || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300 max-w-xs truncate">
                    {{ expense.notes || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm font-medium text-red-600 dark:text-red-400 text-right">
                    -{{ money(expense.amount) }}
                  </td>
                </tr>
                <tr v-if="expenses.length === 0">
                  <td colspan="6" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">
                    No expenses recorded.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="app-page-pagination">
        <AppPagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :per-page="perPage"
          :total="meta.total"
          :disabled="loading"
          @page-change="loadExpenses"
          @limit-change="handleLimitChange"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ReceiptText } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPagination from '../components/AppPagination.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { apiRequest } from '../composables/useApiClient';

const expenses = ref([]);
const router = useRouter();
const search = ref('');
const errorMessage = ref('');
const loading = ref(false);
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const perPage = ref(10);

async function loadExpenses(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/accounts/expenses', {
            params: { page, per_page: perPage.value, search: search.value || undefined },
        });
        expenses.value = response.data || [];
        meta.value = response.meta || meta.value;
        perPage.value = meta.value.per_page || perPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load expenses.';
    } finally {
        loading.value = false;
    }
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadExpenses(1);
}

function money(value) {
    return Number(value || 0).toFixed(2);
}

onMounted(() => {
    loadExpenses();
});
</script>
