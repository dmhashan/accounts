<template>
    <div class="min-h-screen bg-[#f5f5f5]">

        <!-- Loading skeleton -->
        <div v-if="loading" class="max-w-lg mx-auto px-4 pt-10 pb-24">
            <div class="animate-pulse space-y-4">
                <div class="h-8 bg-gray-200 rounded-xl w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                <div class="h-32 bg-gray-200 rounded-2xl"></div>
            </div>
        </div>

        <!-- Event not found -->
        <div v-else-if="!event" class="max-w-lg mx-auto px-4 pt-20 pb-24 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">Event Not Found</h2>
            <p class="text-sm text-gray-500">This event may have ended or the link may be incorrect.</p>
        </div>

        <!-- Registration summary (shown after first submit and on subsequent loads) -->
        <div v-else-if="existingRegistration && !editing" class="max-w-lg mx-auto px-4 pt-6 pb-24">
            <!-- Event hero -->
            <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white rounded-2xl p-5 mb-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-red-400 mb-1">Event</p>
                <h1 class="text-xl font-extrabold leading-tight mb-3">{{ event.name }}</h1>
                <div class="space-y-2 text-sm text-gray-300">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span>{{ formatDatetime(event.start_datetime) }}</span>
                        <template v-if="event.end_datetime">
                            <span class="text-gray-500">&rarr;</span>
                            <span>{{ formatTime(event.end_datetime) }}</span>
                        </template>
                    </div>
                    <div v-if="event.venue" class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <a v-if="event.venue_url" :href="event.venue_url" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2 hover:text-white transition-colors">{{ event.venue }}</a>
                        <span v-else>{{ event.venue }}</span>
                    </div>
                    <div class="flex items-center gap-4 pt-1">
                        <span class="font-semibold text-white">{{ event.ticket_fee > 0 ? `$${Number(event.ticket_fee).toFixed(2)}` : 'Free' }}</span>
                        <span v-if="event.additional_ticket_fee > 0" class="text-gray-400 text-xs">+ ${{ Number(event.additional_ticket_fee).toFixed(2) }} per extra member</span>
                    </div>
                </div>
            </div>

            <!-- Agenda -->
            <div v-if="event.agenda" class="bg-white rounded-2xl shadow-sm p-4 mb-4">
                <h2 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Agenda</h2>
                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ event.agenda }}</p>
            </div>

            <!-- Registration process -->
            <div v-if="event.registration_process" class="bg-white rounded-2xl shadow-sm p-4 mb-4">
                <h2 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Registration Process</h2>
                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ event.registration_process }}</p>
            </div>

            <!-- Registration status banner -->
            <div class="flex items-center gap-3 mb-4 p-3 rounded-xl" :class="justRegistered ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200'">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :class="justRegistered ? 'bg-green-100' : 'bg-gray-200'">
                    <svg class="w-4 h-4" :class="justRegistered ? 'text-green-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold" :class="justRegistered ? 'text-green-800' : 'text-gray-800'">
                        {{ justRegistered ? "You're registered!" : "Your registration" }}
                    </p>
                    <p v-if="justRegistered" class="text-xs text-green-600">Registration confirmed successfully.</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-4 mb-4 space-y-3 text-sm text-gray-700">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Name</p>
                    <p>{{ existingRegistration.first_name }} {{ existingRegistration.last_name }}</p>
                </div>
                <div v-if="existingRegistration.email">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Email</p>
                    <p>{{ existingRegistration.email }}</p>
                </div>
                <div v-if="existingRegistration.phone">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Phone</p>
                    <p>{{ existingRegistration.phone }}</p>
                </div>
                <div v-if="existingRegistration.notes">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Notes</p>
                    <p class="whitespace-pre-wrap">{{ existingRegistration.notes }}</p>
                </div>
            </div>

            <div v-if="existingRegistration.guests?.length > 0" class="bg-white rounded-2xl shadow-sm p-4 mb-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Additional Members</h3>
                <div v-for="(g, i) in existingRegistration.guests" :key="i" class="py-2 border-b border-gray-50 last:border-0">
                    <p class="font-medium text-gray-800">{{ g.first_name }} {{ g.last_name }}</p>
                    <p v-if="g.notes" class="text-xs text-gray-500 mt-0.5 whitespace-pre-wrap">{{ g.notes }}</p>
                </div>
            </div>

            <div v-if="existingRegistration.total_fee > 0" class="bg-white rounded-2xl shadow-sm p-4 mb-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Total fee</span>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-900">${{ Number(existingRegistration.total_fee).toFixed(2) }}</span>
                        <span v-if="existingRegistration.is_paid" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            Paid
                        </span>
                        <span v-else class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                    </div>
                </div>
                <p v-if="existingRegistration.is_paid && existingRegistration.paid_at" class="text-xs text-gray-400 mt-1 text-right">
                    Paid on {{ formatDateShort(existingRegistration.paid_at) }}
                </p>
            </div>

            <button
                v-if="!existingRegistration.is_paid"
                type="button"
                class="w-full py-3 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl transition-colors text-sm"
                @click="editing = true"
            >
                Edit Registration
            </button>
            <p v-else class="text-center text-xs text-gray-400 py-2">Registration locked after payment.</p>
        </div>

        <!-- Main content -->
        <template v-else>
            <!-- Hero banner -->
            <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white">
                <div class="max-w-lg mx-auto px-4 py-8">
                    <p class="text-xs font-semibold uppercase tracking-widest text-red-400 mb-2">Event</p>
                    <h1 class="text-2xl font-extrabold leading-tight mb-3">{{ event.name }}</h1>

                    <div class="space-y-2 text-sm text-gray-300">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <span>{{ formatDatetime(event.start_datetime) }}</span>
                            <template v-if="event.end_datetime">
                                <span class="text-gray-500">&rarr;</span>
                                <span>{{ formatTime(event.end_datetime) }}</span>
                            </template>
                        </div>
                        <div v-if="event.venue" class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <a v-if="event.venue_url" :href="event.venue_url" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2 hover:text-white transition-colors">{{ event.venue }}</a>
                            <span v-else>{{ event.venue }}</span>
                        </div>
                        <div class="flex items-center gap-4 pt-1">
                            <span class="text-white font-semibold">{{ event.ticket_fee > 0 ? `$${Number(event.ticket_fee).toFixed(2)}` : 'Free' }}</span>
                            <span v-if="event.additional_ticket_fee > 0" class="text-gray-400 text-xs">+ ${{ Number(event.additional_ticket_fee).toFixed(2) }} per extra member</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Countdown -->
            <div v-if="!eventStarted" class="max-w-lg mx-auto px-4 mt-4">
                <div class="bg-white rounded-2xl shadow-sm p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3 text-center">Event starts in</p>
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div v-for="unit in countdown" :key="unit.label">
                            <div class="text-2xl font-extrabold text-gray-900 tabular-nums">{{ unit.value }}</div>
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mt-0.5">{{ unit.label }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="max-w-lg mx-auto px-4 mt-4">
                <div class="bg-green-50 border border-green-200 rounded-2xl p-4 text-center">
                    <p class="text-sm font-semibold text-green-700">This event is happening now!</p>
                </div>
            </div>

            <div class="max-w-lg mx-auto px-4 pb-24 space-y-4 mt-4">
                <!-- Agenda -->
                <div v-if="event.agenda" class="bg-white rounded-2xl shadow-sm p-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-2">Agenda</h2>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ event.agenda }}</p>
                </div>

                <!-- Registration process -->
                <div v-if="event.registration_process" class="bg-white rounded-2xl shadow-sm p-4">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-2">Registration Process</h2>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ event.registration_process }}</p>
                </div>

                <!-- Registration form -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <h2 class="text-base font-bold text-gray-900">{{ editing ? 'Edit Your Registration' : 'Register for this Event' }}</h2>
                        <p v-if="memberMeta && !editing" class="text-xs text-green-600 mt-0.5">Logged in as {{ memberMeta.name }} — details pre-filled.</p>
                    </div>

                    <form class="p-4 space-y-4" @submit.prevent="submit">
                        <!-- Main registrant -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.first_name"
                                    type="text"
                                    required
                                    maxlength="100"
                                    class="pp-input"
                                    :readonly="!!memberMeta"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.last_name"
                                    type="text"
                                    required
                                    maxlength="100"
                                    class="pp-input"
                                    :readonly="!!memberMeta"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                maxlength="150"
                                class="pp-input"
                                :readonly="!!memberMeta"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Phone</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                maxlength="30"
                                class="pp-input"
                                :readonly="!!memberMeta"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Notes / Dietary requirements / Special requests</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                maxlength="1000"
                                class="pp-textarea"
                            ></textarea>
                        </div>

                        <!-- Family members section -->
                        <div class="border-t border-gray-100 pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800">Additional Family Members</h3>
                                    <p v-if="event.additional_ticket_fee > 0" class="text-xs text-gray-400 mt-0.5">
                                        ${{ Number(event.additional_ticket_fee).toFixed(2) }} per additional member
                                    </p>
                                    <p v-else class="text-xs text-gray-400 mt-0.5">Free for additional members</p>
                                </div>
                                <button
                                    type="button"
                                    class="flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700 transition-colors"
                                    @click="addGuest"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Add Member
                                </button>
                            </div>

                            <div v-if="form.guests.length === 0" class="text-xs text-gray-400 text-center py-2">
                                No additional members added.
                            </div>

                            <div v-for="(guest, idx) in form.guests" :key="idx" class="mb-3 p-3 bg-gray-50 rounded-xl space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500">Member {{ idx + 1 }}</span>
                                    <button type="button" class="text-xs text-red-500 hover:text-red-700" @click="removeGuest(idx)">Remove</button>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">First Name <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="guest.first_name"
                                            type="text"
                                            required
                                            maxlength="100"
                                            class="pp-input"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Last Name <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="guest.last_name"
                                            type="text"
                                            required
                                            maxlength="100"
                                            class="pp-input"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Notes <span class="text-gray-400">(optional)</span></label>
                                    <textarea
                                        v-model="guest.notes"
                                        rows="2"
                                        maxlength="500"
                                        class="pp-textarea"
                                        placeholder="Dietary requirements, special requests..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Fee summary -->
                        <div v-if="totalFee > 0" class="bg-gray-50 rounded-xl p-3 space-y-1 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Ticket fee</span>
                                <span>${{ Number(event.ticket_fee).toFixed(2) }}</span>
                            </div>
                            <div v-if="form.guests.length > 0" class="flex justify-between text-gray-600">
                                <span>{{ form.guests.length }} × additional member{{ form.guests.length > 1 ? 's' : '' }}</span>
                                <span>${{ (form.guests.length * Number(event.additional_ticket_fee)).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-200 pt-1 mt-1">
                                <span>Total</span>
                                <span>${{ totalFee.toFixed(2) }}</span>
                            </div>
                        </div>

                        <div v-if="errorMessage" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            {{ errorMessage }}
                        </div>

                        <button
                            v-if="editing"
                            type="button"
                            class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors text-sm"
                            @click="cancelEdit"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full py-3 bg-red-500 hover:bg-red-600 disabled:opacity-60 text-white font-bold rounded-xl transition-colors text-sm"
                        >
                            {{ submitting ? (editing ? 'Saving...' : 'Registering...') : (editing ? 'Save Changes' : 'Register Now') }}
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const slug  = computed(() => route.params.slug);

const loading            = ref(true);
const event              = ref(null);
const memberMeta         = ref(null);
const justRegistered     = ref(false);
const existingRegistration = ref(null);
const editing            = ref(false);
const submitting         = ref(false);
const errorMessage       = ref('');

const form = ref({
    first_name: '',
    last_name:  '',
    email:      '',
    phone:      '',
    notes:      '',
    guests:     [],
});

// ─── Countdown ──────────────────────────────────────────────
const now           = ref(new Date());
let   countdownTimer = null;

const eventStarted = computed(() => {
    if (!event.value?.start_datetime) return false;
    return new Date(event.value.start_datetime) <= now.value;
});

const countdown = computed(() => {
    if (!event.value?.start_datetime) return [];
    const diff = Math.max(0, new Date(event.value.start_datetime) - now.value);
    const days    = Math.floor(diff / 86400000);
    const hours   = Math.floor((diff % 86400000) / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    return [
        { label: 'Days',    value: String(days).padStart(2, '0') },
        { label: 'Hours',   value: String(hours).padStart(2, '0') },
        { label: 'Minutes', value: String(minutes).padStart(2, '0') },
        { label: 'Seconds', value: String(seconds).padStart(2, '0') },
    ];
});

const totalFee = computed(() => {
    if (!event.value) return 0;
    return Number(event.value.ticket_fee) + form.value.guests.length * Number(event.value.additional_ticket_fee);
});

// ─── Auth helpers ────────────────────────────────────────────
const MEMBER_ID_KEY = 'public_profile_member_id';

function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function ppHeaders(extra = {}) {
    const token = localStorage.getItem(MEMBER_ID_KEY);
    return {
        'Content-Type':      'application/json',
        'X-Requested-With':  'XMLHttpRequest',
        'X-XSRF-TOKEN':      getCsrfToken(),
        ...(token ? { 'X-PP-Token': token } : {}),
        ...extra,
    };
}

// ─── Data loading ────────────────────────────────────────────
async function loadEvent() {
    try {
        const res  = await fetch(`/api/public/event/${slug.value}`, { headers: ppHeaders() });
        const data = await res.json();
        if (!res.ok) { event.value = null; return; }
        event.value = data;
    } catch {
        event.value = null;
    }
}

async function loadMemberProfile() {
    const token = localStorage.getItem(MEMBER_ID_KEY);
    if (!token) return;
    try {
        const res = await fetch('/api/public/member-profile', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-PP-Token': token },
        });
        if (!res.ok) return;
        const data = await res.json();
        memberMeta.value = data.meta;

        // Pre-fill the form with member details
        const nameParts     = (data.meta.name || '').trim().split(/\s+/);
        form.value.first_name = nameParts[0] || '';
        form.value.last_name  = nameParts.slice(1).join(' ') || '';
        form.value.email      = data.meta.email || '';
        form.value.phone      = data.meta.phone_number || '';
    } catch {
        // non-critical — form stays empty
    }
}

async function loadMyRegistration() {
    const token = localStorage.getItem(MEMBER_ID_KEY);
    if (!token) return;
    try {
        const res = await fetch(`/api/public/event/${slug.value}/my-registration`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-PP-Token': token },
        });
        if (!res.ok) return;
        const data = await res.json();
        if (!data.data) return;
        existingRegistration.value = data.data;
        // Pre-fill editable form fields with existing registration data
        form.value.notes  = data.data.notes || '';
        form.value.guests = (data.data.guests || []).map(g => ({
            first_name: g.first_name,
            last_name:  g.last_name,
            notes:      g.notes || '',
        }));
    } catch {
        // non-critical
    }
}

// ─── Guest management ────────────────────────────────────────
function addGuest() {
    form.value.guests.push({ first_name: '', last_name: '', notes: '' });
}

function removeGuest(idx) {
    form.value.guests.splice(idx, 1);
}

// ─── Submit ──────────────────────────────────────────────────
async function submit() {
    submitting.value   = true;
    errorMessage.value = '';
    try {
        if (editing.value) {
            const res  = await fetch(`/api/public/event/${slug.value}/my-registration`, {
                method:  'PUT',
                headers: ppHeaders(),
                body:    JSON.stringify({ notes: form.value.notes, guests: form.value.guests }),
            });
            const data = await res.json();
            if (!res.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                errorMessage.value = firstError || data.message || 'Update failed. Please try again.';
                return;
            }
            existingRegistration.value = data.data;
            editing.value = false;
        } else {
            const res  = await fetch(`/api/public/event/${slug.value}/register`, {
                method:  'POST',
                headers: ppHeaders(),
                body:    JSON.stringify(form.value),
            });
            const data = await res.json();
            if (!res.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                errorMessage.value = firstError || data.message || 'Registration failed. Please try again.';
                return;
            }
            existingRegistration.value = data.data;
            justRegistered.value = true;
        }
    } catch {
        errorMessage.value = 'Network error. Please try again.';
    } finally {
        submitting.value = false;
    }
}

function cancelEdit() {
    editing.value      = false;
    errorMessage.value = '';
    form.value.notes   = existingRegistration.value.notes || '';
    form.value.guests  = (existingRegistration.value.guests || []).map(g => ({
        first_name: g.first_name,
        last_name:  g.last_name,
        notes:      g.notes || '',
    }));
}

// ─── Formatting ──────────────────────────────────────────────
function formatDatetime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString(undefined, {
        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

function formatDateShort(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

// ─── Lifecycle ───────────────────────────────────────────────
onMounted(async () => {
    await Promise.all([loadEvent(), loadMemberProfile(), loadMyRegistration()]);
    loading.value = false;

    countdownTimer = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (countdownTimer) clearInterval(countdownTimer);
});
</script>
