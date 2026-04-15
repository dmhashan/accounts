<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true">
            <template #cta-slot>
                <RouterLink
                    v-if="permissions.edit"
                    :to="`/users/${route.params.id}/edit`"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
                >
                    Edit User
                </RouterLink>
                <button
                    v-if="permissions.delete && user.canDelete"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
                    :disabled="deleting"
                    @click="deleteUser"
                >Delete</button>
            </template>
        </AppPageHeader>

        <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading...</div>

        <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">{{ errorMessage }}</div>

        <div v-else class="app-page-scroll space-y-5">
            <div class="app-surface rounded-2xl p-4 md:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
                    <div class="h-16 w-16 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center shrink-0">
                        <span class="text-xl font-bold text-white">{{ initials(user.name) }}</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-secondary-900 dark:text-white">{{ user.name }}</h1>
                        <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">{{ user.email }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">Role</p>
                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full"
                            :class="user.role?.slug === 'admin'
                                ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300'
                                : 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300'">
                            {{ user.role?.name || 'No Role' }}
                        </span>
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
import { useAppContext } from '../composables/useAppContext';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();
const context = useAppContext();

const loading = ref(false);
const errorMessage = ref('');
const user = ref({});
const deleting = ref(false);
const permissions = ref({ edit: false, delete: false });

function initials(name = '') {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'U';
}

async function deleteUser() {
    if (!confirm('Are you sure you want to delete this user?')) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/users/${route.params.id}`, { method: 'DELETE' });
        router.push('/users');
    } catch (e) {
        alert(e?.response?.data?.message || 'Failed to delete user.');
    } finally {
        deleting.value = false;
    }
}

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/users/${route.params.id}`);
        user.value = response.data || response;
        // Load permissions from the users list response
        const listResponse = await apiRequest('/api/users', { params: { per_page: 1 } });
        permissions.value = listResponse.permissions || { edit: false, delete: false };
    } catch {
        errorMessage.value = 'Failed to load user.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
</script>
