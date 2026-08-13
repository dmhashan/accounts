<template>
  <div class="member-portal-shell app-shell-viewport flex flex-col selection:bg-red-500 selection:text-white">
    <LoadingScreen v-if="screen === 'loading'" />

    <IdentifyScreen
      v-else-if="screen === 'identify'"
      v-model="phone"
      :tenant-name="tenantName"
      :tenant-logo-url="tenantLogoUrl"
      :error="error"
      :is-loading="isLoading"
      @submit="requestOtp"
    />

    <OtpScreen
      v-else-if="screen === 'otp'"
      v-model="otpCode"
      :phone="phone"
      :error="error"
      :is-loading="isLoading"
      @submit="verifyOtp"
      @back="backToIdentify"
    />

    <!-- ── Main App Shell (Authenticated / Public Event) ── -->
    <template v-else>
      <!-- Top Sticky App Bar -->
      <header class="member-portal-header px-4 sm:px-6 py-2.5">
        <div class="max-w-lg mx-auto flex items-center justify-between gap-3">
          <!-- Left: Brand or Back Button -->
          <div class="flex items-center gap-2.5 min-w-0">
            <button
              v-if="showBackButton"
              type="button"
              class="p-2 -ml-1.5 rounded-2xl bg-black/5 hover:bg-black/10 dark:bg-white/10 dark:hover:bg-white/15 text-gray-700 dark:text-gray-200 transition-colors focus:outline-none"
              aria-label="Go back"
              @click="handleBack"
            >
              <ArrowLeft class="w-5 h-5" :stroke-width="2" />
            </button>

            <router-link
              to="/"
              class="flex items-center gap-2.5 min-w-0 group focus:outline-none"
            >
              <div
                v-if="tenantLogoUrl"
                class="w-8 h-8 rounded-xl bg-white dark:bg-zinc-800 p-1 border border-gray-200/70 dark:border-white/10 shadow-sm flex items-center justify-center shrink-0 overflow-hidden"
              >
                <img
                  :src="tenantLogoUrl"
                  :alt="tenantName"
                  class="w-full h-full object-contain"
                />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-extrabold text-gray-900 dark:text-white truncate tracking-tight group-hover:text-red-500 transition-colors">
                  {{ tenantName || 'Member Portal' }}
                </p>
                <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider leading-none">
                  {{ currentSectionTitle }}
                </p>
              </div>
            </router-link>
          </div>

          <!-- Right: Notifications Bell & Avatar -->
          <div class="flex items-center gap-2 shrink-0">
            <router-link
              to="/notifications"
              class="relative p-2 rounded-2xl transition-all focus:outline-none active:scale-95"
              :class="route.path === '/notifications'
                ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900 shadow-sm'
                : 'bg-black/5 hover:bg-black/10 dark:bg-white/10 dark:hover:bg-white/15 text-gray-600 dark:text-gray-300'"
              aria-label="Notifications"
            >
              <Bell class="w-4 h-4" :stroke-width="2" />
              <!-- Unread badge/dot -->
              <span
                v-if="route.path !== '/notifications'"
                class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-zinc-900"
              />
            </router-link>

            <router-link
              v-if="currentToken"
              to="/profile"
              class="focus:outline-none active:scale-95 transition-transform"
              aria-label="My Profile"
            >
              <MemberAvatar
                :src="meta.profile_photo_url"
                :initials="initials"
                size="sm"
                shape="circle"
                class="ring-2 ring-gray-200 dark:ring-zinc-700 shadow-sm"
              />
            </router-link>
          </div>
        </div>
      </header>

      <!-- ── Scrollable Body ────────────────────────────── -->
      <main class="member-portal-main flex-1 overflow-y-auto pt-3 pb-8">
        <div class="max-w-lg mx-auto px-4 sm:px-5">
          <router-view v-slot="{ Component }">
            <transition name="fade-page" mode="out-in">
              <component
                :is="Component"
                :meta="meta"
                :greeting="greeting"
                :first-name="firstName"
                :last-name="lastName"
                :initials="initials"
                :workouts-data="workoutsData"
                :sales-data="salesData"
                :payments-data="paymentsData"
                :membership-payments="membershipPaymentsData"
                :other-payments="otherPaymentsData"
                :wallet-transactions="walletTransactions"
                :wallet-tx-meta="walletTxMeta"
                :tenant-logo-url="tenantLogoUrl"
                @open-workout="openWorkout"
                @open-sale="openSale"
                @open-payment="openPayment"
                @logout="logout"
              />
            </transition>
          </router-view>
        </div>
      </main>

      <!-- Bottom Navigation Dock -->
      <BottomNavBar />

      <!-- ── Workout Preview Modal / Bottom Sheet ───────── -->
      <Teleport to="body">
        <div
          v-if="activeWorkout"
          class="fixed inset-0 z-50 flex items-end sm:items-center justify-center overflow-y-auto sm:p-4"
        >
          <!-- Backdrop -->
          <div
            class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
            @click="closeWorkout"
          />

          <!-- Dialog Sheet -->
          <div class="relative w-full max-w-3xl bg-white dark:bg-zinc-900 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl max-h-[90vh] flex flex-col z-10 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-zinc-800 shrink-0">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse" />
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                  {{ activeWorkout.title || 'Workout Routine' }}
                </h3>
              </div>
              <div class="flex items-center gap-2">
                <a
                  v-if="activeWorkout.file_url"
                  :href="activeWorkout.file_url"
                  target="_blank"
                  download
                  class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors"
                >
                  <Download class="w-3.5 h-3.5" />
                  <span>Download</span>
                </a>
                <button
                  type="button"
                  class="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors focus:outline-none"
                  aria-label="Close"
                  @click="closeWorkout"
                >
                  <X class="w-4 h-4" :stroke-width="2.2" />
                </button>
              </div>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1">
              <!-- Type 1: Uploaded PDF -->
              <div v-if="activeWorkout.type === 'file' && (activeWorkout.mime_type === 'application/pdf' || activeWorkout.file_name?.endsWith('.pdf'))" class="space-y-4">
                <div v-if="activeWorkout.notes" class="p-4 rounded-2xl bg-gray-50 dark:bg-zinc-800/40 text-sm text-gray-700 dark:text-gray-300">
                  <span class="font-bold">Notes: </span>
                  <span>{{ activeWorkout.notes }}</span>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-zinc-800 overflow-hidden bg-zinc-950 aspect-[4/3] max-h-[500px]">
                  <iframe
                    v-if="activeWorkout.file_url"
                    :src="activeWorkout.file_url"
                    class="w-full h-full border-0"
                    title="Workout PDF Viewer"
                  />
                </div>
              </div>

              <!-- Type 2: Uploaded Image -->
              <div v-else-if="activeWorkout.type === 'file'" class="space-y-4 text-center">
                <div v-if="activeWorkout.notes" class="p-4 rounded-2xl bg-gray-50 dark:bg-zinc-800/40 text-sm text-gray-700 dark:text-gray-300 text-left">
                  <span class="font-bold">Notes: </span>
                  <span>{{ activeWorkout.notes }}</span>
                </div>
                <div class="rounded-2xl border border-gray-200 dark:border-zinc-800 overflow-hidden bg-gray-50 dark:bg-zinc-800/40 p-2 inline-block max-w-full">
                  <img
                    :src="activeWorkout.file_url"
                    :alt="activeWorkout.title"
                    class="max-h-[600px] w-auto rounded-xl object-contain mx-auto"
                  />
                </div>
              </div>

              <!-- Type 3: Formatted Rich Text -->
              <div v-else-if="activeWorkout.type === 'text'" class="space-y-4">
                <div v-if="activeWorkout.notes" class="p-4 rounded-2xl bg-gray-50 dark:bg-zinc-800/40 text-sm text-gray-700 dark:text-gray-300">
                  <span class="font-bold">Notes: </span>
                  <span>{{ activeWorkout.notes }}</span>
                </div>
                <div class="p-6 rounded-2xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-800/60 shadow-sm">
                  <!-- eslint-disable-next-line vue/no-v-html -->
                  <div class="app-rich-editor-content text-sm text-gray-900 dark:text-white" v-html="activeWorkout.formatted_text" />
                </div>
              </div>

              <!-- Type 4: Configured Program -->
              <WorkoutProgramPreviewCard v-else :program="activeWorkout" />
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ── Sale Invoice Preview Modal / Bottom Sheet ──── -->
      <Teleport to="body">
        <div
          v-if="activeSale"
          class="fixed inset-0 z-50 flex items-end sm:items-center justify-center overflow-y-auto sm:p-4"
        >
          <!-- Backdrop -->
          <div
            class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
            @click="closeSale"
          />

          <!-- Dialog Sheet -->
          <div class="relative w-full max-w-2xl bg-white dark:bg-zinc-900 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl max-h-[90vh] flex flex-col z-10 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-zinc-800 shrink-0">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500" />
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                  Invoice Details
                </h3>
              </div>
              <button
                type="button"
                class="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors focus:outline-none"
                aria-label="Close"
                @click="closeSale"
              >
                <X class="w-4 h-4" :stroke-width="2.2" />
              </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1">
              <SaleInvoicePreviewCard :sale="activeSale" />
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ── Payment Receipt Preview Modal / Bottom Sheet ─── -->
      <Teleport to="body">
        <div
          v-if="activePayment"
          class="fixed inset-0 z-50 flex items-end sm:items-center justify-center overflow-y-auto sm:p-4"
        >
          <!-- Backdrop -->
          <div
            class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
            @click="closePayment"
          />

          <!-- Dialog Sheet -->
          <div class="relative w-full max-w-2xl bg-white dark:bg-zinc-900 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl max-h-[90vh] flex flex-col z-10 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-zinc-800 shrink-0">
              <div class="flex items-center gap-2">
                <span
                  class="w-2.5 h-2.5 rounded-full"
                  :class="activePayment.is_paid ? 'bg-emerald-500' : 'bg-red-500 animate-pulse'"
                />
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                  Payment Receipt
                </h3>
              </div>
              <button
                type="button"
                class="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors focus:outline-none"
                aria-label="Close"
                @click="closePayment"
              >
                <X class="w-4 h-4" :stroke-width="2.2" />
              </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1">
              <PaymentReceiptPreviewCard :payment="activePayment" />
            </div>
          </div>
        </div>
      </Teleport>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { X, ArrowLeft, Bell, Download } from 'lucide-vue-next';

