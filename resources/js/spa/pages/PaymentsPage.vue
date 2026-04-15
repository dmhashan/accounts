<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <AppHeaderAction v-if="canManage" to="/payments/new" :icon="CreditCard" label="New Payment" />
            </template>
        </AppPageHeader>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                    {{ errorMessage }}
                </div>

                <div class="app-surface rounded-2xl overflow-hidden">
                    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading payments...</div>

                    <template v-else>
                        <!-- Mobile list -->
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article v-for="payment in payments" :key="payment.id" class="p-4 space-y-1 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors" @click="router.push('/payments/' + payment.id)">
                                <div class="flex justify-between items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ payment.member_name }}</p>
                                        <p class="text-xs text-secondary-500 dark:text-secondary-400">
                                            {{ payment.payment_date }} &bull; {{ payment.account_name }}
                                        </p>
                                        <p class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ money(payment.amount) }}</p>
                                        <p v-if="payment.notes" class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ payment.notes }}</p>
                                    </div>
                                </div>
                            </article>

                            <div v-if="payments.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No payments recorded.</div>
                        </div>

                        <!-- Desktop table -->
                        <div class="hidden md:block app-table-scroll">
                            <table class="w-full">
                                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Member</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Account</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Notes</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                    <tr v-for="payment in payments" :key="payment.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50 cursor-pointer" @click="router.push('/payments/' + payment.id)">
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ payment.member_name }}</p>
                                            <p v-if="payment.member_phone" class="text-xs text-secondary-500 dark:text-secondary-400">{{ payment.member_phone }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300 whitespace-nowrap">{{ payment.payment_date }}</td>
                                        <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ payment.account_name }}</td>
                                        <td class="px-6 py-4 text-sm text-secondary-500 dark:text-secondary-400 max-w-xs truncate">{{ payment.notes || '—' }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-right text-primary-600 dark:text-primary-400 whitespace-nowrap">{{ money(payment.amount) }}</td>
                                    </tr>
                                    <tr v-if="payments.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-secondary-500 dark:text-secondary-400">No payments recorded.</td>
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
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { CreditCard } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPagination from '../components/AppPagination.vue';

const context = useAppContext();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const payments = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });

const canManage = computed(() => Boolean(context.permissions?.paymentsManage));

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function loadPayments(page = 1) {
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

onMounted(() => loadPayments());
</script>
