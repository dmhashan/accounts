<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between pt-12 pb-6">
            <div>
                <p class="text-sm text-gray-400 leading-none mb-1">{{ greeting }},</p>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight tracking-tight">{{ firstName }} {{ lastName }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">Welcome to {{ meta.tenant_name }}</p>
            </div>
            <MemberAvatar
                :src="meta.profile_photo_url"
                :initials="initials"
                size="sm"
                shape="square"
                class="shadow-sm"
            />
        </div>



        <!-- Wallet balance quick card -->
        <button
            v-if="meta.current_balance !== undefined"
            type="button"
            class="w-full text-left mb-5 rounded-3xl overflow-hidden active:scale-[0.99] transition-transform focus:outline-none"
            @click="router.push('/wallet')"
        >
            <div class="relative px-6 py-5 flex items-center justify-between overflow-hidden" style="background: linear-gradient(135deg, #059669 0%, #047857 60%, #065f46 100%);">
                <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full bg-white/10 pointer-events-none"></div>
                <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full bg-white/10 pointer-events-none"></div>
                <div class="relative">
                    <p class="text-[11px] font-bold text-emerald-200 uppercase tracking-widest mb-0.5">Wallet Balance</p>
                    <p class="text-3xl font-extrabold text-white tracking-tight leading-none">{{ formatMoney(meta.current_balance) }}</p>
                </div>
                <div class="relative flex items-center gap-2">
                    <div class="w-9 h-9 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </div>
        </button>

        <!-- Latest Workout Plan -->
        <section v-if="workoutsData.length" class="mb-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-gray-900">Workout Plan</h2>
                <button v-if="workoutsData.length > 1" type="button" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition-colors" @click="router.push('/workout')">
                    See all ({{ workoutsData.length }})
                </button>
            </div>
            <button
                type="button"
                class="w-full text-left rounded-3xl overflow-hidden focus:outline-none active:scale-[0.99] transition-transform"
                @click="$emit('open-workout', workoutsData[0])"
            >
                <div class="relative px-6 pt-6 pb-5 overflow-hidden" style="background:#1a1a1a; min-height:160px;">
                    <div class="absolute -bottom-10 -right-10 w-44 h-44 rounded-full opacity-20 bg-black pointer-events-none"></div>
                    <div class="absolute -top-6 -left-6 w-28 h-28 rounded-full opacity-10 bg-black pointer-events-none"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-red-500 uppercase tracking-widest mb-2">Active Plan</p>
                            <h3 class="text-xl font-bold text-white leading-tight max-w-[200px]">{{ workoutsData[0].title }}</h3>
                            <p v-if="workoutsData[0].creator_name" class="text-xs text-gray-400 mt-1">by {{ workoutsData[0].creator_name }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-2xl bg-red-500 flex items-center justify-center mt-0.5">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-5 mt-5">
                        <div>
                            <p class="text-[10px] text-gray-400 mb-0.5 uppercase tracking-wider">Duration</p>
                            <p class="text-sm font-bold text-white">{{ workoutsData[0].duration_weeks || '-' }} wks</p>
                        </div>
                        <div class="w-px h-6 bg-white/10"></div>
                        <div>
                            <p class="text-[10px] text-gray-400 mb-0.5 uppercase tracking-wider">Start</p>
                            <p class="text-sm font-bold text-white">{{ workoutsData[0].effective_date || '-' }}</p>
                        </div>
                        <div class="w-px h-6 bg-white/10"></div>
                        <div>
                            <p class="text-[10px] text-gray-400 mb-0.5 uppercase tracking-wider">Days</p>
                            <p class="text-sm font-bold text-white">{{ workoutsData[0].days?.length || '-' }}</p>
                        </div>
                        <div class="ml-auto">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </div>
            </button>
        </section>
<!-- Upcoming Events section -->
        <section v-if="eventsLoading || upcomingEvents.length" class="mb-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-gray-900">Upcoming Events</h2>
            </div>

            <!-- Skeleton -->
            <div v-if="eventsLoading" class="space-y-3">
                <div v-for="i in 2" :key="i" class="bg-white rounded-3xl p-5 animate-pulse">
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-2xl bg-gray-100 flex-shrink-0"></div>
                        <div class="flex-1">
                            <div class="h-3 bg-gray-100 rounded-full w-3/5 mb-3"></div>
                            <div class="h-3 bg-gray-100 rounded-full w-2/5"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event cards -->
            <div v-else class="space-y-3">
                <router-link
                    v-for="event in upcomingEvents"
                    :key="event.id"
                    :to="`/event/${event.slug}`"
                    class="block bg-white rounded-3xl px-5 py-4 shadow-sm border border-gray-100 hover:bg-gray-50 active:scale-[0.99] transition-all"
                >
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-2xl bg-gray-900 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 leading-snug truncate">{{ event.name }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatEventDateTime(event.start_datetime) }}</p>
                            <p v-if="event.venue" class="text-xs text-gray-400 mt-0.5 truncate">{{ event.venue }}</p>
                        </div>
                        <div class="flex flex-col items-end flex-shrink-0 gap-1">
                            <span v-if="event.ticket_fee > 0" class="text-sm font-bold text-gray-900">${{ event.ticket_fee.toFixed(2) }}</span>
                            <span v-else class="text-xs font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Free</span>
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </router-link>
            </div>
        </section>

        <!-- Payments section -->
        <section v-if="salesData.length" class="mb-5">

            <!-- Sub-heading -->
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-gray-900">Payments</h2>
            </div>

            <!-- Outstanding total card — red, centered -->
            <div v-if="outstandingSales.length" class="mb-3 rounded-3xl overflow-hidden" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);">
                <div class="relative px-6 py-6 flex flex-col items-center text-center overflow-hidden">
                    <!-- decorative blobs -->
                    <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full bg-white/10 pointer-events-none"></div>
                    <div class="absolute -bottom-8 -left-8 w-28 h-28 rounded-full bg-white/10 pointer-events-none"></div>

                    <p class="text-xs font-semibold text-red-100 uppercase tracking-widest mb-2">Total outstanding</p>
                    <p class="text-5xl font-extrabold text-white tracking-tight leading-none">{{ String(meta.total_outstanding).replace(/^-/, '') }}</p>
                </div>
            </div>

            <!-- Last 3 records (outstanding + paid) -->
            <div class="bg-white rounded-3xl overflow-hidden divide-y divide-gray-50 shadow-sm border border-gray-100">
                <button
                    v-for="(sale, i) in salesData.slice(0, 3)"
                    :key="i"
                    type="button"
                    class="w-full flex items-center gap-4 px-5 py-4 hover:bg-gray-50 active:bg-gray-100 transition-colors focus:outline-none text-left"
                    @click="$emit('open-sale', sale)"
                >
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center"
                        :class="!sale.is_paid ? 'bg-red-50' : 'bg-gray-100'"
                    >
                        <svg style="width:18px;height:18px" :class="!sale.is_paid ? 'text-red-400' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-6-8h6M5 8h.01M5 12h.01M5 16h.01M9 4H4a1 1 0 00-1 1v14a1 1 0 001 1h16a1 1 0 001-1V5a1 1 0 00-1-1h-5"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">Invoice #{{ sale.id }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ sale.created_at }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-bold text-gray-900">{{ sale.total_amount }}</p>
                        <span v-if="!sale.is_paid" class="inline-block text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full mt-0.5">Unpaid</span>
                        <span v-else class="inline-block text-[10px] font-bold text-green-700 bg-[#dcfce7] px-1.5 py-0.5 rounded-full mt-0.5">Paid</span>
                    </div>
                </button>
            </div>

            <button
                v-if="salesData.length > 3"
                type="button"
                class="mt-3 w-full py-3.5 text-sm font-bold bg-gray-900 text-white rounded-2xl hover:bg-gray-800 active:bg-black transition-colors"
                @click="router.push('/transactions')"
            >
                View all {{ salesData.length }} payments
            </button>
        </section>
        <!-- No data at all -->
        <div v-if="!workoutsData.length && !salesData.length" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <p class="text-sm text-gray-400">No data yet</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import MemberAvatar from '../../../components/ui/MemberAvatar.vue';

const props = defineProps({
    meta:         { type: Object,  default: () => ({}) },
    greeting:     { type: String,  default: '' },
    firstName:    { type: String,  default: '' },
    lastName:     { type: String,  default: '' },
    initials:     { type: String,  default: '' },
    workoutsData: { type: Array,   default: () => [] },
    salesData:    { type: Array,   default: () => [] },
});

defineEmits(['open-workout', 'open-sale']);

const router = useRouter();

const outstandingSales = computed(() => props.salesData.filter(s => !s.is_paid));

// ── Upcoming events (lazy loaded) ────────────────────────
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
        // silently ignore — non-critical section
    }
    eventsLoading.value = false;
});

function formatEventDateTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, {
        weekday: 'short', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatMoney(val) {
    const n = parseFloat(val ?? 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
