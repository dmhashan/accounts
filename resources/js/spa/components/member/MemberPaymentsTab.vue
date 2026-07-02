<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
        Payment History
      </h2>
      <div v-if="canManage" class="flex items-center gap-2 shrink-0">
        <button
          type="button"
          class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors"
          @click="openMembershipModal"
        >
          <Plus class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Membership</span>
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
          @click="openOtherModal"
        >
          <Plus class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Other</span>
        </button>
      </div>
    </div>
    <div v-if="paymentsLoading" class="px-5 py-6 text-center text-sm text-secondary-400">
      Loading...
    </div>
    <div v-else-if="memberPayments.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">
      No payments recorded for this member.
    </div>
    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
      <RouterLink
        v-for="payment in memberPayments"
        :key="payment.id"
        :to="`/payments/${payment.id}`"
        class="flex items-center justify-between px-5 py-3 gap-3 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors"
      >
        <div class="min-w-0">
          <p class="text-sm font-semibold text-secondary-900 dark:text-white">
            {{ formatMoney(payment.amount) }}
          </p>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
            {{ formatDate(payment.payment_date) }}
            <span v-if="payment.payment_method_name || payment.account_name" class="ml-1 opacity-70">&bull; {{ payment.payment_method_name || payment.account_name }}</span>
          </p>
          <p v-if="payment.reference_number" class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5">
            Ref: {{ payment.reference_number }}
          </p>
        </div>
        <div class="shrink-0 text-right">
          <span
            class="inline-block px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full"
            :class="payment.payment_method === 'member_wallet'
              ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800'
              : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'"
          >
            {{ payment.payment_method === 'member_wallet' ? 'Wallet' : (payment.payment_method_name || 'Method') }}
          </span>
        </div>
      </RouterLink>
    </div>
    <div v-if="paymentsMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
      <p class="text-xs text-secondary-500 dark:text-secondary-400">
        Page {{ paymentsMeta.current_page }} of {{ paymentsMeta.last_page }}
      </p>
      <div class="flex gap-1">
        <button
          type="button"
          class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
          :disabled="paymentsMeta.current_page <= 1"
          @click="loadMemberPayments(paymentsMeta.current_page - 1)"
        >
          Prev
        </button>
        <button
          type="button"
          class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
          :disabled="paymentsMeta.current_page >= paymentsMeta.last_page"
          @click="loadMemberPayments(paymentsMeta.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>

  <!-- Membership payment modal -->
  <Teleport to="body">
    <div v-if="memModalOpen" class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-black/60 overflow-y-auto">
      <PaymentMembershipForm
        :accounts="metaAccounts"
        :payment-methods="metaPaymentMethods"
        :plans="metaPlans"
        :member-id="Number(memberId)"
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
        :payment-methods="metaPaymentMethods"
        :plans="metaPlans"
        :member-id="Number(memberId)"
        :saving="otherModalSaving"
        :error="otherModalError"
        @submit="submitOtherPayment"
        @cancel="closeOtherModal"
      />
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Plus } from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';
import { useAppContext } from '../../composables/useAppContext';
import PaymentMembershipForm from '../forms/PaymentMembershipForm.vue';
import PaymentOtherForm from '../forms/PaymentOtherForm.vue';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
});

const context = useAppContext();
const canManage = computed(() => Boolean(context.permissions?.paymentsManage));

const { formatDate, formatMoney } = useMemberFormatters();

const paymentsLoading = ref(false);
const memberPayments = ref([]);
const paymentsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

async function loadMemberPayments(page = 1) {
    paymentsLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/payments?page=${page}&per_page=15`);
        memberPayments.value = res.data || [];
        paymentsMeta.value = res.meta || paymentsMeta.value;
    } catch { /* ignore */ } finally {
        paymentsLoading.value = false;
    }
}

// ── Meta ──────────────────────────────────────────────────
const metaLoaded = ref(false);
const metaAccounts = ref([]);
const metaPaymentMethods = ref([]);
const metaPlans = ref([]);

async function loadMeta() {
    if (metaLoaded.value) return;
    try {
        const response = await apiRequest('/api/payments/meta');
        metaAccounts.value = response.accounts || [];
        metaPaymentMethods.value = response.payment_methods || [];
        metaPlans.value = (response.plans || []).filter((p) => p.is_active !== false);
        metaLoaded.value = true;
    } catch { /* silent */ }
}

// ── Membership payment modal ──────────────────────────────
const memModalOpen = ref(false);
const memModalSaving = ref(false);
const memModalError = ref('');

async function openMembershipModal() {
    memModalError.value = '';
    memModalSaving.value = false;
    await loadMeta();
    memModalOpen.value = true;
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
        loadMemberPayments(1);
    } catch (err) {
        memModalError.value = err?.response?.data?.message || 'Failed to record payment.';
    } finally {
        memModalSaving.value = false;
    }
}

// ── Other payment modal ───────────────────────────────────
const otherModalOpen = ref(false);
const otherModalSaving = ref(false);
const otherModalError = ref('');

async function openOtherModal() {
    otherModalError.value = '';
    otherModalSaving.value = false;
    await loadMeta();
    otherModalOpen.value = true;
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
        loadMemberPayments(1);
    } catch (err) {
        otherModalError.value = err?.response?.data?.message || 'Failed to record payment.';
    } finally {
        otherModalSaving.value = false;
    }
}

defineExpose({ loadMemberPayments });
</script>
