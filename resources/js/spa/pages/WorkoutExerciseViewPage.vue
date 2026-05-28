<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/workout/exercises/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Exercise
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteExercise"
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
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
          <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
            {{ exercise.name }}
          </h1>
          <span
            class="self-start rounded-full px-3 py-1 text-xs font-semibold"
            :class="exercise.status === 'active'
              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
              : 'bg-secondary-200 text-secondary-700 dark:bg-secondary-700 dark:text-secondary-300'"
          >
            {{ exercise.status }}
          </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Default Sets
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ exercise.default_sets || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Default Reps
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ exercise.default_reps || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Tempo
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ exercise.default_tempo || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Rest (s)
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ exercise.default_rest ?? '—' }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="exercise.variations && exercise.variations.length > 0" class="app-surface rounded-2xl overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-secondary-200 dark:border-secondary-700">
          <h2 class="text-base font-semibold text-secondary-900 dark:text-white">
            Variations ({{ exercise.variations.length }})
          </h2>
        </div>
        <div class="divide-y divide-secondary-200 dark:divide-secondary-700">
          <div v-for="v in exercise.variations" :key="v.id" class="px-4 py-3 text-sm text-secondary-800 dark:text-secondary-200">
            {{ v.variation_name || v.name || '—' }}
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const exercise = ref({});
const deleting = ref(false);

async function deleteExercise() {
    if (!confirm(`Delete exercise "${exercise.value.name}"?`)) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/exercises/${route.params.id}`, { method: 'DELETE' });
        router.push('/workout/exercises');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete exercise.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/exercises/${route.params.id}`);
        exercise.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load exercise.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
