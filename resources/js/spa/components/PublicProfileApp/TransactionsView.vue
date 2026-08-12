<template>
  <div class="space-y-4 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between pt-2 pb-1">
      <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
          Payments &amp; Invoices
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
          Official receipts, bills &amp; transaction vouchers
        </p>
      </div>

      <div v-if="parseFloat(meta.total_outstanding) > 0" class="px-2.5 py-1 rounded-full text-xs font-black bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 animate-pulse">
        Due: {{ meta.total_outstanding }}
      </div>
      <div v-else class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
        All Settled
      </div>
    </div>

    <!-- Segmented Tabs Switch -->
    <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800/80 rounded-2xl">
      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
        :class="activeTab === 'outstanding'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeTab = 'outstanding'"
      >
        <span>Outstanding</span>
        <span
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
          :class="activeTab === 'outstanding'
            ? 'bg-red-500 text-white'
            : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-400'"
        >
          {{ outstandingSales.length }}
        </span>
      </button>

      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
        :class="activeTab === 'paid'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeTab = 'paid'"
      >
        <span>Paid Invoices</span>
        <span
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
          :class="activeTab === 'paid'
            ? 'bg-emerald-500 text-white'
            : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-400'"
        >
          {{ paidSales.length }}
        </span>
      </button>
    </div>

    <!-- Outstanding tab content -->
    <div v-if="activeTab === 'outstanding'">
      <div v-if="outstandingSales.length" class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <button
          v-for="(sale, i) in outstandingSales"
          :key="i"
          type="button"
          class="w-full flex items-center gap-3.5 px-4 sm:px-5 py-4 hover:bg-gray-50 dark:hover:bg-zinc-800/40 active:bg-gray-100 dark:active:bg-zinc-800 transition-colors focus:outline-none text-left cursor-pointer group"
          @click="$emit('open-sale', sale)"
        >
          <div class="w-11 h-11 rounded-2xl bg-red-50 dark:bg-red-950/40 text-red-500 flex items-center justify-center shrink-0">
            <FileText class="w-5 h-5" :stroke-width="1.8" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-red-500 transition-colors">
                Invoice #{{ sale.id }}
              </p>
              <span class="text-[10px] font-extrabold text-red-600 bg-red-50 dark:bg-red-950/60 dark:text-red-400 px-2 py-0.5 rounded-full">
                Due
              </span>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
              Issued {{ sale.created_at }} &middot; {{ sale.items?.length || 0 }} item{{ sale.items?.length === 1 ? '' : 's' }}
            </p>
          </div>

          <div class="text-right shrink-0">
            <p class="text-base font-black text-gray-900 dark:text-white">
              {{ sale.total_amount }}
            </p>
            <p class="text-[11px] font-bold text-red-500 mt-0.5">
              Bal: {{ sale.balance }}
            </p>
          </div>
        </button>
      </div>

      <div
        v-else
        class="pp-glass-card rounded-3xl p-12 flex flex-col items-center justify-center text-center gap-2.5 text-gray-400"
      >
        <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
          <CheckCircle2 class="w-7 h-7" :stroke-width="2" />
        </div>
        <p class="text-base font-bold text-gray-800 dark:text-gray-200">
          No Pending Dues
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs">
          Your account is in good standing with zero outstanding balance.
        </p>
      </div>
    </div>

    <!-- Paid tab content -->
    <div v-if="activeTab === 'paid'">
      <div v-if="paidSales.length" class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <button
          v-for="(sale, i) in paidSales"
          :key="i"
          type="button"
          class="w-full flex items-center gap-3.5 px-4 sm:px-5 py-4 hover:bg-gray-50 dark:hover:bg-zinc-800/40 active:bg-gray-100 dark:active:bg-zinc-800 transition-colors focus:outline-none text-left cursor-pointer group"
          @click="$emit('open-sale', sale)"
        >
          <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500 flex items-center justify-center shrink-0">
            <FileCheck class="w-5 h-5" :stroke-width="1.8" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-red-500 transition-colors">
                Invoice #{{ sale.id }}
              </p>
              <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-400 px-2 py-0.5 rounded-full">
                Paid
              </span>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
              {{ sale.created_at }} &middot; {{ sale.items?.length || 0 }} item{{ sale.items?.length === 1 ? '' : 's' }}
            </p>
          </div>

          <div class="text-right shrink-0">
            <p class="text-base font-black text-gray-900 dark:text-white">
              {{ sale.total_amount }}
            </p>
            <ChevronRight class="w-4 h-4 text-gray-400 ml-auto mt-1 group-hover:translate-x-0.5 transition-transform" :stroke-width="2" />
          </div>
        </button>
      </div>

      <div
        v-else
        class="pp-glass-card rounded-3xl p-12 flex flex-col items-center justify-center text-center gap-2.5 text-gray-400"
      >
        <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-400 flex items-center justify-center">
          <Receipt class="w-7 h-7" :stroke-width="1.5" />
        </div>
        <p class="text-base font-bold text-gray-800 dark:text-gray-200">
          No Paid Invoices
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs">
          Completed invoice payments will appear here for your records.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { FileText, FileCheck, CheckCircle2, Receipt, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    meta:      { type: Object, default: () => ({}) },
    salesData: { type: Array,  default: () => [] },
});

defineEmits(['open-sale']);

const activeTab = ref('outstanding');

const outstandingSales = computed(() => props.salesData.filter(s => !s.is_paid));
const paidSales        = computed(() => props.salesData.filter(s =>  s.is_paid));
</script>
