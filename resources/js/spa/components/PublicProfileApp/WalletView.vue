<template>
    <div>
        <!-- Header -->
        <div class="pt-12 pb-6">
            <h1 class="text-2xl font-bold text-gray-900 leading-tight tracking-tight">Wallet</h1>
            <p class="text-xs text-gray-400 mt-0.5">Your balance & transaction history</p>
        </div>

        <!-- Balance hero card -->
        <div class="relative rounded-3xl overflow-hidden mb-5" style="background: linear-gradient(135deg, #059669 0%, #047857 60%, #065f46 100%); min-height: 140px;">
            <!-- Decorative blobs -->
            <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-white/10 pointer-events-none"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 rounded-full bg-white/10 pointer-events-none"></div>
            <div class="absolute top-6 right-24 w-16 h-16 rounded-full bg-white/5 pointer-events-none"></div>

            <div class="relative px-6 py-7">
                <p class="text-xs font-bold text-emerald-200 uppercase tracking-widest mb-1">Current Balance</p>
                <p class="text-4xl font-extrabold text-white tracking-tight leading-none">
                    {{ formatMoney(meta.current_balance ?? 0) }}
                </p>
                <div class="mt-4 flex items-center gap-3">
                    <div class="flex items-center gap-1.5 text-emerald-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span class="text-xs font-medium">{{ meta.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary chips -->
        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="bg-white rounded-2xl px-4 py-3.5 shadow-sm border border-gray-100">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <p class="text-[11px] text-gray-400 font-medium">Total Credits</p>
                </div>
                <p class="text-base font-bold text-gray-900">{{ formatMoney(totalCredits) }}</p>
            </div>
            <div class="bg-white rounded-2xl px-4 py-3.5 shadow-sm border border-gray-100">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 rounded-lg bg-red-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <p class="text-[11px] text-gray-400 font-medium">Total Debits</p>
                </div>
                <p class="text-base font-bold text-gray-900">{{ formatMoney(totalDebits) }}</p>
            </div>
        </div>

        <!-- Transaction history -->
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-gray-900">Transactions</h2>
                <span v-if="txMeta.total > 0" class="text-xs text-gray-400">{{ txMeta.total }} total</span>
            </div>

            <!-- Loading skeleton -->
            <div v-if="loading && transactions.length === 0" class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm divide-y divide-gray-50">
                <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-5 py-4 animate-pulse">
                    <div class="w-10 h-10 rounded-2xl bg-gray-100 shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 bg-gray-100 rounded-full w-2/5"></div>
                        <div class="h-2.5 bg-gray-100 rounded-full w-1/3"></div>
                    </div>
                    <div class="w-16 space-y-2">
                        <div class="h-3 bg-gray-100 rounded-full"></div>
                        <div class="h-2.5 bg-gray-100 rounded-full w-3/4 ml-auto"></div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else-if="transactions.length === 0" class="flex flex-col items-center justify-center py-20 gap-3">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <p class="text-sm text-gray-400">No wallet transactions yet</p>
            </div>

            <!-- Transaction list -->
            <div v-else class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm divide-y divide-gray-50">
                <div
                    v-for="tx in transactions"
                    :key="tx.id"
                    class="flex items-center gap-4 px-5 py-4"
                >
                    <!-- Icon -->
                    <div
                        class="shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center"
                        :class="tx.direction === 'credit' ? 'bg-emerald-50' : 'bg-red-50'"
                    >
                        <!-- Topup / credit -->
                        <svg v-if="tx.direction === 'credit'" style="width:18px;height:18px" class="text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <!-- Debit -->
                        <svg v-else style="width:18px;height:18px" class="text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ tx.label }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ tx.date }}
                            <span v-if="tx.reference" class="ml-1 opacity-75">&middot; {{ tx.reference }}</span>
                        </p>
                        <p v-if="tx.notes" class="text-[11px] text-gray-400 mt-0.5 truncate">{{ tx.notes }}</p>
                    </div>

                    <!-- Amount -->
                    <div class="shrink-0 text-right">
                        <p
                            class="text-sm font-bold"
                            :class="tx.direction === 'credit' ? 'text-emerald-600' : 'text-red-500'"
                        >
                            {{ tx.direction === 'credit' ? '+' : '-' }}{{ formatMoney(tx.amount) }}
                        </p>
                        <span
                            class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded-full mt-0.5"
                            :class="tx.direction === 'credit'
                                ? 'text-emerald-600 bg-emerald-50'
                                : 'text-red-500 bg-red-50'"
                        >{{ tx.direction === 'credit' ? 'Credit' : 'Debit' }}</span>
                    </div>
                </div>
            </div>

            <!-- Load more -->
            <button
                v-if="txMeta.current_page < txMeta.last_page"
                type="button"
                :disabled="loading"
                class="mt-3 w-full py-3.5 text-sm font-bold bg-gray-900 text-white rounded-2xl hover:bg-gray-800 active:bg-black transition-colors disabled:opacity-50"
                @click="loadMore"
            >
                {{ loading ? 'Loading...' : `Load more (${txMeta.total - transactions.length} remaining)` }}
            </button>
        </section>

        <!-- bottom spacer -->
        <div class="h-4"></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const MEMBER_KEY = 'public_profile_member_id';

const props = defineProps({
    meta:                { type: Object, default: () => ({}) },
    walletTransactions:  { type: Array,  default: () => [] },
    walletTxMeta:        { type: Object, default: () => ({ current_page: 1, last_page: 1, total: 0, per_page: 10 }) },
});

const transactions = ref([...props.walletTransactions]);
const txMeta       = ref({ ...props.walletTxMeta });
const loading      = ref(false);

function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

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

// If initial data was empty (old cached session), try to fetch fresh
onMounted(() => {
    if (transactions.value.length === 0 && (props.walletTxMeta?.total ?? 0) === 0) {
        // Trigger a first-page load so the view is always populated
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
    transactions.value.filter(t => t.direction === 'credit').reduce((s, t) => s + t.amount, 0)
);

const totalDebits = computed(() =>
    transactions.value.filter(t => t.direction === 'debit').reduce((s, t) => s + t.amount, 0)
);

function formatMoney(val) {
    const n = parseFloat(val ?? 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
