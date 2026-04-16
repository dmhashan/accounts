<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" />

        <div class="app-page-scroll space-y-4">
            <div v-if="errorMessage" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                {{ errorMessage }}
            </div>

            <div v-if="loading" class="app-surface rounded-2xl p-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                Loading comparison…
            </div>

            <template v-else>
                <!-- Comparison table -->
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-secondary-200 dark:border-secondary-700">
                        <h2 class="text-sm font-semibold text-secondary-900 dark:text-white">Reconciliation Comparison</h2>
                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">{{ session?.date }}</p>
                    </div>

                    <!-- Mobile cards -->
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                        <article v-for="item in items" :key="`${item.type}-${item.reference_id}`" class="p-4 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-secondary-900 dark:text-white">{{ item.label }}</p>
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300">{{ item.type }}</span>
                                </div>
                                <span
                                    v-if="item.difference !== null"
                                    class="text-sm font-semibold"
                                    :class="differenceClass(item.difference)"
                                >
                                    {{ formatDiff(item.difference) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs text-secondary-600 dark:text-secondary-400">
                                <div>
                                    <p class="text-secondary-400 dark:text-secondary-500">Opening</p>
                                    <p class="font-medium text-secondary-800 dark:text-secondary-200">{{ item.opening_value }}</p>
                                </div>
                                <div>
                                    <p class="text-secondary-400 dark:text-secondary-500">Expected</p>
                                    <p class="font-medium text-secondary-800 dark:text-secondary-200">{{ item.expected_close }}</p>
                                </div>
                                <div>
                                    <p class="text-secondary-400 dark:text-secondary-500">Actual</p>
                                    <p class="font-medium text-secondary-800 dark:text-secondary-200">{{ item.actual_close ?? '—' }}</p>
                                </div>
                            </div>
                        </article>
                        <div v-if="items.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No items.</div>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Item</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Type</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Opening</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">System Changes</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Expected Close</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actual Close</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Difference</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="item in items" :key="`${item.type}-${item.reference_id}`" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                    <td class="px-5 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ item.label }}</td>
                                    <td class="px-5 py-4 text-sm text-secondary-500 dark:text-secondary-400">{{ item.type }}</td>
                                    <td class="px-5 py-4 text-sm text-right text-secondary-700 dark:text-secondary-300">{{ item.opening_value }}</td>
                                    <td class="px-5 py-4 text-sm text-right" :class="item.system_delta >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        {{ item.system_delta >= 0 ? '+' : '' }}{{ item.system_delta }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-right text-secondary-700 dark:text-secondary-300">{{ item.expected_close }}</td>
                                    <td class="px-5 py-4 text-sm text-right text-secondary-700 dark:text-secondary-300">{{ item.actual_close ?? '—' }}</td>
                                    <td class="px-5 py-4 text-sm text-right font-semibold" :class="differenceClass(item.difference)">
                                        {{ item.difference !== null ? formatDiff(item.difference) : '—' }}
                                    </td>
                                </tr>
                                <tr v-if="items.length === 0">
                                    <td colspan="7" class="px-5 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No items.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Adjustment reason (only when confirming, not view-only) -->
                <div v-if="!readOnly" class="app-surface rounded-2xl p-5 md:p-6 space-y-3">
                    <div v-if="hasDifferences" class="rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                        Differences detected. Please provide an adjustment reason before confirming.
                    </div>

                    <AppFormField
                        label="Adjustment Reason"
                        :required="hasDifferences"
                        :optional="!hasDifferences"
                    >
                        <AppFormTextarea
                            v-model="adjustmentReason"
                            rows="3"
                            maxlength="2000"
                            :placeholder="hasDifferences ? 'Explain the discrepancies…' : 'Optional notes…'"
                            :required="hasDifferences"
                        />
                    </AppFormField>

                    <div class="flex flex-col sm:flex-row gap-3 justify-end">
                        <button
                            type="button"
                            class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300"
                            @click="goBack"
                        >
                            ← Back to Close Form
                        </button>
                        <button
                            type="button"
                            class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium"
                            :disabled="confirming || (hasDifferences && !adjustmentReason.trim())"
                            @click="confirm"
                        >
                            {{ confirming ? 'Confirming…' : 'Confirm & Submit' }}
                        </button>
                    </div>
                </div>

                <!-- View-only adjustment reason -->
                <div v-else-if="session?.adjustment_reason" class="app-surface rounded-2xl p-5 md:p-6">
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 mb-1 uppercase font-medium tracking-wide">Adjustment Reason</p>
                    <p class="text-sm text-secondary-800 dark:text-secondary-200">{{ session.adjustment_reason }}</p>
                </div>
            </template>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route  = useRoute();
const router = useRouter();

const sessionId    = computed(() => route.params.id);
// View-only mode when accessed from history
const readOnly     = computed(() => route.query.readonly === '1' || (session.value?.status === 'closed' && route.path.includes('/sessions/')));

const loading          = ref(true);
const confirming       = ref(false);
const errorMessage     = ref('');
const session          = ref(null);
const items            = ref([]);
const adjustmentReason = ref('');

const hasDifferences = computed(() =>
    items.value.some(i => i.difference !== null && i.difference !== 0)
);

function differenceClass(diff) {
    if (diff === null || diff === undefined) return 'text-secondary-500 dark:text-secondary-400';
    if (diff === 0) return 'text-green-600 dark:text-green-400';
    return 'text-red-600 dark:text-red-400';
}

function formatDiff(diff) {
    if (diff === null || diff === undefined) return '—';
    const sign = diff > 0 ? '+' : '';
    return `${sign}${diff}`;
}

async function loadPreview() {
    loading.value = true;
    const data = await apiRequest(`/api/reconciliation/sessions/${sessionId.value}/preview`);
    session.value = data?.session ?? null;
    items.value   = data?.items ?? [];
    loading.value = false;
}

function goBack() {
    router.push(`/reconciliation/close/${sessionId.value}`);
}

async function confirm() {
    if (hasDifferences.value && !adjustmentReason.value.trim()) return;

    // Guard: all items must have close values saved (via the close form).
    if (items.value.some(i => i.actual_close === null)) {
        errorMessage.value = 'Some closing values are missing. Please go back and complete the close form.';
        return;
    }

    confirming.value   = true;
    errorMessage.value = '';

    try {
        const res = await apiRequest(`/api/reconciliation/sessions/${sessionId.value}/close`, {
            method: 'POST',
            data:   {
                adjustment_reason: adjustmentReason.value.trim() || null,
            },
        });
        if (res?.session) {
            router.push('/reconciliation');
        }
    } catch (err) {
        const errData = err?.response?.data;
        if (errData?.errors) {
            const messages = Object.values(errData.errors).flat();
            errorMessage.value = messages.join(' ');
        } else {
            errorMessage.value = errData?.message ?? 'Failed to close session.';
        }
    } finally {
        confirming.value = false;
    }
}

onMounted(loadPreview);
</script>
