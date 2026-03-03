<template>
    <section>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 md:mb-6">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">Member Management</h2>
                <p class="text-sm text-secondary-500 dark:text-secondary-400">All member records are loaded via REST API.</p>
            </div>
            <RouterLink
                v-if="permissions.create"
                to="/members/new"
                class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
            >
                Add Member
            </RouterLink>
        </div>

        <div class="mb-4 flex gap-2">
            <input
                v-model="search"
                type="text"
                placeholder="Search members by id, name, email, or phone"
                class="w-full md:max-w-md px-4 py-2.5 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-900 text-secondary-900 dark:text-white"
                @keyup.enter="loadMembers(1)"
            />
            <button type="button" class="px-4 py-2 rounded-lg border border-secondary-300 dark:border-secondary-700 text-sm" @click="loadMembers(1)">Search</button>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
            <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading members...</div>

            <template v-else>
                <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                    <article v-for="member in members" :key="member.id" class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">ID: {{ member.member_id }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ memberFullName(member) }}</p>
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="genderBadgeClass(member)">
                                        {{ capitalize(member.gender) || 'N/A' }}
                                    </span>
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="member.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'">
                                        {{ member.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="member.is_verified ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'">
                                        {{ member.is_verified ? 'Verified' : 'Unverified' }}
                                    </span>
                                </div>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">
                                    {{ member.phone_number || 'N/A' }} • {{ member.email || 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 text-sm flex-wrap">
                            <RouterLink v-if="permissions.edit" :to="`/members/${member.id}/edit`" class="text-primary-600 dark:text-primary-400">Edit</RouterLink>
                            <button v-if="permissions.edit" type="button" class="text-secondary-700 dark:text-secondary-300" @click="toggleStatus(member)">
                                {{ member.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button v-if="permissions.edit" type="button" class="text-blue-600 dark:text-blue-400" @click="toggleVerification(member)">
                                {{ member.is_verified ? 'Unverify' : 'Verify' }}
                            </button>
                            <button v-if="permissions.delete" type="button" class="text-red-600 dark:text-red-400" @click="removeMember(member.id)">Delete</button>
                        </div>
                    </article>

                    <div v-if="members.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No members found.</div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Member</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <tr v-for="member in members" :key="member.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                <td class="px-6 py-4 text-sm text-secondary-900 dark:text-white">{{ member.member_id }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-secondary-900 dark:text-white">{{ memberFullName(member) }}</span>
                                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="genderBadgeClass(member)">{{ capitalize(member.gender) || 'N/A' }}</span>
                                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="member.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'">
                                            {{ member.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="member.is_verified ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'">
                                            {{ member.is_verified ? 'Verified' : 'Unverified' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">
                                        {{ member.phone_number || 'N/A' }} • {{ member.email || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                    <RouterLink v-if="permissions.edit" :to="`/members/${member.id}/edit`" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">Edit</RouterLink>
                                    <button v-if="permissions.edit" type="button" class="text-secondary-700 hover:text-secondary-900 dark:text-secondary-300 dark:hover:text-secondary-100" @click="toggleStatus(member)">
                                        {{ member.is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button v-if="permissions.edit" type="button" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" @click="toggleVerification(member)">
                                        {{ member.is_verified ? 'Unverify' : 'Verify' }}
                                    </button>
                                    <button v-if="permissions.delete" type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" @click="removeMember(member.id)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="members.length === 0">
                                <td colspan="3" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No members found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        <AppPagination
            :current-page="meta.current_page"
            :last-page="meta.last_page"
            :per-page="perPage"
            :total="meta.total"
            :disabled="loading"
            @page-change="handlePageChange"
            @limit-change="handleLimitChange"
        />
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppPagination from '../components/AppPagination.vue';
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';

const context = useAppContext();
const members = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const search = ref('');
const permissions = ref({ create: false, edit: false, delete: false });
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);

function capitalize(value = '') {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
}

function memberFullName(member) {
    const firstName = (member.first_name || '').trim();
    const lastName = (member.last_name || '').trim();

    if (firstName || lastName) {
        return `${firstName} ${lastName}`.trim();
    }

    return member.name || '-';
}

function genderBadgeClass(member) {
    if (member.gender === 'male') {
        return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300';
    }

    if (member.gender === 'female') {
        return 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300';
    }

    return 'bg-secondary-100 text-secondary-700 dark:bg-secondary-700 dark:text-secondary-300';
}

async function loadMembers(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/members', {
            params: {
                page,
                per_page: perPage.value,
                search: search.value,
            },
        });

        members.value = response.data || [];
        permissions.value = response.permissions || permissions.value;
        meta.value = response.meta || meta.value;
        perPage.value = meta.value.per_page || perPage.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load members.';
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    loadMembers(page);
}

function handleLimitChange(limit) {
    perPage.value = Number(limit);
    loadMembers(1);
}

async function toggleStatus(member) {
    try {
        await apiRequest(`/api/members/${member.id}/toggle-status`, { method: 'patch' });
        await loadMembers(meta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member status.';
    }
}

async function toggleVerification(member) {
    try {
        await apiRequest(`/api/members/${member.id}/toggle-verification`, { method: 'patch' });
        await loadMembers(meta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member verification.';
    }
}

async function removeMember(memberId) {
    if (!window.confirm('Are you sure you want to delete this member?')) {
        return;
    }

    try {
        await apiRequest(`/api/members/${memberId}`, { method: 'delete' });
        await loadMembers(meta.value.current_page);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete member.';
    }
}

onMounted(() => {
    loadMembers();
});
</script>