import LoadingScreen    from '../components/PublicProfileApp/LoadingScreen.vue';
import IdentifyScreen   from '../components/PublicProfileApp/IdentifyScreen.vue';
import OtpScreen        from '../components/PublicProfileApp/OtpScreen.vue';
import BottomNavBar     from '../components/PublicProfileApp/BottomNavBar.vue';
import MemberAvatar     from '../../components/ui/MemberAvatar.vue';

import WorkoutProgramPreviewCard from '../components/WorkoutProgramPreviewCard.vue';
import SaleInvoicePreviewCard    from '../components/SaleInvoicePreviewCard.vue';
import PaymentReceiptPreviewCard from '../components/PaymentReceiptPreviewCard.vue';

const MEMBER_ID_KEY  = 'public_profile_member_id';
const SESSION_ID_KEY = 'pp_session_id';

// ── Activity tracking ──────────────────────────────────────
function getSessionId() {
    let sid = sessionStorage.getItem(SESSION_ID_KEY);
    if (!sid) {
        sid = (typeof crypto !== 'undefined' && crypto.randomUUID)
            ? crypto.randomUUID()
            : Math.random().toString(36).slice(2) + Date.now().toString(36);
        sessionStorage.setItem(SESSION_ID_KEY, sid);
    }
    return sid;
}

