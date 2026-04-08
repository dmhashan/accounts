<template>
    <div>
        <div class="flex items-center gap-3 pt-12 pb-6">
            <h1 class="text-xl font-bold text-gray-900">Transactions</h1>
            <span v-if="parseFloat(meta.total_outstanding) > 0" class="ml-auto shrink-0 text-xs font-bold text-red-600 bg-red-50 border border-red-100 px-2.5 py-1 rounded-full">
                Due: {{ meta.total_outstanding }}
            </span>
        </div>
        <div v-if="!salesData.length" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p class="text-sm text-gray-400">No transactions found</p>
        </div>
        <div v-else class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50 mb-4">
            <button
                v-for="(sale, i) in salesData"
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
                    <span v-if="!sale.is_paid" class="inline-block text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full mt-0.5">Unpaid</span>
                    <span v-else class="inline-block text-[10px] font-bold text-green-700 bg-[#dcfce7] px-1.5 py-0.5 rounded-full mt-0.5">Paid</span>
                </div>
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    meta:      { type: Object, default: () => ({}) },
    salesData: { type: Array,  default: () => [] },
});

defineEmits(['open-sale']);
</script>
