<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true">
            <template #cta-slot>
                <RouterLink
                    :to="`/roles/${route.params.id}/edit`"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
                >
                    Manage Permissions
                </RouterLink>
            </template>
        </AppPageHeader>

        <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading...</div>

        <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">{{ errorMessage }}</div>

        <div v-else class="app-page-scroll space-y-5">
            <div class="app-surface rounded-2xl p-4 md:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                    <div>
                        <h1 class="text-xl font-bold text-secondary-900 dark:text-white">{{ role.name }}</h1>
                        <p class="mt-1 text-xs text-secondary-400 font-mono">{{ role.slug }}</p>
                    </div>
                    <span v-if="!role.is_editable" class="self-start px-2 py-1 text-xs bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300 rounded">Predefined</span>
                </div>

                <p v-if="role.description" class="text-sm text-secondary-700 dark:text-secondary-300 mb-4">{{ role.description }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">Users Assigned</p>
                        <p class="font-medium text-secondary-800 dark:text-secondary-200">{{ role.users_count ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">Permissions</p>
                        <p class="font-medium text-secondary-800 dark:text-secondary-200">{{ role.permissions_count ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();

const loading = ref(false);
const errorMessage = ref('');
const role = ref({});

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/roles/${route.params.id}`);
        role.value = response.role || response.data || response;
    } catch {
        errorMessage.value = 'Failed to load role.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
