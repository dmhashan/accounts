<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="context.permissions?.employeesManage"
          to="/employees/new"
          :icon="UserRoundPlus"
          label="Add Employee"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <AppSearchField
          v-model="employeeSearch"
          class="md:max-w-md"
          placeholder="Search employees"
          :disabled="employeesLoading"
          @search="loadEmployees(1)"
        />
        <select
          v-model="statusFilter"
          class="rounded-xl border border-secondary-300 bg-white px-3 py-2 text-sm text-secondary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
          @change="loadEmployees(1)"
        >
          <option value="">
            All statuses
          </option>
          <option v-for="status in meta.employee_statuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>

      <div class="app-page-scroll">
        <div class="app-surface overflow-hidden rounded-2xl">
          <div v-if="employeesLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Loading employees...
          </div>

          <template v-else>
            <div class="divide-y divide-secondary-200 md:hidden dark:divide-secondary-700">
              <article
                v-for="employee in employees"
                :key="employee.id"
                class="cursor-pointer p-4 transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/40"
                @click="router.push('/employees/' + employee.id)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ employee.name }}
                    </p>
                    <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                      {{ employee.employee_code || 'No code' }}<span v-if="employee.job_title"> &bull; {{ employee.job_title }}</span>
                    </p>
                    <p class="mt-2 text-sm font-bold text-primary-600 dark:text-primary-400">
                      {{ money(employee.daily_rate) }} / day
                    </p>
                  </div>
                  <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="employeeStatusClass(employee.status)">
                    {{ employee.status_label }}
                  </span>
                </div>
              </article>
              <div v-if="employees.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
                No employees found.
              </div>
            </div>

            <div class="hidden app-table-scroll md:block">
              <table class="w-full">
                <thead class="app-table-head-sticky border-b border-secondary-200 bg-secondary-50 dark:border-secondary-700 dark:bg-background-dark">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Employee
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Role
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Contact
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-secondary-500 dark:text-secondary-400">
                      Daily rate
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr
                    v-for="employee in employees"
                    :key="employee.id"
                    class="cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/50"
                    @click="router.push('/employees/' + employee.id)"
                  >
                    <td class="px-6 py-4">
                      <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                        {{ employee.name }}
                      </p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">
                        {{ employee.employee_code || 'No code' }}
                      </p>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-sm text-secondary-800 dark:text-secondary-200">
                        {{ employee.job_title || '—' }}
                      </p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">
                        {{ employee.department || '—' }}
                      </p>
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                      <p>{{ employee.phone || '—' }}</p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">
                        {{ employee.email || '—' }}
                      </p>
                    </td>
                    <td class="px-6 py-4">
                      <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="employeeStatusClass(employee.status)">
                        {{ employee.status_label }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-semibold text-primary-600 dark:text-primary-400">
                      {{ money(employee.daily_rate) }}
                    </td>
                  </tr>
                  <tr v-if="employees.length === 0">
                    <td colspan="5" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">
                      No employees found.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>

      <div class="app-page-pagination">
        <AppPagination
          :current-page="employeePagination.current_page"
          :last-page="employeePagination.last_page"
          :per-page="employeePerPage"
          :total="employeePagination.total"
          :disabled="employeesLoading"
          @page-change="loadEmployees"
          @limit-change="handleEmployeeLimit"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { UserRoundPlus } from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPagination from '../components/AppPagination.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const router = useRouter();
const context = useAppContext();

const meta = ref({ employee_statuses: [] });
const errorMessage = ref('');
const employees = ref([]);
const employeesLoading = ref(false);
const employeeSearch = ref('');
const statusFilter = ref('');
const employeePerPage = ref(15);
const employeePagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

async function loadMeta() {
    meta.value = await apiRequest('/api/employees/meta');
}

async function loadEmployees(page = 1) {
    employeesLoading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest('/api/employees', {
            params: {
                page,
                per_page: employeePerPage.value,
                search: employeeSearch.value,
                status: statusFilter.value || undefined,
            },
        });
        employees.value = response.data || [];
        employeePagination.value = response.meta || employeePagination.value;
        employeePerPage.value = employeePagination.value.per_page || employeePerPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load employees.';
    } finally {
        employeesLoading.value = false;
    }
}

function handleEmployeeLimit(limit) {
    employeePerPage.value = Number(limit);
    loadEmployees(1);
}

function employeeStatusClass(status) {
    if (status === 'active') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300';
    if (status === 'terminated') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
    return 'bg-secondary-100 text-secondary-700 dark:bg-secondary-800 dark:text-secondary-300';
}

function money(value) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'LKR', maximumFractionDigits: 2 }).format(Number(value || 0));
}

onMounted(async () => {
    await loadMeta();
    await loadEmployees();
});
</script>
