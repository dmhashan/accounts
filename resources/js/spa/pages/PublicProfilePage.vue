<template>
    <div class="min-h-screen bg-[#f5f5f5] flex flex-col">

        <LoadingScreen v-if="screen === 'loading'" />

        <IdentifyScreen
            v-else-if="screen === 'identify'"
            v-model="phone"
            :tenant-name="tenantName"
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
            <main class="flex-1 overflow-y-auto pb-28">
                <div class="max-w-lg mx-auto px-5">

                    <HomeView
                        v-show="activeNav === 'home'"
                        :meta="meta"
                        :greeting="greeting"
                        :first-name="firstName"
                        :last-name="lastName"
                        :initials="initials"
                        :workouts-data="workoutsData"
                        :sales-data="salesData"
                        @open-workout="openWorkout"
                        @open-sale="openSale"
                        @navigate="activeNav = $event"
                    />

                    <WorkoutView
                        v-show="activeNav === 'workout'"
                        :workouts-data="workoutsData"
                        @open-workout="openWorkout"
                    />

                    <TransactionsView
                        v-show="activeNav === 'transactions'"
                        :meta="meta"
                        :sales-data="salesData"
                        @open-sale="openSale"
                    />

                    <ProfileView
                        v-show="activeNav === 'profile'"
                        :meta="meta"
                        :initials="initials"
                        :workouts-data="workoutsData"
                        :sales-data="salesData"
                        @logout="logout"
                    />

                </div>
            </main>

            <BottomNavBar :active-nav="activeNav" @navigate="activeNav = $event" />

            <!-- ── Workout Preview Modal ──────────────────────── -->
            <Teleport to="body">
                <div v-if="activeWorkout" class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto" @keydown.escape.window="closeWorkout">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeWorkout"></div>
                    <div class="relative w-full max-w-4xl my-4">
                        <div class="flex justify-end mb-2">
                            <button type="button" class="p-2 rounded-xl bg-white text-gray-500 hover:text-gray-700 shadow-md transition-colors" @click="closeWorkout" aria-label="Close">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <WorkoutProgramPreviewCard :program="activeWorkout" />
                    </div>
                </div>
            </Teleport>

            <!-- ── Sale Invoice Preview Modal ─────────────────── -->
            <Teleport to="body">
                <div v-if="activeSale" class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto" @keydown.escape.window="closeSale">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeSale"></div>
                    <div class="relative w-full max-w-2xl my-4">
                        <div class="flex justify-end mb-2">
                            <button type="button" class="p-2 rounded-xl bg-white text-gray-500 hover:text-gray-700 shadow-md transition-colors" @click="closeSale" aria-label="Close">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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

import LoadingScreen    from '../components/PublicProfileApp/LoadingScreen.vue';
import IdentifyScreen   from '../components/PublicProfileApp/IdentifyScreen.vue';
import OtpScreen        from '../components/PublicProfileApp/OtpScreen.vue';
import HomeView         from '../components/PublicProfileApp/HomeView.vue';
import WorkoutView      from '../components/PublicProfileApp/WorkoutView.vue';
import TransactionsView from '../components/PublicProfileApp/TransactionsView.vue';
import ProfileView      from '../components/PublicProfileApp/ProfileView.vue';
import BottomNavBar     from '../components/PublicProfileApp/BottomNavBar.vue';

import WorkoutProgramPreviewCard from '../components/WorkoutProgramPreviewCard.vue';
import SaleInvoicePreviewCard    from '../components/SaleInvoicePreviewCard.vue';

const MEMBER_ID_KEY = 'public_profile_member_id';

// ── Auth state ─────────────────────────────────────────────
const screen    = ref('loading'); // 'loading' | 'identify' | 'otp' | 'profile'
const phone     = ref('');
const otpCode   = ref('');
const error     = ref('');
const isLoading = ref(false);

// ── Profile data ───────────────────────────────────────────
const workoutsData = ref([]);
const salesData    = ref([]);
const meta         = ref({});

const tenantName = computed(() => window.__tenantName || '');

// ── Nav state ──────────────────────────────────────────────
const activeNav     = ref('home');
const activeWorkout = ref(null);
const activeSale    = ref(null);

// ── Bootstrap ──────────────────────────────────────────────
onMounted(async () => {
    const memberId = localStorage.getItem(MEMBER_ID_KEY);
    if (memberId) {
        await loadProfile(memberId);
    } else {
        screen.value = 'identify';
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
        localStorage.setItem(MEMBER_ID_KEY, data.member_id);
        await loadProfile(data.member_id);
    } catch {
        error.value = 'Network error. Please try again.';
    } finally {
        isLoading.value = false;
    }
}

async function loadProfile(memberId) {
    isLoading.value = true;
    try {
        const res = await fetch(`/api/public/member-profile/${memberId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) {
            localStorage.removeItem(MEMBER_ID_KEY);
            screen.value = 'identify';
            return;
        }
        const data = await res.json();
        meta.value         = data.meta;
        workoutsData.value = data.workouts;
        salesData.value    = data.sales;
        screen.value       = 'profile';
    } catch {
        localStorage.removeItem(MEMBER_ID_KEY);
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
    localStorage.removeItem(MEMBER_ID_KEY);
    meta.value         = {};
    workoutsData.value = [];
    salesData.value    = [];
    phone.value        = '';
    otpCode.value      = '';
    error.value        = '';
    activeNav.value    = 'home';
    screen.value       = 'identify';
}
</script>
