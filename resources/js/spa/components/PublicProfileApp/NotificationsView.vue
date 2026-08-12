<template>
  <div class="space-y-4 pb-6">
    <!-- Header -->
    <div class="pt-2 pb-1">
      <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
        Messages &amp; Alerts
      </h1>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
        Club announcements, reminders &amp; upcoming events
      </p>
    </div>

    <!-- Segmented Tab Switch -->
    <div class="flex gap-1.5 p-1 bg-gray-100 dark:bg-zinc-800/80 rounded-2xl">
      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
        :class="activeTab === 'messages'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeTab = 'messages'"
      >
        <Bell class="w-3.5 h-3.5" :stroke-width="2" />
        <span>Messages</span>
        <span
          v-if="items.length"
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-gray-300"
        >
          {{ items.length }}
        </span>
      </button>

      <button
        type="button"
        class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer"
        :class="activeTab === 'events'
          ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
          : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
        @click="activeTab = 'events'"
      >
        <Calendar class="w-3.5 h-3.5" :stroke-width="2" />
        <span>Events</span>
        <span
          v-if="upcomingEvents.length"
          class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-emerald-500 text-white"
        >
          {{ upcomingEvents.length }}
        </span>
      </button>
    </div>

    <!-- ── Messages Tab ───────────────────────────────── -->
    <div v-if="activeTab === 'messages'" class="space-y-3">
      <!-- Loading Skeleton -->
      <div v-if="loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="pp-glass-card rounded-3xl p-5 animate-pulse">
          <div class="flex gap-3.5">
            <div class="w-10 h-10 rounded-2xl bg-gray-200 dark:bg-zinc-800 shrink-0" />
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-200 dark:bg-zinc-800 rounded w-2/5" />
              <div class="h-3 bg-gray-200 dark:bg-zinc-800 rounded w-full" />
              <div class="h-3 bg-gray-200 dark:bg-zinc-800 rounded w-4/5" />
            </div>
          </div>
        </div>
      </div>

      <!-- Messages List -->
      <div v-else-if="items.length" class="space-y-3">
        <div
          v-for="item in items"
          :key="item.id"
          class="pp-glass-card rounded-3xl p-4 sm:p-5 shadow-sm"
        >
          <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-2xl bg-gray-900 dark:bg-zinc-800 text-white dark:text-gray-200 flex items-center justify-center shrink-0 shadow-sm mt-0.5">
              <Bell class="w-4 h-4" :stroke-width="2" />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-baseline justify-between gap-2">
                <p class="text-sm font-extrabold text-gray-900 dark:text-white leading-snug">
                  {{ item.title }}
                </p>
                <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 shrink-0">
                  {{ formatDate(item.sent_at) }}
                </span>
              </div>

              <p class="text-xs text-gray-600 dark:text-gray-300 mt-1.5 leading-relaxed whitespace-pre-line">
                {{ item.message }}
              </p>
            </div>
          </div>
        </div>

        <!-- Infinite scroll sentinel -->
        <div ref="sentinel" class="h-2" />

        <div v-if="loadingMore" class="py-4 flex justify-center">
          <div class="w-5 h-5 border-2 border-red-500 border-t-transparent rounded-full animate-spin" />
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="pp-glass-card rounded-3xl p-12 flex flex-col items-center justify-center text-center gap-2.5 text-gray-400"
      >
        <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-400 flex items-center justify-center">
          <Bell class="w-7 h-7" :stroke-width="1.5" />
        </div>
        <p class="text-base font-bold text-gray-800 dark:text-gray-200">
          No Messages Yet
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs">
          Club notifications and SMS messages will be saved here.
        </p>
      </div>
    </div>

    <!-- ── Events Tab ─────────────────────────────────── -->
    <div v-if="activeTab === 'events'" class="space-y-3">
      <!-- Loading Skeleton -->
      <div v-if="eventsLoading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="pp-glass-card rounded-3xl p-4 animate-pulse">
          <div class="flex gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gray-200 dark:bg-zinc-800 shrink-0" />
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-200 dark:bg-zinc-800 rounded w-3/4" />
              <div class="h-3 bg-gray-200 dark:bg-zinc-800 rounded w-1/2" />
            </div>
          </div>
        </div>
      </div>

      <!-- Events List -->
      <div v-else-if="upcomingEvents.length" class="space-y-3">
        <router-link
          v-for="event in upcomingEvents"
          :key="event.id"
          :to="`/event/${event.slug}`"
          class="block pp-glass-card rounded-3xl p-4 hover:shadow-md active:scale-[0.99] transition-all group focus:outline-none"
        >
          <div class="flex items-center gap-3.5">
            <!-- Date Tile -->
            <div class="w-12 h-12 rounded-2xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 flex flex-col items-center justify-center shrink-0 shadow-sm">
              <span class="text-[9px] font-extrabold uppercase tracking-wider leading-none opacity-80">
                {{ getEventMonth(event.start_datetime) }}
              </span>
              <span class="text-lg font-black leading-tight">
                {{ getEventDay(event.start_datetime) }}
              </span>
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-red-500 transition-colors">
                {{ event.name }}
              </p>
              <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                <span class="truncate">{{ formatEventTime(event.start_datetime) }}</span>
                <span v-if="event.venue" class="truncate">&middot; {{ event.venue }}</span>
              </div>
            </div>

            <!-- Price & Action -->
            <div class="flex flex-col items-end shrink-0 gap-1">
              <span
                v-if="event.ticket_fee > 0"
                class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-zinc-800 text-gray-900 dark:text-white"
              >${{ Number(event.ticket_fee).toFixed(2) }}</span>
              <span
                v-else
                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
              >FREE</span>
              <ChevronRight class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform" :stroke-width="2" />
            </div>
          </div>
        </router-link>

        <!-- Load more events -->
        <button
          v-if="eventsCurrentPage < eventsLastPage"
          type="button"
          :disabled="loadingMoreEvents"
          class="w-full py-3 rounded-2xl text-xs font-bold bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 transition-all disabled:opacity-50 cursor-pointer shadow-md"
          @click="loadMoreEvents"
        >
          <span v-if="loadingMoreEvents" class="flex items-center justify-center gap-2">
            <span class="w-3.5 h-3.5 border-2 border-white dark:border-gray-900 border-t-transparent rounded-full animate-spin" />
            Loading more events&hellip;
          </span>
          <span v-else>
            Load more events
          </span>
        </button>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="pp-glass-card rounded-3xl p-12 flex flex-col items-center justify-center text-center gap-2.5 text-gray-400"
      >
        <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-400 flex items-center justify-center">
          <Calendar class="w-7 h-7" :stroke-width="1.5" />
        </div>
        <p class="text-base font-bold text-gray-800 dark:text-gray-200">
          No Upcoming Events
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs">
          New fitness workshops, competitions, and seminars will appear here.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Calendar, ChevronRight, Bell } from 'lucide-vue-next';

