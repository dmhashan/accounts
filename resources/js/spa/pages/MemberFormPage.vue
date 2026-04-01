<template>
    <section>
        <AppPageHeader :show-back="true" />

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-5 md:p-6 space-y-4" @submit.prevent="submit">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AppFormField label="First Name" :required="true">
                    <AppFormInput v-model="form.first_name" required />
                </AppFormField>
                <AppFormField label="Last Name" :required="true">
                    <AppFormInput v-model="form.last_name" required />
                </AppFormField>
                <AppFormField label="Username" :required="true">
                    <AppFormInput v-model="form.username" required />
                </AppFormField>
                <AppFormField label="Gender" :required="true">
                    <AppSearchableDropdown
                        v-model="form.gender"
                        :options="[
                            { id: '', label: 'Select gender' },
                            { id: 'male', label: 'Male' },
                            { id: 'female', label: 'Female' }
                        ]"
                        :option-label="option => option.label"
                        :option-key="option => option.id"
                        placeholder="Select gender"
                        no-results-text="No gender found."
                        :searchable="false"
                        required
                    />
                </AppFormField>
                <AppFormField label="Email" :required="true">
                    <AppFormInput v-model="form.email" type="email" required />
                </AppFormField>
                <AppFormField label="Phone Number" :required="true">
                    <AppFormInput v-model="form.phone_number" required />
                </AppFormField>
                <AppFormField label="NIC" :optional="true">
                    <AppFormInput v-model="form.nic" />
                </AppFormField>
                <AppFormField label="Date of Birth" :required="true">
                    <AppFormInput v-model="form.date_of_birth" type="date" required />
                </AppFormField>
                <AppFormField label="Age" :required="true">
                    <AppFormInput v-model="form.age" type="number" min="1" max="120" required />
                </AppFormField>
                <AppFormField label="Member Role" :required="true">
                    <AppFormInput v-model="form.member_role" required />
                </AppFormField>
                <AppFormField label="Payment Plan" :required="true">
                    <AppFormInput v-model="form.payment_plan" required />
                </AppFormField>
                <AppFormField label="Price" :required="true">
                    <AppFormInput v-model="form.price" type="number" step="0.01" min="0" required />
                </AppFormField>
                <AppFormField label="Admission Fee" :optional="true">
                    <AppFormInput v-model="form.admission_fee" type="number" step="0.01" min="0" />
                </AppFormField>
                <AppFormField label="Joined Date" :required="true">
                    <AppFormInput v-model="form.joined_date" type="date" required />
                </AppFormField>
                <AppFormField label="Address" class="md:col-span-2" :optional="true">
                    <AppFormTextarea v-model="form.address" rows="2" />
                </AppFormField>
                <AppFormField label="Comment" class="md:col-span-2" :optional="true">
                    <AppFormTextarea v-model="form.comment" rows="2" />
                </AppFormField>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update Member' : 'Create Member') }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');

const form = ref({
    first_name: '',
    last_name: '',
    username: '',
    gender: '',
    email: '',
    phone_number: '',
    nic: '',
    date_of_birth: '',
    age: '',
    address: '',
    member_role: '',
    admission_fee: '',
    payment_plan: '',
    price: '',
    joined_date: '',
    comment: '',
});

async function loadMember() {
    if (!isEdit.value) return;
    const response = await apiRequest(`/api/members/${route.params.id}`);
    form.value = {
        ...form.value,
        ...response.data,
        age: response.data?.age ? String(response.data.age) : '',
        admission_fee: response.data?.admission_fee ?? '',
        price: response.data?.price ?? '',
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        let memberId = route.params.id;

        if (isEdit.value) {
            await apiRequest(`/api/members/${route.params.id}`, { method: 'put', data: form.value });
        } else {
            const response = await apiRequest('/api/members', { method: 'post', data: form.value });
            memberId = response?.data?.id;
        }

        router.push(memberId ? `/members/${memberId}` : '/members');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save member.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMember();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load member.';
    }
});
</script>
