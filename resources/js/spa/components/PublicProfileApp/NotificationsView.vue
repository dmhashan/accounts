<template>
  <div>
    <!-- Header -->
    <div class="pt-12 pb-6">
      <h1 class="text-2xl font-bold text-gray-900 leading-tight tracking-tight">
        Notifications
      </h1>
      <p class="text-xs text-gray-400 mt-0.5">
        Messages from {{ tenantName }}
      </p>
    </div>

    <!-- Loading skeleton (initial load) -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="bg-white rounded-3xl p-5 animate-pulse">
        <div class="flex gap-3">
          <div class="w-9 h-9 rounded-2xl bg-gray-100 flex-shrink-0" />
          <div class="flex-1">
            <div class="h-3 bg-gray-100 rounded-full w-2/5 mb-3" />
            <div class="h-3 bg-gray-100 rounded-full w-full mb-2" />
            <div class="h-3 bg-gray-100 rounded-full w-4/5" />
          </div>
        </div>
      </div>
    </div>

    <!-- ── Upcoming Events ──────────────────────────────── -->
    <section class="mb-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-bold text-gray-900">
          Upcoming Events
        </h2>
      </div>

      <!-- Skeleton -->
      <div v-if="eventsLoading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="bg-white rounded-3xl p-5 animate-pulse">
          <div class="flex gap-4">
            <div class="w-10 h-10 rounded-2xl bg-gray-100 flex-shrink-0" />
            <div class="flex-1">
              <div class="h-3 bg-gray-100 rounded-full w-3/5 mb-3" />
              <div class="h-3 bg-gray-100 rounded-full w-2/5" />
            </div>
          </div>
        </div>
      </div>

      <!-- Event cards -->
      <div v-else-if="upcomingEvents.length" class="space-y-3">
        <router-link
          v-for="event in upcomingEvents"
          :key="event.id"
          :to="`/event/${event.slug}`"
          class="block bg-white rounded-3xl px-5 py-4 shadow-sm border border-gray-100 hover:bg-gray-50 active:scale-[0.99] transition-all"
        >
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-2xl bg-gray-900 flex items-center justify-center flex-shrink-0 mt-0.5">
              <Calendar class="w-5 h-5 text-white" :stroke-width="1.8" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-900 leading-snug truncate">
                {{ event.name }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                {{ formatEventDate(event.start_datetime) }}
              </p>
              <p v-if="event.venue" class="text-xs text-gray-400 mt-0.5 truncate">
                {{ event.venue }}
              </p>
            </div>
            <div class="flex flex-col items-end flex-shrink-0 gap-1 ml-2">
              <span v-if="event.ticket_fee > 0" class="text-sm font-bold text-gray-900">${{ event.ticket_fee.toFixed(2) }}</span>
              <span v-else class="text-xs font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Free</span>
              <ChevronRight class="w-4 h-4 text-gray-300" :stroke-width="2" />
            </div>
          </div>
        </router-link>

        <!-- Load more events button -->
        <button
          v-if="eventsCurrentPage < eventsLastPage"
          type="button"
          class="w-full py-3 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-2xl hover:bg-gray-50 active:bg-gray-100 transition-colors"
          :disabled="loadingMoreEvents"
          @click="loadMoreEvents"
        >
          <span v-if="loadingMoreEvents" class="flex items-center justify-center gap-2">
            <span class="w-4 h-4 border-2 border-gray-300 border-t-gray-700 rounded-full animate-spin" />
            Loading…
          </span>
          <span v-else>Load more events</span>
        </button>
      </div>

      <!-- No upcoming events -->
      <div v-else class="flex flex-col items-center justify-center py-10 gap-3">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
          <Calendar class="w-7 h-7 text-gray-300" :stroke-width="1.5" />
        </div>
        <p class="text-sm text-gray-400">
          No upcoming events
        </p>
      </div>
    </section>

    <!-- ── Notifications ───────────────────────────────── -->
    <section class="mb-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-bold text-gray-900">
          Messages
        </h2>
      </div>

      <!-- Notification list -->
      <template v-if="!loading">
        <div v-if="items.length" class="space-y-3 mb-6">
          <div
            v-for="item in items"
            :key="item.id"
            class="bg-white rounded-3xl px-5 py-4 shadow-sm border border-gray-100"
          >
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-2xl bg-gray-900 flex items-center justify-center flex-shrink-0 mt-0.5">
                <Bell class="w-4 h-4 text-white" :stroke-width="1.8" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-900 leading-snug">
                  {{ item.title }}
                </p>
                <p class="text-sm text-gray-600 mt-1 leading-relaxed whitespace-pre-line">
                  {{ item.message }}
                </p>
                <p class="text-xs text-gray-400 mt-2">
                  {{ formatDate(item.sent_at) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Loading more spinner -->
          <div v-if="loadingMore" class="py-4 flex justify-center">
            <div class="w-5 h-5 border-2 border-gray-200 border-t-gray-800 rounded-full animate-spin" />
          </div>

          <!-- All loaded indicator -->
          <p v-else-if="currentPage >= lastPage && items.length > 0" class="text-center text-xs text-gray-300 py-3">
            All notifications loaded
          </p>
        </div>

        <!-- Empty state for notifications -->
        <div v-else class="flex flex-col items-center justify-center py-12 gap-3 mb-6">
          <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
            <Bell class="w-8 h-8 text-gray-300" :stroke-width="1.5" />
          </div>
          <p class="text-sm text-gray-400">
            No notifications yet
          </p>
        </div>
      </template>
    </section>

    <!-- Infinite scroll sentinel -->
    <div ref="sentinel" class="h-1" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Calendar, ChevronRight, Bell } from 'lucide-vue-next';

const props = defineProps({
    meta: { type: Object, default: () => ({}) },
});

const tenantName  = computed(() => props.meta?.tenant_name ?? '');

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

function formatEventDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, {
        weekday: 'short', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
    // Load notifications and events in parallel
    const [notifData, eventsData] = await Promise.all([
        fetchPage(1),
        fetchEvents(1),
    ]);

    if (notifData) {
        items.value    = notifData.data;
        lastPage.value = notifData.meta.last_page;
    }
    loading.value = false;

    if (eventsData) {
        upcomingEvents.value = eventsData.data;
        eventsLastPage.value = eventsData.meta.last_page;
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