defineProps({
    meta: { type: Object, default: () => ({}) },
});

const activeTab = ref('messages'); // 'messages' | 'events'

// ── Notifications ─────────────────────────────────────────
const items       = ref([]);
const loading     = ref(true);
const loadingMore = ref(false);
const currentPage = ref(1);
const lastPage    = ref(1);
const sentinel    = ref(null);

let observer = null;

const MEMBER_ID_KEY = 'public_profile_member_id';

function ppHeaders() {
    const token = localStorage.getItem(MEMBER_ID_KEY);
    return token ? { 'X-PP-Token': token } : {};
}

async function fetchPage(page) {
    try {
        const res = await fetch(`/api/public/notifications?page=${page}&per_page=15`, {
            headers: ppHeaders(),
        });
        if (!res.ok) return null;
        return res.json();
    } catch {
        return null;
    }
}

async function loadMore() {
    if (loadingMore.value || currentPage.value >= lastPage.value) return;
    loadingMore.value = true;
    currentPage.value++;
    const data = await fetchPage(currentPage.value);
    if (data) {
        items.value.push(...data.data);
        lastPage.value = data.meta.last_page;
    }
    loadingMore.value = false;
}

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
    });
}

// ── Upcoming Events ───────────────────────────────────────
const upcomingEvents    = ref([]);
const eventsLoading     = ref(true);
const loadingMoreEvents = ref(false);
const eventsCurrentPage = ref(1);
const eventsLastPage    = ref(1);

async function fetchEvents(page) {
    try {
        const res = await fetch(`/api/public/upcoming-events?page=${page}&per_page=5`);
        if (!res.ok) return null;
        return res.json();
    } catch {
        return null;
    }
}

async function loadMoreEvents() {
    if (loadingMoreEvents.value || eventsCurrentPage.value >= eventsLastPage.value) return;
    loadingMoreEvents.value = true;
    eventsCurrentPage.value++;
    const data = await fetchEvents(eventsCurrentPage.value);
    if (data) {
        upcomingEvents.value.push(...data.data);
        eventsLastPage.value = data.meta.last_page;
    }
    loadingMoreEvents.value = false;
}

function getEventMonth(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short' });
}

function getEventDay(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { day: 'numeric' });
}

function formatEventTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
    const [notifData, eventsData] = await Promise.all([
        fetchPage(1),
        fetchEvents(1),
    ]);

    if (notifData) {
        items.value    = notifData.data || [];
        lastPage.value = notifData.meta?.last_page || 1;
    }
    loading.value = false;

    if (eventsData) {
        upcomingEvents.value = eventsData.data || [];
        eventsLastPage.value = eventsData.meta?.last_page || 1;
    }
    eventsLoading.value = false;

    await nextTick();
    if (sentinel.value) {
        observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) loadMore();
        }, { threshold: 0.1 });
        observer.observe(sentinel.value);
    }
});

onBeforeUnmount(() => {
    observer?.disconnect();
});
</script>
