<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <AppHeaderAction
                    v-if="canManage"
                    to="/reconciliation/config"
                    :icon="Settings"
                    label="Configure"
                />
            </template>
        </AppPageHeader>

        <div class="app-page-scroll space-y-4">
            <div v-if="loading" class="app-surface rounded-2xl p-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                Loading…
            </div>

            <template v-else>
                <!-- Today's session card -->
                <div class="app-surface rounded-2xl p-5 md:p-6 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-secondary-900 dark:text-white">Today's Reconciliation</h2>
                            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">{{ todayLabel }}</p>
                        </div>
                        <span
                            class="px-3 py-1 text-xs font-semibold rounded-full"
                            :class="statusClass"
                        >{{ statusLabel }}</span>
                    </div>

                    <!-- Not started -->
                    <template v-if="!session">
                        <p class="text-sm text-secondary-600 dark:text-secondary-300">
                            No session has been opened yet. Start by recording opening balances and stock counts.
                        </p>
                        <RouterLink
                            v-if="canPerform"
                            to="/reconciliation/open"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <ClipboardList :size="16" />
                            Open Session
                        </RouterLink>
                    </template>

                    <!-- Open session -->
                    <template v-else-if="session.status === 'open'">
                        <p class="text-sm text-secondary-600 dark:text-secondary-300">
                            Session opened by <strong>{{ session.opened_by }}</strong>. Enter closing counts to complete the day.
                        </p>
                        <RouterLink
                            v-if="canPerform"
                            :to="`/reconciliation/close/${session.id}`"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <ClipboardCheck :size="16" />
                            Enter Closing Counts
                        </RouterLink>
                    </template>

                    <!-- Closed session -->
                    <template v-else>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                            <div>
                                <p class="text-secondary-500 dark:text-secondary-400 text-xs">Opened by</p>
                                <p class="font-medium text-secondary-900 dark:text-white">{{ session.opened_by }}</p>
                            </div>
                            <div>
                                <p class="text-secondary-500 dark:text-secondary-400 text-xs">Closed by</p>
                                <p class="font-medium text-secondary-900 dark:text-white">{{ session.closed_by }}</p>
                            </div>
                            <div>
                                <p class="text-secondary-500 dark:text-secondary-400 text-xs">Closed at</p>
                                <p class="font-medium text-secondary-900 dark:text-white">{{ formatTime(session.closed_at) }}</p>
                            </div>
                        </div>
                        <div v-if="session.adjustment_reason" class="rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                            <span class="font-medium">Adjustment Reason:</span> {{ session.adjustment_reason }}
                        </div>
                        <RouterLink
                            :to="`/reconciliation/sessions/${session.id}`"
                            class="inline-flex items-center gap-2 text-sm text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            View full comparison →
                        </RouterLink>
                    </template>
                </div>

                <!-- History (admin only) -->
                <div v-if="canManage" class="app-surface rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">Session History</h3>
                    </div>

                    <div v-if="historyLoading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400 text-center">Loading…</div>

                    <!-- Mobile list -->
                    <div v-else class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                        <article v-for="s in history" :key="s.id" class="p-4 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-secondary-900 dark:text-white">{{ s.date }}</p>
                                <span class="px-2 py-0.5 text-xs rounded-full" :class="s.status === 'closed' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300'">{{ s.status }}</span>
                            </div>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400">By {{ s.opened_by }}</p>
                            <RouterLink :to="`/reconciliation/sessions/${s.id}`" class="text-xs text-primary-600 dark:text-primary-400">View →</RouterLink>
                        </article>
                        <div v-if="history.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No sessions yet.</div>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden md:block">
                        <table class="w-full">
                            <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Opened By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Closed By</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="s in history" :key="s.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                    <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ s.date }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 text-xs rounded-full" :class="s.status === 'closed' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300'">{{ s.status }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ s.opened_by }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ s.closed_by ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <RouterLink :to="`/reconciliation/sessions/${s.id}`" class="text-primary-600 dark:text-primary-400 hover:underline">View</RouterLink>
                                    </td>
                                </tr>
                                <tr v-if="history.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No sessions yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="app-page-pagination" v-if="historyMeta.last_page > 1">
                        <AppPagination
                            :current-page="historyMeta.current_page"
                            :last-page="historyMeta.last_page"
                            :per-page="historyMeta.per_page"
                            :total="historyMeta.total"
                            :disabled="historyLoading"
                            @page-change="loadHistory"
                        />
                    </div>
                </div>
            </template>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { ClipboardList, ClipboardCheck, Settings } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPagination from '../components/AppPagination.vue';

const context = useAppContext();
const canPerform = computed(() => context.permissions?.reconciliationPerform);
const canManage  = computed(() => context.permissions?.reconciliationManage);

const loading  = ref(true);
const session  = ref(null);

const historyLoading = ref(false);
const history        = ref([]);
const historyMeta    = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

const todayLabel = computed(() => {
    const d = new Date();
    return d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const statusLabel = computed(() => {
    if (!session.value) return 'Not Started';
    return session.value.status === 'open' ? 'Open' : 'Closed';
});

const statusClass = computed(() => {
    if (!session.value) return 'bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300';
    if (session.value.status === 'open') return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300';
    return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300';
});

function formatTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

async function loadToday() {
    loading.value = true;
    const data = await apiRequest('/api/reconciliation/today');
    session.value = data?.session ?? null;
    loading.value = false;
}

async function loadHistory(page = 1) {
    historyLoading.value = true;
    const data = await apiRequest(`/api/reconciliation?page=${page}`);
    history.value      = data?.data ?? [];
    historyMeta.value  = data?.meta ?? historyMeta.value;
    historyLoading.value = false;
}

onMounted(() => {
    loadToday();
    if (canManage.value) loadHistory();
});
</script>
