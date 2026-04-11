<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" />

        <div class="app-page-scroll">
            <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                {{ errorMessage }}
            </div>

            <div v-if="loading" class="app-surface rounded-2xl p-8 text-center text-sm text-secondary-500 dark:text-secondary-400">
                Loading…
            </div>

            <form v-else class="space-y-4" @submit.prevent="goToComparison">
                <!-- Accounts -->
                <div v-if="accounts.length" class="app-surface rounded-2xl p-5 md:p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide">Account Balances — Closing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppFormField v-for="item in accounts" :key="item.reference_id" :label="item.label" :required="true">
                            <AppFormInput
                                v-model.number="accountEntries[item.reference_id]"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                            <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                                Opening: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ item.opening_value }}</span>
                                &nbsp;|&nbsp;
                                Expected: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ item.expected_close }}</span>
                            </p>
                        </AppFormField>
                    </div>
                </div>

                <!-- Stock -->
                <div v-if="products.length" class="app-surface rounded-2xl p-5 md:p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide">Stock Quantities — Closing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppFormField v-for="item in products" :key="item.reference_id" :label="item.label" :required="true">
                            <AppFormInput
                                v-model.number="stockEntries[item.reference_id]"
                                type="number"
                                min="0"
                                required
                            />
                            <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                                Opening: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ item.opening_value }}</span>
                                &nbsp;|&nbsp;
                                Expected: <span class="font-medium text-secondary-600 dark:text-secondary-300">{{ item.expected_close }}</span>
                            </p>
                        </AppFormField>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <RouterLink to="/reconciliation" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300">
                        Cancel
                    </RouterLink>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium"
                        :disabled="saving"
                    >
                        {{ saving ? 'Saving…' : 'Review Comparison →' }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';

const route  = useRoute();
const router = useRouter();

const sessionId    = computed(() => route.params.id);
const loading      = ref(true);
const saving       = ref(false);
const errorMessage = ref('');

const allItems       = ref([]);
const accountEntries = ref({});
const stockEntries   = ref({});

const accounts = computed(() => allItems.value.filter(i => i.type === 'account'));
const products = computed(() => allItems.value.filter(i => i.type === 'stock'));

async function loadPreview() {
    loading.value = true;
    const data = await apiRequest(`/api/reconciliation/sessions/${sessionId.value}/preview`);
    allItems.value = data?.items ?? [];
    // Pre-fill with existing close entries if any
    allItems.value.forEach(item => {
        if (item.type === 'account') {
            accountEntries.value[item.reference_id] = item.actual_close ?? '';
        } else {
            stockEntries.value[item.reference_id] = item.actual_close ?? '';
        }
    });
    loading.value = false;
}

async function goToComparison() {
    saving.value   = true;
    errorMessage.value = '';

    const entries = [
        ...accounts.value.map(i => ({
            type:          'account',
            reference_id:  i.reference_id,
            entered_value: accountEntries.value[i.reference_id],
        })),
        ...products.value.map(i => ({
            type:          'stock',
            reference_id:  i.reference_id,
            entered_value: stockEntries.value[i.reference_id],
        })),
    ];

    try {
        await apiRequest(`/api/reconciliation/sessions/${sessionId.value}/save-close`, {
            method: 'POST',
            data:   { entries },
        });
        router.push(`/reconciliation/comparison/${sessionId.value}`);
    } catch (err) {
        const errData = err?.response?.data;
        if (errData?.errors) {
            const messages = Object.values(errData.errors).flat();
            errorMessage.value = messages.join(' ');
        } else {
            errorMessage.value = errData?.message ?? 'Failed to save entries.';
        }
    } finally {
        saving.value = false;
    }
}

onMounted(loadPreview);
</script>