function track(eventType, extra = {}) {
    const payload = {
        session_id:    getSessionId(),
        event_type:    eventType,
        screen_width:  window.screen?.width  || null,
        screen_height: window.screen?.height || null,
        ...extra,
    };
    const headers = {
        'Content-Type':     'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN':     getCsrfToken(),
    };
    if (currentToken.value) headers['X-PP-Token'] = currentToken.value;
    // Fire-and-forget
    fetch('/api/public/activity', {
        method:  'POST',
        headers,
        body: JSON.stringify(payload),
    }).catch(() => { /* silent */ });
}

// ── Auth state ─────────────────────────────────────────────
const screen        = ref('loading'); // 'loading' | 'identify' | 'otp' | 'profile'
const phone         = ref('');
const otpCode       = ref('');
const error         = ref('');
const isLoading     = ref(false);
const currentToken  = ref(null);

// ── Profile data ───────────────────────────────────────────
const workoutsData           = ref([]);
const salesData              = ref([]);
const paymentsData           = ref([]);
const membershipPaymentsData = ref([]);
const otherPaymentsData      = ref([]);
const walletTransactions     = ref([]);
const walletTxMeta           = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
const meta                   = ref({});

const tenantName    = computed(() => window.__tenantName || '');
const tenantLogoUrl = computed(() => window.__tenantLogoUrl || null);

// ── Nav state ──────────────────────────────────────────────
const router        = useRouter();
const route         = useRoute();
const activeWorkout = ref(null);
const activeSale    = ref(null);
const activePayment = ref(null);

// Event pages are publicly accessible — no OTP required
const isEventPage = computed(() => route.path.startsWith('/event/'));

const showBackButton = computed(() => {
    return route.path !== '/' && (route.path.startsWith('/event/') || route.path === '/notifications');
});

const currentSectionTitle = computed(() => {
    if (route.path === '/') return 'Overview';
    if (route.path.startsWith('/workout')) return 'Workouts';
    if (route.path.startsWith('/wallet')) return 'Wallet';
    if (route.path.startsWith('/transactions')) return 'Payments';
    if (route.path.startsWith('/profile')) return 'Profile';
    if (route.path.startsWith('/notifications')) return 'Messages';
    if (route.path.startsWith('/event/')) return 'Event';
    return 'Member Portal';
});

