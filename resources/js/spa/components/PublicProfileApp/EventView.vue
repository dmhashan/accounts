<template>
  <div class="space-y-4 pb-6">
    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4 pt-4">
      <div class="pp-glass-card rounded-3xl p-6 animate-pulse space-y-3">
        <div class="h-6 bg-gray-200 dark:bg-zinc-800 rounded-xl w-3/4" />
        <div class="h-4 bg-gray-200 dark:bg-zinc-800 rounded w-1/2" />
        <div class="h-28 bg-gray-200 dark:bg-zinc-800 rounded-2xl" />
      </div>
    </div>

    <!-- Event not found -->
    <div v-else-if="!event" class="pp-glass-card rounded-3xl p-12 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-3xl bg-red-500/10 text-red-500 flex items-center justify-center">
        <X class="w-8 h-8" :stroke-width="2" />
      </div>
      <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mb-1">
        Event Not Found
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 max-w-xs mx-auto">
        This event may have ended or the link may be incorrect.
      </p>
    </div>

    <!-- Registration summary (shown after submit or if already registered) -->
    <div v-else-if="existingRegistration && !editing" class="space-y-4 pt-2">
      <!-- Event hero card -->
      <div class="pp-membership-card p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/10 pointer-events-none" />
        <p class="text-[10px] font-black uppercase tracking-widest text-red-400 mb-1">
          Event Registration
        </p>
        <h1 class="text-2xl font-black text-white leading-tight mb-3">
          {{ event.name }}
        </h1>
        <div class="space-y-2 text-xs text-zinc-300">
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4 text-red-400 shrink-0" :stroke-width="2" />
            <span>{{ formatDatetime(event.start_datetime) }}</span>
            <template v-if="event.end_datetime">
              <span class="text-zinc-500">&rarr;</span>
              <span>{{ formatTime(event.end_datetime) }}</span>
            </template>
          </div>
          <div v-if="event.venue" class="flex items-center gap-2">
            <MapPin class="w-4 h-4 text-red-400 shrink-0" :stroke-width="2" />
            <a
              v-if="event.venue_url"
              :href="event.venue_url"
              target="_blank"
              rel="noopener noreferrer"
              class="underline underline-offset-2 hover:text-white transition-colors"
            >{{ event.venue }}</a>
            <span v-else>{{ event.venue }}</span>
          </div>
        </div>
      </div>

      <!-- Registration status banner -->
      <div
        class="flex items-center gap-3 p-4 rounded-3xl border shadow-sm"
        :class="justRegistered
          ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300'
          : 'bg-gray-50 dark:bg-zinc-800/60 border-gray-200 dark:border-zinc-700 text-gray-800 dark:text-gray-200'"
      >
        <div
          class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0"
          :class="justRegistered ? 'bg-emerald-500 text-white shadow-md' : 'bg-gray-200 dark:bg-zinc-700 text-gray-600 dark:text-gray-300'"
        >
          <Check class="w-5 h-5" :stroke-width="2.5" />
        </div>
        <div>
          <p class="text-sm font-extrabold">
            {{ justRegistered ? "You're Registered!" : "Your Registration Pass" }}
          </p>
          <p class="text-xs opacity-90 mt-0.5">
            {{ justRegistered ? "Registration confirmed successfully." : "Registered attendee for this event." }}
          </p>
        </div>
      </div>

      <!-- Registrant Details -->
      <div class="pp-glass-card rounded-3xl p-5 space-y-3 text-xs">
        <div class="border-b border-gray-100 dark:border-zinc-800 pb-2.5">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">
            Registered Name
          </p>
          <p class="text-sm font-bold text-gray-900 dark:text-white">
            {{ existingRegistration.name }}
          </p>
        </div>
        <div v-if="existingRegistration.email" class="border-b border-gray-100 dark:border-zinc-800 pb-2.5">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">
            Email Address
          </p>
          <p class="text-sm font-bold text-gray-900 dark:text-white">
            {{ existingRegistration.email }}
          </p>
        </div>
        <div v-if="existingRegistration.phone" class="border-b border-gray-100 dark:border-zinc-800 pb-2.5">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">
            Phone Number
          </p>
          <p class="text-sm font-bold text-gray-900 dark:text-white">
            {{ existingRegistration.phone }}
          </p>
        </div>
        <div v-if="existingRegistration.notes">
          <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">
            Notes / Dietary Requests
          </p>
          <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
            {{ existingRegistration.notes }}
          </p>
        </div>
      </div>

      <!-- Additional guests -->
      <div v-if="existingRegistration.guests?.length > 0" class="pp-glass-card rounded-3xl p-5">
        <h3 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">
          Additional Members / Family ({{ existingRegistration.guests.length }})
        </h3>
        <div
          v-for="(g, i) in existingRegistration.guests"
          :key="i"
          class="py-2.5 border-b border-gray-100 dark:border-zinc-800/60 last:border-0"
        >
          <p class="font-bold text-sm text-gray-900 dark:text-white">
            {{ g.name }}
          </p>
          <p v-if="g.notes" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 whitespace-pre-wrap">
            {{ g.notes }}
          </p>
        </div>
      </div>

      <!-- Fee summary -->
      <div v-if="existingRegistration.total_fee > 0" class="pp-glass-card rounded-3xl p-5">
        <div class="flex justify-between items-center text-sm">
          <span class="font-bold text-gray-600 dark:text-gray-400">Total Registration Fee</span>
          <div class="flex items-center gap-2">
            <span class="font-black text-lg text-gray-900 dark:text-white">${{ Number(existingRegistration.total_fee).toFixed(2) }}</span>
            <span v-if="existingRegistration.is_paid" class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
              Paid
            </span>
            <span v-else class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">
              Pending
            </span>
          </div>
        </div>
      </div>

      <button
        v-if="!existingRegistration.is_paid"
        type="button"
        class="w-full py-3.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-2xl shadow-md transition-all active:scale-[0.99] text-sm cursor-pointer"
        @click="editing = true"
      >
        Edit Registration Details
      </button>
    </div>

    <!-- Main Registration Form & Details -->
    <template v-else>
      <!-- Hero Banner -->
      <div class="pp-membership-card p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-white/10 pointer-events-none" />
        <p class="text-[10px] font-black uppercase tracking-widest text-red-400 mb-1">
          Fitness Club Event
        </p>
        <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight mb-3">
          {{ event.name }}
        </h1>

        <div class="space-y-2 text-xs text-zinc-300">
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4 text-red-400 shrink-0" :stroke-width="2" />
            <span>{{ formatDatetime(event.start_datetime) }}</span>
            <template v-if="event.end_datetime">
              <span class="text-zinc-500">&rarr;</span>
              <span>{{ formatTime(event.end_datetime) }}</span>
            </template>
          </div>
          <div v-if="event.venue" class="flex items-center gap-2">
            <MapPin class="w-4 h-4 text-red-400 shrink-0" :stroke-width="2" />
            <a
              v-if="event.venue_url"
              :href="event.venue_url"
              target="_blank"
              rel="noopener noreferrer"
              class="underline underline-offset-2 hover:text-white transition-colors"
            >{{ event.venue }}</a>
            <span v-else>{{ event.venue }}</span>
          </div>
          <div class="flex items-center gap-3 pt-1">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/15 backdrop-blur-sm text-white">
              {{ event.ticket_fee > 0 ? `$${Number(event.ticket_fee).toFixed(2)}` : 'Free Entry' }}
            </span>
            <span v-if="event.additional_ticket_fee > 0" class="text-zinc-400 text-xs">
              + ${{ Number(event.additional_ticket_fee).toFixed(2) }} per extra member
            </span>
          </div>
        </div>
      </div>

      <!-- Countdown Timer -->
      <div v-if="!eventStarted" class="pp-glass-card rounded-3xl p-4 sm:p-5 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 text-center mb-3">
          Event Commences In
        </p>
        <div class="grid grid-cols-4 gap-2 text-center">
          <div v-for="unit in countdown" :key="unit.label" class="bg-gray-100 dark:bg-zinc-800 rounded-2xl p-2 sm:p-3">
            <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tabular-nums leading-none">
              {{ unit.value }}
            </div>
            <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-1">
              {{ unit.label }}
            </div>
          </div>
        </div>
      </div>

      <!-- Agenda -->
      <div v-if="event.agenda" class="pp-glass-card rounded-3xl p-5 shadow-sm space-y-2">
        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Event Agenda
        </h2>
        <p class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
          {{ event.agenda }}
        </p>
      </div>

      <!-- Registration Form -->
      <div class="pp-glass-card rounded-3xl p-5 sm:p-6 shadow-sm">
        <div class="border-b border-gray-100 dark:border-zinc-800 pb-3 mb-4">
          <h2 class="text-base font-extrabold text-gray-900 dark:text-white tracking-tight">
            {{ editing ? 'Edit Your Registration' : 'Register for this Event' }}
          </h2>
          <p v-if="memberMeta && !editing" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">
            Logged in as {{ memberMeta.name }} &middot; Info pre-filled.
          </p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
              Full Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              maxlength="200"
              class="pp-input"
              :readonly="!!memberMeta"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
              Email Address
            </label>
            <input
              v-model="form.email"
              type="email"
              maxlength="150"
              class="pp-input"
              :readonly="!!memberMeta"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
              Phone Number
            </label>
            <AppFormPhoneInput
              v-model="form.phone"
              :disabled="!!memberMeta"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
              Notes / Special Requests
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              maxlength="1000"
              class="pp-textarea"
              placeholder="Dietary requirements, questions, etc."
            />
          </div>

          <!-- Additional Members Builder -->
          <div class="border-t border-gray-100 dark:border-zinc-800 pt-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h3 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">
                  Additional Members / Guests
                </h3>
                <p v-if="event.additional_ticket_fee > 0" class="text-[11px] text-gray-400 mt-0.5">
                  ${{ Number(event.additional_ticket_fee).toFixed(2) }} per additional guest
                </p>
                <p v-else class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">
                  Free for additional guests
                </p>
              </div>

              <button
                type="button"
                class="px-3 py-1.5 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 text-xs font-bold flex items-center gap-1 hover:bg-red-100 transition-colors cursor-pointer"
                @click="addGuest"
              >
                <Plus class="w-3.5 h-3.5" :stroke-width="2.5" />
                <span>Add Guest</span>
              </button>
            </div>

            <div v-for="(guest, idx) in form.guests" :key="idx" class="mb-3 p-3.5 bg-gray-50 dark:bg-zinc-800/50 rounded-2xl space-y-2 border border-gray-100 dark:border-zinc-800">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Guest {{ idx + 1 }}</span>
                <button
                  type="button"
                  class="text-xs font-bold text-red-500 hover:text-red-700 cursor-pointer"
                  @click="removeGuest(idx)"
                >
                  Remove
                </button>
              </div>
              <div>
                <label class="block text-[11px] font-bold text-gray-500 mb-1">Name *</label>
                <input
                  v-model="guest.name"
                  type="text"
                  required
                  maxlength="200"
                  class="pp-input text-xs py-2"
                  placeholder="Guest's full name"
                />
              </div>
            </div>
          </div>

          <!-- Total Fee Display -->
          <div v-if="totalFee > 0" class="bg-gray-50 dark:bg-zinc-800/60 rounded-2xl p-4 space-y-1.5 text-xs">
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
              <span>Main Entry Ticket</span>
              <span>${{ Number(event.ticket_fee).toFixed(2) }}</span>
            </div>
            <div v-if="form.guests.length > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
              <span>{{ form.guests.length }} &times; Guest Pass</span>
              <span>${{ (form.guests.length * Number(event.additional_ticket_fee)).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-black text-sm text-gray-900 dark:text-white border-t border-gray-200 dark:border-zinc-700 pt-2 mt-2">
              <span>Total Fee</span>
              <span>${{ totalFee.toFixed(2) }}</span>
            </div>
          </div>

          <div v-if="errorMessage" class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-3 text-xs font-bold text-red-600 dark:text-red-400">
            {{ errorMessage }}
          </div>

          <div class="flex gap-2 pt-2">
            <button
              v-if="editing"
              type="button"
              class="flex-1 py-3.5 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-gray-300 font-bold rounded-2xl text-xs transition-colors cursor-pointer"
              @click="cancelEdit"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="flex-1 py-3.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-2xl text-xs shadow-md transition-all active:scale-[0.99] disabled:opacity-60 cursor-pointer"
            >
              {{ submitting ? (editing ? 'Saving...' : 'Registering...') : (editing ? 'Save Changes' : 'Confirm Registration &rarr;') }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { X, Calendar, MapPin, Check, Plus } from 'lucide-vue-next';
import { useRoute } from 'vue-router';
import AppFormPhoneInput from '../forms/AppFormPhoneInput.vue';

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
    name:   '',
    email:  '',
    phone:  '',
    notes:  '',
    guests: [],
});

// ── Countdown ─────────────────────────────────────────────
const now            = ref(new Date());
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
        { label: 'Mins',    value: String(minutes).padStart(2, '0') },
        { label: 'Secs',    value: String(seconds).padStart(2, '0') },
    ];
});

const totalFee = computed(() => {
    if (!event.value) return 0;
    return Number(event.value.ticket_fee) + form.value.guests.length * Number(event.value.additional_ticket_fee);
});

// ── Auth helpers ──────────────────────────────────────────
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

// ── Data loading ──────────────────────────────────────────
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

        form.value.name  = data.meta.name || '';
        form.value.email = data.meta.email || '';
        form.value.phone = data.meta.phone_number || '';
    } catch {
        // silent
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
        form.value.notes  = data.data.notes || '';
        form.value.guests = (data.data.guests || []).map(g => ({
            name:  g.name,
            notes: g.notes || '',
        }));
    } catch {
        // silent
    }
}

function addGuest() {
    form.value.guests.push({ name: '', notes: '' });
}

function removeGuest(idx) {
    form.value.guests.splice(idx, 1);
}

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
    form.value.notes  = existingRegistration.value.notes || '';
    form.value.guests = (existingRegistration.value.guests || []).map(g => ({
        name:  g.name,
        notes: g.notes || '',
    }));
}

function formatDatetime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString(undefined, {
        weekday: 'short', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

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
