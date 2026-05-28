<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/workout/assignments/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Assignment
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleting"
          @click="deleteAssignment"
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
        <h1 class="text-xl font-bold text-secondary-900 dark:text-white mb-1">
          {{ assignment.member_name }}
        </h1>
        <p v-if="assignment.member_code" class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">
          #{{ assignment.member_code }}
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Assigned Program
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ assignment.assigned_program_title || assignment.program_title || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Effective Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ assignment.effective_date || '—' }}
            </p>
          </div>
          <div v-if="assignment.created_at">
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Assigned On
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ assignment.created_at }}
            </p>
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
const assignment = ref({});
const deleting = ref(false);

async function deleteAssignment() {
    if (!confirm('Delete this workout assignment?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/workout-program-assignments/${route.params.id}`, { method: 'DELETE' });
        router.push('/workout/assignments');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete assignment.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/workout-program-assignments/${route.params.id}`);
        assignment.value = response.data || response;
    } catch {
        errorMessage.value = 'Failed to load assignment.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
