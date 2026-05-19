<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
        Sales History
      </h2>
    </div>
    <div v-if="salesLoading" class="px-5 py-6 text-center text-sm text-secondary-400">
      Loading...
    </div>
    <div v-else-if="memberSales.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">
      No sales found for this member.
    </div>
    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
      <RouterLink
        v-for="sale in memberSales"
        :key="sale.id"
        :to="`/sales/${sale.id}`"
        class="flex items-center justify-between px-5 py-3 gap-3 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors"
      >
        <div class="min-w-0">
          <p class="text-sm font-semibold text-secondary-900 dark:text-white">
            {{ formatMoney(sale.total_amount) }}
          </p>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
            {{ sale.created_at }}
            <span v-if="sale.items_count" class="ml-1 opacity-70">&bull; {{ sale.items_count }} item{{ sale.items_count !== 1 ? 's' : '' }}</span>
          </p>
          <p v-if="sale.reference_number" class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5">
            Ref: {{ sale.reference_number }}
          </p>
        </div>
        <div class="shrink-0 flex flex-col items-end gap-1">
          <span
            class="inline-block px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full border"
            :class="sale.is_paid
              ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'
              : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800'"
          >
            {{ sale.is_paid ? 'Paid' : 'Outstanding' }}
          </span>
          <span v-if="!sale.is_paid" class="text-xs font-semibold text-amber-600 dark:text-amber-400">Balance: {{ formatMoney(sale.balance) }}</span>
        </div>
      </RouterLink>
    </div>
    <div v-if="salesMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
      <p class="text-xs text-secondary-500 dark:text-secondary-400">
        Page {{ salesMeta.current_page }} of {{ salesMeta.last_page }}
      </p>
      <div class="flex gap-1">
        <button
          type="button"
          class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
          :disabled="salesMeta.current_page <= 1"
          @click="loadMemberSales(salesMeta.current_page - 1)"
        >
          Prev
        </button>
        <button
          type="button"
          class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
          :disabled="salesMeta.current_page >= salesMeta.last_page"
          @click="loadMemberSales(salesMeta.current_page + 1)"
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

const { formatMoney } = useMemberFormatters();

const salesLoading = ref(false);
const memberSales = ref([]);
const salesMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

async function loadMemberSales(page = 1) {
    salesLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/sales?page=${page}&per_page=15`);
        memberSales.value = res.data || [];
        salesMeta.value = res.meta || salesMeta.value;
    } catch { /* ignore */ } finally {
        salesLoading.value = false;
    }
}

defineExpose({ loadMemberSales });
</script>
