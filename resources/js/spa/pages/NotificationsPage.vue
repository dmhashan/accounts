<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #cta-slot>
        <AppHeaderAction to="/settings/notifications/new" :icon="BellPlus" label="New Notification" />
      </template>
      <template #extra-slot>
        <AppSearchField
          v-model="search"
          placeholder="Search notifications by name"
          :disabled="loading"
          @search="load(1)"
        />
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div class="min-h-0 flex flex-1 flex-col">
      <div class="app-page-scroll">
        <div class="app-surface rounded-2xl overflow-hidden">
          <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
            Loading notifications...
          </div>

          <template v-else-if="notifications.length === 0">
            <div class="p-10 text-center text-secondary-500 dark:text-secondary-400 text-sm">
              No notifications found. Create one to get started.
            </div>
          </template>

          <template v-else>
            <!-- Mobile cards -->
            <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
              <article
                v-for="n in notifications"
                :key="n.id"
                class="p-4 space-y-2 cursor-pointer hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors"
                @click="router.push('/settings/notifications/' + n.id)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                        {{ n.name }}
                      </p>
                      <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="statusClass(n.status)">{{ capitalize(n.status) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400 line-clamp-2">
                      {{ n.message }}
                    </p>
                    <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                      {{ n.recipients_count }} recipient{{ n.recipients_count === 1 ? '' : 's' }}
                      <template v-if="n.sent_at">
                        &bull; Sent {{ formatDate(n.sent_at) }}
                      </template>
                      <template v-else>
                        &bull; Created {{ formatDate(n.created_at) }}
                      </template>
                    </p>
                  </div>
                </div>
              </article>
            </div>

            <!-- Desktop table -->
            <table class="hidden md:table w-full text-sm">
              <thead class="border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Name
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Message
                  </th>
                  <th class="px-4 py-3 text-center font-semibold text-secondary-700 dark:text-secondary-300">
                    Recipients
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Status
                  </th>
                  <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">
                    Date
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                <tr
                  v-for="n in notifications"
                  :key="n.id"
                  class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors cursor-pointer"
                  @click="router.push('/settings/notifications/' + n.id)"
                >
                  <td class="px-4 py-3 font-medium text-secondary-900 dark:text-white">
                    {{ n.name }}
                  </td>
                  <td class="px-4 py-3 text-secondary-600 dark:text-secondary-400 max-w-xs truncate">
                    {{ n.message }}
                  </td>
                  <td class="px-4 py-3 text-center text-secondary-600 dark:text-secondary-400">
                    {{ n.recipients_count }}
                  </td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="statusClass(n.status)">{{ capitalize(n.status) }}</span>
                  </td>
                  <td class="px-4 py-3 text-secondary-500 dark:text-secondary-400 whitespace-nowrap">
                    {{ n.sent_at ? formatDate(n.sent_at) : formatDate(n.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </template>
        </div>

        <AppPagination
          v-if="meta.last_page > 1"
          :meta="meta"
          :disabled="loading"
          class="mt-4"
          @page="load"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { BellPlus } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppPagination from '../components/AppPagination.vue';

const loading = ref(false);
const router = useRouter();
const errorMessage = ref('');
const search = ref('');
const notifications = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

async function load(page = 1) {
    loading.value = true;
    errorMessage.value = '';
    try {
        const params = new URLSearchParams({ page, per_page: 15 });
        if (search.value) params.set('search', search.value);
        const res = await apiRequest(`/api/notifications?${params}`);
        notifications.value = res.data;
        meta.value = res.meta;
    } catch {
        errorMessage.value = 'Failed to load notifications.';
    } finally {
        loading.value = false;
    }
}

function statusClass(status) {
    return status === 'sent'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
}

function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

const { formatDate } = useDateTimeFormat();

onMounted(() => load());
</script>
