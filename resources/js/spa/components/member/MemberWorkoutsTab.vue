<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3">
      <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
        Workout Assignments
      </h2>
      <button
        v-if="canManage"
        type="button"
        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors"
        @click="openAssignModal"
      >
        <Plus class="w-3.5 h-3.5" />
        <span class="hidden sm:inline">Assign Plan</span>
      </button>
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
            <FolderOpen class="w-4 h-4 text-primary-600 dark:text-primary-400" />
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
        <ChevronRight class="w-4 h-4 text-secondary-400 shrink-0 mt-2" />
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

  <!-- Assign workout plan modal -->
  <Teleport to="body">
    <div v-if="assignModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
      <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b border-secondary-200 dark:border-secondary-700">
          <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
            Assign Workout Plan
          </h3>
          <button type="button" class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200" @click="closeAssignModal">
            <X class="w-5 h-5" />
          </button>
        </div>
        <form class="p-5 space-y-4" @submit.prevent="submitAssign">
          <div v-if="assignError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ assignError }}
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Workout Program <span class="text-red-500">*</span></label>
            <select
              v-model="assignForm.program_id"
              required
              class="w-full px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="" disabled>
                Select program
              </option>
              <option v-for="p in programs" :key="p.id" :value="p.id">
                {{ p.title }}
              </option>
            </select>
            <p v-if="programsLoading" class="mt-1 text-xs text-secondary-400">
              Loading programs...
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Start Date <span class="text-red-500">*</span></label>
            <AppFormDateInput
              v-model="assignForm.effective_date"
              required
              input-class="w-full px-3 py-2 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>

          <div class="flex justify-end gap-2 pt-1">
            <button type="button" class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800" @click="closeAssignModal">
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 transition-colors"
              :disabled="assignSaving || !assignForm.program_id || !assignForm.effective_date"
            >
              {{ assignSaving ? 'Assigning...' : 'Assign' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Plus, X, FolderOpen, ChevronRight } from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';
import { useAppContext } from '../../composables/useAppContext';
import AppFormDateInput from '../forms/AppFormDateInput.vue';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
});

const context = useAppContext();
const canManage = computed(() => Boolean(context.permissions?.workout));

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

// ── Assign modal ───────────────────────────────────────────
const assignModalOpen = ref(false);
const assignSaving = ref(false);
const assignError = ref('');
const assignForm = ref({ program_id: '', effective_date: '' });

const programs = ref([]);
const programsLoading = ref(false);

function todayStr() {
    return new Date().toISOString().slice(0, 10);
}

async function openAssignModal() {
    assignForm.value = { program_id: '', effective_date: todayStr() };
    assignError.value = '';
    assignSaving.value = false;
    assignModalOpen.value = true;
    if (programs.value.length === 0) {
        programsLoading.value = true;
        try {
            const res = await apiRequest('/api/workout-programs?per_page=200');
            programs.value = res.data || res;
        } catch { /* silent */ } finally {
            programsLoading.value = false;
        }
    }
}

function closeAssignModal() {
    assignModalOpen.value = false;
}

async function submitAssign() {
    if (!assignForm.value.program_id || !assignForm.value.effective_date) return;
    assignSaving.value = true;
    assignError.value = '';
    try {
        await apiRequest('/api/workout-program-assignments', {
            method: 'post',
            data: {
                program_id: assignForm.value.program_id,
                member_ids: [Number(props.memberId)],
                effective_date: assignForm.value.effective_date,
            },
        });
        closeAssignModal();
        loadMemberWorkouts(1);
    } catch (err) {
        assignError.value = err?.response?.data?.message || 'Failed to assign program.';
    } finally {
        assignSaving.value = false;
    }
}

defineExpose({ loadMemberWorkouts });
</script>
