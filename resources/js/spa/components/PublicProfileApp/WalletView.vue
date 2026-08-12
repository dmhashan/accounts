<template>
  <div class="space-y-5 pb-6">
    <!-- Header -->
    <div class="pt-2 pb-1">
      <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
        Digital Wallet
      </h1>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
        Club credits, top-up balance &amp; expenditure history
      </p>
    </div>

    <!-- Digital Wallet Card -->
    <div class="pp-wallet-card p-6 relative overflow-hidden select-none shadow-xl">
      <!-- Decorative ambient shapes -->
      <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/10 pointer-events-none" />
      <div class="absolute -bottom-10 -left-10 w-36 h-36 rounded-full bg-black/10 pointer-events-none" />

      <!-- Card Top -->
      <div class="relative flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-white/20 p-1.5 backdrop-blur-sm border border-white/20 flex items-center justify-center">
            <CreditCard class="w-full h-full text-white" :stroke-width="2" />
          </div>
          <div>
            <p class="text-xs font-black tracking-wider uppercase text-white/90">
              {{ meta.tenant_name || 'Member Wallet' }}
            </p>
            <p class="text-[9px] font-semibold text-emerald-200 uppercase tracking-widest leading-none">
              Prepaid Member Account
            </p>
          </div>
        </div>

        <Wifi class="w-5 h-5 text-emerald-200 rotate-90" :stroke-width="2" />
      </div>

      <!-- Card Balance -->
      <div class="relative py-1">
        <p class="text-[11px] font-bold text-emerald-200 uppercase tracking-wider mb-1">
          Available Balance
        </p>
        <p class="text-4xl sm:text-5xl font-black text-white tracking-tight leading-none">
          {{ formatMoney(meta.current_balance ?? 0) }}
        </p>
      </div>

      <!-- Card Bottom -->
      <div class="relative mt-6 pt-3 border-t border-white/15 flex items-center justify-between text-xs">
        <div>
          <p class="font-bold text-white uppercase tracking-tight truncate">
            {{ meta.name }}
          </p>
          <p class="text-[10px] text-emerald-200 font-mono mt-0.5">
            {{ meta.member_code || meta.member_id || '#MEMBER' }}
          </p>
        </div>

        <div class="text-right">
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/20 backdrop-blur-sm text-white">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse" />
            Active
          </span>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 gap-3">
      <div class="pp-glass-card rounded-2xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
          <ArrowDownLeft class="w-5 h-5" :stroke-width="2.2" />
        </div>
        <div class="min-w-0">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            Total Top-ups
          </p>
          <p class="text-base font-black text-gray-900 dark:text-white truncate mt-0.5">
            +{{ formatMoney(totalCredits) }}
          </p>
        </div>
      </div>

      <div class="pp-glass-card rounded-2xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-500 dark:text-rose-400 flex items-center justify-center shrink-0">
          <ArrowUpRight class="w-5 h-5" :stroke-width="2.2" />
        </div>
        <div class="min-w-0">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            Total Spent
          </p>
          <p class="text-base font-black text-gray-900 dark:text-white truncate mt-0.5">
            -{{ formatMoney(totalDebits) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Transaction History Section -->
    <section class="space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-base font-extrabold text-gray-900 dark:text-white tracking-tight">
          Transaction Activity
        </h2>
        <span v-if="txMeta.total > 0" class="text-xs font-semibold text-gray-400 dark:text-gray-500">
          {{ filteredTransactions.length }} of {{ txMeta.total }}
        </span>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800/80 rounded-2xl">
        <button
          v-for="filter in ['all', 'credit', 'debit']"
          :key="filter"
          type="button"
          class="flex-1 py-1.5 text-xs font-bold rounded-xl transition-all capitalize focus:outline-none cursor-pointer"
          :class="activeFilter === filter
            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
          @click="activeFilter = filter"
        >
          {{ filter === 'all' ? 'All' : (filter === 'credit' ? 'Top-ups (+)' : 'Spent (-)') }}
        </button>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading && transactions.length === 0" class="pp-glass-card rounded-3xl p-4 divide-y divide-gray-100 dark:divide-zinc-800">
        <div v-for="i in 4" :key="i" class="flex items-center gap-3 py-3 animate-pulse">
          <div class="w-10 h-10 rounded-2xl bg-gray-200 dark:bg-zinc-800 shrink-0" />
          <div class="flex-1 space-y-1.5">
            <div class="h-3.5 bg-gray-200 dark:bg-zinc-800 rounded w-1/2" />
            <div class="h-2.5 bg-gray-200 dark:bg-zinc-800 rounded w-1/3" />
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredTransactions.length === 0"
        class="pp-glass-card rounded-3xl p-10 flex flex-col items-center justify-center text-center gap-2 text-gray-400"
      >
        <Receipt class="w-10 h-10 text-gray-300 dark:text-zinc-600" :stroke-width="1.5" />
        <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
          No transactions found
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500">
          Transactions in this category will appear here.
        </p>
      </div>

      <!-- Transactions List -->
      <div v-else class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <div
          v-for="tx in filteredTransactions"
          :key="tx.id"
          class="flex items-center gap-3.5 px-4 sm:px-5 py-4 hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors"
        >
          <!-- Icon -->
          <div
            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0"
            :class="tx.direction === 'credit'
              ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'
              : 'bg-rose-50 dark:bg-rose-950/50 text-rose-500 dark:text-rose-400'"
          >
            <ArrowDownLeft v-if="tx.direction === 'credit'" class="w-5 h-5" :stroke-width="2.2" />
            <ShoppingBag v-else class="w-5 h-5" :stroke-width="2" />
          </div>

          <!-- Details -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
              {{ tx.label || (tx.direction === 'credit' ? 'Wallet Top-up' : 'Account Purchase') }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">
              {{ tx.date }}
              <span v-if="tx.reference" class="opacity-80">&middot; {{ tx.reference }}</span>
            </p>
            <p v-if="tx.notes" class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 truncate">
              {{ tx.notes }}
            </p>
          </div>

          <!-- Amount -->
          <div class="text-right shrink-0">
            <p
              class="text-sm font-black"
              :class="tx.direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white'"
            >
              {{ tx.direction === 'credit' ? '+' : '-' }}{{ formatMoney(tx.amount) }}
            </p>
            <span
              class="inline-block text-[10px] font-extrabold px-2 py-0.5 rounded-full mt-0.5"
              :class="tx.direction === 'credit'
                ? 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-400'
                : 'text-gray-600 bg-gray-100 dark:bg-zinc-800 dark:text-gray-300'"
            >
              {{ tx.direction === 'credit' ? 'Credit' : 'Debit' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Load More Button -->
      <button
        v-if="txMeta.current_page < txMeta.last_page"
        type="button"
        :disabled="loading"
        class="w-full py-3 rounded-2xl text-xs font-bold bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 active:scale-98 transition-all disabled:opacity-50 cursor-pointer shadow-md"
        @click="loadMore"
      >
        <span v-if="loading" class="flex items-center justify-center gap-2">
          <span class="w-3.5 h-3.5 border-2 border-white dark:border-gray-900 border-t-transparent rounded-full animate-spin" />
          Loading more&hellip;
        </span>
        <span v-else>
          Load more transactions ({{ txMeta.total - transactions.length }} remaining)
        </span>
      </button>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
    CreditCard,
    ArrowDownLeft,
    ArrowUpRight,
    ShoppingBag,
    Receipt,
    Wifi,
} from 'lucide-vue-next';

const props = defineProps({
    meta:                { type: Object, default: () => ({}) },
    walletTransactions:  { type: Array,  default: () => [] },
    walletTxMeta:        { type: Object, default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 }) },
});

const MEMBER_KEY = 'public_profile_member_id';

const transactions = ref([...props.walletTransactions]);
const txMeta       = ref({ ...props.walletTxMeta });
const loading      = ref(false);
const activeFilter = ref('all'); // 'all' | 'credit' | 'debit'

function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const filteredTransactions = computed(() => {
    if (activeFilter.value === 'all') return transactions.value;
    return transactions.value.filter(t => t.direction === activeFilter.value);
});

async function loadMore() {
    if (loading.value) return;
    loading.value = true;
    try {
        const token = localStorage.getItem(MEMBER_KEY);
        const nextPage = txMeta.value.current_page + 1;
        const res = await fetch(`/api/public/wallet/transactions?page=${nextPage}&per_page=15`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN':     getCsrfToken(),
                'X-PP-Token':       token || '',
            },
        });
        if (!res.ok) return;
        const data = await res.json();
        transactions.value.push(...(data.data || []));
        txMeta.value = data.meta || txMeta.value;
    } catch {
        /* silent */
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (transactions.value.length === 0 && (props.walletTxMeta?.total ?? 0) === 0) {
        fetchFirstPage();
    }
});

async function fetchFirstPage() {
    loading.value = true;
    try {
        const token = localStorage.getItem(MEMBER_KEY);
        const res = await fetch('/api/public/wallet/transactions?page=1&per_page=10', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN':     getCsrfToken(),
                'X-PP-Token':       token || '',
            },
        });
        if (!res.ok) return;
        const data = await res.json();
        transactions.value = data.data || [];
        txMeta.value       = data.meta || txMeta.value;
    } catch {
        /* silent */
    } finally {
        loading.value = false;
    }
}

const totalCredits = computed(() =>
    transactions.value.filter(t => t.direction === 'credit').reduce((s, t) => s + (parseFloat(t.amount) || 0), 0)
);

const totalDebits = computed(() =>
    transactions.value.filter(t => t.direction === 'debit').reduce((s, t) => s + (parseFloat(t.amount) || 0), 0)
);

function formatMoney(val) {
    const n = parseFloat(val ?? 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
