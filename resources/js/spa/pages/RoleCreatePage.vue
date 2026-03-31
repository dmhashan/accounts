<template>
    <section>
        <AppPageHeader :show-back="true" />

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="app-surface rounded-2xl p-5 md:p-6" @submit.prevent="createRole">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AppFormField label="Name" :required="true">
                    <AppFormInput v-model="form.name" type="text" required />
                </AppFormField>
                <AppFormField label="Slug" :required="true">
                    <AppFormInput v-model="form.slug" type="text" required />
                </AppFormField>
                <AppFormField label="Description" class="md:col-span-2" :optional="true">
                    <AppFormTextarea v-model="form.description" rows="3" />
                </AppFormField>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row gap-2 sm:justify-end">
                <RouterLink to="/roles" class="inline-flex items-center justify-center px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-200">
                    Cancel
                </RouterLink>
                <button type="submit" :disabled="saving" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg disabled:opacity-50">
                    {{ saving ? 'Creating...' : 'Create Role' }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const router = useRouter();
const saving = ref(false);
const errorMessage = ref('');
const form = ref({
    name: '',
    slug: '',
    description: '',
});

async function createRole() {
    saving.value = true;
    errorMessage.value = '';

    try {
        await apiRequest('/api/roles', {
            method: 'post',
            data: {
                name: form.value.name,
                slug: form.value.slug,
                description: form.value.description,
            },
        });

        router.push('/roles');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to create role.';
    } finally {
        saving.value = false;
    }
}
</script>
