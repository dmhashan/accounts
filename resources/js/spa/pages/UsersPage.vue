<template>
    <section class="app-page-frame">
        <div class="app-surface rounded-2xl p-4 sm:p-5 md:p-6 mb-4 md:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] text-secondary-500 dark:text-secondary-400">Users</p>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">User Management</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">All user records are loaded via REST API.</p>
                </div>
                <RouterLink
                    v-if="permissions.create"
                    to="/users/new"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white font-semibold transition-all hover:brightness-110"
                >
                    Add User
                </RouterLink>
            </div>

            <div class="mt-4 flex flex-col md:flex-row gap-2">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search users by name or email"
                    class="w-full md:flex-1 px-4 py-2.5 border border-secondary-300 dark:border-secondary-700 rounded-xl bg-white dark:bg-secondary-900 text-secondary-900 dark:text-white"
                    @keyup.enter="loadUsers(1)"
                />
                <button type="button" class="px-4 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-700 text-sm font-semibold hover:bg-secondary-50 dark:hover:bg-secondary-800" @click="loadUsers(1)">Search</button>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading users...</div>

                    <template v-else>
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article v-for="user in users" :key="user.id" class="p-4 space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-semibold text-white">{{ initials(user.name) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ user.name }}</p>
                                        <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">{{ user.email }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-secondary-500 dark:text-secondary-400">Role</span>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                        :class="user.role?.slug === 'admin'
                                            ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300'
                                            : 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300'">
                                        {{ user.role?.name || 'No Role' }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-3 text-sm">
                                    <RouterLink v-if="permissions.edit" :to="`/users/${user.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                                    <button
                                        v-if="permissions.delete && user.canDelete"
                                        type="button"
                                        class="text-red-600 dark:text-red-400"
                                        @click="removeUser(user.id)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </article>

                            <div v-if="users.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No users found.</div>
                        </div>

                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Role</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                    <tr v-for="user in users" :key="user.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center">
                                                    <span class="text-xs font-semibold text-white">{{ initials(user.name) }}</span>
                                                </div>
                                                <span class="text-sm font-medium text-secondary-900 dark:text-white">{{ user.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ user.email }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full"
                                                :class="user.role?.slug === 'admin'
                                                    ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300'
                                                    : 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300'">
                                                {{ user.role?.name || 'No Role' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm">
                                            <RouterLink v-if="permissions.edit" :to="`/users/${user.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Edit</RouterLink>
                                            <button
                                                v-if="permissions.delete && user.canDelete"
                                                type="button"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                @click="removeUser(user.id)"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="users.length === 0">
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No users found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
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
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';

const context = useAppContext();
const users = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const search = ref('');
const permissions = ref({ create: false, edit: false, delete: false });
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);

function initials(name = '') {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'U';
}

async function loadUsers(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/users', {
            params: {
                page,
                per_page: perPage.value,
                search: search.value,
            },
        });

        users.value = response.data || [];
        permissions.value = response.permissions || permissions.value;
        meta.value = response.meta || meta.value;
        perPage.value = meta.value.per_page || perPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load users.';
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    loadUsers(page);
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadUsers(1);
}

async function removeUser(userId) {
    if (!window.confirm('Are you sure you want to delete this user?')) {
        return;
    }

    try {
        await apiRequest(`/api/users/${userId}`, { method: 'delete' });
        await loadUsers(meta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete user.';
    }
}

onMounted(() => {
    loadUsers();
});
</script>
