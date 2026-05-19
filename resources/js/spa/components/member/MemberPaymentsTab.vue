<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
        Payment History
      </h2>
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
            <span v-if="payment.account_name" class="ml-1 opacity-70">&bull; {{ payment.account_name }}</span>
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
            {{ payment.payment_method === 'member_wallet' ? 'Wallet' : 'Cash' }}
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
</template>

<script setup>
import { ref } from 'vue';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
});

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

defineExpose({ loadMemberPayments });
</script>
