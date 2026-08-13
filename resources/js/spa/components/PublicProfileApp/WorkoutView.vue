<template>
  <div class="space-y-4 pb-6">
    <!-- Header -->
    <div class="flex items-center justify-between pt-2 pb-1">
      <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
          Workout Programs
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
          Assigned training routines &amp; exercise schedules
        </p>
      </div>

      <span
        v-if="workoutsData.length"
        class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20"
      >
        {{ workoutsData.length }} Plan{{ workoutsData.length > 1 ? 's' : '' }}
      </span>
    </div>

    <!-- Empty State -->
    <div
      v-if="!workoutsData.length"
      class="pp-glass-card rounded-3xl p-12 flex flex-col items-center justify-center text-center gap-3 text-gray-400"
    >
      <div class="w-16 h-16 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center">
        <Dumbbell class="w-8 h-8" :stroke-width="1.8" />
      </div>
      <div>
        <p class="text-base font-bold text-gray-800 dark:text-gray-200">
          No Workout Plans Assigned
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 max-w-xs">
          Your gym trainer has not assigned any workout routines yet. Ask your instructor to assign a plan.
        </p>
      </div>
    </div>

    <!-- Programs List -->
    <div v-else class="space-y-3">
      <button
        v-for="(workout, i) in workoutsData"
        :key="i"
        type="button"
        class="w-full text-left rounded-3xl p-4 sm:p-5 transition-all duration-200 focus:outline-none active:scale-[0.99] group cursor-pointer"
        :class="i === 0
          ? 'pp-membership-card shadow-lg shadow-red-500/10'
          : 'pp-glass-card hover:shadow-md'"
        @click="$emit('open-workout', workout)"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
              <span
                v-if="i === 0"
                class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-500 text-white shadow-sm"
              >
                Active
              </span>
              <span
                v-else
                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400"
              >
                Plan {{ i + 1 }}
              </span>

              <!-- Type Badge -->
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                :class="i === 0 ? 'bg-white/20 text-white' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20'"
              >
                {{ getBadgeLabel(workout) }}
              </span>

              <span
                v-if="workout.effective_date"
                class="text-[11px] font-medium"
                :class="i === 0 ? 'text-zinc-400' : 'text-gray-400 dark:text-gray-500'"
              >
                Started {{ workout.effective_date }}
              </span>
            </div>

            <h3
              class="text-lg font-black leading-snug truncate"
              :class="i === 0 ? 'text-white' : 'text-gray-900 dark:text-white group-hover:text-red-500 transition-colors'"
            >
              {{ workout.title }}
            </h3>

            <p
              v-if="workout.creator_name"
              class="text-xs mt-0.5"
              :class="i === 0 ? 'text-zinc-400' : 'text-gray-500 dark:text-gray-400'"
            >
              Assigned by {{ workout.creator_name }}
            </p>
          </div>

          <div
            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
            :class="i === 0 ? 'bg-red-500 text-white shadow-md shadow-red-500/30' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-300'"
          >
            <component :is="getWorkoutIcon(workout)" class="w-5 h-5" :stroke-width="2" />
          </div>
        </div>

        <!-- Meta breakdown chips -->
        <div
          class="grid grid-cols-3 gap-2 mt-4 pt-3 text-center text-xs"
          :class="i === 0 ? 'border-t border-zinc-800' : 'border-t border-gray-100 dark:border-zinc-800/60'"
        >
          <div :class="i === 0 ? 'bg-white/5 rounded-xl p-1.5' : 'bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-1.5'">
            <span class="block text-[10px] uppercase tracking-wider font-bold" :class="i === 0 ? 'text-zinc-400' : 'text-gray-400'">
              {{ workout.type === 'file' ? 'Format' : (workout.type === 'text' ? 'Format' : 'Duration') }}
            </span>
            <span class="font-extrabold truncate block" :class="i === 0 ? 'text-white' : 'text-gray-900 dark:text-white'">
              {{ workout.type === 'file' ? (workout.file_name?.endsWith('.pdf') ? 'PDF Document' : 'Image File') : (workout.type === 'text' ? 'Rich Text' : (workout.duration_weeks ? `${workout.duration_weeks} wks` : '-')) }}
            </span>
          </div>

          <div :class="i === 0 ? 'bg-white/5 rounded-xl p-1.5' : 'bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-1.5'">
            <span class="block text-[10px] uppercase tracking-wider font-bold" :class="i === 0 ? 'text-zinc-400' : 'text-gray-400'">
              {{ workout.type === 'file' ? 'File Size' : (workout.type === 'text' ? 'Content' : 'Routines') }}
            </span>
            <span class="font-extrabold truncate block" :class="i === 0 ? 'text-white' : 'text-gray-900 dark:text-white'">
              {{ workout.type === 'file' ? (formatFileSize(workout.file_size) || 'Attached') : (workout.type === 'text' ? 'Formatted' : `${workout.days?.length || 0} routines`) }}
            </span>
          </div>

          <div :class="i === 0 ? 'bg-white/5 rounded-xl p-1.5' : 'bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-1.5'">
            <span class="block text-[10px] uppercase tracking-wider font-bold" :class="i === 0 ? 'text-zinc-400' : 'text-gray-400'">
              {{ workout.type === 'file' ? 'Access' : (workout.type === 'text' ? 'Action' : 'Extras') }}
            </span>
            <span class="font-extrabold truncate block" :class="i === 0 ? 'text-white' : 'text-gray-900 dark:text-white'">
              {{ workout.type === 'file' ? 'View/Download' : (workout.type === 'text' ? 'Read Plan' : `${workout.extras?.length || 0} extras`) }}
            </span>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { Zap, Dumbbell, FileText, FileSpreadsheet, Image as ImageIcon } from 'lucide-vue-next';

defineProps({
    workoutsData: { type: Array, default: () => [] },
});

defineEmits(['open-workout']);

function getBadgeLabel(workout) {
    if (workout.type === 'file') {
        return workout.file_name?.endsWith('.pdf') ? 'PDF Plan' : 'Image Plan';
    }
    if (workout.type === 'text') return 'Custom Routine';
    return 'Program Routine';
}

function getWorkoutIcon(workout) {
    if (workout.type === 'file') {
        if (workout.file_name?.endsWith('.pdf')) return FileSpreadsheet;
        return ImageIcon;
    }
    if (workout.type === 'text') return FileText;
    return Zap;
}

function formatFileSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}
</script>
