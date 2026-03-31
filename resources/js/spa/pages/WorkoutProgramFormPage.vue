<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" :title="isEdit ? 'Edit Workout Program' : 'Create Workout Program'">
            <template #extra-slot>
                <div class="inline-flex rounded-xl app-surface-soft p-1">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                        :class="builderTab === 'builder' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                        @click="builderTab = 'builder'"
                    >
                        Builder
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                        :class="builderTab === 'preview' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                        @click="builderTab = 'preview'"
                    >
                        Preview
                    </button>
                </div>
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div v-if="builderTab === 'builder'" class="app-page-scroll pr-1">
            <section class="space-y-4">
                <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
                    <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">Program Details</h4>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <AppFormField label="Program Title" class="md:col-span-2" :required="true">
                            <AppFormInput v-model="builderForm.title" type="text" placeholder="Program title" required />
                        </AppFormField>
                        <AppFormField label="Description" class="md:col-span-2" :optional="true">
                            <AppFormTextarea v-model="builderForm.description" rows="4" placeholder="Program description" />
                        </AppFormField>
                    </div>
                </article>

                <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">Workout Days</h4>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400">Build the exact day-by-day structure shown in the final plan.</p>
                        </div>
                        <button type="button" class="rounded-lg bg-primary-600 hover:bg-primary-700 px-3 py-2 text-sm font-semibold text-white" @click="addBuilderDay">Add Day</button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <article v-for="(day, dayIndex) in orderedBuilderDays" :key="day.localKey" class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 overflow-hidden">
                            <div class="border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800 px-4 py-3 flex items-center justify-between gap-3">
                                <div class="grid flex-1 gap-2 md:grid-cols-[120px_1fr]">
                                    <AppFormField label="Day Number">
                                        <AppFormInput v-model.number="day.day_number" type="number" min="1" max="7" placeholder="Day #" />
                                    </AppFormField>
                                    <AppFormField label="Day Title">
                                        <AppFormInput v-model="day.title" type="text" placeholder="Day title (Push / Pull / Legs)" />
                                    </AppFormField>
                                </div>
                                <button type="button" class="text-sm font-semibold text-red-600 dark:text-red-400" @click="removeBuilderDay(dayIndex)">Remove</button>
                            </div>

                            <div class="p-4 space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500 dark:text-secondary-400">Exercise Rows</p>
                                    <button type="button" class="text-sm font-semibold text-primary-600 dark:text-primary-400" @click="addExerciseRow(day)">Add Exercise Row</button>
                                </div>

                                <div class="space-y-3">
                                    <div v-for="(row, rowIndex) in orderedExerciseRows(day.exercises)" :key="row.localKey" class="rounded-xl border border-secondary-200 dark:border-secondary-700 p-3 bg-secondary-50/70 dark:bg-secondary-800/40">
                                        <div class="flex items-center justify-between gap-2 mb-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500 dark:text-secondary-400">Exercise {{ rowIndex + 1 }}</p>
                                            <button type="button" class="text-xs text-red-600 dark:text-red-400" @click="removeExerciseRow(day, rowIndex)">Remove</button>
                                        </div>
                                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-6">
                                            <AppFormField label="Exercise" class="xl:col-span-2">
                                                <AppFormSelect v-model.number="row.exercise_id" @change="handleExerciseChange(row)">
                                                    <option :value="null">Select exercise</option>
                                                    <option v-for="exercise in exercises" :key="exercise.id" :value="exercise.id">{{ exercise.name }}</option>
                                                </AppFormSelect>
                                            </AppFormField>
                                            <AppFormField label="Week 1 / 3">
                                                <AppFormSelect v-model="row.w1_w3_exercise" :disabled="!row.exercise_id">
                                                    <option value="">W1 / W3 Variation</option>
                                                    <option v-for="variation in getExerciseVariationOptions(row)" :key="`${row.localKey}-w13-${variation.id}`" :value="variation.variation_name">{{ variation.variation_name }}</option>
                                                </AppFormSelect>
                                            </AppFormField>
                                            <AppFormField label="Week 2 / 4">
                                                <AppFormSelect v-model="row.w2_w4_exercise" :disabled="!row.exercise_id">
                                                    <option value="">W2 / W4 Variation</option>
                                                    <option v-for="variation in getExerciseVariationOptions(row)" :key="`${row.localKey}-w24-${variation.id}`" :value="variation.variation_name">{{ variation.variation_name }}</option>
                                                </AppFormSelect>
                                            </AppFormField>
                                            <AppFormField label="Sets">
                                                <AppFormInput v-model.number="row.sets" type="number" min="1" placeholder="Sets" />
                                            </AppFormField>
                                        </div>
                                        <div class="mt-2 grid gap-2 md:grid-cols-3 xl:grid-cols-3">
                                            <AppFormField label="Reps">
                                                <AppFormInput v-model="row.reps" type="text" placeholder="Reps" />
                                            </AppFormField>
                                            <AppFormField label="Tempo">
                                                <AppFormInput v-model="row.tempo" type="text" placeholder="Tempo" />
                                            </AppFormField>
                                            <AppFormField label="Rest Seconds">
                                                <AppFormInput v-model.number="row.rest_seconds" type="number" min="0" placeholder="Rest Seconds" />
                                            </AppFormField>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <p v-if="builderDays.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">No workout days added yet.</p>
                    </div>
                </article>

                <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">Core Section</h4>
                        <button type="button" class="text-sm font-semibold text-primary-600 dark:text-primary-400" @click="addCoreExtra">Add Row</button>
                    </div>
                    <div class="mt-3 space-y-3">
                        <div v-for="(item, index) in builderCoreExtras" :key="item.localKey" class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500 dark:text-secondary-400">Core Row {{ index + 1 }}</p>
                                <button type="button" class="text-xs text-red-600 dark:text-red-400" @click="removeCoreExtra(index)">Remove</button>
                            </div>
                            <AppFormField label="Exercise Name">
                                <AppFormInput v-model="item.exercise_name" type="text" placeholder="Exercise name" />
                            </AppFormField>
                            <div class="grid grid-cols-2 gap-2">
                                <AppFormField label="Sets">
                                    <AppFormInput v-model.number="item.sets" type="number" min="1" placeholder="Sets" />
                                </AppFormField>
                                <AppFormField label="Reps / Time">
                                    <AppFormInput v-model="item.reps_or_time" type="text" placeholder="Reps / Time" />
                                </AppFormField>
                            </div>
                            <AppFormField label="Rest" :optional="true">
                                <AppFormInput v-model="item.rest" type="text" placeholder="Rest" />
                            </AppFormField>
                            <AppFormField label="Notes" :optional="true">
                                <AppFormTextarea v-model="item.notes" rows="2" placeholder="Notes" />
                            </AppFormField>
                        </div>
                        <p v-if="builderCoreExtras.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">No core rows added.</p>
                    </div>
                </article>

                <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">Cardio Section</h4>
                        <button type="button" class="text-sm font-semibold text-primary-600 dark:text-primary-400" @click="addCardioExtra">Add Row</button>
                    </div>
                    <div class="mt-3 space-y-3">
                        <div v-for="(item, index) in builderCardioExtras" :key="item.localKey" class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-500 dark:text-secondary-400">Cardio Row {{ index + 1 }}</p>
                                <button type="button" class="text-xs text-red-600 dark:text-red-400" @click="removeCardioExtra(index)">Remove</button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <AppFormField label="Times / Week">
                                    <AppFormInput v-model.number="item.frequency_per_week" type="number" min="1" max="14" placeholder="Times / Week" />
                                </AppFormField>
                                <AppFormField label="Minutes">
                                    <AppFormInput v-model.number="item.duration_minutes" type="number" min="1" placeholder="Minutes" />
                                </AppFormField>
                            </div>
                            <AppFormField label="Cardio Type">
                                <AppFormInput v-model="item.cardio_type" type="text" placeholder="Cardio type" />
                            </AppFormField>
                            <AppFormField label="Notes" :optional="true">
                                <AppFormTextarea v-model="item.notes" rows="2" placeholder="Notes" />
                            </AppFormField>
                        </div>
                        <p v-if="builderCardioExtras.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">No cardio rows added.</p>
                    </div>
                </article>
            </section>
        </div>

        <div v-else class="app-page-scroll bg-secondary-100/80 dark:bg-secondary-950 p-4 md:p-6 rounded-2xl">
            <div class="mx-auto w-full max-w-4xl rounded-[2rem] bg-white text-slate-900 shadow-2xl shadow-slate-900/10 print:shadow-none">
                <div class="border-b border-slate-200 px-6 py-6 md:px-10">
                    <div class="grid gap-4 md:grid-cols-[1.2fr_0.8fr] md:items-end">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Online Coaching</p>
                            <h2 class="mt-2 text-2xl md:text-4xl font-bold tracking-tight">{{ builderForm.title || 'Workout Program Title' }}</h2>
                            <p class="mt-2 text-sm text-slate-600">{{ builderForm.description || 'Structured professional workout program preview.' }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-950 px-5 py-5 text-white">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-300">Program Summary</p>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-slate-400">Duration</p>
                                    <p class="font-semibold">{{ builderForm.duration_weeks || 0 }} weeks</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">Days/Week</p>
                                    <p class="font-semibold">{{ builderForm.days_per_week || 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">Level</p>
                                    <p class="font-semibold capitalize">{{ builderForm.level }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400">Status</p>
                                    <p class="font-semibold capitalize">{{ builderForm.status }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8 px-4 py-6 md:px-10 md:py-8">
                    <section v-for="(day, index) in previewDays" :key="day.localKey || day.id || index" class="space-y-3">
                        <div class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">Day {{ String(day.day_number || index + 1).padStart(2, '0') }} <span class="ml-2 normal-case tracking-normal text-slate-500">{{ day.title || 'Workout Day' }}</span></div>
                        <div class="overflow-hidden rounded-xl border border-slate-300">
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700">
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Exercise</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">W1 / W3</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">W2 / W4</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Sets</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Reps</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Tempo</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Rest</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in orderedExerciseRows(day.exercises)" :key="row.localKey || row.id">
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ displayExerciseName(row) }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ row.w1_w3_exercise || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ row.w2_w4_exercise || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ row.sets || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ row.reps || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ row.tempo || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ formatRest(row.rest_seconds) }}</td>
                                    </tr>
                                    <tr v-if="!day.exercises || day.exercises.length === 0">
                                        <td colspan="7" class="border border-slate-300 px-3 py-3 text-center text-slate-500">No exercises added.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section v-if="previewCoreExtras.length > 0" class="space-y-3">
                        <div class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">Core</div>
                        <div class="overflow-hidden rounded-xl border border-slate-300">
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700">
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Exercise</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Sets</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Reps / Time</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Rest</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in previewCoreExtras" :key="item.localKey || item.id">
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.exercise_name || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.sets || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.reps_or_time || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.rest || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.notes || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section v-if="previewCardioExtras.length > 0" class="space-y-3">
                        <div class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">Cardio</div>
                        <div class="overflow-hidden rounded-xl border border-slate-300">
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700">
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Frequency</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Cardio</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Duration</th>
                                        <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in previewCardioExtras" :key="item.localKey || item.id">
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ formatFrequency(item.frequency_per_week) }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.cardio_type || '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.duration_minutes ? `${item.duration_minutes} minute` : '-' }}</td>
                                        <td class="border border-slate-300 px-3 py-2 align-top">{{ item.notes || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600" @click="builderTab = 'preview'">Preview Output</button>
            <div class="flex items-center gap-2">
                <RouterLink to="/workout?tab=programs" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600">Cancel</RouterLink>
                <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600" @click="printBuilderPreview">Print</button>
                <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50" :disabled="saving" @click="saveProgramBuilder">{{ saving ? 'Saving...' : 'Save Program' }}</button>
            </div>
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
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));

const builderTab = ref('builder');
const saving = ref(false);
const errorMessage = ref('');

const exercises = ref([]);
const builderProgramId = ref(null);
const builderForm = ref(defaultProgramForm());
const builderDays = ref([]);
const builderCoreExtras = ref([]);
const builderCardioExtras = ref([]);
const originalProgramSnapshot = ref(null);
const localKeySeed = ref(0);

const orderedBuilderDays = computed(() => {
    return builderDays.value.slice().sort((left, right) => Number(left.day_number || 0) - Number(right.day_number || 0));
});

const previewDays = computed(() => orderedBuilderDays.value);
const previewCoreExtras = computed(() => meaningfulCoreExtras(builderCoreExtras.value));
const previewCardioExtras = computed(() => meaningfulCardioExtras(builderCardioExtras.value));

function nextLocalKey(prefix) {
    localKeySeed.value += 1;
    return `${prefix}-${localKeySeed.value}`;
}

function defaultProgramForm() {
    return {
        title: '',
        description: '',
        duration_weeks: 4,
        days_per_week: 4,
        level: 'beginner',
        status: 'active',
    };
}

function createExerciseRow() {
    return {
        localKey: nextLocalKey('row'),
        id: null,
        exercise_id: null,
        exercise_name: '',
        w1_w3_exercise: '',
        w2_w4_exercise: '',
        sets: 3,
        reps: '10',
        tempo: '3-1-1-0',
        rest_seconds: 60,
        exercise_order: 1,
    };
}

function createDay() {
    return {
        localKey: nextLocalKey('day'),
        id: null,
        day_number: builderDays.value.length + 1,
        title: '',
        exercises: [createExerciseRow()],
    };
}

function createCoreExtra() {
    return {
        localKey: nextLocalKey('core'),
        id: null,
        exercise_name: '',
        sets: 3,
        reps_or_time: '',
        rest: '',
        notes: '',
    };
}

function createCardioExtra() {
    return {
        localKey: nextLocalKey('cardio'),
        id: null,
        frequency_per_week: 3,
        duration_minutes: 20,
        cardio_type: '',
        notes: '',
    };
}

function orderedExerciseRows(rows = []) {
    return rows.slice();
}

function syncExerciseOrder(day) {
    day.exercises.forEach((row, index) => {
        row.exercise_order = index + 1;
    });
}

function displayExerciseName(row) {
    if (row.exercise_name) return row.exercise_name;

    const match = exercises.value.find((item) => item.id === Number(row.exercise_id));
    return match?.name || 'Exercise';
}

function getExerciseVariationOptions(row) {
    const exercise = exercises.value.find((item) => item.id === Number(row.exercise_id));
    return exercise?.variations || [];
}

function handleExerciseChange(row) {
    const exercise = exercises.value.find((item) => item.id === Number(row.exercise_id));
    row.exercise_name = exercise?.name || '';

    // Load exercise-level defaults into the row when exercise changes.
    if (exercise) {
        row.sets = Number(exercise.default_sets || 1);
        row.reps = exercise.default_reps || '';
        row.tempo = exercise.default_tempo || '';
        row.rest_seconds = Number(exercise.default_rest || 0);
    }

    const variations = exercise?.variations || [];
    const hasW1 = variations.some((item) => item.variation_name === row.w1_w3_exercise);
    const hasW2 = variations.some((item) => item.variation_name === row.w2_w4_exercise);

    if (!hasW1) {
        row.w1_w3_exercise = '';
    }

    if (!hasW2) {
        row.w2_w4_exercise = '';
    }
}

function formatRest(value) {
    return value === null || value === undefined || value === '' ? '-' : `${value}s`;
}

function formatFrequency(value) {
    if (!value) return '-';
    return `${value} time${value > 1 ? 's' : ''} a week`;
}

function meaningfulCoreExtras(items) {
    return items.filter((item) => item.exercise_name || item.reps_or_time || item.rest || item.notes || item.sets);
}

function meaningfulCardioExtras(items) {
    return items.filter((item) => item.cardio_type || item.duration_minutes || item.frequency_per_week || item.notes);
}

async function loadExercises() {
    const response = await apiRequest('/api/exercises', { params: { per_page: 100 } });
    exercises.value = response?.data?.data || [];
}

async function loadProgram() {
    if (!isEdit.value) {
        builderProgramId.value = null;
        builderForm.value = defaultProgramForm();
        builderDays.value = [createDay()];
        builderCoreExtras.value = [];
        builderCardioExtras.value = [];
        originalProgramSnapshot.value = { days: [], extras: [] };
        return;
    }

    const response = await apiRequest(`/api/workout-programs/${route.params.id}`);
    builderProgramId.value = route.params.id;
    hydrateBuilder(response?.data || {});
}

function hydrateBuilder(program) {
    builderForm.value = {
        title: program.title || '',
        description: program.description || '',
        duration_weeks: Number(program.duration_weeks || 4),
        days_per_week: Number(program.days_per_week || 4),
        level: program.level || 'beginner',
        status: program.status || 'active',
    };

    builderDays.value = (program.days || []).map((day) => ({
        localKey: nextLocalKey('day'),
        id: day.id,
        day_number: Number(day.day_number || 1),
        title: day.title || '',
        exercises: (day.exercises || []).map((exercise) => ({
            localKey: nextLocalKey('row'),
            id: exercise.id,
            exercise_id: exercise.exercise_id || null,
            exercise_name: exercise.exercise_name || '',
            w1_w3_exercise: exercise.w1_w3_exercise || '',
            w2_w4_exercise: exercise.w2_w4_exercise || '',
            sets: Number(exercise.sets || 1),
            reps: exercise.reps || '',
            tempo: exercise.tempo || '',
            rest_seconds: Number(exercise.rest_seconds || 0),
            exercise_order: Number(exercise.exercise_order || 1),
        })),
    }));

    builderCoreExtras.value = (program.extras || [])
        .filter((item) => item.type === 'core')
        .map((item) => ({
            localKey: nextLocalKey('core'),
            id: item.id,
            exercise_name: item.exercise_name || '',
            sets: item.sets || 3,
            reps_or_time: item.reps_or_time || '',
            rest: item.rest || '',
            notes: item.notes || '',
        }));

    builderCardioExtras.value = (program.extras || [])
        .filter((item) => item.type === 'cardio')
        .map((item) => ({
            localKey: nextLocalKey('cardio'),
            id: item.id,
            frequency_per_week: item.frequency_per_week || 3,
            duration_minutes: item.duration_minutes || 20,
            cardio_type: item.cardio_type || '',
            notes: item.notes || '',
        }));

    if (builderDays.value.length === 0) {
        builderDays.value = [createDay()];
    }

    originalProgramSnapshot.value = {
        days: (program.days || []).map((day) => ({
            id: day.id,
            exercises: (day.exercises || []).map((exercise) => ({ id: exercise.id })),
        })),
        extras: (program.extras || []).map((item) => ({ id: item.id, type: item.type })),
    };
}

function addBuilderDay() {
    builderDays.value.push(createDay());
}

function removeBuilderDay(index) {
    builderDays.value.splice(index, 1);
}

function addExerciseRow(day) {
    const row = createExerciseRow();
    day.exercises.push(row);
    syncExerciseOrder(day);
}

function removeExerciseRow(day, rowIndex) {
    day.exercises.splice(rowIndex, 1);
    syncExerciseOrder(day);
}

function addCoreExtra() {
    builderCoreExtras.value.push(createCoreExtra());
}

function removeCoreExtra(index) {
    builderCoreExtras.value.splice(index, 1);
}

function addCardioExtra() {
    builderCardioExtras.value.push(createCardioExtra());
}

function removeCardioExtra(index) {
    builderCardioExtras.value.splice(index, 1);
}

function normalizeProgramPayload() {
    return {
        title: builderForm.value.title,
        description: builderForm.value.description,
        duration_weeks: Number(builderForm.value.duration_weeks || 1),
        days_per_week: Number(builderForm.value.days_per_week || 1),
        level: builderForm.value.level,
        status: builderForm.value.status,
    };
}

function normalizeDayPayload(day) {
    return {
        day_number: Number(day.day_number || 1),
        title: day.title || `Day ${day.day_number || 1}`,
    };
}

function normalizeExercisePayload(row, index) {
    return {
        exercise_id: Number(row.exercise_id),
        w1_w3_exercise: row.w1_w3_exercise,
        w2_w4_exercise: row.w2_w4_exercise,
        sets: Number(row.sets || 1),
        reps: row.reps,
        tempo: row.tempo,
        rest_seconds: Number(row.rest_seconds || 0),
        exercise_order: index + 1,
    };
}

function normalizeCoreExtraPayload(item) {
    return {
        type: 'core',
        exercise_name: item.exercise_name,
        sets: Number(item.sets || 1),
        reps_or_time: item.reps_or_time,
        rest: item.rest,
        notes: item.notes || null,
    };
}

function normalizeCardioExtraPayload(item) {
    return {
        type: 'cardio',
        frequency_per_week: Number(item.frequency_per_week || 1),
        duration_minutes: Number(item.duration_minutes || 1),
        cardio_type: item.cardio_type,
        notes: item.notes || null,
    };
}

async function saveProgramBuilder() {
    saving.value = true;
    errorMessage.value = '';

    try {
        let programId = builderProgramId.value;

        if (programId) {
            await apiRequest(`/api/workout-programs/${programId}`, {
                method: 'put',
                data: normalizeProgramPayload(),
            });
        } else {
            const response = await apiRequest('/api/workout-programs', {
                method: 'post',
                data: normalizeProgramPayload(),
            });
            programId = response?.data?.id;
            builderProgramId.value = programId;
        }

        const originalDays = new Map((originalProgramSnapshot.value?.days || []).map((day) => [day.id, day]));
        const savedDayIds = [];

        for (const day of orderedBuilderDays.value) {
            if (!day.title || !day.day_number) {
                continue;
            }

            let dayId = day.id;
            if (dayId) {
                await apiRequest(`/api/workout-program-days/${dayId}`, {
                    method: 'put',
                    data: normalizeDayPayload(day),
                });
            } else {
                const response = await apiRequest(`/api/workout-programs/${programId}/days`, {
                    method: 'post',
                    data: normalizeDayPayload(day),
                });
                dayId = response?.data?.id;
                day.id = dayId;
            }

            savedDayIds.push(dayId);

            const originalExerciseIds = new Set((originalDays.get(dayId)?.exercises || []).map((item) => item.id));
            const currentExerciseIds = [];
            const rows = orderedExerciseRows(day.exercises).filter((row) => row.exercise_id && row.w1_w3_exercise && row.w2_w4_exercise && row.reps && row.tempo);

            for (let index = 0; index < rows.length; index += 1) {
                const row = rows[index];
                const payload = normalizeExercisePayload(row, index);

                if (row.id) {
                    await apiRequest(`/api/workout-day-exercises/${row.id}`, {
                        method: 'put',
                        data: payload,
                    });
                    currentExerciseIds.push(row.id);
                } else {
                    const response = await apiRequest(`/api/workout-program-days/${dayId}/exercises`, {
                        method: 'post',
                        data: payload,
                    });
                    row.id = response?.data?.id;
                    currentExerciseIds.push(row.id);
                }
            }

            for (const originalExerciseId of originalExerciseIds) {
                if (!currentExerciseIds.includes(originalExerciseId)) {
                    await apiRequest(`/api/workout-day-exercises/${originalExerciseId}`, {
                        method: 'delete',
                    });
                }
            }
        }

        for (const originalDay of originalProgramSnapshot.value?.days || []) {
            if (!savedDayIds.includes(originalDay.id)) {
                await apiRequest(`/api/workout-program-days/${originalDay.id}`, {
                    method: 'delete',
                });
            }
        }

        const originalExtras = originalProgramSnapshot.value?.extras || [];
        const savedExtraIds = [];

        for (const item of meaningfulCoreExtras(builderCoreExtras.value)) {
            const payload = normalizeCoreExtraPayload(item);
            if (item.id) {
                await apiRequest(`/api/workout-program-extras/${item.id}`, {
                    method: 'put',
                    data: payload,
                });
                savedExtraIds.push(item.id);
            } else {
                const response = await apiRequest(`/api/workout-programs/${programId}/extras`, {
                    method: 'post',
                    data: payload,
                });
                item.id = response?.data?.id;
                savedExtraIds.push(item.id);
            }
        }

        for (const item of meaningfulCardioExtras(builderCardioExtras.value)) {
            const payload = normalizeCardioExtraPayload(item);
            if (item.id) {
                await apiRequest(`/api/workout-program-extras/${item.id}`, {
                    method: 'put',
                    data: payload,
                });
                savedExtraIds.push(item.id);
            } else {
                const response = await apiRequest(`/api/workout-programs/${programId}/extras`, {
                    method: 'post',
                    data: payload,
                });
                item.id = response?.data?.id;
                savedExtraIds.push(item.id);
            }
        }

        for (const extra of originalExtras) {
            if (!savedExtraIds.includes(extra.id)) {
                await apiRequest(`/api/workout-program-extras/${extra.id}`, {
                    method: 'delete',
                });
            }
        }

        router.push('/workout?tab=programs');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save workout program.';
    } finally {
        saving.value = false;
    }
}

function printBuilderPreview() {
    builderTab.value = 'preview';
    window.setTimeout(() => window.print(), 100);
}

onMounted(async () => {
    try {
        await Promise.all([loadExercises(), loadProgram()]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load workout program data.';
    }
});
</script>
