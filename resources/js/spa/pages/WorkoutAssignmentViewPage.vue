<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <a
          v-if="assignment.file_url"
          :href="assignment.file_url"
          target="_blank"
          download
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          <Download class="w-4 h-4" />
          <span>Download File</span>
        </a>

        <RouterLink
          v-if="assignment.type === 'program' || !assignment.type"
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
          <Trash2 class="w-4 h-4" />
          <span>Delete</span>
        </button>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll space-y-5 pb-8">
      <!-- Member & Workout Meta Card -->
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap mb-4">
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
                {{ assignment.title || assignment.assigned_program_title || 'Workout Assignment' }}
              </h1>
              <span
                class="px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider rounded-full border"
                :class="getBadgeClasses(assignment)"
              >
                {{ getBadgeLabel(assignment) }}
              </span>
            </div>
            <p v-if="assignment.member_name" class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
              Member: <RouterLink :to="`/members/${assignment.member_id}`" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline">
                {{ assignment.member_name }}
              </RouterLink>
              <span v-if="assignment.member_code" class="ml-1 opacity-70">(#{{ assignment.member_code }})</span>
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm border-t border-secondary-100 dark:border-secondary-800 pt-4">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Effective Date
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ assignment.effective_date || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Created By
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ assignment.created_by_name || 'Staff' }}
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

        <div v-if="assignment.notes" class="mt-4 pt-4 border-t border-secondary-100 dark:border-secondary-800 text-sm">
          <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
            Notes
          </p>
          <p class="text-secondary-700 dark:text-secondary-300 italic">
            "{{ assignment.notes }}"
          </p>
        </div>
      </div>

      <!-- Workout Routine Preview Card -->
      <!-- Case 1: Uploaded PDF -->
      <div v-if="assignment.type === 'file' && isPdf(assignment)" class="app-surface rounded-2xl p-4 md:p-6 space-y-3">
        <h2 class="text-base font-bold text-secondary-900 dark:text-white">
          Attached PDF Document
        </h2>
        <div class="rounded-2xl border border-secondary-200 dark:border-secondary-700 overflow-hidden bg-secondary-950 aspect-[4/3] max-h-[650px]">
          <iframe
            v-if="assignment.file_url"
            :src="assignment.file_url"
            class="w-full h-full border-0"
            title="Workout PDF"
          />
        </div>
      </div>

      <!-- Case 2: Uploaded Image -->
      <div v-else-if="assignment.type === 'file'" class="app-surface rounded-2xl p-4 md:p-6 space-y-3 text-center">
        <h2 class="text-base font-bold text-secondary-900 dark:text-white text-left">
          Attached Image Routine
        </h2>
        <div class="rounded-2xl border border-secondary-200 dark:border-secondary-700 overflow-hidden bg-secondary-50 dark:bg-secondary-800/40 p-2 inline-block max-w-full">
          <img
            :src="assignment.file_url"
            :alt="assignment.title"
            class="max-h-[650px] w-auto rounded-xl object-contain mx-auto"
          />
        </div>
      </div>

      <!-- Case 3: Formatted Rich Text -->
      <div v-else-if="assignment.type === 'text'" class="app-surface rounded-2xl p-4 md:p-6 space-y-3">
        <h2 class="text-base font-bold text-secondary-900 dark:text-white">
          Custom Workout Routine
        </h2>
        <div class="p-6 rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800/60">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="app-rich-editor-content text-sm text-secondary-900 dark:text-white" v-html="assignment.formatted_text" />
        </div>
      </div>

      <!-- Case 4: Configured Program -->
      <div v-else-if="assignment.program_details">
        <WorkoutProgramPreviewCard :program="assignment.program_details" />
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Download, Trash2 } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import WorkoutProgramPreviewCard from '../components/WorkoutProgramPreviewCard.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const assignment = ref({});
const deleting = ref(false);

function getBadgeLabel(wa) {
    if (wa.type === 'file') {
        return isPdf(wa) ? 'PDF Plan' : 'Image Plan';
    }
    if (wa.type === 'text') return 'Custom Routine';
    return 'Program Routine';
}

function getBadgeClasses(wa) {
    if (wa.type === 'file') {
        return isPdf(wa)
            ? 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20'
            : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
    }
    if (wa.type === 'text') return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    return 'bg-primary-500/10 text-primary-600 dark:text-primary-400 border-primary-500/20';
}

function isPdf(wa) {
    return wa.mime_type === 'application/pdf' || (wa.file_name && wa.file_name.toLowerCase().endsWith('.pdf'));
}

async function deleteAssignment() {
    if (!confirm('Delete this workout assignment?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/workout-program-assignments/${route.params.id}`, { method: 'DELETE' });
        if (assignment.value.member_id) {
            router.push(`/members/${assignment.value.member_id}`);
        } else {
            router.push('/workout/assignments');
        }
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
