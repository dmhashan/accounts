<template>
    <div>
        <div class="flex items-center gap-3 pt-12 pb-6">
            <h1 class="text-xl font-bold text-gray-900">Workout Plans</h1>
        </div>
        <div v-if="!workoutsData.length" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <p class="text-sm text-gray-400">No workout plans assigned</p>
        </div>
        <div v-else class="space-y-2.5 pb-4">
            <button
                v-for="(workout, i) in workoutsData"
                :key="i"
                type="button"
                class="w-full text-left bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 flex items-center gap-4 hover:shadow-md active:scale-[0.99] transition-all focus:outline-none"
                @click="$emit('open-workout', workout)"
            >
                <div class="flex-shrink-0 w-11 h-11 rounded-2xl flex items-center justify-center" :style="i === 0 ? 'background:#ef4444' : 'background:#f5f5f5'">
                    <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ workout.title }}</p>
                        <span v-if="i === 0" class="shrink-0 text-[10px] font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">Active</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ workout.duration_weeks || '-' }} weeks · {{ workout.effective_date || '-' }}</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    workoutsData: { type: Array, default: () => [] },
});

defineEmits(['open-workout']);
</script>
