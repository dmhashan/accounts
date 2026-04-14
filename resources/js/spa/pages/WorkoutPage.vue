<template>
    <section class="app-page-frame">
        <AppPageHeader title="Workout Manager">

            <template #cta-slot>
                <RouterLink
                    v-if="activeTab === 'exercises'"
                    to="/workout/exercises/new"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold"
                >
                    Add Exercise
                </RouterLink>
                <RouterLink
                    v-else-if="activeTab === 'programs'"
                    to="/workout/programs/new"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold"
                >
                    Add Program
                </RouterLink>
                <RouterLink
                    v-else-if="activeTab === 'assignments'"
                    to="/workout/assignments/new"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold"
                >
                    Assign Program
                </RouterLink>
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="activeTab === 'exercises'" class="min-h-0 flex flex-1 flex-col">
            <div class="mb-4">
                <AppSearchField
                    v-model="exerciseSearch"
                    placeholder="Search exercises by name or status"
                />
            </div>

            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                        <article v-for="item in filteredExercises" :key="item.id" class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ item.name }}</p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ item.variations?.length || 0 }} variation(s)</p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-[11px] font-semibold" :class="item.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-secondary-200 text-secondary-700 dark:bg-secondary-700 dark:text-secondary-300'">
                                    {{ item.status }}
                                </span>
                            </div>
                            <div class="flex gap-3 text-sm">
                                <button type="button" class="text-primary-600 dark:text-primary-400" @click="openExerciseForm(item.id)">Edit</button>
                                <button type="button" class="text-red-600 dark:text-red-400" @click="removeExercise(item)">Delete</button>
                            </div>
                        </article>
                        <div v-if="filteredExercises.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No exercises found.</div>
                    </div>

                    <div class="hidden md:block app-table-scroll">
                        <table class="w-full">
                            <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Variations</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="item in filteredExercises" :key="item.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                    <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ item.variations?.length || 0 }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ item.status }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <button type="button" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3" @click="openExerciseForm(item.id)">Edit</button>
                                        <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeExercise(item)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="filteredExercises.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No exercises found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="activeTab === 'programs'" class="min-h-0 flex flex-1 flex-col">
            <div class="mb-4">
                <AppSearchField
                    v-model="programSearch"
                    placeholder="Search programs by title or duration"
                />
            </div>

            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                        <article v-for="program in filteredPrograms" :key="program.id" class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ program.title }}</p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400">{{ program.duration_weeks }} weeks</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-sm">
                                <button type="button" class="text-primary-600 dark:text-primary-400" @click="openProgramForm(program.id)">Manage</button>
                                <button type="button" class="text-red-600 dark:text-red-400" @click="removeProgram(program)">Delete</button>
                            </div>
                        </article>
                        <div v-if="filteredPrograms.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No programs found.</div>
                    </div>

                    <div class="hidden md:block app-table-scroll">
                        <table class="w-full">
                            <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Duration</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="program in filteredPrograms" :key="program.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                    <td class="px-6 py-4 text-sm font-medium text-secondary-900 dark:text-white">{{ program.title }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ program.duration_weeks }} weeks</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <button type="button" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3" @click="openProgramForm(program.id)">Manage</button>
                                        <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeProgram(program)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="filteredPrograms.length === 0">
                                    <td colspan="3" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No programs found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Tab -->
        <div v-else-if="activeTab === 'assignments'" class="min-h-0 flex flex-1 flex-col gap-4">

            <!-- Assignments list -->
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <!-- Mobile -->
                    <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                        <article v-for="rec in assignments" :key="rec.id" class="p-4 space-y-1">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ rec.member_name }}</p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400">#{{ rec.member_code }}</p>
                                </div>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0">{{ rec.effective_date }}</p>
                            </div>
                            <p class="text-xs text-secondary-700 dark:text-secondary-300">{{ rec.assigned_program_title }}</p>
                            <div class="flex gap-3 text-sm pt-1">
                                <button type="button" class="text-primary-600 dark:text-primary-400" @click="router.push('/workout/assignments/' + rec.id + '/edit')">Edit</button>
                                <button type="button" class="text-red-600 dark:text-red-400" @click="removeAssignment(rec)">Delete</button>
                            </div>
                        </article>
                        <div v-if="assignments.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No assignments yet.</div>
                    </div>
                    <!-- Desktop -->
                    <div class="hidden md:block app-table-scroll">
                        <table class="w-full">
                            <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Member</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Program</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Effective Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Assigned At</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="rec in assignments" :key="rec.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                    <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">
                                        <p class="font-medium">{{ rec.member_name }}</p>
                                        <p class="text-xs text-secondary-400">#{{ rec.member_code }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ rec.assigned_program_title }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ rec.effective_date }}</td>
                                    <td class="px-6 py-4 text-sm text-secondary-700 dark:text-secondary-300">{{ rec.created_at }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <button type="button" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3" @click="router.push('/workout/assignments/' + rec.id + '/edit')">Edit</button>
                                        <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeAssignment(rec)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="assignments.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No assignments yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit assignment modal -->
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();
const validTabs = ['exercises', 'programs', 'assignments'];

const activeTab = ref(route.path === '/workout/exercises' ? 'exercises' : route.path === '/workout/assignments' ? 'assignments' : 'programs');
const loading = ref(false);
const errorMessage = ref('');

const exercises = ref([]);
const programs = ref([]);
const exerciseSearch = ref('');
const programSearch = ref('');

// ── Assignments ──────────────────────────────────────────────────────────────
const assignments = ref([]);

// ─────────────────────────────────────────────────────────────────────────────

const filteredExercises = computed(() => {
    const query = exerciseSearch.value.trim().toLowerCase();
    if (!query) return exercises.value;

    return exercises.value.filter((item) => {
        return [item.name, item.status].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

const filteredPrograms = computed(() => {
    const query = programSearch.value.trim().toLowerCase();
    if (!query) return programs.value;

    return programs.value.filter((item) => {
        return [item.title, item.duration_weeks]
            .some((value) => String(value || '').toLowerCase().includes(query));
    });
});

async function loadExercises() {
    const response = await apiRequest('/api/exercises', { params: { per_page: 100 } });
    exercises.value = response?.data?.data || [];
}

async function loadPrograms() {
    const response = await apiRequest('/api/workout-programs', { params: { per_page: 100 } });
    programs.value = response?.data?.data || [];
}

async function loadAssignments() {
    const response = await apiRequest('/api/workout-program-assignments', { params: { per_page: 100 } });
    assignments.value = response?.data?.data || [];
}

async function loadAll() {
    loading.value = true;
    errorMessage.value = '';

    try {
        await Promise.all([loadExercises(), loadPrograms(), loadAssignments()]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load workout data.';
    } finally {
        loading.value = false;
    }
}

function openExerciseForm(id) {
    router.push(`/workout/exercises/${id}/edit`);
}

function openProgramForm(id) {
    router.push(`/workout/programs/${id}/edit`);
}

async function removeExercise(item) {
    if (!window.confirm(`Delete exercise "${item.name}"?`)) {
        return;
    }

    try {
        await apiRequest(`/api/exercises/${item.id}`, { method: 'delete' });
        await loadExercises();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete exercise.';
    }
}

async function removeProgram(item) {
    if (!window.confirm(`Delete workout program "${item.title}"?`)) {
        return;
    }

    try {
        await apiRequest(`/api/workout-programs/${item.id}`, { method: 'delete' });
        await loadPrograms();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete workout program.';
    }
}

// ── Assignment helpers ───────────────────────────────────────────────────────

async function removeAssignment(rec) {
    if (!window.confirm(`Remove assignment for "${rec.member_name}"?`)) return;

    try {
        await apiRequest(`/api/workout-program-assignments/${rec.id}`, { method: 'delete' });
        await loadAssignments();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete assignment.';
    }
}

// ─────────────────────────────────────────────────────────────────────────────

watch(
    () => route.path,
    (path) => {
        const newTab = path === '/workout/exercises' ? 'exercises' : path === '/workout/assignments' ? 'assignments' : 'programs';
        if (validTabs.includes(newTab) && newTab !== activeTab.value) activeTab.value = newTab;
    }
);

onMounted(() => {
    loadAll();
});
</script>
