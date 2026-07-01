<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <template v-if="canManagePayments && activeTab === 'payments'">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold transition-colors"
            @click="openMembershipModal"
          >
            <component :is="CreditCard" class="w-4 h-4" />
            Membership Payment
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-secondary-300 dark:border-secondary-700 hover:bg-secondary-50 dark:hover:bg-secondary-800 text-secondary-700 dark:text-secondary-300 text-sm font-semibold transition-colors"
            @click="openOtherModal"
          >
            <component :is="Plus" class="w-4 h-4" />
            Other Payment
          </button>
        </template>
        <button
          v-if="canManagePlans && activeTab === 'plans'"
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
                      {{ formatPlanDuration(plan) }}
                    </p>
                    <p class="text-sm font-bold text-primary-600 dark:text-primary-400 mt-1">
                      {{ money(plan.price) }}
                    </p>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <button
                      type="button"
                      class="text-xs font-medium px-2 py-0.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 hover:bg-primary-200 dark:hover:bg-primary-900/50"
                      @click="router.push(`/members?plan_id=${plan.id}&plan_name=${encodeURIComponent(plan.name)}`)"
                    >
                      {{ plan.member_count }} member{{ plan.member_count !== 1 ? 's' : '' }}
                    </button>
                    <div v-if="canManagePlans" class="flex gap-2">
                      <button type="button" class="text-xs text-primary-600 dark:text-primary-400 hover:underline" @click="openPlanModal(plan)">
                        Edit
                      </button>
                      <button type="button" class="text-xs text-red-500 hover:underline" @click="deletePlan(plan)">
                        Delete
                      </button>
                    </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Members
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">
                      Price
                    </th>
                    <th v-if="canManagePlans" class="px-6 py-3" />
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr v-for="plan in plans" :key="plan.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                    <td class="px-6 py-4 text-sm font-semibold text-secondary-900 dark:text-white">
                      {{ plan.name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">
                      {{ formatPlanDuration(plan) }}
                    </td>
                    <td class="px-6 py-4">
                      <span
                        class="inline-block text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="plan.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-500'"
                      >{{ plan.is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td class="px-6 py-4">
                      <button
                        type="button"
                        class="text-xs font-medium px-2 py-0.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 hover:bg-primary-200 dark:hover:bg-primary-900/50"
                        @click="router.push(`/members?plan_id=${plan.id}&plan_name=${encodeURIComponent(plan.name)}`)"
                      >
                        {{ plan.member_count }} member{{ plan.member_count !== 1 ? 's' : '' }}
                      </button>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-right text-primary-600 dark:text-primary-400">
                      {{ money(plan.price) }}
                    </td>
                    <td v-if="canManagePlans" class="px-6 py-4 text-right">
                      <button type="button" class="text-xs text-primary-600 dark:text-primary-400 hover:underline mr-3" @click="openPlanModal(plan)">
                        Edit
                      </button>
                      <button type="button" class="text-xs text-red-500 hover:underline" @click="deletePlan(plan)">
                        Delete
                      </button>
                    </td>
                  </tr>
                  <tr v-if="plans.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
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
                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Duration</label>
                <div class="flex gap-2">
                  <input
                    v-model.number="planForm.duration_value"
                    type="number"
                    min="1"
                    max="1000"
                    required
                    placeholder="Value"
                    class="w-1/2 px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                  <select
                    v-model="planForm.duration_unit"
                    required
                    class="w-1/2 px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    <option v-for="opt in PLAN_UNIT_OPTIONS" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                </div>
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

    <!-- Plan archive confirmation modal -->
    <AppConfirmModal
      v-if="planToArchive"
      title="Cannot Delete Plan"
      confirm-label="Archive Plan"
      loading-label="Archiving..."
      variant="warning"
      :loading="planArchiving"
      @confirm="forceDeletePlan"
      @cancel="planToArchive = null"
    >
      <div class="space-y-3">
        <p class="text-sm text-secondary-700 dark:text-secondary-300">
          <strong class="text-secondary-900 dark:text-white">{{ planToArchive.name }}</strong>
          has <strong>{{ planToArchive.member_count }} member{{ planToArchive.member_count !== 1 ? 's' : '' }}</strong> assigned.
          It cannot be permanently deleted while members are on this plan.
        </p>
        <p class="text-sm text-secondary-600 dark:text-secondary-400">
          You can <strong>archive</strong> it instead — the plan will be hidden from active plans but member assignments and payment history are preserved.
        </p>
      </div>
    </AppConfirmModal>

    <!-- Plan delete confirmation modal -->
    <AppConfirmModal
      v-if="planToDeleteSimple"
      title="Delete Plan"
      confirm-label="Delete"
      loading-label="Deleting..."
      :loading="planDeleteSimpleLoading"
      @confirm="confirmDeletePlan"
      @cancel="planToDeleteSimple = null"
    >
      <p class="text-sm text-secondary-700 dark:text-secondary-300">
        Delete plan <strong class="text-secondary-900 dark:text-white">{{ planToDeleteSimple.name }}</strong>?
        Existing payments linked to this plan will not be affected.
      </p>
    </AppConfirmModal>

    <!-- Membership payment modal -->
    <Teleport to="body">
      <div v-if="memModalOpen" class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-black/60 overflow-y-auto">
        <PaymentMembershipForm
          :accounts="metaAccounts"
          :plans="metaPlans"
          :members="metaMembers"
          :saving="memModalSaving"
          :error="memModalError"
          @submit="submitMembershipPayment"
          @cancel="closeMembershipModal"
        />
      </div>
    </Teleport>

    <!-- Other payment modal -->
    <Teleport to="body">
      <div v-if="otherModalOpen" class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-black/60 overflow-y-auto">
        <PaymentOtherForm
          :accounts="metaAccounts"
          :plans="metaPlans"
          :members="metaMembers"
          :saving="otherModalSaving"
          :error="otherModalError"
          @submit="submitOtherPayment"
          @cancel="closeOtherModal"
        />
      </div>
    </Teleport>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { CreditCard, Plus, Tag, X } from 'lucide-vue-next';
import AppConfirmModal from '../components/AppConfirmModal.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPagination from '../components/AppPagination.vue';
import PaymentMembershipForm from '../components/forms/PaymentMembershipForm.vue';
import PaymentOtherForm from '../components/forms/PaymentOtherForm.vue';
import { formatPlanDuration, PLAN_UNIT_OPTIONS } from '../composables/usePlanDuration.js';

const context = useAppContext();
const router = useRouter();
const route = useRoute();

const canManagePayments = computed(() => Boolean(context.permissions?.paymentsManage));
const canManagePlans = computed(() => Boolean(context.permissions?.paymentPlansManage));

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
    if (!canManagePayments.value) return;

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

async function loadPlans() {
    if (!canManagePlans.value) return;

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

const planForm = ref({ id: null, name: '', duration_value: 1, duration_unit: 'month', price: '', is_active: true });

function openPlanModal(plan = null) {
    planModalError.value = '';
    if (plan) {
        planForm.value = {
            id: plan.id,
            name: plan.name,
            duration_value: Number(plan.duration_value) || 1,
            duration_unit: plan.duration_unit || 'month',
            price: String(plan.price),
            is_active: plan.is_active,
        };
    } else {
        planForm.value = { id: null, name: '', duration_value: 1, duration_unit: 'month', price: '', is_active: true };
    }
    planModalOpen.value = true;
}

function closePlanModal() {
    planModalOpen.value = false;
}

async function savePlan() {
    if (!planForm.value.name || !planForm.value.duration_value || !planForm.value.duration_unit || planForm.value.price === '') return;

    planSaving.value = true;
    planModalError.value = '';
    try {
        const payload = {
            name:           planForm.value.name,
            duration_value: Number(planForm.value.duration_value),
            duration_unit:  planForm.value.duration_unit,
            price:          parseFloat(planForm.value.price),
            is_active:      planForm.value.is_active,
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

// ── Plan delete / archive ──────────────────────────────────
const planToArchive = ref(null);
const planArchiving = ref(false);

const planToDeleteSimple = ref(null);
const planDeleteSimpleLoading = ref(false);

async function deletePlan(plan) {
    if (plan.member_count > 0) {
        planToArchive.value = plan;
        return;
    }
    planToDeleteSimple.value = plan;
}

async function confirmDeletePlan() {
    if (!planToDeleteSimple.value) return;
    planDeleteSimpleLoading.value = true;
    try {
        await apiRequest(`/api/payment-plans/${planToDeleteSimple.value.id}`, { method: 'delete' });
        planToDeleteSimple.value = null;
        await loadPlans();
    } catch {
        plansError.value = 'Failed to delete plan.';
        planToDeleteSimple.value = null;
    } finally {
        planDeleteSimpleLoading.value = false;
    }
}

async function forceDeletePlan() {
    if (!planToArchive.value) return;
    planArchiving.value = true;
    try {
        await apiRequest(`/api/payment-plans/${planToArchive.value.id}?force=1`, { method: 'delete' });
        planToArchive.value = null;
        await loadPlans();
    } catch (err) {
        plansError.value = err?.response?.data?.message || 'Failed to archive plan.';
        planToArchive.value = null;
    } finally {
        planArchiving.value = false;
    }
}

// ── Meta for modals ───────────────────────────────────────
const metaLoaded = ref(false);
const metaMembers = ref([]);
const metaAccounts = ref([]);
const metaPlans = ref([]);

async function loadMeta() {
    if (metaLoaded.value || !canManagePayments.value) return;
    try {
        const response = await apiRequest('/api/payments/meta');
        metaMembers.value = response.members || [];
        metaAccounts.value = response.accounts || [];
        metaPlans.value = (response.plans || []).filter(p => p.is_active !== false);
        metaLoaded.value = true;
    } catch {
        // silent — modal errors will surface if submit is attempted without data
    }
}

// ── Membership payment modal ──────────────────────────────
const memModalOpen = ref(false);
const memModalSaving = ref(false);
const memModalError = ref('');

function openMembershipModal() {
    memModalError.value = '';
    memModalSaving.value = false;
    memModalOpen.value = true;
    loadMeta();
}

function openMembershipModalFromRoute() {
    if (!canManagePayments.value || activeTab.value !== 'payments' || route.query.action !== 'membership') {
        return;
    }

    openMembershipModal();
    router.replace({ path: '/payments', query: {} }).catch(() => {});
}

function closeMembershipModal() {
    memModalOpen.value = false;
}

async function submitMembershipPayment(payload) {
    memModalSaving.value = true;
    memModalError.value = '';
    try {
        await apiRequest('/api/payments', { method: 'post', data: payload });
        closeMembershipModal();
        loadPayments();
    } catch (err) {
        memModalError.value = err?.response?.data?.message || 'Failed to record payment.';
    } finally {
        memModalSaving.value = false;
    }
}

// ── Other payment modal ───────────────────────────────────────────────
const otherModalOpen = ref(false);
const otherModalSaving = ref(false);
const otherModalError = ref('');

function openOtherModal() {
    otherModalError.value = '';
    otherModalSaving.value = false;
    otherModalOpen.value = true;
    loadMeta();
}

function closeOtherModal() {
    otherModalOpen.value = false;
}

async function submitOtherPayment(payload) {
    otherModalSaving.value = true;
    otherModalError.value = '';
    try {
        await apiRequest('/api/payments', { method: 'post', data: payload });
        closeOtherModal();
        loadPayments();
    } catch (err) {
        otherModalError.value = err?.response?.data?.message || 'Failed to record payment.';
    } finally {
        otherModalSaving.value = false;
    }
}

// ── Init ────────────────────────────────────────────────────────────
onMounted(() => {
    loadPayments();
    loadPlans();
    loadMeta();
    openMembershipModalFromRoute();
});

watch(
    () => [route.path, route.query.action, route.query.open],
    openMembershipModalFromRoute
);
</script>