function handleBack() {
    if (window.history.length > 1) {
        router.back();
    } else {
        router.push('/');
    }
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (activeWorkout.value) closeWorkout();
        if (activeSale.value) closeSale();
        if (activePayment.value) closePayment();
    }
}

// ── Bootstrap ──────────────────────────────────────────────
onMounted(async () => {
    window.addEventListener('keydown', handleKeydown);
    const token = localStorage.getItem(MEMBER_ID_KEY);
    if (token) {
        currentToken.value = token;
        await loadProfile(token);
        track('session_resume');
    } else if (isEventPage.value) {
        screen.value = 'profile';
        track('session_start');
    } else {
        screen.value = 'identify';
        track('session_start');
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// ── CSRF helper ────────────────────────────────────────────
function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

// ── Auth actions ───────────────────────────────────────────
async function requestOtp() {
    error.value = '';
    isLoading.value = true;
    try {
        const res = await fetch('/api/public/request-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ phone_number: phone.value }),
        });
        const data = await res.json();
        if (!res.ok) { error.value = data.message || 'Something went wrong.'; return; }
        track('otp_requested');
        screen.value = 'otp';
    } catch {
        error.value = 'Network error. Please try again.';
    } finally {
        isLoading.value = false;
    }
}

async function verifyOtp() {
    error.value = '';
    isLoading.value = true;
    try {
        const res = await fetch('/api/public/verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ phone_number: phone.value, otp: otpCode.value }),
        });
        const data = await res.json();
        if (!res.ok) { error.value = data.message || 'Invalid OTP.'; return; }
        currentToken.value = data.token;
        localStorage.setItem(MEMBER_ID_KEY, data.token);
        track('otp_verified');
        await loadProfile(data.token);
    } catch {
        error.value = 'Network error. Please try again.';
    } finally {
        isLoading.value = false;
    }
}

async function loadProfile(token) {
    isLoading.value = true;
    try {
        const res = await fetch('/api/public/member-profile', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-PP-Token':       token,
            },
        });
        if (!res.ok) {
            localStorage.removeItem(MEMBER_ID_KEY);
            currentToken.value = null;
            screen.value = 'identify';
            return;
        }
        const data = await res.json();
        meta.value                   = data.meta;
        workoutsData.value           = data.workouts || [];
        salesData.value              = data.sales || [];
        paymentsData.value           = data.payments || [];
        membershipPaymentsData.value = data.membership_payments || [];
        otherPaymentsData.value      = data.other_payments || [];
        walletTransactions.value     = data.wallet_transactions || [];
        walletTxMeta.value           = data.wallet_tx_meta || walletTxMeta.value;
        screen.value                 = 'profile';
    } catch {
        localStorage.removeItem(MEMBER_ID_KEY);
        currentToken.value = null;
        screen.value = 'identify';
    } finally {
        isLoading.value = false;
    }
}

function backToIdentify() {
    otpCode.value = '';
    error.value   = '';
    screen.value  = 'identify';
}

// ── Computed ───────────────────────────────────────────────
const initials = computed(() => {
    const parts = (meta.value.name || '').trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return (parts[0]?.[0] ?? '?').toUpperCase();
});

const firstName = computed(() => {
    return (meta.value.name || '').trim().split(/\s+/)[0] || meta.value.name || '';
});

const lastName = computed(() => {
    const parts = (meta.value.name || '').trim().split(/\s+/);
    return parts.length > 1 ? parts.slice(1).join(' ') : '';
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h >= 5  && h < 12) return 'Good morning';
    if (h >= 12 && h < 18) return 'Good afternoon';
    if (h >= 18 && h < 22) return 'Good evening';
    return 'Welcome back';
});

// ── Modal actions ──────────────────────────────────────────
function openWorkout(workout)  { activeWorkout.value = workout; }
function closeWorkout()        { activeWorkout.value = null; }
function openSale(sale)        { activeSale.value = sale; }
function closeSale()           { activeSale.value = null; }
function openPayment(payment)  { activePayment.value = payment; }
function closePayment()        { activePayment.value = null; }

function logout() {
    track('logout');
    localStorage.removeItem(MEMBER_ID_KEY);
    currentToken.value = null;
    meta.value                   = {};
    workoutsData.value           = [];
    salesData.value              = [];
    paymentsData.value           = [];
    membershipPaymentsData.value = [];
    otherPaymentsData.value      = [];
    walletTransactions.value     = [];
    walletTxMeta.value           = { current_page: 1, last_page: 1, total: 0, per_page: 10 };
    phone.value        = '';
    otpCode.value      = '';
    error.value        = '';
    router.push('/');
    screen.value       = 'identify';
}
</script>

<style scoped>
.fade-page-enter-active,
.fade-page-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-page-enter-from {
  opacity: 0;
  transform: translateY(4px);
}

.fade-page-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
