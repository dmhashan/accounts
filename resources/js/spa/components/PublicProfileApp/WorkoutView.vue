<template>
  <div>
    <div class="flex items-center gap-3 pt-12 pb-6">
      <h1 class="text-xl font-bold text-gray-900">
        Workout Plans
      </h1>
    </div>
    <div v-if="!workoutsData.length" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
      <Zap class="w-12 h-12" :stroke-width="1.2" />
      <p class="text-sm text-gray-400">
        No workout plans assigned
      </p>
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
          <Zap class="w-5 h-5 text-gray-800" :stroke-width="2" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-gray-900 truncate">
              {{ workout.title }}
            </p>
            <span v-if="i === 0" class="shrink-0 text-[10px] font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">Active</span>
          </div>
          <p class="text-xs text-gray-400 mt-0.5">
            {{ workout.duration_weeks || '-' }} weeks · {{ workout.effective_date || '-' }}
          </p>
        </div>
        <ChevronRight class="w-4 h-4 text-gray-300 shrink-0" :stroke-width="2" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { Zap, ChevronRight } from 'lucide-vue-next';

defineProps({
    workoutsData: { type: Array, default: () => [] },
});

defineEmits(['open-workout']);
</script>
