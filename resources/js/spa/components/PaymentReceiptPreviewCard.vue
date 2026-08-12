<template>
  <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl shadow-slate-900/10 overflow-hidden w-full">
    <!-- Header -->
    <div class="border-b border-gray-100 dark:border-zinc-800 px-6 py-5 sm:px-8">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span
              class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider"
              :class="payment.type === 'membership'
                ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20'
                : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'"
            >
              <Crown v-if="payment.type === 'membership'" class="w-3 h-3" :stroke-width="2.2" />
              <Receipt v-else class="w-3 h-3" :stroke-width="2.2" />
              {{ payment.type === 'membership' ? 'Membership Receipt' : 'Payment Receipt' }}
            </span>
          </div>

          <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">
            Receipt #{{ payment.id }}
          </h2>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5 font-medium">
            <Calendar class="w-3.5 h-3.5 shrink-0" />
            <span>Date: {{ payment.payment_date || payment.created_at }}</span>
          </p>
        </div>

        <div class="flex flex-col gap-2 items-start sm:items-end shrink-0">
          <span
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
            :class="payment.is_paid
              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-500/20'
              : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-400 border border-red-500/20 animate-pulse'"
          >
            {{ payment.is_paid ? 'Paid & Settled' : 'Outstanding Due' }}
          </span>

          <div v-if="payment.payment_method" class="flex items-center gap-1.5 text-xs">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-zinc-700">
              <CreditCard class="w-3 h-3 shrink-0" />
              <span>{{ payment.payment_method }}</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="px-5 py-5 sm:px-8 space-y-5">
      <!-- Membership Subscription Card if applicable -->
      <div
        v-if="payment.type === 'membership' || payment.plan_name"
        class="rounded-2xl p-4 sm:p-5 bg-gradient-to-br from-red-500/5 via-rose-500/5 to-amber-500/5 dark:from-red-950/30 dark:via-rose-950/20 dark:to-zinc-900 border border-red-500/20 relative overflow-hidden"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="space-y-1">
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-red-600 dark:text-red-400">
              Subscription Plan
            </p>
            <h3 class="text-lg font-black text-gray-900 dark:text-white">
              {{ payment.plan_name || 'Gym Membership' }}
            </h3>
          </div>

          <div class="w-10 h-10 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center shrink-0">
            <Crown class="w-5 h-5" :stroke-width="2" />
          </div>
        </div>

        <div v-if="payment.start_date || payment.end_date" class="mt-4 pt-3 border-t border-red-500/10 grid grid-cols-2 gap-3 text-xs">
          <div>
            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
              Start Date
            </p>
            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">
              {{ payment.start_date || 'N/A' }}
            </p>
          </div>

          <div>
            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
              Valid Until
            </p>
            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">
              {{ payment.end_date || 'N/A' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Other Payment Details Card if not membership -->
      <div
        v-else-if="payment.notes"
        class="rounded-2xl p-4 bg-gray-50 dark:bg-zinc-800/60 border border-gray-200/60 dark:border-zinc-700/60"
      >
        <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 dark:text-gray-500">
          Payment Purpose / Remarks
        </p>
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-1">
          {{ payment.notes }}
        </p>
      </div>

      <!-- Payment Summary Table -->
      <div class="grid grid-cols-3 gap-3 text-center">
        <div class="bg-gray-50 dark:bg-zinc-800/80 rounded-2xl px-3 py-3 border border-gray-100 dark:border-zinc-800">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">
            Total
          </p>
          <p class="text-base font-black text-gray-900 dark:text-white">
            {{ money(payment.amount) }}
          </p>
        </div>

        <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl px-3 py-3 border border-emerald-500/20">
          <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-0.5">
            Paid
          </p>
          <p class="text-base font-black text-emerald-700 dark:text-emerald-400">
            {{ money(payment.paid_amount || (payment.is_paid ? payment.amount : 0)) }}
          </p>
        </div>

        <div
          class="rounded-2xl px-3 py-3 border"
          :class="!payment.is_paid && parseFloat(payment.balance || 0) > 0
            ? 'bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border-red-500/20'
            : 'bg-gray-50 dark:bg-zinc-800/80 text-gray-500 dark:text-gray-400 border-gray-100 dark:border-zinc-800'"
        >
          <p class="text-[10px] font-bold uppercase tracking-wider mb-0.5" :class="!payment.is_paid ? 'text-red-500' : 'text-gray-400 dark:text-gray-500'">
            Due
          </p>
          <p class="text-base font-black" :class="!payment.is_paid ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
            {{ money(payment.balance || 0) }}
          </p>
        </div>
      </div>

      <!-- Additional Details List -->
      <div class="rounded-2xl border border-gray-100 dark:border-zinc-800 divide-y divide-gray-100 dark:divide-zinc-800 overflow-hidden text-xs">
        <div v-if="payment.reference_number" class="px-4 py-2.5 flex items-center justify-between">
          <span class="text-gray-400 dark:text-gray-500 font-medium">Reference #</span>
          <span class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ payment.reference_number }}</span>
        </div>

        <div v-if="payment.payment_method" class="px-4 py-2.5 flex items-center justify-between">
          <span class="text-gray-400 dark:text-gray-500 font-medium">Payment Method</span>
          <span class="font-bold text-gray-800 dark:text-gray-200">{{ payment.payment_method }}</span>
        </div>

        <div v-if="payment.notes && payment.type === 'membership'" class="px-4 py-2.5 flex items-center justify-between">
          <span class="text-gray-400 dark:text-gray-500 font-medium">Notes</span>
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ payment.notes }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Crown, Receipt, Calendar, CreditCard } from 'lucide-vue-next';

defineProps({
    /**
     * Payment object:
     * {
     *   id: number,
     *   type: 'membership' | 'other',
     *   payment_date: string,
     *   created_at: string,
     *   amount: string|number,
     *   paid_amount: string|number,
     *   balance: string|number,
     *   is_paid: boolean,
     *   payment_method: string,
     *   reference_number: string|null,
     *   notes: string|null,
     *   plan_name: string|null,
     *   start_date: string|null,
     *   end_date: string|null,
     * }
     */
    payment: { type: Object, required: true },
});

const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function money(value) {
    return moneyFormatter.format(Number(value || 0));
}
</script>
