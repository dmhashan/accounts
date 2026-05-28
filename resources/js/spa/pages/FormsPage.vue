<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction :icon="Plus" label="New Form" @click="router.push('/forms/new')" />
      </template>
      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search forms…"
          :disabled="loading"
          @search="load"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="app-alert app-alert-error">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <div v-for="i in 5" :key="i" class="p-4 space-y-2">
              <div class="app-skeleton h-3.5 w-48 rounded" />
              <div class="app-skeleton h-3 w-72 rounded" />
            </div>
          </div>

          <template v-else-if="filtered.length === 0">
            <AppEmptyState :icon="ClipboardList" title="No form templates yet" description="Create a dynamic form template to get started." />
          </template>

          <template v-else>
            <!-- Mobile cards -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article
                v-for="t in filtered"
                :key="t.id"
                class="p-4 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
                @click="router.push('/forms/' + t.id + '/submissions')"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                      <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                        {{ t.title }}
                      </p>
                      <span
                        class="px-2 py-0.5 text-[10px] font-semibold rounded-full border"
                        :class="t.is_active ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-500 dark:text-secondary-400 border-secondary-200 dark:border-secondary-700'"
                      >
                        {{ t.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </div>
                    <p v-if="t.description" class="text-xs text-secondary-500 dark:text-secondary-400 line-clamp-2">
                      {{ t.description }}
                    </p>
                    <p class="text-[11px] text-secondary-400 dark:text-secondary-500 mt-1">
                      {{ t.fields_count }} field{{ t.fields_count === 1 ? '' : 's' }}
                      <span v-if="t.created_by"> &bull; by {{ t.created_by.name }}</span>
                      <span v-if="t.created_at"> &bull; {{ t.created_at }}</span>
                    </p>
                  </div>
                  <ChevronRight class="h-4 w-4 text-secondary-400 shrink-0 mt-1" :stroke-width="2" />
                </div>
              </article>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="app-table-th">
                      Title
                    </th>
                    <th class="app-table-th">
                      Description
                    </th>
                    <th class="app-table-th text-center">
                      Fields
                    </th>
                    <th class="app-table-th text-center">
                      Status
                    </th>
                    <th class="app-table-th">
                      Created by
                    </th>
                    <th class="app-table-th">
                      Created
                    </th>
                    <th class="app-table-th" />
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr
                    v-for="t in filtered"
                    :key="t.id"
                    class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors cursor-pointer"
                    @click="router.push('/forms/' + t.id + '/submissions')"
                  >
                    <td class="app-table-td font-semibold text-secondary-900 dark:text-white">
                      {{ t.title }}
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400 max-w-xs truncate">
                      {{ t.description || '—' }}
                    </td>
                    <td class="app-table-td text-center">
                      {{ t.fields_count }}
                    </td>
                    <td class="app-table-td text-center">
                      <span
                        class="px-2 py-0.5 text-[10px] font-semibold rounded-full border"
                        :class="t.is_active ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800' : 'bg-secondary-100 dark:bg-secondary-800 text-secondary-500 dark:text-secondary-400 border-secondary-200 dark:border-secondary-700'"
                      >
                        {{ t.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400">
                      {{ t.created_by?.name || '—' }}
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400 whitespace-nowrap">
                      {{ t.created_at }}
                    </td>
                    <td class="app-table-td">
                      <div class="flex items-center justify-end gap-1">
                        <button
                          type="button"
                          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/40 border border-primary-200 dark:border-primary-800/50 transition-colors"
                          @click.stop="router.push('/forms/' + t.id + '/edit')"
                        >
                          Edit
                        </button>
                        <button
                          type="button"
                          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-secondary-50 dark:bg-secondary-800 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 border border-secondary-200 dark:border-secondary-700 transition-colors"
                          @click.stop="router.push('/forms/' + t.id + '/submissions')"
                        >
                          Submissions
                        </button>
                        <button
                          type="button"
                          class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-800/50 transition-colors"
                          @click.stop="confirmDelete(t)"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Delete confirm modal -->
    <div v-if="deleteTarget" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="deleteTarget = null" />
      <div class="relative z-10 w-full max-w-sm rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
        <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-2">
          Delete Form Template?
        </h3>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">
          <strong class="text-secondary-800 dark:text-secondary-200">{{ deleteTarget.title }}</strong> will be permanently deleted along with all its submissions.
        </p>
        <div class="flex items-center justify-end gap-2">
          <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="deleteTarget = null">
            Cancel
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50"
            :disabled="deleting"
            @click="executeDelete"
          >
            {{ deleting ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ChevronRight, ClipboardList, Plus } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppEmptyState from '../components/AppEmptyState.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';

const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const templates = ref([]);
const search = ref('');

const deleteTarget = ref(null);
const deleting = ref(false);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return templates.value;
    return templates.value.filter(t =>
        t.title.toLowerCase().includes(q) ||
        (t.description || '').toLowerCase().includes(q),
    );
});

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const res = await apiRequest('/api/forms/templates');
        templates.value = (res.data || []).map(t => ({
            ...t,
            fields_count: (t.fields ?? []).length,
        }));
    } catch {
        errorMessage.value = 'Failed to load form templates.';
    } finally {
        loading.value = false;
    }
}

function confirmDelete(t) {
    deleteTarget.value = t;
}

async function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/forms/templates/${deleteTarget.value.id}`, { method: 'delete' });
        templates.value = templates.value.filter(t => t.id !== deleteTarget.value.id);
        deleteTarget.value = null;
    } catch {
        errorMessage.value = 'Failed to delete the form template.';
        deleteTarget.value = null;
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>
