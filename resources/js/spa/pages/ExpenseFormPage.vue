<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" />

        <div class="app-page-scroll">
        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AppFormField label="Account" :required="true">
                    <AppFormSelect v-model="form.company_account_id" required>
                        <option value="">Select account</option>
                        <option v-for="account in accounts" :key="account.id" :value="String(account.id)">
                            {{ account.name }} &bull; {{ money(account.current_balance) }}
                        </option>
                    </AppFormSelect>
                </AppFormField>

                <AppFormField label="Category" :required="true">
                    <AppFormInput
                        v-if="form.category_custom"
                        v-model="form.category"
                        type="text"
                        required
                        maxlength="255"
                        placeholder="Enter custom category"
                    />
                    <AppFormSelect
                        v-else
                        v-model="form.category"
                        required
                        @change="handleCategoryChange"
                    >
                        <option value="">Select category</option>
                        <option v-for="cat in expenseCategories" :key="cat" :value="cat">{{ cat }}</option>
                        <option value="__other__">Other (custom)</option>
                    </AppFormSelect>
                    <button
                        v-if="form.category_custom"
                        type="button"
                        class="mt-1 text-xs text-primary-600 dark:text-primary-400 hover:underline"
                        @click="resetCategoryToSelect"
                    >
                        Choose from list
                    </button>
                </AppFormField>

                <AppFormField label="Amount" :required="true">
                    <AppFormInput v-model="form.amount" type="number" min="0.01" step="0.01" required />
                </AppFormField>

                <AppFormField label="Expense Date" :required="true">
                    <AppFormInput v-model="form.expense_date" type="date" required />
                </AppFormField>

                <AppFormField label="Reference" class="md:col-span-2" help="Invoice number, receipt ID, etc." :optional="true">
                    <AppFormInput v-model="form.reference_number" type="text" maxlength="255" placeholder="Invoice number, receipt ID, etc." />
                </AppFormField>

                <AppFormField label="Notes" class="md:col-span-2" :optional="true">
                    <AppFormTextarea v-model="form.notes" rows="3" maxlength="1000" />
                </AppFormField>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <RouterLink :to="{ path: '/accounts', query: { tab: 'expenses' } }" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300">Cancel</RouterLink>
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update Expense' : 'Record Expense') }}
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
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');
const accounts = ref([]);

const expenseCategories = [
    'Building Rent',
    'Electricity',
    'Water',
    'Internet',
    'Equipment Maintenance',
    'Equipment Purchase',
    'Cleaning Supplies',
    'Staff Salaries',
    'Insurance',
    'Marketing',
    'Printing & Stationery',
    'Fuel & Transport',
    'Miscellaneous',
];

const form = ref({
    company_account_id: '',
    category: '',
    category_custom: false,
    amount: '',
    expense_date: new Date().toISOString().slice(0, 10),
    reference_number: '',
    notes: '',
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function handleCategoryChange() {
    if (form.value.category === '__other__') {
        form.value.category = '';
        form.value.category_custom = true;
    }
}

function resetCategoryToSelect() {
    form.value.category = '';
    form.value.category_custom = false;
}

async function loadMeta() {
    const response = await apiRequest('/api/accounts/meta');
    accounts.value = response.accounts || [];
}

async function loadExpense() {
    if (!isEdit.value) {
        return;
    }

    const response = await apiRequest(`/api/accounts/expenses/${route.params.id}`);
    const expense = response.data || {};

    const isKnownCategory = expenseCategories.includes(expense.category);

    form.value = {
        company_account_id: expense.company_account_id ? String(expense.company_account_id) : '',
        category: expense.category || '',
        category_custom: !isKnownCategory && Boolean(expense.category),
        amount: expense.amount != null ? String(expense.amount) : '',
        expense_date: expense.expense_date || new Date().toISOString().slice(0, 10),
        reference_number: expense.reference_number || '',
        notes: expense.notes || '',
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            company_account_id: form.value.company_account_id,
            category: form.value.category,
            amount: form.value.amount,
            expense_date: form.value.expense_date,
            reference_number: form.value.reference_number || null,
            notes: form.value.notes || null,
        };

        if (isEdit.value) {
            await apiRequest(`/api/accounts/expenses/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
        } else {
            await apiRequest('/api/accounts/expenses', {
                method: 'post',
                data: payload,
            });
        }

        router.push({ path: '/accounts', query: { tab: 'expenses' } });
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save expense.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadExpense();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load expense data.';
    }
});
</script>
