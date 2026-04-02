<template>
    <section>
        <AppPageHeader :show-back="true" />

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-5 md:p-6 space-y-4" @submit.prevent="submit">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AppFormField label="Name" class="md:col-span-2" :required="true">
                    <AppFormInput v-model="form.name" type="text" required />
                </AppFormField>

                <AppFormField label="Email" class="md:col-span-2" :required="true">
                    <AppFormInput v-model="form.email" type="email" required />
                </AppFormField>

                <AppFormField label="Role" :required="true">
                    <AppSearchableDropdown
                        v-model="form.role_id"
                        :options="[...roles.map(role => ({ id: String(role.id), label: role.name }))]"
                        :option-label="option => option.label"
                        :option-key="option => option.id"
                        placeholder="Select role"
                        no-results-text="No roles found."
                        required
                    />
                </AppFormField>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AppFormField label="Password" :required="!isEdit" :optional="isEdit">
                    <AppFormInput v-model="form.password" type="password" :required="!isEdit" />
                </AppFormField>
                <AppFormField label="Confirm Password" :required="!isEdit" :optional="isEdit">
                    <AppFormInput v-model="form.password_confirmation" type="password" :required="!isEdit" />
                </AppFormField>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg" :disabled="submitting">
                    {{ submitting ? 'Saving...' : (isEdit ? 'Update User' : 'Create User') }}
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

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));

const roles = ref([]);
const submitting = ref(false);
const errorMessage = ref('');
const form = ref({
    name: '',
    email: '',
    role_id: '',
    password: '',
    password_confirmation: '',
});

async function loadMeta() {
    const response = await apiRequest('/api/users/meta');
    roles.value = response.roles || [];
}

async function loadUser() {
    if (!isEdit.value) return;
    const response = await apiRequest(`/api/users/${route.params.id}`);
    form.value.name = response.data?.name || '';
    form.value.email = response.data?.email || '';
    form.value.role_id = String(response.data?.role_id || '');
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            name: form.value.name,
            email: form.value.email,
            role_id: Number(form.value.role_id),
            password: form.value.password || undefined,
            password_confirmation: form.value.password_confirmation || undefined,
        };

        if (isEdit.value) {
            await apiRequest(`/api/users/${route.params.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/users', { method: 'post', data: payload });
        }

        router.push('/users');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save user.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadUser();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load form data.';
    }
});
</script>
