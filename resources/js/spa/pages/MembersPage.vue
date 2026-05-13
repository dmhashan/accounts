<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <div class="flex flex-row gap-2 w-full lg:w-auto">
                    <AppHeaderAction v-if="activeTab === 'members'" :icon="Download" :label="exporting ? 'Exporting...' : 'Export to Google Contact'" variant="secondary" :disabled="exporting" @click="exportGoogleContacts" />
                    <AppHeaderAction v-if="permissions.create && activeTab === 'temp'" variant="secondary" :icon="Clock" label="Add Temp Member" @click="tempModalOpen = true" />
                    <AppHeaderAction v-if="permissions.create && activeTab === 'members'" to="/members/new" :icon="UserRoundPlus" label="Add Member" />
                </div>
            </template>

            <template #extra-slot>
                <AppSearchField v-model="search" placeholder="Search members by id, name, email, or phone" :disabled="loading" @search="loadMembers(1)" />
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="app-alert app-alert-error">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">

                    <!-- Skeleton loading -->
                    <template v-if="loading">
                        <div class="divide-y divide-secondary-200 dark:divide-secondary-700">
                            <div v-for="i in 6" :key="i" class="p-4 flex items-center gap-3">
                                <div class="app-skeleton h-10 w-10 rounded-full shrink-0"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="app-skeleton h-3.5 w-40 rounded"></div>
                                    <div class="app-skeleton h-3 w-56 rounded"></div>
                                </div>
                                <div class="app-skeleton h-5 w-14 rounded-full shrink-0"></div>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <!-- Mobile cards -->
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article
                                v-for="member in members"
                                :key="member.id"
                                class="p-4 flex items-center gap-3 cursor-pointer transition-colors hover:bg-secondary-50 dark:hover:bg-secondary-800/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500"
                                role="link"
                                tabindex="0"
                                @click="openMember(member.id)"
                                @keydown.enter.prevent="openMember(member.id)"
                                @keydown.space.prevent="openMember(member.id)"
                            >
                                <!-- Avatar -->
                                <MemberAvatar
                                    :initials="memberInitials(member)"
                                    size="sm"
                                />

                                <!-- Info -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ memberFullName(member) }}</p>
                                        <AppBadge :color="member.gender === 'male' ? 'indigo' : member.gender === 'female' ? 'purple' : 'secondary'">
                                            {{ capitalize(member.gender) || 'N/A' }}
                                        </AppBadge>
                                        <AppBadge :color="member.is_active ? 'green' : 'red'">
                                            {{ member.is_active ? 'Active' : 'Inactive' }}
                                        </AppBadge>
                                        <AppBadge v-if="activeTab === 'members'" :color="member.is_verified ? 'blue' : 'amber'">
                                            {{ member.is_verified ? 'Verified' : 'Unverified' }}
                                        </AppBadge>
                                    </div>
                                    <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400 truncate">
                                        {{ member.member_id }} · {{ member.phone_number || member.email || 'No contact' }}
                                    </p>
                                </div>

                                <!-- Chevron -->
                                <ChevronRight class="h-4 w-4 text-secondary-400 shrink-0" :stroke-width="2" />
                            </article>

                            <AppEmptyState v-if="members.length === 0" :icon="Users" title="No members found" description="Try adjusting your search or add a new member." />
                        </div>

                        <!-- Desktop table -->
                        <div class="hidden md:block app-table-scroll">
                            <table class="w-full">
                                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                    <tr>
                                        <th class="app-table-th">Member</th>
                                        <th class="app-table-th">ID</th>
                                        <th class="app-table-th">Contact</th>
                                        <th class="app-table-th">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                    <tr
                                        v-for="member in members"
                                        :key="member.id"
                                        class="app-table-row cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500"
                                        role="link"
                                        tabindex="0"
                                        @click="openMember(member.id)"
                                        @keydown.enter.prevent="openMember(member.id)"
                                        @keydown.space.prevent="openMember(member.id)"
                                    >
                                        <td class="app-table-td">
                                            <div class="flex items-center gap-3">
                                                <MemberAvatar
                                                    :initials="memberInitials(member)"
                                                    size="xs"
                                                />
                                                <div>
                                                    <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ memberFullName(member) }}</p>
                                                    <div class="mt-0.5 flex flex-wrap gap-1">
                                                        <AppBadge :color="member.gender === 'male' ? 'indigo' : member.gender === 'female' ? 'purple' : 'secondary'">
                                                            {{ capitalize(member.gender) || 'N/A' }}
                                                        </AppBadge>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="app-table-td text-secondary-500 dark:text-secondary-400 text-xs">{{ member.member_id }}</td>
                                        <td class="app-table-td text-secondary-600 dark:text-secondary-300 text-xs">
                                            <div>{{ member.phone_number || '—' }}</div>
                                            <div class="text-secondary-400 dark:text-secondary-500">{{ member.email || '—' }}</div>
                                        </td>
                                        <td class="app-table-td">
                                            <div class="flex flex-wrap gap-1.5">
                                                <AppBadge :color="member.is_active ? 'green' : 'red'">
                                                    {{ member.is_active ? 'Active' : 'Inactive' }}
                                                </AppBadge>
                                                <AppBadge v-if="activeTab === 'members'" :color="member.is_verified ? 'blue' : 'amber'">
                                                    {{ member.is_verified ? 'Verified' : 'Unverified' }}
                                                </AppBadge>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="members.length === 0">
                                        <td colspan="4">
                                            <AppEmptyState :icon="Users" title="No members found" description="Try adjusting your search or add a new member." />
                                        </td>
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

    <TempMemberFormModal
        v-if="tempModalOpen"
        @close="tempModalOpen = false"
        @created="onTempMemberCreated"
    />
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPagination from '../components/AppPagination.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppBadge from '../components/AppBadge.vue';
import AppEmptyState from '../components/AppEmptyState.vue';
import TempMemberFormModal from '../components/TempMemberFormModal.vue';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';
import { ChevronRight, Clock, Download, Users, UserRoundPlus } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();

const router = useRouter();
const members = ref([]);
const loading = ref(false);
const exporting = ref(false);
const errorMessage = ref('');
const search = ref('');
const permissions = ref({ create: false, edit: false, delete: false });
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const perPage = ref(15);
const tempModalOpen = ref(false);
const activeTab = ref(route.path === '/members/temp' ? 'temp' : 'members');

function switchTab(tab) {
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    search.value = '';
    perPage.value = 15;
    loadMembers(1);
}

watch(
    () => route.path,
    (path) => {
        const newTab = path === '/members/temp' ? 'temp' : 'members';
        if (activeTab.value !== newTab) switchTab(newTab);
    }
);

function onTempMemberCreated(memberId) {
    tempModalOpen.value = false;
    if (memberId) {
        router.push(`/members/${memberId}`);
    } else {
        loadMembers(1);
    }
}

function capitalize(value = '') {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
}

function memberInitials(member) {
    const first = (member.first_name || '').trim();
    const last = (member.last_name || '').trim();
    if (first || last) {
        return `${first[0] || ''}${last[0] || ''}`.toUpperCase();
    }
    return (member.name || '?')[0].toUpperCase();
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
        const params = {
            page,
            per_page: perPage.value,
            search: search.value,
            is_temp: activeTab.value === 'temp' ? '1' : '0',
        };

        const response = await apiRequest('/api/members', { params });

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
