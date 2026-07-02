<template>
  <div class="member-portal-shell app-shell-viewport flex flex-col">
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

    <!-- ── Scrollable body (profile screens) ──────────── -->
    <template v-else>
      <main class="member-portal-main flex-1 overflow-y-auto">
        <div class="max-w-lg mx-auto px-5">
          <router-view v-slot="{ Component }">
            <component
              :is="Component"
              :meta="meta"
              :greeting="greeting"
              :first-name="firstName"
              :last-name="lastName"
              :initials="initials"
              :workouts-data="workoutsData"
              :sales-data="salesData"
              :wallet-transactions="walletTransactions"
              :wallet-tx-meta="walletTxMeta"
              :tenant-logo-url="tenantLogoUrl"
              @open-workout="openWorkout"
              @open-sale="openSale"
              @logout="logout"
            />
          </router-view>
        </div>
      </main>

      <BottomNavBar />

      <!-- ── Workout Preview Modal ──────────────────────── -->
      <Teleport to="body">
        <!-- eslint-disable-next-line vue/valid-v-on -->
        <div v-if="activeWorkout" class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto" @keydown.escape.window="closeWorkout">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeWorkout" />
          <div class="relative w-full max-w-4xl my-4">
            <div class="flex justify-end mb-2">
              <button
                type="button"
                class="p-2 rounded-xl bg-white text-gray-500 hover:text-gray-700 shadow-md transition-colors"
                aria-label="Close"
                @click="closeWorkout"
              >
                <X class="w-5 h-5" :stroke-width="2" />
              </button>
            </div>
            <WorkoutProgramPreviewCard :program="activeWorkout" />
          </div>
        </div>
      </Teleport>

      <!-- ── Sale Invoice Preview Modal ─────────────────── -->
      <Teleport to="body">
        <!-- eslint-disable-next-line vue/valid-v-on -->
        <div v-if="activeSale" class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto" @keydown.escape.window="closeSale">
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeSale" />
          <div class="relative w-full max-w-2xl my-4">
            <div class="flex justify-end mb-2">
              <button
                type="button"
                class="p-2 rounded-xl bg-white text-gray-500 hover:text-gray-700 shadow-md transition-colors"
                aria-label="Close"
                @click="closeSale"
              >
                <X class="w-5 h-5" :stroke-width="2" />
              </button>
            </div>
            <SaleInvoicePreviewCard :sale="activeSale" />
          </div>
        </div>
      </Teleport>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { X } from 'lucide-vue-next';

import LoadingScreen    from '../components/PublicProfileApp/LoadingScreen.vue';
import IdentifyScreen   from '../components/PublicProfileApp/IdentifyScreen.vue';
import OtpScreen        from '../components/PublicProfileApp/OtpScreen.vue';
import BottomNavBar     from '../components/PublicProfileApp/BottomNavBar.vue';

import WorkoutProgramPreviewCard from '../components/WorkoutProgramPreviewCard.vue';
import SaleInvoicePreviewCard    from '../components/SaleInvoicePreviewCard.vue';

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
    // Fire-and-forget — intentionally not awaited to avoid blocking UI
    fetch('/api/public/activity', {
        method:  'POST',
        headers,
        body: JSON.stringify(payload),
    }).catch(() => { /* silent — tracking must never affect UX */ });
}

// ── Auth state ─────────────────────────────────────────────
const screen        = ref('loading'); // 'loading' | 'identify' | 'otp' | 'profile'
const phone         = ref('');
const otpCode       = ref('');
const error         = ref('');
const isLoading     = ref(false);
const currentToken  = ref(null);

// ── Profile data ───────────────────────────────────────────
const workoutsData       = ref([]);
const salesData          = ref([]);
const walletTransactions = ref([]);
const walletTxMeta       = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
const meta               = ref({});

const tenantName    = computed(() => window.__tenantName || '');
const tenantLogoUrl = computed(() => window.__tenantLogoUrl || null);

// ── Nav state ──────────────────────────────────────────────
const router        = useRouter();
const route         = useRoute();
const activeWorkout = ref(null);
const activeSale    = ref(null);

// Event pages are publicly accessible — no OTP required
const isEventPage = computed(() => route.path.startsWith('/event/'));

// ── Bootstrap ──────────────────────────────────────────────
onMounted(async () => {
    const token = localStorage.getItem(MEMBER_ID_KEY);
    if (token) {
        currentToken.value = token;
        await loadProfile(token);
        track('session_resume');
    } else if (isEventPage.value) {
        // Event pages are public — skip OTP login
        screen.value = 'profile';
        track('session_start');
    } else {
        screen.value = 'identify';
        track('session_start');
    }
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
        meta.value               = data.meta;
        workoutsData.value       = data.workouts;
        salesData.value          = data.sales;
        walletTransactions.value = data.wallet_transactions || [];
        walletTxMeta.value       = data.wallet_tx_meta || walletTxMeta.value;
        screen.value             = 'profile';
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
    return 'Hello';
});

// ── Modal actions ──────────────────────────────────────────
function openWorkout(workout)  { activeWorkout.value = workout; }
function closeWorkout()        { activeWorkout.value = null; }
function openSale(sale)        { activeSale.value = sale; }
function closeSale()           { activeSale.value = null; }

function logout() {
    track('logout');
    localStorage.removeItem(MEMBER_ID_KEY);
    currentToken.value = null;
    meta.value               = {};
    workoutsData.value       = [];
    salesData.value          = [];
    walletTransactions.value = [];
    walletTxMeta.value       = { current_page: 1, last_page: 1, total: 0, per_page: 10 };
    phone.value        = '';
    otpCode.value      = '';
    error.value        = '';
    router.push('/');
    screen.value       = 'identify';
}
</script>
