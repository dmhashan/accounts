<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-lg overflow-hidden border border-secondary-200 dark:border-secondary-700 mx-0">
    <!-- Gradient banner -->
    <div class="relative bg-gradient-to-br from-primary-700 via-primary-600 to-indigo-600 px-4 pt-4 pb-7">
      <!-- Decorative shapes -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-8 -right-8 w-48 h-48 rounded-full bg-white/5" />
        <div class="absolute -bottom-6 left-8 w-32 h-32 rounded-full bg-white/5" />
        <div class="absolute top-4 right-28 w-16 h-16 rounded-full bg-white/5" />
      </div>

      <!-- Top bar: back + actions -->
      <div class="relative flex items-center justify-between gap-2 mb-5">
        <RouterLink to="/members" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/15 hover:bg-white/25 border border-white/20 text-white transition-colors" title="Back to Members">
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M15 19l-7-7 7-7"
          /></svg>
        </RouterLink>

        <div v-if="permissions.edit || permissions.delete" class="flex flex-wrap items-center justify-end gap-1.5">
          <RouterLink
            v-if="permissions.edit"
            :to="`/members/${member.id}/edit`"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors"
          >
            <svg
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            ><path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
            /></svg>
            Edit
          </RouterLink>
          <button
            v-if="permissions.edit"
            type="button"
            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="Boolean(actionInProgress)"
            @click="$emit('toggle-status')"
          >
            {{ actionInProgress === 'status' ? '...' : activeActionLabel }}
          </button>
          <button
            v-if="permissions.edit"
            type="button"
            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="Boolean(actionInProgress)"
            @click="$emit('toggle-verification')"
          >
            {{ actionInProgress === 'verification' ? '...' : verificationActionLabel }}
          </button>
          <button
            v-if="permissions.delete"
            type="button"
            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-500/30 hover:bg-red-500/50 border border-red-300/30 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="Boolean(actionInProgress)"
            @click="$emit('remove')"
          >
            {{ actionInProgress === 'delete' ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>

      <!-- Avatar + name on banner -->
      <div class="relative flex flex-col sm:flex-row sm:items-end gap-3">
        <MemberAvatarUploader
          :member-id="member.id"
          :photo-url="member.profile_photo_url"
          :initials="initials"
          :can-edit="permissions.edit"
          @update:photo-url="$emit('update:photo-url', $event)"
        />
        <div class="sm:pb-0.5 flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-bold text-white leading-tight">
              {{ fullName }}
            </h1>
            <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-white/15 border border-white/25 text-white">{{ normalizedGender }}</span>
          </div>
          <p class="mt-1 text-xs text-primary-100/90 tracking-wide">
            {{ member.member_id }}<span v-if="member.username" class="ml-2 opacity-70">@{{ member.username }}</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Status badges row -->
    <div class="px-5 py-3 flex flex-wrap gap-1.5 bg-secondary-50 dark:bg-secondary-800/60 border-b border-secondary-100 dark:border-secondary-800">
      <span
        class="px-2.5 py-1 text-[11px] font-semibold rounded-full border"
        :class="member.is_active
          ? 'bg-green-50 dark:bg-green-900/25 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
          : 'bg-red-50 dark:bg-red-900/25 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800'"
      >
        {{ member.is_active ? '● Active' : '● Inactive' }}
      </span>
      <span
        class="px-2.5 py-1 text-[11px] font-semibold rounded-full border"
        :class="member.is_verified
          ? 'bg-blue-50 dark:bg-blue-900/25 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800'
          : 'bg-amber-50 dark:bg-amber-900/25 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800'"
      >
        {{ member.is_verified ? '✓ Verified' : '! Unverified' }}
      </span>
      <span v-if="member.is_temp" class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-orange-50 dark:bg-orange-900/25 border border-orange-200 dark:border-orange-800 text-orange-700 dark:text-orange-400">Temp</span>
      <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-secondary-100 dark:bg-secondary-700 border border-secondary-200 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300">{{ displayValue(member.member_role) }}</span>
      <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-secondary-100 dark:bg-secondary-700 border border-secondary-200 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300">{{ displayValue(member.payment_plan) }}</span>
    </div>

    <!-- Wallet balance -->
    <div class="mx-4 my-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <div class="shrink-0 w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-700/60 flex items-center justify-center">
          <svg
            class="w-5 h-5 text-emerald-600 dark:text-emerald-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
          /></svg>
        </div>
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
            Wallet Balance
          </p>
          <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-300 leading-tight">
            {{ formatMoney(member.current_balance) }}
          </p>
        </div>
      </div>
      <div v-if="permissions.edit" class="flex gap-2 sm:shrink-0">
        <button
          type="button"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white transition-colors shadow-sm"
          @click="$emit('open-topup')"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M12 4v16m8-8H4"
          /></svg>
          Top Up
        </button>
        <button
          type="button"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-xl bg-violet-600 hover:bg-violet-700 text-white transition-colors shadow-sm"
          @click="$emit('open-redeem')"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
          /></svg>
          Redeem Voucher
        </button>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="grid grid-cols-3 border-t border-secondary-100 dark:border-secondary-800">
      <div class="px-4 py-3.5 text-center border-r border-secondary-100 dark:border-secondary-800">
        <p class="text-[11px] text-secondary-400 dark:text-secondary-500 uppercase tracking-wide">
          Plan
        </p>
        <p class="mt-0.5 text-sm font-bold text-secondary-900 dark:text-white">
          {{ formatMoney(member.price) }}<span class="text-xs font-normal text-secondary-400 dark:text-secondary-500">/mo</span>
        </p>
      </div>
      <div class="px-4 py-3.5 text-center border-r border-secondary-100 dark:border-secondary-800">
        <p class="text-[11px] text-secondary-400 dark:text-secondary-500 uppercase tracking-wide">
          Joined
        </p>
        <p class="mt-0.5 text-sm font-semibold text-secondary-900 dark:text-white">
          {{ formatDate(member.joined_date) }}
        </p>
      </div>
      <div class="px-4 py-3.5 text-center">
        <p class="text-[11px] text-secondary-400 dark:text-secondary-500 uppercase tracking-wide">
          Member Since
        </p>
        <p class="mt-0.5 text-sm font-semibold text-secondary-900 dark:text-white">
          {{ formatDate(member.created_at) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import MemberAvatarUploader from '../MemberAvatarUploader.vue';
import { useMemberFormatters } from '../../composables/useMemberFormatters';

const props = defineProps({
    member: { type: Object, required: true },
    permissions: { type: Object, required: true },
    actionInProgress: { type: String, default: '' },
});

defineEmits(['toggle-status', 'toggle-verification', 'remove', 'open-topup', 'open-redeem', 'update:photo-url']);

const { capitalize, displayValue, formatDate, formatMoney } = useMemberFormatters();

const fullName = computed(() => {
    const firstName = (props.member.first_name || '').trim();
    const lastName = (props.member.last_name || '').trim();
    if (firstName || lastName) return `${firstName} ${lastName}`.trim();
    return props.member.name || 'Member';
});

const initials = computed(() => {
    const value = fullName.value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
    return value || 'MB';
});

const normalizedGender = computed(() => {
    if (!props.member?.gender) return 'Not provided';
    return capitalize(props.member.gender);
});

const activeActionLabel = computed(() => (props.member?.is_active ? 'Deactivate' : 'Activate'));
const verificationActionLabel = computed(() => (props.member?.is_verified ? 'Unverify' : 'Verify'));
</script>
