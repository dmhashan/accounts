<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="canManage && activeTab === 'payments'"
          to="/payments/new"
          :icon="CreditCard"
          label="New Payment"
        />
        <button
          v-if="canManage && activeTab === 'plans'"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold transition-colors"
          @click="openPlanModal()"
        >
          <component :is="Tag" class="w-4 h-4" />
          New Plan
        </button>
      </template>
    </AppPageHeader>

    <!-- Payments tab -->
    <div v-if="activeTab === 'payments'" class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
          {{ errorMessage }}
        </div>

        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Loading payments...
          </div>

          <template v-else>
            <!-- Mobile list -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article
                v-for="payment in payments"
                :key="payment.id"
                class="p-4 space-y-1 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
                @click="router.push('/payments/' + payment.id)"
              >
                <div class="flex justify-between items-start gap-3">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                      {{ payment.member_name }}
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      {{ payment.payment_date }} &bull; {{ payment.account_name }}
                      <span v-if="payment.payment_plan_name"> &bull; {{ payment.payment_plan_name }}</span>
                    </p>
                    <p class="text-sm font-bold text-primary-600 dark:text-primary-400">
                      {{ money(payment.amount) }}
                    </p>
                    <p v-if="payment.notes" class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                      {{ payment.notes }}
                    </p>
                  </div>
                </div>
              </article>

              <div v-if="payments.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
                No payments recorded.
              </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Member
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Account
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Plan
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
                    v-for="payment in payments"
                    :key="payment.id"
                    class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 cursor-pointer"
                    @click="router.push('/payments/' + payment.id)"
                  >
                    <td class="px-6 py-4">
                      <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                        {{ payment.member_name }}
                      </p>
                      <p v-if="payment.member_phone" class="text-xs text-secondary-500 dark:text-secondary-400">
                        {{ payment.member_phone }}
                      </p>
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">
                      {{ payment.payment_date }}
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ payment.account_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ payment.payment_plan_name || '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-500 dark:text-secondary-400 max-w-xs truncate">
                      {{ payment.notes || '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-right text-primary-600 dark:text-primary-400 whitespace-nowrap">
                      {{ money(payment.amount) }}
                    </td>
                  </tr>
                  <tr v-if="payments.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                      No payments recorded.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>

        <AppPagination
          v-if="pagination.last_page > 1"
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          class="mt-4"
          @page-change="loadPayments"
        />
      </div>
    </div>

    <!-- Plans tab -->
    <div v-if="activeTab === 'plans'" class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div v-if="plansError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
          {{ plansError }}
        </div>

        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="plansLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Loading plans...
          </div>

          <template v-else>
            <!-- Mobile list -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article v-for="plan in plans" :key="plan.id" class="p-4 space-y-1">
                <div class="flex justify-between items-start gap-3">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ plan.name }}
                    </p>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400">
                      {{ formatDuration(plan.duration_days) }}
                    </p>
                    <p class="text-sm font-bold text-primary-600 dark:text-primary-400 mt-1">
                      {{ money(plan.price) }}
                    </p>
                  </div>
                  <div v-if="canManage" class="flex gap-2 shrink-0">
                    <button type="button" class="text-xs text-primary-600 dark:text-primary-400 hover:underline" @click="openPlanModal(plan)">
                      Edit
                    </button>
                    <button type="button" class="text-xs text-red-500 hover:underline" @click="deletePlan(plan)">
                      Delete
                    </button>
                  </div>
                </div>
                <span
                  class="inline-block text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="plan.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-500'"
                >{{ plan.is_active ? 'Active' : 'Inactive' }}</span>
              </article>
              <div v-if="plans.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
                No payment plans defined yet.
              </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Plan Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Duration
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Price
                    </th>
                    <th v-if="canManage" class="px-6 py-3" />
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr v-for="plan in plans" :key="plan.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                    <td class="px-6 py-4 text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ plan.name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ formatDuration(plan.duration_days) }}
                    </td>
                    <td class="px-6 py-4">
                      <span
                        class="inline-block text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="plan.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-500'"
                      >{{ plan.is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-right text-primary-600 dark:text-primary-400">
                      {{ money(plan.price) }}
                    </td>
                    <td v-if="canManage" class="px-6 py-4 text-right">
                      <button type="button" class="text-xs text-primary-600 dark:text-primary-400 hover:underline mr-3" @click="openPlanModal(plan)">
                        Edit
                      </button>
                      <button type="button" class="text-xs text-red-500 hover:underline" @click="deletePlan(plan)">
                        Delete
                      </button>
                    </td>
                  </tr>
                  <tr v-if="plans.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                      No payment plans defined yet.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Plan create/edit modal -->
    <Teleport to="body">
      <div v-if="planModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-200 dark:border-secondary-700">
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              {{ planForm.id ? 'Edit Payment Plan' : 'Create a New Payment Plan' }}
            </h3>
            <button type="button" class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200" @click="closePlanModal">
              <component :is="X" class="w-5 h-5" />
            </button>
          </div>

          <p class="px-5 pt-3 text-sm text-secondary-500 dark:text-secondary-400">
            Fill out the form below to {{ planForm.id ? 'update this' : 'create a new' }} payment plan for gym members.
          </p>

          <form class="p-5 space-y-4" @submit.prevent="savePlan">
            <div v-if="planModalError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
              {{ planModalError }}
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-1">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Plan Name</label>
                <input
                  v-model="planForm.name"
                  type="text"
                  required
                  maxlength="255"
                  placeholder="Enter a new plan name"
                  class="w-full px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>

              <div class="col-span-1">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Duration (days)</label>
                <select
                  v-model="planDurationSelect"
                  class="w-full px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                  @change="onDurationSelect"
                >
                  <option value="" disabled>
                    Select a duration
                  </option>
                  <option value="1">
                    1 day
                  </option>
                  <option value="30">
                    1 month
                  </option>
                  <option value="90">
                    3 months
                  </option>
                  <option value="180">
                    6 months
                  </option>
                  <option value="365">
                    1 year
                  </option>
                  <option value="custom">
                    Custom
                  </option>
                </select>
              </div>

              <div v-if="planDurationSelect === 'custom'" class="col-span-2">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Custom Duration (days)</label>
                <input
                  v-model.number="planForm.duration_days"
                  type="number"
                  min="1"
                  max="36500"
                  required
                  placeholder="Enter number of days"
                  class="w-full px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>

              <div class="col-span-2">
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Price</label>
                <input
                  v-model="planForm.price"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                  placeholder="Enter price"
                  class="w-full px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>

              <div v-if="planForm.id" class="col-span-2 flex items-center gap-2">
                <input
                  id="plan-active"
                  v-model="planForm.is_active"
                  type="checkbox"
                  class="rounded"
                />
                <label for="plan-active" class="text-sm text-secondary-700 dark:text-secondary-300">Active</label>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button type="button" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300" @click="closePlanModal">
                Cancel
              </button>
              <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium disabled:opacity-50" :disabled="planSaving">
                {{ planSaving ? 'Saving...' : (planForm.id ? 'Update Plan' : 'Create Plan') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { CreditCard, Tag, X } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPagination from '../components/AppPagination.vue';

const context = useAppContext();
const router = useRouter();
const route = useRoute();

const canManage = computed(() => Boolean(context.permissions?.paymentsManage));

// ── Tab state ──────────────────────────────────────────────
const activeTab = computed(() => route.path === '/payments/plans' ? 'plans' : 'payments');

// ── Payments ──────────────────────────────────────────────
const loading = ref(false);
const errorMessage = ref('');
const payments = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function loadPayments(page = 1) {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest('/api/payments', { params: { page, per_page: 20 } });
        payments.value = response.data || [];
        pagination.value = response.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 };
    } catch {
        errorMessage.value = 'Failed to load payments.';
    } finally {
        loading.value = false;
    }
}

// ── Plans ─────────────────────────────────────────────────
const plansLoading = ref(false);
const plansError = ref('');
const plans = ref([]);

const DURATION_PRESETS = { 1: true, 30: true, 90: true, 180: true, 365: true };

function formatDuration(days) {
    if (days === 1)   return '1 day';
    if (days === 30)  return '1 month';
    if (days === 90)  return '3 months';
    if (days === 180) return '6 months';
    if (days === 365) return '1 year';
    return `${days} days`;
}

async function loadPlans() {
    plansLoading.value = true;
    plansError.value = '';
    try {
        const response = await apiRequest('/api/payment-plans');
        plans.value = response.data || [];
    } catch {
        plansError.value = 'Failed to load plans.';
    } finally {
        plansLoading.value = false;
    }
}

// ── Plan modal ────────────────────────────────────────────
const planModalOpen = ref(false);
const planModalError = ref('');
const planSaving = ref(false);
const planDurationSelect = ref('');

const planForm = ref({ id: null, name: '', duration_days: null, price: '', is_active: true });

function openPlanModal(plan = null) {
    planModalError.value = '';
    if (plan) {
        planForm.value = { id: plan.id, name: plan.name, duration_days: plan.duration_days, price: String(plan.price), is_active: plan.is_active };
        planDurationSelect.value = DURATION_PRESETS[plan.duration_days] ? String(plan.duration_days) : 'custom';
    } else {
        planForm.value = { id: null, name: '', duration_days: null, price: '', is_active: true };
        planDurationSelect.value = '';
    }
    planModalOpen.value = true;
}

function closePlanModal() {
    planModalOpen.value = false;
}

function onDurationSelect() {
    if (planDurationSelect.value !== 'custom') {
        planForm.value.duration_days = parseInt(planDurationSelect.value, 10);
    } else {
        planForm.value.duration_days = null;
    }
}

async function savePlan() {
    if (!planForm.value.name || !planForm.value.duration_days || planForm.value.price === '') return;

    planSaving.value = true;
    planModalError.value = '';
    try {
        const payload = {
            name:          planForm.value.name,
            duration_days: planForm.value.duration_days,
            price:         parseFloat(planForm.value.price),
            is_active:     planForm.value.is_active,
        };

        if (planForm.value.id) {
            await apiRequest(`/api/payment-plans/${planForm.value.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/payment-plans', { method: 'post', data: payload });
        }

        closePlanModal();
        await loadPlans();
    } catch (err) {
        planModalError.value = err?.response?.data?.message || 'Failed to save plan.';
    } finally {
        planSaving.value = false;
    }
}

async function deletePlan(plan) {
    if (!confirm(`Delete plan "${plan.name}"? Existing payments linked to this plan will not be affected.`)) return;
    try {
        await apiRequest(`/api/payment-plans/${plan.id}`, { method: 'delete' });
        await loadPlans();
    } catch {
        plansError.value = 'Failed to delete plan.';
    }
}

// ── Init ──────────────────────────────────────────────────
onMounted(() => {
    loadPayments();
    loadPlans();
});
</script>

