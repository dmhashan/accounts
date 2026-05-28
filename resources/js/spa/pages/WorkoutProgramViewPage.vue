<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/workout/programs/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Program
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteProgram"
        >
          Delete
        </button>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll space-y-5">
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <h1 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">
          {{ program.title }}
        </h1>

        <div v-if="program.description" class="text-sm text-secondary-700 dark:text-secondary-300 mb-4 whitespace-pre-wrap">
          {{ program.description }}
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div v-if="program.duration_weeks">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Duration
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ program.duration_weeks }} weeks
            </p>
          </div>
          <div v-if="program.days">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Training Days
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ program.days.length }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="program.days && program.days.length > 0" class="space-y-4">
        <div v-for="day in sortedDays" :key="day.id || day.day_number" class="app-surface rounded-2xl overflow-hidden">
          <div class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
            <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
              Day {{ day.day_number }}<span v-if="day.title"> — {{ day.title }}</span>
            </h3>
          </div>
          <div v-if="day.exercises && day.exercises.length > 0" class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <div v-for="ex in day.exercises" :key="ex.id" class="px-4 py-3 text-sm">
              <p class="font-medium text-secondary-900 dark:text-white">
                {{ ex.exercise_name || ex.name }}
                <span v-if="ex.variation_name" class="font-normal text-secondary-500 dark:text-secondary-400"> – {{ ex.variation_name }}</span>
              </p>
              <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                {{ ex.sets }} sets × {{ ex.reps }}<span v-if="ex.tempo"> · {{ ex.tempo }}</span><span v-if="ex.rest_seconds"> · {{ ex.rest_seconds }}s rest</span>
              </p>
            </div>
          </div>
          <div v-else class="px-4 py-3 text-sm text-secondary-400 dark:text-secondary-500">
            No exercises added.
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const program = ref({});
const deleting = ref(false);

const sortedDays = computed(() => {
    return [...(program.value.days || [])].sort((a, b) => (a.day_number ?? 0) - (b.day_number ?? 0));
});

async function deleteProgram() {
    if (!confirm(`Delete workout program "${program.value.title}"?`)) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/workout-programs/${route.params.id}`, { method: 'DELETE' });
        router.push('/workout');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete program.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/workout-programs/${route.params.id}`);
        program.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load program.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
