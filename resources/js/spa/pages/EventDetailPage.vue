<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <RouterLink
          :to="`/events/${route.params.id}/registrations`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-secondary-100 hover:bg-secondary-200 dark:bg-secondary-700 dark:hover:bg-secondary-600 text-secondary-800 dark:text-secondary-100 text-sm font-semibold rounded-xl transition-colors"
        >
          Registrations
        </RouterLink>
        <RouterLink
          :to="`/events/${route.params.id}/edit`"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Edit Event
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50"
          :disabled="deleteConfirming"
          @click="deleteEvent"
        >
          Delete
        </button>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="p-6 text-sm text-secondary-500 dark:text-secondary-400">
      Loading...
    </div>

    <div v-else-if="errorMessage" class="p-6 text-sm text-red-600 dark:text-red-400">
      {{ errorMessage }}
    </div>

    <div v-else class="app-page-scroll space-y-5">
      <!-- Event header -->
      <div class="app-surface rounded-2xl p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
          <div>
            <h1 class="text-xl font-bold text-secondary-900 dark:text-white">
              {{ event.name }}
            </h1>
            <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400 font-mono break-all">
              {{ registrationLink }}
            </p>
          </div>
          <span
            class="self-start px-3 py-1 text-xs font-semibold rounded-full"
            :class="event.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-secondary-100 text-secondary-600 dark:bg-secondary-700 dark:text-secondary-400'"
          >
            {{ event.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Start
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ formatDateTime(event.start_datetime) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              End
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ event.end_datetime ? formatDateTime(event.end_datetime) : '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Venue
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ event.venue || '—' }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Registrations
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ event.registrations_count }}
            </p>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Ticket Fee
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ formatFee(event.ticket_fee) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-secondary-400 uppercase tracking-wide mb-1">
              Additional Member Fee
            </p>
            <p class="font-medium text-secondary-800 dark:text-secondary-200">
              {{ formatFee(event.additional_ticket_fee) }}
            </p>
          </div>
        </div>

        <!-- Registration link -->
        <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-2">
          <p class="text-xs text-secondary-400 uppercase tracking-wide shrink-0">
            Registration Link
          </p>
          <div class="flex items-center gap-2 min-w-0 flex-1">
            <code class="text-xs bg-secondary-100 dark:bg-secondary-800 px-2 py-1 rounded text-primary-700 dark:text-primary-300 truncate select-all flex-1">{{ registrationLink }}</code>
            <button type="button" class="shrink-0 text-xs text-secondary-500 hover:text-secondary-800 dark:hover:text-white transition-colors" @click="copyLink">
              {{ copied ? 'Copied!' : 'Copy' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Agenda & registration process -->
      <div v-if="event.agenda || event.registration_process" class="app-surface rounded-2xl p-4 md:p-6 space-y-4">
        <div v-if="event.agenda">
          <h2 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide mb-2">
            Agenda
          </h2>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ event.agenda }}
          </p>
        </div>
        <div v-if="event.registration_process">
          <h2 class="text-sm font-semibold text-secondary-700 dark:text-secondary-300 uppercase tracking-wide mb-2">
            Registration Process
          </h2>
          <p class="text-sm text-secondary-800 dark:text-secondary-200 whitespace-pre-wrap">
            {{ event.registration_process }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';
import { useDateTimeFormat } from '../composables/useDateTimeFormat';
import AppPageHeader from '../components/AppPageHeader.vue';

const route  = useRoute();
const router = useRouter();
const context = useAppContext();
const loading      = ref(false);
const errorMessage = ref('');
const event        = ref({});
const copied       = ref(false);
const deleteConfirming = ref(false);



const registrationLink = computed(() => {
    if (!event.value.slug || !memberPortalUrl.value) return '';
    return `${memberPortalUrl.value}/event/${event.value.slug}`;
});

const memberPortalUrl = computed(() => (context.tenant?.member_portal_url || '').replace(/\/$/, ''));

async function deleteEvent() {
    if (!confirm('Delete this event? All registrations will also be deleted.')) return;
    deleteConfirming.value = true;
    try {
        await apiRequest(`/api/events/${route.params.id}`, { method: 'DELETE' });
        router.push('/events');
    } catch (e) {
        alert(e?.response?.data?.message || e?.message || 'Failed to delete event.');
    } finally {
        deleteConfirming.value = false;
    }
}

async function loadEvent() {
    loading.value = true;
    errorMessage.value = '';
    try {
        event.value = await apiRequest(`/api/events/${route.params.id}`);
    } catch {
        errorMessage.value = 'Failed to load event.';
    } finally {
        loading.value = false;
    }
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(registrationLink.value);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        // fallback handled silently
    }
}

const { formatDateTime } = useDateTimeFormat();

function formatFee(fee) {
    return Number(fee) > 0 ? `$${Number(fee).toFixed(2)}` : 'Free';
}

onMounted(() => loadEvent());
</script>
