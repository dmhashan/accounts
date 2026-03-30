<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <div class="flex flex-row gap-2 w-full lg:w-auto">
                    <AppHeaderAction :icon="Download" :label="exporting ? 'Exporting...' : 'Export to Google Contact'" variant="secondary" :disabled="exporting" @click="exportGoogleContacts" />
                    <AppHeaderAction v-if="permissions.create" to="/members/new" :icon="UserRoundPlus" label="Add Member" />
                </div>
            </template>

            <template #extra-slot>
                <AppSearchField v-model="search" placeholder="Search members by id, name, email, or phone" :disabled="loading" @search="loadMembers(1)" />
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading members...</div>

                    <template v-else>
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article
                                v-for="member in members"
                                :key="member.id"
                                class="p-4 space-y-3 cursor-pointer transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                role="link"
                                tabindex="0"
                                @click="openMember(member.id)"
                                @keydown.enter.prevent="openMember(member.id)"
                                @keydown.space.prevent="openMember(member.id)"
                            >
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
                                        <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs">
                                            <p class="text-secondary-500 dark:text-secondary-400">
                                                {{ member.phone_number || 'N/A' }} • {{ member.email || 'N/A' }}
                                            </p>
                                            <span class="font-semibold text-primary-600 dark:text-primary-400">View details</span>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <div v-if="members.length === 0" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">No members found.</div>
                        </div>

                        <div class="hidden md:block app-table-scroll">
                            <table class="w-full">
                                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase">Member</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                    <tr
                                        v-for="member in members"
                                        :key="member.id"
                                        class="cursor-pointer transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500"
                                        role="link"
                                        tabindex="0"
                                        @click="openMember(member.id)"
                                        @keydown.enter.prevent="openMember(member.id)"
                                        @keydown.space.prevent="openMember(member.id)"
                                    >
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
                                            <div class="mt-2 text-xs font-semibold text-primary-600 dark:text-primary-400">Open member details</div>
                                        </td>
                                    </tr>
                                    <tr v-if="members.length === 0">
                                        <td colspan="2" class="px-6 py-10 text-center text-sm text-secondary-500 dark:text-secondary-400">No members found.</td>
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
import { useRouter } from 'vue-router';
import AppPagination from '../components/AppPagination.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { Download, UserRoundPlus } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';

const router = useRouter();
const members = ref([]);
const loading = ref(false);
const exporting = ref(false);
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

function openMember(memberId) {
    router.push(`/members/${memberId}`);
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

async function exportGoogleContacts() {
    exporting.value = true;
    errorMessage.value = '';

    try {
        const csvBlob = await apiRequest('/api/members/export/google-contacts', {
            responseType: 'blob',
            headers: {
                Accept: 'text/csv',
            },
        });

        const downloadUrl = window.URL.createObjectURL(csvBlob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = `google-contacts-members-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(downloadUrl);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to export members for Google Contacts.';
    } finally {
        exporting.value = false;
    }
}

onMounted(() => {
    loadMembers();
});
</script>
