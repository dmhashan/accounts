<template>
    <section class="app-page-frame">
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Roles</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Role Management</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Manage roles and permissions through SPA data loading.</p>
                </div>

                <RouterLink
                    v-if="allowRoleCreate"
                    to="/roles/new"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white font-semibold transition-all hover:brightness-110"
                >
                    Add Role
                </RouterLink>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
                    <article v-for="role in roles" :key="role.id" class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-5 md:p-6">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">{{ role.name }}</h3>
                            <span v-if="!role.is_editable" class="px-2 py-1 text-xs bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300 rounded">Predefined</span>
                        </div>
                        <p v-if="role.description" class="mt-1 text-sm text-secondary-600 dark:text-secondary-400">{{ role.description }}</p>

                        <div class="mt-4 space-y-2 text-sm text-secondary-700 dark:text-secondary-300">
                            <p>{{ role.users_count }} {{ pluralize(role.users_count, 'user') }}</p>
                            <p>{{ role.permissions_count }} {{ pluralize(role.permissions_count, 'permission') }}</p>
                        </div>

                        <RouterLink :to="`/roles/${role.id}/edit`" class="mt-4 block w-full px-4 py-2 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/50 rounded-lg text-center font-medium transition-colors">
                            Manage Permissions
                        </RouterLink>
                    </article>
                </div>

                <div v-if="!loading && roles.length === 0" class="mt-4 text-sm text-secondary-500 dark:text-secondary-400">No roles found.</div>
            </div>

            <div class="app-page-pagination">
                <AppPagination
                    :current-page="meta.current_page"
                    :last-page="meta.last_page"
                    :per-page="perPage"
                    :total="meta.total"
                    :disabled="loading"
                    @page-change="handlePageChange"
                    @limit-change="handleLimitChange"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppPagination from '../components/AppPagination.vue';
import { apiRequest } from '../composables/useApiClient';

const loading = ref(false);
const roles = ref([]);
const errorMessage = ref('');
const allowRoleCreate = ref(false);
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 });
const perPage = ref(12);

function pluralize(count, noun) {
    return count === 1 ? noun : `${noun}s`;
}

async function loadRoles(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/roles', {
            params: {
                page,
                per_page: perPage.value,
            },
        });
        roles.value = response.data || [];
        meta.value = response.meta || meta.value;
        perPage.value = meta.value.per_page || perPage.value;
        allowRoleCreate.value = Boolean(response.permissions?.managePermissions);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load roles.';
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    loadRoles(page);
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadRoles(1);
}

onMounted(() => {
    loadRoles();
});
</script>
