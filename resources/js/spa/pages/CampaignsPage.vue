<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction
          v-if="context.permissions?.campaignsCreate"
          :icon="Megaphone"
          label="New Campaign"
          to="/settings/campaigns/new"
        />
      </template>
      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search campaigns by title or slug"
          :disabled="loading"
          @search="loadCampaigns(1)"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="app-alert app-alert-error">
      {{ errorMessage }}
    </div>
    <div v-if="successMessage" class="app-alert app-alert-success">
      {{ successMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <div v-for="i in 5" :key="i" class="p-4 space-y-2">
              <div class="app-skeleton h-4 w-48 rounded" />
              <div class="app-skeleton h-3 w-80 rounded" />
            </div>
          </div>

          <AppEmptyState
            v-else-if="campaigns.length === 0"
            :icon="Megaphone"
            title="No campaigns yet"
            description="Create a public registration campaign when you are ready."
          />

          <template v-else>
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article v-for="campaign in campaigns" :key="campaign.id" class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
                        {{ campaign.title }}
                      </h3>
                      <span :class="statusClass(campaign.status)">
                        {{ statusLabel(campaign.status) }}
                      </span>
                    </div>
                    <p class="mt-1 truncate text-xs text-secondary-500 dark:text-secondary-400">
                      /campaigns/{{ campaign.slug }}
                    </p>
                  </div>
                  <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-secondary-300 text-secondary-600 dark:border-secondary-700 dark:text-secondary-300"
                    title="Edit campaign"
                    @click="router.push(`/settings/campaigns/${campaign.id}/edit`)"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs text-secondary-500 dark:text-secondary-400">
                  <span>{{ campaign.registrations_count }} registrations</span>
                  <span>{{ formatDate(campaign.created_at) }}</span>
                </div>

                <div class="flex flex-wrap gap-2">
                  <button class="app-icon-text-button" type="button" @click="copyUrl(campaign)">
                    <Copy class="h-3.5 w-3.5" /> Copy
                  </button>
                  <a
                    class="app-icon-text-button"
                    :href="campaign.public_url"
                    target="_blank"
                    rel="noreferrer"
                  >
                    <ExternalLink class="h-3.5 w-3.5" /> Open
                  </a>
                  <button class="app-icon-text-button" type="button" @click="openRegistrations(campaign)">
                    <Users class="h-3.5 w-3.5" /> Registrations
                  </button>
                  <button
                    v-if="nextStatus(campaign)"
                    class="app-icon-text-button"
                    type="button"
                    @click="changeStatus(campaign, nextStatus(campaign).value)"
                  >
                    <component :is="nextStatus(campaign).icon" class="h-3.5 w-3.5" />
                    {{ nextStatus(campaign).label }}
                  </button>
                </div>
              </article>
            </div>

            <div class="hidden md:block app-table-scroll">
              <table class="w-full">
                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                  <tr>
                    <th class="app-table-th">
                      Campaign
                    </th>
                    <th class="app-table-th">
                      Status
                    </th>
                    <th class="app-table-th">
                      Created
                    </th>
                    <th class="app-table-th">
                      Published
                    </th>
                    <th class="app-table-th">
                      Closed
                    </th>
                    <th class="app-table-th text-center">
                      Registrations
                    </th>
                    <th class="app-table-th" />
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                  <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40">
                    <td class="app-table-td">
                      <p class="font-semibold text-secondary-900 dark:text-white">
                        {{ campaign.title }}
                      </p>
                      <p class="mt-0.5 text-xs text-secondary-500 dark:text-secondary-400">
                        /campaigns/{{ campaign.slug }}
                      </p>
                    </td>
                    <td class="app-table-td">
                      <span :class="statusClass(campaign.status)">
                        {{ statusLabel(campaign.status) }}
                      </span>
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400">
                      {{ formatDate(campaign.created_at) }}
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400">
                      {{ formatDate(campaign.published_at) }}
                    </td>
                    <td class="app-table-td text-secondary-500 dark:text-secondary-400">
                      {{ formatDate(campaign.closed_at) }}
                    </td>
                    <td class="app-table-td text-center">
                      <button type="button" class="font-semibold text-primary-600 hover:underline dark:text-primary-400" @click="openRegistrations(campaign)">
                        {{ campaign.registrations_count }}
                      </button>
                    </td>
                    <td class="app-table-td">
                      <div class="flex items-center justify-end gap-1.5">
                        <button
                          class="app-icon-button"
                          type="button"
                          title="Copy URL"
                          @click="copyUrl(campaign)"
                        >
                          <Copy class="h-4 w-4" />
                        </button>
                        <a
                          class="app-icon-button"
                          :href="campaign.public_url"
                          target="_blank"
                          rel="noreferrer"
                          title="Open public page"
                        >
                          <ExternalLink class="h-4 w-4" />
                        </a>
                        <button
                          class="app-icon-button"
                          type="button"
                          title="Edit campaign"
                          @click="router.push(`/settings/campaigns/${campaign.id}/edit`)"
                        >
                          <Pencil class="h-4 w-4" />
                        </button>
                        <button
                          v-if="nextStatus(campaign)"
                          class="app-icon-button"
                          type="button"
                          :title="nextStatus(campaign).label"
                          @click="changeStatus(campaign, nextStatus(campaign).value)"
                        >
                          <component :is="nextStatus(campaign).icon" class="h-4 w-4" />
                        </button>
                        <button
                          v-if="context.permissions?.campaignsDelete"
                          class="app-icon-button text-red-600 dark:text-red-400"
                          type="button"
                          title="Delete campaign"
                          @click="confirmDelete(campaign)"
                        >
                          <Trash2 class="h-4 w-4" />
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

      <div class="app-page-pagination">
        <AppPagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :per-page="perPage"
          :total="meta.total"
          :disabled="loading"
          @page-change="loadCampaigns"
          @limit-change="value => { perPage = Number(value); loadCampaigns(1); }"
        />
      </div>
    </div>

    <div v-if="registrationModalOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-secondary-950/50 p-3 sm:items-center" @click.self="registrationModalOpen = false">
      <article class="app-surface flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl">
        <header class="flex items-center justify-between border-b border-secondary-200 px-5 py-4 dark:border-secondary-700">
          <div>
            <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
              Registrations
            </h3>
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              {{ registrationCampaign?.title }}
            </p>
          </div>
          <button
            type="button"
            class="app-icon-button"
            title="Close"
            @click="registrationModalOpen = false"
          >
            <X class="h-4 w-4" />
          </button>
        </header>
        <div class="flex-1 overflow-y-auto">
          <div v-if="registrationsLoading" class="p-5 text-sm text-secondary-500 dark:text-secondary-400">
            Loading registrations...
          </div>
          <div v-else-if="registrations.length === 0" class="p-5 text-sm text-secondary-500 dark:text-secondary-400">
            No registrations received yet.
          </div>
          <div v-else class="divide-y divide-secondary-200 dark:divide-secondary-700">
            <article v-for="member in registrations" :key="member.id" class="flex items-center justify-between gap-3 px-5 py-3">
              <div class="min-w-0">
                <RouterLink :to="`/members/${member.id}`" class="truncate text-sm font-semibold text-secondary-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400">
                  {{ member.name || 'Member' }}
                </RouterLink>
                <p class="truncate text-xs text-secondary-500 dark:text-secondary-400">
                  {{ member.phone_number || member.email || 'No contact' }}
                </p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <span :class="member.is_verified ? verifiedBadgeClass : unverifiedBadgeClass">
                  {{ member.is_verified ? 'Verified' : 'Unverified' }}
                </span>
                <span class="text-xs text-secondary-400 dark:text-secondary-500">
                  {{ member.documents_count }} docs
                </span>
              </div>
            </article>
          </div>
        </div>
      </article>
    </div>

    <AppConfirmModal
      v-if="deleteTarget"
      title="Delete Campaign"
      message="This campaign will be deleted. Members already registered from it will remain in the member list."
      confirm-label="Delete"
      loading-label="Deleting..."
      :loading="deleting"
      @confirm="deleteCampaign"
      @cancel="deleteTarget = null"
    />
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
    Copy,
    ExternalLink,
    Lock,
    Megaphone,
    Pencil,
    Play,
    RefreshCw,
    Trash2,
    Users,
    X,
} from 'lucide-vue-next';
import AppConfirmModal from '../components/AppConfirmModal.vue';
import AppEmptyState from '../components/AppEmptyState.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPagination from '../components/AppPagination.vue';
import AppSearchField from '../components/AppSearchField.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const router = useRouter();
const context = useAppContext();

const campaigns = ref([]);
const loading = ref(false);
const perPage = ref(15);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const search = ref('');
const errorMessage = ref('');
const successMessage = ref('');
const deleteTarget = ref(null);
const deleting = ref(false);

const registrationModalOpen = ref(false);
const registrationCampaign = ref(null);
const registrationsLoading = ref(false);
const registrations = ref([]);

const verifiedBadgeClass = 'rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-900/25 dark:text-blue-300';
const unverifiedBadgeClass = 'rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-900/25 dark:text-amber-300';

function statusLabel(status) {
    return {
        draft: 'Draft',
        published: 'Published',
        closed: 'Closed',
    }[status] || status;
}

function statusClass(status) {
    const base = 'inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold';
    if (status === 'published') return `${base} border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/25 dark:text-green-300`;
    if (status === 'closed') return `${base} border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/25 dark:text-amber-300`;
    return `${base} border-secondary-200 bg-secondary-100 text-secondary-600 dark:border-secondary-700 dark:bg-secondary-800 dark:text-secondary-300`;
}

function nextStatus(campaign) {
    if (campaign.status === 'draft' && context.permissions?.campaignsPublish) {
        return { value: 'published', label: 'Publish', icon: Play };
    }

    if (campaign.status === 'published' && context.permissions?.campaignsClose) {
        return { value: 'closed', label: 'Close', icon: Lock };
    }

    if (campaign.status === 'closed' && context.permissions?.campaignsPublish) {
        return { value: 'published', label: 'Reopen', icon: RefreshCw };
    }

    return null;
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value));
}

