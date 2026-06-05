<template>
  <div v-if="summary.can_view" class="app-surface rounded-2xl border border-secondary-200/70 dark:border-secondary-700/70 p-4 sm:p-5 flex min-h-0 flex-col gap-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--text-muted)">
          Today Auth Details
        </p>
        <p class="mt-1 text-xs sm:text-sm" style="color: var(--text-muted)">
          Success attempts, payment-expired access attempts, and other failed attempts.
        </p>
      </div>
      <p class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap" style="color: var(--text-strong); background: var(--surface-muted)">
        Total: {{ formatNumber(summary.counts?.total) }}
      </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2">
      <label class="text-xs font-medium" style="color: var(--text-muted)">
        Date
      </label>
      <div class="flex flex-wrap items-center gap-1.5">
        <button
          v-for="shortcut in dateShortcuts"
          :key="shortcut.id"
          type="button"
          class="rounded-md border px-2 py-1 text-[11px] font-medium transition-colors"
          :class="selectedDate === shortcut.value
            ? 'border-primary-300 bg-primary-50 text-primary-700 dark:border-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
            : 'border-secondary-200 bg-white text-secondary-600 hover:bg-secondary-50 dark:border-secondary-700 dark:bg-secondary-800 dark:text-secondary-300 dark:hover:bg-secondary-700'"
          @click="onShortcutDateChange(shortcut.value)"
        >
          {{ shortcut.label }}
        </button>
      </div>
      <input
        type="date"
        :value="selectedDate"
        class="rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-2.5 py-1.5 text-xs sm:text-sm text-secondary-700 dark:text-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500/30"
        @change="onDateChange"
      />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
      <button
        type="button"
        class="rounded-xl border px-3 py-2.5 text-left transition-colors"
        :class="authActiveTab === 'success_attempts' ? 'border-green-300 bg-green-50/70 dark:border-green-900/50 dark:bg-green-900/20' : 'border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900/40 hover:bg-secondary-50 dark:hover:bg-secondary-800/70'"
        @click="authActiveTab = 'success_attempts'"
      >
        <p class="text-[11px] sm:text-xs font-medium" style="color: var(--text-muted)">
          Success
        </p>
        <p class="mt-1 text-base font-bold text-green-700 dark:text-green-400 leading-none">
          {{ formatNumber(summary.counts?.success) }}
        </p>
      </button>

      <button
        type="button"
        class="rounded-xl border px-3 py-2.5 text-left transition-colors"
        :class="authActiveTab === 'payment_expired' ? 'border-red-300 bg-red-50/70 dark:border-red-900/50 dark:bg-red-900/20' : 'border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900/40 hover:bg-secondary-50 dark:hover:bg-secondary-800/70'"
        @click="authActiveTab = 'payment_expired'"
      >
        <p class="text-[11px] sm:text-xs font-medium" style="color: var(--text-muted)">
          Payment Expired
        </p>
        <p class="mt-1 text-base font-bold text-red-700 dark:text-red-400 leading-none">
          {{ formatNumber(summary.counts?.payment_expired) }}
        </p>
      </button>

      <button
        type="button"
        class="rounded-xl border px-3 py-2.5 text-left transition-colors"
        :class="authActiveTab === 'other_failed' ? 'border-amber-300 bg-amber-50/70 dark:border-amber-900/50 dark:bg-amber-900/20' : 'border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900/40 hover:bg-secondary-50 dark:hover:bg-secondary-800/70'"
        @click="authActiveTab = 'other_failed'"
      >
        <p class="text-[11px] sm:text-xs font-medium" style="color: var(--text-muted)">
          Other Fails
        </p>
        <p class="mt-1 text-base font-bold text-amber-700 dark:text-amber-400 leading-none">
          {{ formatNumber(summary.counts?.other_failed) }}
        </p>
      </button>
    </div>

    <div class="rounded-xl overflow-hidden" style="border: 1px solid var(--surface-border)">
      <template v-if="loading">
        <div class="divide-y divide-secondary-200 dark:divide-secondary-700">
          <div v-for="i in 5" :key="`auth-skeleton-${i}`" class="px-4 py-3 flex items-center gap-3">
            <div class="app-skeleton h-10 w-10 rounded-full" />
            <div class="flex-1 space-y-1.5">
              <div class="app-skeleton h-3.5 w-36 rounded" />
              <div class="app-skeleton h-3 w-28 rounded" />
            </div>
          </div>
        </div>
      </template>

      <AppEmptyState
        v-else-if="activeAuthList.length === 0"
        :icon="ScanFace"
        title="No events in this tab"
        description="No authentication records matched the selected tab for today."
      />

      <ul v-else class="m-0 p-0 h-[340px] overflow-auto divide-y divide-secondary-200 dark:divide-secondary-700">
        <li
          v-for="event in activeAuthList"
          :key="`auth-${authActiveTab}-${event.id}`"
          class="px-4 py-3 flex items-center gap-3 hover:bg-secondary-50/70 dark:hover:bg-secondary-800/40 transition-colors"
        >
          <div class="h-10 w-10 rounded-full overflow-hidden shrink-0 bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center border border-secondary-200 dark:border-secondary-700">
            <button
              v-if="event.picture_url"
              type="button"
              class="group relative h-full w-full"
              @click="openImageViewer(event)"
            >
              <img
                :src="event.picture_url"
                alt="Captured snapshot"
                class="h-full w-full object-cover"
                loading="lazy"
              />
              <span class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-colors" />
            </button>
            <ScanFace v-else class="h-4 w-4 text-secondary-400" :stroke-width="1.8" />
          </div>

          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold truncate" style="color: var(--text-strong)">
              {{ event.member?.name || event.person_name || 'Unknown person' }}
            </p>
            <p class="text-xs truncate" style="color: var(--text-muted)">
              {{ formatDateTime(event.event_time) }}
            </p>
            <p class="text-[11px] truncate mt-0.5" style="color: var(--text-muted)">
              Method: {{ formatAuthMethod(event.auth_method) }}
            </p>
          </div>

          <span
            class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold shrink-0"
            :class="authStatusClass(event)"
          >
            {{ authStatusText(event) }}
          </span>
        </li>
      </ul>
    </div>
  </div>

  <div
    v-if="imageViewerOpen && imageViewerSrc"
    class="fixed inset-0 z-[80] bg-black/75 backdrop-blur-sm px-4 py-6 sm:px-8 sm:py-10"
    @click="closeImageViewer"
  >
    <div class="relative mx-auto flex h-full w-full max-w-5xl items-center justify-center" @click.stop>
      <button
        type="button"
        class="absolute right-0 top-0 -mt-2 inline-flex items-center gap-1 rounded-lg bg-white/90 px-2.5 py-1.5 text-xs font-medium text-secondary-700 hover:bg-white transition-colors"
        @click="closeImageViewer"
      >
        <X class="w-3.5 h-3.5" :stroke-width="2" />
        Close
      </button>

      <img
        :src="imageViewerSrc"
        :alt="imageViewerAlt"
        class="max-h-full max-w-full rounded-xl border border-white/20 shadow-2xl object-contain"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ScanFace, X } from 'lucide-vue-next';
