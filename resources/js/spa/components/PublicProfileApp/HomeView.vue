<template>
  <div class="space-y-5 pb-6">
    <!-- Member Greeting & Quick Status -->
    <div class="flex items-center justify-between pt-2 pb-1">
      <div class="min-w-0">
        <div class="flex items-center gap-1.5 mb-1">
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse" />
            {{ meta.member_role || 'Active Member' }}
          </span>
          <span v-if="meta.member_id" class="text-[11px] font-semibold text-gray-400 dark:text-gray-500">
            ID: {{ meta.member_id }}
          </span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight leading-tight">
          {{ greeting }}, {{ firstName }}
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
          Ready for your workout today?
        </p>
      </div>

      <router-link
        to="/profile"
        class="shrink-0 p-1 rounded-2xl bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 shadow-sm focus:outline-none active:scale-95 transition-transform"
        aria-label="Profile"
      >
        <MemberAvatar
          :src="meta.profile_photo_url"
          :initials="initials"
          size="md"
          shape="circle"
        />
      </router-link>
    </div>

    <!-- Outstanding Balance Warning (if balance > 0) -->
    <div
      v-if="outstandingSales.length"
      class="relative overflow-hidden rounded-3xl p-4 sm:p-5 bg-gradient-to-r from-red-600 via-red-500 to-rose-600 text-white shadow-xl shadow-red-500/20"
    >
      <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white/10 pointer-events-none" />
      <div class="relative flex items-center justify-between gap-4">
        <div class="min-w-0">
          <div class="flex items-center gap-1.5 mb-0.5">
            <AlertCircle class="w-4 h-4 text-white shrink-0" :stroke-width="2.2" />
            <p class="text-[11px] font-bold uppercase tracking-wider text-red-100">
              Payment Outstanding
            </p>
          </div>
          <p class="text-2xl sm:text-3xl font-extrabold tracking-tight">
            {{ String(meta.total_outstanding).replace(/^-/, '') }}
          </p>
          <p class="text-[11px] text-red-100 mt-0.5">
            {{ outstandingSales.length }} invoice{{ outstandingSales.length > 1 ? 's' : '' }} pending payment
          </p>
        </div>

        <button
          type="button"
          class="shrink-0 px-4 py-2.5 rounded-2xl bg-white text-red-600 font-bold text-xs shadow-md hover:bg-red-50 active:scale-95 transition-all cursor-pointer"
          @click="router.push('/transactions')"
        >
          View Dues &rarr;
        </button>
      </div>
    </div>

    <!-- Digital Gym Membership & Wallet Card -->
    <div
      v-if="meta.current_balance !== undefined"
      class="pp-wallet-card p-5 sm:p-6 cursor-pointer active:scale-[0.99] transition-transform select-none"
      @click="router.push('/wallet')"
    >
      <!-- Background Ambient Glow -->
      <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/10 pointer-events-none" />
      <div class="absolute -bottom-10 -left-10 w-36 h-36 rounded-full bg-black/10 pointer-events-none" />

      <!-- Card Top: Gym Name & Chip Icon -->
      <div class="relative flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <div
            v-if="tenantLogoUrl"
            class="w-7 h-7 rounded-lg bg-white/20 p-1 backdrop-blur-sm border border-white/20 flex items-center justify-center overflow-hidden"
          >
            <img :src="tenantLogoUrl" :alt="meta.tenant_name" class="w-full h-full object-contain" />
          </div>
          <div>
            <p class="text-xs font-black tracking-wider uppercase text-white/90">
              {{ meta.tenant_name || 'CXFIT' }}
            </p>
            <p class="text-[9px] font-semibold text-emerald-200 uppercase tracking-widest leading-none">
              Club Member Pass
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Wifi class="w-5 h-5 text-emerald-200 rotate-90" :stroke-width="1.8" />
          <CreditCard class="w-5 h-5 text-emerald-200" :stroke-width="1.8" />
        </div>
      </div>

      <!-- Card Middle: Balance Info -->
      <div class="relative py-1">
        <p class="text-[11px] font-bold text-emerald-200 uppercase tracking-wider mb-1">
          Available Wallet Balance
        </p>
        <div class="flex items-baseline gap-1.5">
          <p class="text-3xl sm:text-4xl font-black text-white tracking-tight leading-none">
            {{ formatMoney(meta.current_balance) }}
          </p>
        </div>
      </div>

      <!-- Card Bottom: Member Name, Member Code & Action -->
      <div class="relative mt-5 pt-3 border-t border-white/15 flex items-center justify-between text-xs">
        <div class="min-w-0">
          <p class="font-bold text-white tracking-tight uppercase truncate">
            {{ firstName }} {{ lastName }}
          </p>
          <p class="text-[10px] text-emerald-200 font-mono mt-0.5">
            {{ meta.member_code || meta.member_id || '#MEMBER' }}
          </p>
        </div>

        <div class="flex items-center gap-1 text-[11px] font-bold text-white bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-xl border border-white/20 backdrop-blur-sm transition-colors">
          <span>Manage Wallet</span>
          <ChevronRight class="w-3.5 h-3.5" :stroke-width="2.2" />
        </div>
      </div>
    </div>

    <!-- Active Workout Plan -->
    <section v-if="workoutsData.length">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center">
            <Zap class="w-3.5 h-3.5" :stroke-width="2.2" />
          </div>
          <h2 class="text-base font-extrabold text-gray-900 dark:text-white tracking-tight">
            Workout Program
          </h2>
        </div>
        <button
          v-if="workoutsData.length > 1"
          type="button"
          class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline transition-colors focus:outline-none"
          @click="router.push('/workout')"
        >
          View all ({{ workoutsData.length }}) &rarr;
        </button>
      </div>

      <button
        type="button"
        class="w-full text-left rounded-3xl overflow-hidden focus:outline-none active:scale-[0.99] transition-transform select-none group"
        @click="$emit('open-workout', workoutsData[0])"
      >
        <div class="pp-membership-card p-5 sm:p-6">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-500 text-white mb-2 shadow-sm">
                <Flame class="w-3 h-3" :stroke-width="2.5" />
                Active Routine
              </div>
              <h3 class="text-xl sm:text-2xl font-black text-white leading-snug">
                {{ workoutsData[0].title }}
              </h3>
              <p v-if="workoutsData[0].creator_name" class="text-xs text-zinc-400 mt-1 font-medium">
                Trainer: {{ workoutsData[0].creator_name }}
              </p>
            </div>

            <div class="w-11 h-11 rounded-2xl bg-red-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-red-500/30 group-hover:scale-105 transition-transform">
              <Dumbbell class="w-5 h-5" :stroke-width="2" />
            </div>
          </div>

          <!-- Quick Routine Stats -->
          <div class="grid grid-cols-3 gap-2 mt-5 pt-4 border-t border-zinc-800 text-center">
            <div class="bg-white/5 rounded-2xl p-2 border border-white/5">
              <p class="text-[10px] uppercase tracking-wider text-zinc-400 font-bold">
                Duration
              </p>
              <p class="text-sm font-black text-white mt-0.5">
                {{ workoutsData[0].duration_weeks || '-' }} wks
              </p>
            </div>
            <div class="bg-white/5 rounded-2xl p-2 border border-white/5">
              <p class="text-[10px] uppercase tracking-wider text-zinc-400 font-bold">
                Routines
              </p>
              <p class="text-sm font-black text-white mt-0.5">
                {{ workoutsData[0].days?.length || '-' }} days
              </p>
            </div>
            <div class="bg-white/5 rounded-2xl p-2 border border-white/5">
              <p class="text-[10px] uppercase tracking-wider text-zinc-400 font-bold">
                Started
              </p>
              <p class="text-xs font-bold text-white mt-0.5 truncate">
                {{ workoutsData[0].effective_date || '-' }}
              </p>
            </div>
          </div>

          <!-- Bottom Action -->
          <div class="flex items-center justify-between mt-4 text-xs font-bold text-red-400 group-hover:text-red-300 transition-colors">
            <span>Tap to open full workout routine</span>
            <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" :stroke-width="2.5" />
          </div>
        </div>
      </button>
    </section>

    <!-- Upcoming Events Section -->
    <section v-if="eventsLoading || upcomingEvents.length">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center">
            <Calendar class="w-3.5 h-3.5" :stroke-width="2.2" />
          </div>
          <h2 class="text-base font-extrabold text-gray-900 dark:text-white tracking-tight">
            Upcoming Events
          </h2>
        </div>
        <router-link
          v-if="upcomingEvents.length > 2"
          to="/notifications"
          class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline transition-colors"
        >
          See all &rarr;
        </router-link>
      </div>

      <!-- Skeleton loader -->
      <div v-if="eventsLoading" class="space-y-3">
        <div v-for="i in 2" :key="i" class="pp-glass-card rounded-3xl p-4 animate-pulse">
          <div class="flex gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gray-200 dark:bg-zinc-800 shrink-0" />
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-200 dark:bg-zinc-800 rounded-full w-3/4" />
              <div class="h-3 bg-gray-200 dark:bg-zinc-800 rounded-full w-1/2" />
            </div>
          </div>
        </div>
      </div>

      <!-- Event Cards -->
      <div v-else class="space-y-3">
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

            <!-- Price & Arrow -->
            <div class="flex flex-col items-end shrink-0 gap-1">
              <span
                v-if="event.ticket_fee > 0"
                class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-zinc-800 text-gray-900 dark:text-white"
              >${{ Number(event.ticket_fee).toFixed(2) }}</span>
              <span
                v-else
                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
              >FREE</span>
              <ChevronRight class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform" :stroke-width="2" />
            </div>
          </div>
        </router-link>
      </div>
    </section>

    <!-- Recent Payments Section -->
    <section v-if="salesData.length">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
            <Receipt class="w-3.5 h-3.5" :stroke-width="2.2" />
          </div>
          <h2 class="text-base font-extrabold text-gray-900 dark:text-white tracking-tight">
            Recent Payments
          </h2>
        </div>
        <button
          type="button"
          class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline transition-colors focus:outline-none"
          @click="router.push('/transactions')"
        >
          View all ({{ salesData.length }}) &rarr;
        </button>
      </div>

      <div class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <button
          v-for="(sale, i) in salesData.slice(0, 3)"
          :key="i"
          type="button"
          class="w-full flex items-center gap-3.5 px-4 sm:px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-zinc-800/40 active:bg-gray-100 dark:active:bg-zinc-800 transition-colors focus:outline-none text-left cursor-pointer"
          @click="$emit('open-sale', sale)"
        >
          <div
            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0"
            :class="!sale.is_paid
              ? 'bg-red-50 dark:bg-red-950/40 text-red-500'
              : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500'"
          >
            <FileText class="w-5 h-5" :stroke-width="1.8" />
          </div>

          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
              Invoice #{{ sale.id }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
              {{ sale.created_at }} &middot; {{ sale.items?.length || 0 }} item{{ sale.items?.length === 1 ? '' : 's' }}
            </p>
          </div>

          <div class="text-right shrink-0">
            <p class="text-sm font-black text-gray-900 dark:text-white">
              {{ sale.total_amount }}
            </p>
            <span
              v-if="!sale.is_paid"
              class="inline-block text-[10px] font-extrabold text-red-600 bg-red-50 dark:bg-red-950/60 dark:text-red-400 px-2 py-0.5 rounded-full mt-0.5"
            >Unpaid</span>
            <span
              v-else
              class="inline-block text-[10px] font-extrabold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-400 px-2 py-0.5 rounded-full mt-0.5"
            >Paid</span>
          </div>
        </button>
      </div>
    </section>

    <!-- Empty State if no data at all -->
    <div
      v-if="!workoutsData.length && !salesData.length && !upcomingEvents.length"
      class="pp-glass-card rounded-3xl p-10 flex flex-col items-center justify-center text-center gap-3 text-gray-400"
    >
      <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-zinc-800 flex items-center justify-center text-gray-400">
        <Inbox class="w-8 h-8" :stroke-width="1.5" />
      </div>
      <div>
        <p class="text-base font-bold text-gray-800 dark:text-gray-200">
          Welcome to your member portal
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 max-w-xs">
          Your active workouts, wallet balance, and invoices will appear right here.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import MemberAvatar from '../../../components/ui/MemberAvatar.vue';
