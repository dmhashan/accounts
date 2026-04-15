<template>
    <section class="app-page-frame">
        <AppPageHeader>
            <template #cta-slot>
                <AppHeaderAction to="/events/new" :icon="CalendarPlus" label="New Event" />
            </template>
            <template #extra-slot>
                <AppSearchField v-model="search" placeholder="Search events by name" :disabled="loading" @search="load(1)" />
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">
                    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">Loading events...</div>

                    <template v-else-if="events.length === 0">
                        <div class="p-10 text-center text-secondary-500 dark:text-secondary-400 text-sm">
                            No events found. Create one to get started.
                        </div>
                    </template>

                    <template v-else>
                        <!-- Mobile cards -->
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article v-for="ev in events" :key="ev.id" class="p-4 space-y-2 cursor-pointer" @click="router.push(`/events/${ev.id}`)">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">{{ ev.name }}</p>
                                            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full" :class="ev.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-secondary-100 text-secondary-600 dark:bg-secondary-700 dark:text-secondary-400'">
                                                {{ ev.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                                            {{ formatDatetime(ev.start_datetime) }}
                                            <template v-if="ev.venue"> &bull; {{ ev.venue }}</template>
                                        </p>
                                        <p class="mt-0.5 text-xs text-secondary-400 dark:text-secondary-500">
                                            {{ ev.registrations_count }} registered
                                            <template v-if="ev.total_paid > 0"> &bull; <span class="text-green-600 dark:text-green-400">{{ formatFee(ev.total_paid) }} paid</span></template>
                                            <template v-if="ev.total_outstanding > 0"> &bull; <span class="text-amber-600 dark:text-amber-400">{{ formatFee(ev.total_outstanding) }} outstanding</span></template>
                                        </p>
                                    </div>
                                    <div class="flex gap-2 shrink-0 text-sm" @click.stop>
                                        <RouterLink :to="`/events/${ev.id}/registrations`" class="text-emerald-600 dark:text-emerald-400">Registrations</RouterLink>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Desktop table -->
                        <table class="hidden md:table w-full text-sm">
                            <thead class="border-b border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">Date & Venue</th>
                                    <th class="px-4 py-3 text-center font-semibold text-secondary-700 dark:text-secondary-300">Registered</th>
                                    <th class="px-4 py-3 text-right font-semibold text-secondary-700 dark:text-secondary-300">Paid</th>
                                    <th class="px-4 py-3 text-right font-semibold text-secondary-700 dark:text-secondary-300">Outstanding</th>
                                    <th class="px-4 py-3 text-left font-semibold text-secondary-700 dark:text-secondary-300">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold text-secondary-700 dark:text-secondary-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <tr v-for="ev in events" :key="ev.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors cursor-pointer" @click="router.push(`/events/${ev.id}`)">
                                    <td class="px-4 py-3 font-medium text-secondary-900 dark:text-white">
                                        {{ ev.name }}
                                        <span class="ml-1.5 text-xs text-secondary-400 font-mono">/{{ ev.slug }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-secondary-600 dark:text-secondary-400">
                                        <div>{{ formatDatetime(ev.start_datetime) }}</div>
                                        <div v-if="ev.venue" class="text-xs text-secondary-400">{{ ev.venue }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-secondary-600 dark:text-secondary-400">{{ ev.registrations_count }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap font-medium" :class="ev.total_paid > 0 ? 'text-green-600 dark:text-green-400' : 'text-secondary-400 dark:text-secondary-500'">{{ ev.total_paid > 0 ? formatFee(ev.total_paid) : '—' }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap font-medium" :class="ev.total_outstanding > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-secondary-400 dark:text-secondary-500'">{{ ev.total_outstanding > 0 ? formatFee(ev.total_outstanding) : '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="ev.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-secondary-100 text-secondary-600 dark:bg-secondary-700 dark:text-secondary-400'">
                                            {{ ev.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap" @click.stop>
                                        <RouterLink :to="`/events/${ev.id}/registrations`" class="text-emerald-600 dark:text-emerald-400 hover:underline">Registrations</RouterLink>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                </div>

                <AppPagination v-if="meta.last_page > 1" :meta="meta" :disabled="loading" class="mt-4" @page="load" />
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { CalendarPlus } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppSearchField from '../components/AppSearchField.vue';
import AppPagination from '../components/AppPagination.vue';

const loading      = ref(false);
const router       = useRouter();
const errorMessage = ref('');
const search       = ref('');
const events       = ref([]);
const meta         = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

async function load(page = 1) {
    loading.value = true;
    errorMessage.value = '';
    try {
        const params = new URLSearchParams({ page, per_page: 15 });
        if (search.value) params.set('search', search.value);
        const res = await apiRequest(`/api/events?${params}`);
        events.value = res.data;
        meta.value   = res.meta;
    } catch {
        errorMessage.value = 'Failed to load events.';
    } finally {
        loading.value = false;
    }
}

function formatDatetime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatFee(fee) {
    return fee > 0 ? `$${Number(fee).toFixed(2)}` : 'Free';
}

onMounted(() => load());
</script>
