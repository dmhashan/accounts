<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" />

        <div class="app-page-scroll">
            <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                {{ errorMessage }}
            </div>

            <form class="app-surface rounded-2xl p-4 md:p-6" @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <AppFormField label="Member" :required="true" class="md:col-span-2">
                        <AppSearchableDropdown
                            v-model="form.member_id"
                            :options="members"
                            :option-label="option => option.label"
                            :option-key="option => option.id"
                            placeholder="Select member..."
                            search-placeholder="Search member..."
                            no-results-text="No members found."
                            @update:modelValue="onMemberSelect"
                        />
                    </AppFormField>

                    <div v-if="selectedMember" class="md:col-span-2 rounded-lg border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/20 px-4 py-3">
                        <p class="text-sm font-semibold text-primary-800 dark:text-primary-200">{{ selectedMember.name }}</p>
                        <p class="text-xs text-primary-600 dark:text-primary-400">
                            {{ selectedMember.phone_number }}
                            <span v-if="selectedMember.payment_plan"> &bull; {{ selectedMember.payment_plan }}</span>
                            <span v-if="selectedMember.price > 0"> &bull; {{ money(selectedMember.price) }}</span>
                        </p>
                    </div>

                    <AppFormField label="Account" :required="true">
                        <AppSearchableDropdown
                            v-model="form.company_account_id"
                            :options="accounts"
                            :option-label="option => option.name"
                            :option-key="option => option.id"
                            placeholder="Select account..."
                            search-placeholder="Search account..."
                            no-results-text="No accounts found."
                        />
                    </AppFormField>

                    <AppFormField label="Amount" :required="true">
                        <AppFormInput v-model="form.amount" type="number" min="0.01" step="0.01" required />
                    </AppFormField>

                    <AppFormField label="Payment Date" :required="true">
                        <AppFormInput v-model="form.payment_date" type="date" required />
                    </AppFormField>

                    <AppFormField label="Reference" help="Receipt ID, transaction reference, etc." :optional="true">
                        <AppFormInput v-model="form.reference_number" type="text" maxlength="255" placeholder="Receipt ID, transaction reference, etc." />
                    </AppFormField>

                    <AppFormField label="Notes" class="md:col-span-2" :optional="true">
                        <AppFormTextarea v-model="form.notes" rows="3" maxlength="1000" />
                    </AppFormField>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <RouterLink to="/payments" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300">Cancel</RouterLink>
                    <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm disabled:opacity-50" :disabled="submitDisabled">
                        {{ submitting ? 'Saving...' : (isEdit ? 'Update Payment' : 'Record Payment') }}
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
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');
const members = ref([]);
const accounts = ref([]);

const form = ref({
    member_id: null,
    company_account_id: null,
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    reference_number: '',
    notes: '',
});

const selectedMember = computed(() => {
    if (!form.value.member_id) return null;
    return members.value.find(m => m.id === form.value.member_id) || null;
});

const submitDisabled = computed(() => {
    return submitting.value || !form.value.member_id || !form.value.company_account_id || !form.value.amount;
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function onMemberSelect(memberId) {
    const member = members.value.find(m => m.id === memberId);
    if (member && member.price > 0 && !form.value.amount) {
        form.value.amount = String(member.price);
    }
}

async function loadMeta() {
    const response = await apiRequest('/api/payments/meta');
    members.value = response.members || [];
    accounts.value = response.accounts || [];
    if (!isEdit.value && accounts.value.length > 0) {
        form.value.company_account_id = accounts.value[0].id;
    }
}

async function loadPayment() {
    if (!isEdit.value) return;

    const response = await apiRequest(`/api/payments/${route.params.id}`);
    const payment = response.data || {};

    form.value = {
        member_id: payment.member_id ?? null,
        company_account_id: payment.company_account_id ?? null,
        amount: payment.amount != null ? String(payment.amount) : '',
        payment_date: payment.payment_date || new Date().toISOString().slice(0, 10),
        reference_number: payment.reference_number || '',
        notes: payment.notes || '',
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            member_id: form.value.member_id,
            company_account_id: form.value.company_account_id,
            amount: form.value.amount,
            payment_date: form.value.payment_date,
            reference_number: form.value.reference_number || null,
            notes: form.value.notes || null,
        };

        if (isEdit.value) {
            await apiRequest(`/api/payments/${route.params.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/payments', { method: 'post', data: payload });
        }

        router.push('/payments');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save payment.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadPayment();
    } catch {
        errorMessage.value = 'Failed to load data.';
    }
});
</script>
