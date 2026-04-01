<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" title="Edit Assignment" />

        <div v-if="loadError" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ loadError }}
        </div>

        <div v-if="loading" class="flex flex-1 items-center justify-center text-sm text-secondary-500 dark:text-secondary-400">
            Loading…
        </div>

        <div v-else-if="!assignment" class="flex flex-1 items-center justify-center text-sm text-secondary-500 dark:text-secondary-400">
            Assignment not found.
        </div>

        <div v-else class="app-page-scroll">
            <form class="app-surface rounded-2xl p-4 md:p-5 space-y-4" @submit.prevent="submit">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <AppFormField label="Workout Program" :required="true">
                        <AppSearchableDropdown
                            v-model="form.program_id"
                            :options="programs.map(p => ({ id: p.id, label: p.title }))"
                            :option-label="option => option.label"
                            :option-key="option => option.id"
                            placeholder="Select program"
                            no-results-text="No programs found."
                            required
                        />
                    </AppFormField>
                    <AppFormField label="Effective Date" :required="true">
                        <AppFormInput v-model="form.effective_date" type="date" required />
                    </AppFormField>
                </div>

                <div v-if="formError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                    {{ formError }}
                </div>

                <div class="flex justify-end gap-3">
                    <RouterLink
                        to="/workout?tab=assignments"
                        class="px-4 py-2 rounded-lg text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700"
                    >
                        Cancel
                    </RouterLink>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white text-sm font-semibold"
                    >
                        {{ submitting ? 'Saving…' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';

const route = useRoute();
const router = useRouter();

const id = Number(route.params.id);

const loading = ref(true);
const loadError = ref('');
const formError = ref('');
const submitting = ref(false);

const assignment = ref(null);
const programs = ref([]);

const form = ref({
    program_id: '',
    member_id: null,
    effective_date: '',
});

async function loadData() {
    loading.value = true;
    loadError.value = '';
    try {
        const [assignmentsRes, programsRes] = await Promise.all([
            apiRequest('/api/workout-program-assignments', { params: { per_page: 200 } }),
            apiRequest('/api/workout-programs', { params: { per_page: 100 } }),
        ]);

        programs.value = programsRes?.data?.data || [];

        const list = assignmentsRes?.data?.data || [];
        assignment.value = list.find((a) => a.id === id) || null;

        if (assignment.value) {
            form.value = {
                program_id: assignment.value.source_program_id,
                member_id: assignment.value.member_id,
                effective_date: assignment.value.effective_date || '',
            };
        }
    } catch (error) {
        loadError.value = error?.response?.data?.message || 'Failed to load data.';
    } finally {
        loading.value = false;
    }
}

async function submit() {
    formError.value = '';
    submitting.value = true;
    try {
        await apiRequest(`/api/workout-program-assignments/${id}`, {
            method: 'put',
            data: {
                program_id: form.value.program_id,
                member_id: form.value.member_id,
                effective_date: form.value.effective_date,
            },
        });
        router.push('/workout?tab=assignments');
    } catch (error) {
        formError.value = error?.response?.data?.message || 'Failed to update assignment.';
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    loadData();
});
</script>
