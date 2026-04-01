<template>
    <div class="workout-program-print-root mx-auto w-full max-w-4xl rounded-[2rem] bg-white text-slate-900 shadow-2xl shadow-slate-900/10 print:shadow-none print:rounded-none">
        <!-- Header -->
        <div class="border-b border-slate-200 px-6 py-6 md:px-10">
            <div class="grid gap-4 md:grid-cols-[1.2fr_0.8fr] md:items-end">
                <div>
                    <p class="mt-2 text-sm text-slate-600">Workout Program</p>
                    <h2 class="text-2xl md:text-4xl font-bold tracking-tight">{{ program.title }}</h2>
                    <p v-if="program.creator_name" class="mt-2 text-sm text-slate-600">Prepared by {{ program.creator_name }}</p>
                </div>
                <div class="rounded-3xl bg-slate-950 px-5 py-5 text-white">
                    <div class="text-sm">
                        <p class="text-slate-400">Duration</p>
                        <p class="font-semibold">{{ program.duration_weeks || 0 }} weeks</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Days / Exercises -->
        <div class="space-y-8 px-4 py-6 md:px-10 md:py-8">
            <section v-for="(day, index) in sortedDays" :key="day.id || index" class="space-y-3">
                <div class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">
                    Day {{ padDay(day.day_number || index + 1) }}
                    <span class="ml-2 normal-case tracking-normal text-slate-500">{{ day.title || '' }}</span>
                </div>
                <div class="overflow-x-auto overflow-hidden rounded-xl border border-slate-300">
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
                            <tr v-for="(row, rIdx) in day.exercises" :key="row.id || rIdx">
                                <td class="border border-slate-300 px-3 py-2 align-top">{{ row.exercise_name || 'Exercise' }}</td>
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

            <!-- Core Extras -->
            <section v-if="coreExtras.length > 0" class="space-y-3">
                <div class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">Core</div>
                <div class="overflow-x-auto overflow-hidden rounded-xl border border-slate-300">
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
                            <tr v-for="(item, i) in coreExtras" :key="item.id || i">
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

            <!-- Cardio Extras -->
            <section v-if="cardioExtras.length > 0" class="space-y-3">
                <div class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-700">Cardio</div>
                <div class="overflow-x-auto overflow-hidden rounded-xl border border-slate-300">
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
                            <tr v-for="(item, i) in cardioExtras" :key="item.id || i">
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
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    /**
     * Normalised program object:
     * {
     *   title: string,
     *   duration_weeks: number,
     *   creator_name: string|null,
     *   days: [{ day_number, title, exercises: [{ exercise_name, w1_w3_exercise, w2_w4_exercise, sets, reps, tempo, rest_seconds }] }],
     *   extras: [{ type: 'core'|'cardio', ...fields }]
     * }
     */
    program: { type: Object, required: true },
});

const sortedDays = computed(() =>
    [...(props.program.days || [])].sort((a, b) => Number(a.day_number || 0) - Number(b.day_number || 0))
);

const coreExtras = computed(() =>
    (props.program.extras || []).filter(
        (e) => e.type === 'core' && (e.exercise_name || e.reps_or_time || e.rest || e.notes || e.sets)
    )
);

const cardioExtras = computed(() =>
    (props.program.extras || []).filter(
        (e) => e.type === 'cardio' && (e.cardio_type || e.duration_minutes || e.frequency_per_week || e.notes)
    )
);

function padDay(n) {
    return String(n).padStart(2, '0');
}

function formatRest(value) {
    return value === null || value === undefined || value === '' ? '-' : `${value}s`;
}

function formatFrequency(value) {
    if (!value) return '-';
    return `${value} time${value > 1 ? 's' : ''} a week`;
}
</script>

<style>
@media print {
    body.printing-workout-program * {
        visibility: hidden;
    }

    body.printing-workout-program .workout-program-print-root,
    body.printing-workout-program .workout-program-print-root * {
        visibility: visible;
    }

    body.printing-workout-program .workout-program-print-root {
        position: absolute;
        inset: 0;
        width: 100%;
        max-width: none;
        margin: 0;
        border-radius: 0;
        box-shadow: none;
    }

    @page {
        size: A4;
        margin: 12mm;
    }
}
</style>