import AppEmptyState from '../AppEmptyState.vue';
import { useDateTimeFormat } from '../../composables/useDateTimeFormat';

const props = defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
  summary: {
    type: Object,
    default: () => ({
      can_view: true,
      counts: {
        total: 0,
        success: 0,
        payment_expired: 0,
        other_failed: 0,
      },
      lists: {
        success_attempts: [],
        payment_expired: [],
        other_failed: [],
      },
    }),
  },
  selectedDate: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['date-change']);

const { formatDateTime } = useDateTimeFormat();

const authActiveTab = ref('success_attempts');
const imageViewerOpen = ref(false);
const imageViewerSrc = ref('');
const imageViewerAlt = ref('Authentication snapshot');

const numberFormatter = new Intl.NumberFormat();

function formatNumber(value) {
  return numberFormatter.format(Number(value || 0));
}

function formatAuthMethod(method) {
  const map = { face: 'Face', card: 'Card', fingerprint: 'Fingerprint', password: 'Password' };
  return map[method] || 'Authentication';
}

function authStatusText(event) {
  if (event?.result === 'success') return 'Success';
  if (event?.fail_reason === 'payment_expired') return 'Payment Expired';
  return 'Attempted';
}

function authStatusClass(event) {
  if (event?.result === 'success') {
    return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
  }

  if (event?.fail_reason === 'payment_expired') {
    return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
  }

  return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400';
}

const activeAuthList = computed(() => {
  if (authActiveTab.value === 'payment_expired') {
    return props.summary?.lists?.payment_expired || [];
  }

  if (authActiveTab.value === 'other_failed') {
    return props.summary?.lists?.other_failed || [];
  }

  return props.summary?.lists?.success_attempts || [];
});

function openImageViewer(event) {
  if (!event?.picture_url) return;
  imageViewerSrc.value = event.picture_url;
  imageViewerAlt.value = `${event.member?.name || event.person_name || 'Unknown person'} authentication snapshot`;
  imageViewerOpen.value = true;
}

function closeImageViewer() {
  imageViewerOpen.value = false;
  imageViewerSrc.value = '';
}

function onDateChange(event) {
  emit('date-change', event?.target?.value || '');
}

function toInputDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

const dateShortcuts = computed(() => {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 1);
  const weekStart = new Date(today);
  weekStart.setDate(weekStart.getDate() - 6);

  return [
    { id: 'today', label: 'Today', value: toInputDate(today) },
    { id: 'yesterday', label: 'Yesterday', value: toInputDate(yesterday) },
    { id: 'last7start', label: 'Last 7D Start', value: toInputDate(weekStart) },
  ];
});

function onShortcutDateChange(date) {
  emit('date-change', date);
}

function handleWindowKeydown(event) {
  if (event.key === 'Escape' && imageViewerOpen.value) {
    closeImageViewer();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleWindowKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleWindowKeydown);
});
</script>