import {
    ChevronRight,
    Zap,
    Calendar,
    FileText,
    Inbox,
    AlertCircle,
    CreditCard,
    Receipt,
    Flame,
    Dumbbell,
    Wifi,
} from 'lucide-vue-next';

const props = defineProps({
    meta:          { type: Object,  default: () => ({}) },
    greeting:      { type: String,  default: '' },
    firstName:     { type: String,  default: '' },
    lastName:      { type: String,  default: '' },
    initials:      { type: String,  default: '' },
    workoutsData:  { type: Array,   default: () => [] },
    salesData:     { type: Array,   default: () => [] },
    tenantLogoUrl: { type: String,  default: null },
});

defineEmits(['open-workout', 'open-sale']);

const router = useRouter();

const outstandingSales = computed(() => props.salesData.filter(s => !s.is_paid));

// ── Upcoming events ───────────────────────────────────────
const upcomingEvents = ref([]);
const eventsLoading  = ref(true);

onMounted(async () => {
    try {
        const res = await fetch('/api/public/upcoming-events?per_page=3');
        if (res.ok) {
            const data = await res.json();
            upcomingEvents.value = data.data ?? [];
        }
    } catch {
        // silent
    }
    eventsLoading.value = false;
});

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

function formatMoney(val) {
    const n = parseFloat(val ?? 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
