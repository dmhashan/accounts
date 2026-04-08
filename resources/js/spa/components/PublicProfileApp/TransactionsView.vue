<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 pt-12 pb-4">
            <h1 class="text-xl font-bold text-gray-900">Payments</h1>
            <span v-if="parseFloat(meta.total_outstanding) > 0" class="ml-auto shrink-0 text-xs font-bold text-red-600 bg-red-50 border border-red-100 px-2.5 py-1 rounded-full">
                Due: {{ meta.total_outstanding }}
            </span>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-4">
            <button
                type="button"
                :class="activeTab === 'outstanding' ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300'"
                class="flex-1 py-2.5 text-xs font-semibold rounded-2xl transition-colors focus:outline-none"
                @click="activeTab = 'outstanding'"
            >
                Outstanding
                <span v-if="outstandingSales.length" class="ml-1 opacity-70">({{ outstandingSales.length }})</span>
            </button>
            <button
                type="button"
                :class="activeTab === 'paid' ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300'"
                class="flex-1 py-2.5 text-xs font-semibold rounded-2xl transition-colors focus:outline-none"
                @click="activeTab = 'paid'"
            >
                Paid
                <span v-if="paidSales.length" class="ml-1 opacity-70">({{ paidSales.length }})</span>
            </button>
        </div>

        <!-- Outstanding tab -->
        <div v-if="activeTab === 'outstanding'">
            <div v-if="outstandingSales.length" class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50 mb-4">
                <button
                    v-for="(sale, i) in outstandingSales"
                    :key="i"
                    type="button"
                    class="w-full flex items-center gap-4 px-5 py-4 hover:bg-gray-50 active:bg-gray-100 transition-colors focus:outline-none text-left"
                    @click="$emit('open-sale', sale)"
                >
                    <div class="flex-shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center bg-red-50">
                        <svg style="width:18px;height:18px" class="text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-6-8h6M5 8h.01M5 12h.01M5 16h.01M9 4H4a1 1 0 00-1 1v14a1 1 0 001 1h16a1 1 0 001-1V5a1 1 0 00-1-1h-5"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">Invoice #{{ sale.id }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ sale.created_at }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-bold text-gray-900">{{ sale.total_amount }}</p>
                        <span class="inline-block text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full mt-0.5">Unpaid</span>
                    </div>
                </button>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm text-gray-400">No outstanding transactions</p>
            </div>
        </div>

        <!-- Paid tab -->
        <div v-if="activeTab === 'paid'">
            <div v-if="paidSales.length" class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50 mb-4">
                <button
                    v-for="(sale, i) in paidSales"
                    :key="i"
                    type="button"
                    class="w-full flex items-center gap-4 px-5 py-4 hover:bg-gray-50 active:bg-gray-100 transition-colors focus:outline-none text-left"
                    @click="$emit('open-sale', sale)"
                >
                    <div class="flex-shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center bg-gray-100">
                        <svg style="width:18px;height:18px" class="text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-6-8h6M5 8h.01M5 12h.01M5 16h.01M9 4H4a1 1 0 00-1 1v14a1 1 0 001 1h16a1 1 0 001-1V5a1 1 0 00-1-1h-5"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">Invoice #{{ sale.id }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ sale.created_at }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-bold text-gray-900">{{ sale.total_amount }}</p>
                        <span class="inline-block text-[10px] font-bold text-green-700 bg-[#dcfce7] px-1.5 py-0.5 rounded-full mt-0.5">Paid</span>
                    </div>
                </button>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm text-gray-400">No paid transactions</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    meta:      { type: Object, default: () => ({}) },
    salesData: { type: Array,  default: () => [] },
});

defineEmits(['open-sale']);

const activeTab = ref('outstanding');

const outstandingSales = computed(() => props.salesData.filter(s => !s.is_paid));
const paidSales        = computed(() => props.salesData.filter(s =>  s.is_paid));
</script>