async function loadCampaigns(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/campaigns', {
            params: {
                page,
                per_page: perPage.value,
                search: search.value.trim() || undefined,
            },
        });
        campaigns.value = response.data || [];
        meta.value = response.meta || meta.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load campaigns.';
    } finally {
        loading.value = false;
    }
}

async function changeStatus(campaign, status) {
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await apiRequest(`/api/campaigns/${campaign.id}/status`, {
            method: 'patch',
            data: { status },
        });
        const updated = response.data;
        campaigns.value = campaigns.value.map((item) => item.id === campaign.id ? { ...item, ...updated } : item);
        successMessage.value = response.message || 'Campaign status updated.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update campaign status.';
    }
}

async function copyUrl(campaign) {
    try {
        await navigator.clipboard.writeText(campaign.public_url);
        successMessage.value = 'Public campaign URL copied.';
    } catch {
        errorMessage.value = 'Could not copy the public URL.';
    }
}

async function openRegistrations(campaign) {
    registrationCampaign.value = campaign;
    registrationModalOpen.value = true;
    registrationsLoading.value = true;
    registrations.value = [];

    try {
        const response = await apiRequest(`/api/campaigns/${campaign.id}/registrations`, {
            params: { per_page: 100 },
        });
        registrations.value = response.data || [];
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load campaign registrations.';
        registrationModalOpen.value = false;
    } finally {
        registrationsLoading.value = false;
    }
}

function confirmDelete(campaign) {
    deleteTarget.value = campaign;
}

async function deleteCampaign() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    errorMessage.value = '';

    try {
        await apiRequest(`/api/campaigns/${deleteTarget.value.id}`, { method: 'delete' });
        campaigns.value = campaigns.value.filter((campaign) => campaign.id !== deleteTarget.value.id);
        deleteTarget.value = null;
        successMessage.value = 'Campaign deleted successfully.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete campaign.';
    } finally {
        deleting.value = false;
    }
}

onMounted(() => loadCampaigns(1));
</script>
