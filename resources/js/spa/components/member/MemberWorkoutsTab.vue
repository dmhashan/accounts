<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
        Workout Assignments
      </h2>
    </div>
    <div v-if="workoutsLoading" class="px-5 py-6 text-center text-sm text-secondary-400">
      Loading...
    </div>
    <div v-else-if="memberWorkouts.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">
      No workout programs assigned to this member.
    </div>
    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
      <RouterLink
        v-for="wa in memberWorkouts"
        :key="wa.id"
        :to="`/workout-assignments/${wa.id}`"
        class="flex items-start justify-between px-5 py-3.5 gap-3 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors"
      >
        <div class="min-w-0 flex items-start gap-3">
          <div class="shrink-0 w-9 h-9 rounded-lg bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 flex items-center justify-center mt-0.5">
            <svg
              class="w-4 h-4 text-primary-600 dark:text-primary-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            ><path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
            /></svg>
          </div>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
              {{ wa.assigned_program_title || wa.source_program_title || 'Program' }}
            </p>
            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
              Effective: {{ formatDate(wa.effective_date) }}
              <span v-if="wa.created_by_name" class="ml-1 opacity-70">&bull; by {{ wa.created_by_name }}</span>
            </p>
          </div>
        </div>
        <svg
          class="w-4 h-4 text-secondary-400 shrink-0 mt-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        ><path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 5l7 7-7 7"
        /></svg>
      </RouterLink>
    </div>
    <div v-if="workoutsMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
      <p class="text-xs text-secondary-500 dark:text-secondary-400">
        Page {{ workoutsMeta.current_page }} of {{ workoutsMeta.last_page }}
      </p>
      <div class="flex gap-1">
        <button
          type="button"
          class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
          :disabled="workoutsMeta.current_page <= 1"
          @click="loadMemberWorkouts(workoutsMeta.current_page - 1)"
        >
          Prev
        </button>
        <button
          type="button"
          class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
          :disabled="workoutsMeta.current_page >= workoutsMeta.last_page"
          @click="loadMemberWorkouts(workoutsMeta.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
});

const { formatDate } = useMemberFormatters();

const workoutsLoading = ref(false);
const memberWorkouts = ref([]);
const workoutsMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

async function loadMemberWorkouts(page = 1) {
    workoutsLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/workouts?page=${page}&per_page=10`);
        const payload = res.data ?? res;
        memberWorkouts.value = payload.data || [];
        workoutsMeta.value = payload.meta || workoutsMeta.value;
    } catch { /* ignore */ } finally {
        workoutsLoading.value = false;
    }
}

defineExpose({ loadMemberWorkouts });
</script>
