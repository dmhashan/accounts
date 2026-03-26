<template>
    <section>
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Accounts</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">{{ isEdit ? 'Edit Account' : 'Add Account' }}</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Define company accounts with opening balances.</p>
                </div>
                <RouterLink to="/accounts" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-700 text-secondary-700 dark:text-secondary-300 text-sm font-semibold transition-all hover:bg-secondary-50 dark:hover:bg-secondary-800">
                    ← Back to Accounts
                </RouterLink>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Account Name</label>
                    <input v-model="form.name" type="text" required maxlength="255" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Opening Balance</label>
                    <input v-model="form.opening_balance" type="number" step="0.01" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Description</label>
                    <textarea v-model="form.description" rows="4" maxlength="1000" class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"></textarea>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <RouterLink to="/accounts" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm">Cancel</RouterLink>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update Account' : 'Create Account') }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');

const form = ref({
    name: '',
    opening_balance: '0.00',
    description: '',
});

async function loadAccount() {
    if (!isEdit.value) {
        return;
    }

    const response = await apiRequest(`/api/accounts/${route.params.id}`);
    const account = response.data || {};

    form.value = {
        name: account.name || '',
        opening_balance: String(account.opening_balance ?? '0.00'),
        description: account.description || '',
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            name: form.value.name,
            opening_balance: form.value.opening_balance,
            description: form.value.description || null,
        };

        if (isEdit.value) {
            await apiRequest(`/api/accounts/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
        } else {
            await apiRequest('/api/accounts', {
                method: 'post',
                data: payload,
            });
        }

        router.push('/accounts');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save account.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadAccount();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load account data.';
    }
});
</script>