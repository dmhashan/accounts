<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" :title="isEdit ? 'Edit Exercise' : 'Add Exercise'" />

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="app-page-scroll">
        <form class="app-surface rounded-2xl p-4 md:p-5 grid gap-3" @submit.prevent="submit">
            <div class="grid gap-3 md:grid-cols-2">
                <AppFormField label="Exercise Name" :required="true">
                    <AppFormInput v-model="form.name" type="text" placeholder="Exercise Name" required />
                </AppFormField>
                <AppFormField label="Status" :required="true">
                    <AppSearchableDropdown
                        v-model="form.status"
                        :options="[
                            { id: 'active', label: 'Active' },
                            { id: 'inactive', label: 'Inactive' }
                        ]"
                        :option-label="option => option.label"
                        :option-key="option => option.id"
                        placeholder="Select status"
                        no-results-text="No status found."
                        :searchable="false"
                        required
                    />
                </AppFormField>
            </div>

            <div class="grid gap-2 md:grid-cols-4">
                <AppFormField label="Default Sets" :required="true">
                    <AppFormInput v-model.number="form.default_sets" type="number" min="1" placeholder="Default Sets" required />
                </AppFormField>
                <AppFormField label="Default Reps" :required="true">
                    <AppFormInput v-model="form.default_reps" type="text" placeholder="Default Reps" required />
                </AppFormField>
                <AppFormField label="Default Tempo" :required="true">
                    <AppFormInput v-model="form.default_tempo" type="text" placeholder="Default Tempo" required />
                </AppFormField>
                <AppFormField label="Default Rest" :required="true">
                    <AppFormInput v-model.number="form.default_rest" type="number" min="0" placeholder="Default Rest" required />
                </AppFormField>
            </div>

            <div class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-3 md:p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">Exercise Variations (Names)</h4>
                    <button type="button" class="text-sm font-semibold text-primary-600 dark:text-primary-400" @click="addVariation">Add Variation</button>
                </div>

                <div class="space-y-3">
                    <div v-for="(variation, index) in form.variations" :key="variation.localKey" class="rounded-lg border border-secondary-200 dark:border-secondary-700 p-3 bg-secondary-50 dark:bg-secondary-800/40 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500 dark:text-secondary-400">Variation {{ index + 1 }}</p>
                            <button type="button" class="text-xs text-red-600 dark:text-red-400" @click="removeVariation(index)">Remove</button>
                        </div>
                        <AppFormInput v-model="variation.variation_name" type="text" placeholder="Variation Name" class="dark:bg-secondary-900" required />
                    </div>
                </div>

                <p v-if="form.variations.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">No variations added. Add at least one variation for this exercise.</p>
            </div>

            <div class="flex justify-end gap-2 mt-2">
                <RouterLink to="/workout?tab=exercises" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600">Cancel</RouterLink>
                <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50" :disabled="submitting">{{ submitting ? 'Saving...' : 'Save Exercise' }}</button>
            </div>
        </form>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));

const submitting = ref(false);
const errorMessage = ref('');
const localKeySeed = ref(0);

const form = ref(defaultForm());

function nextLocalKey() {
    localKeySeed.value += 1;
    return `variation-${localKeySeed.value}`;
}

function createVariation() {
    return {
        localKey: nextLocalKey(),
        id: null,
        variation_name: '',
    };
}

function defaultForm() {
    return {
        name: '',
        status: 'active',
        default_sets: 3,
        default_reps: '10',
        default_tempo: '3-1-1-0',
        default_rest: 60,
        variations: [createVariation()],
    };
}

function addVariation() {
    form.value.variations.push(createVariation());
}

function removeVariation(index) {
    form.value.variations.splice(index, 1);
}

async function loadExercise() {
    if (!isEdit.value) {
        return;
    }

    const response = await apiRequest(`/api/exercises/${route.params.id}`);
    const exercise = response?.data || {};

    form.value = {
        name: exercise.name || '',
        status: exercise.status || 'active',
        default_sets: Number(exercise.default_sets || 1),
        default_reps: exercise.default_reps || '',
        default_tempo: exercise.default_tempo || '',
        default_rest: Number(exercise.default_rest || 0),
        variations: (exercise.variations || []).map((variation) => ({
            localKey: nextLocalKey(),
            id: variation.id || null,
            variation_name: variation.variation_name || '',
        })),
    };

    if (form.value.variations.length === 0) {
        form.value.variations = [createVariation()];
    }
}

function normalizePayload() {
    return {
        name: form.value.name,
        status: form.value.status,
        default_sets: Number(form.value.default_sets || 1),
        default_reps: form.value.default_reps,
        default_tempo: form.value.default_tempo,
        default_rest: Number(form.value.default_rest || 0),
        variations: form.value.variations,
    };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        if (isEdit.value) {
            await apiRequest(`/api/exercises/${route.params.id}`, {
                method: 'put',
                data: normalizePayload(),
            });
        } else {
            await apiRequest('/api/exercises', {
                method: 'post',
                data: normalizePayload(),
            });
        }

        router.push('/workout?tab=exercises');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save exercise.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadExercise();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load exercise data.';
    }
});
</script>
