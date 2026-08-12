<template>
  <div class="space-y-5 pb-6">
    <!-- Header Avatar & Profile Hero -->
    <div class="flex flex-col items-center pt-2 pb-2 text-center">
      <div class="relative p-1.5 rounded-full bg-gradient-to-tr from-red-500 via-rose-500 to-amber-500 shadow-xl">
        <MemberAvatar
          :src="meta.profile_photo_url"
          :initials="initials"
          size="2xl"
          shape="circle"
          class="border-4 border-white dark:border-zinc-900 shadow-inner"
        />
      </div>

      <h2 class="mt-3.5 text-2xl font-black text-gray-900 dark:text-white tracking-tight">
        {{ meta.name }}
      </h2>

      <div class="flex items-center gap-2 mt-2">
        <span
          v-if="meta.member_role"
          class="px-3 py-0.5 rounded-full text-xs font-bold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20"
        >
          {{ meta.member_role }}
        </span>

        <span
          v-if="meta.member_code || meta.member_id"
          class="px-3 py-0.5 rounded-full text-xs font-mono font-bold bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-300"
        >
          {{ meta.member_code || meta.member_id }}
        </span>
      </div>
    </div>

    <!-- Personal Information Card -->
    <section>
      <div class="flex items-center gap-2 mb-2.5 px-1">
        <User class="w-4 h-4 text-red-500" :stroke-width="2.2" />
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Personal Information
        </h3>
      </div>

      <div class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <div class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400 flex items-center justify-center shrink-0">
            <User class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Full Name
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white truncate mt-0.5">
              {{ meta.name }}
            </p>
          </div>
        </div>

        <div v-if="meta.phone_number" class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400 flex items-center justify-center shrink-0">
            <Phone class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Mobile Phone
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white truncate mt-0.5">
              {{ meta.phone_number }}
            </p>
          </div>
        </div>

        <div v-if="meta.email" class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400 flex items-center justify-center shrink-0">
            <Mail class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Email Address
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white truncate mt-0.5">
              {{ meta.email }}
            </p>
          </div>
        </div>

        <div v-if="meta.gender" class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400 flex items-center justify-center shrink-0">
            <PersonStanding class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Gender
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
              {{ capitalize(meta.gender) }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-gray-400 flex items-center justify-center shrink-0">
            <Calendar class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Member Since
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
              {{ meta.joined_date || '-' }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Membership & Activity Stats -->
    <section>
      <div class="flex items-center gap-2 mb-2.5 px-1">
        <Activity class="w-4 h-4 text-emerald-500" :stroke-width="2.2" />
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Account Overview
        </h3>
      </div>

      <div class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <div class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-red-50 dark:bg-red-950/40 text-red-500 flex items-center justify-center shrink-0">
            <Zap class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Assigned Workout Plans
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
              {{ workoutsData.length }} program{{ workoutsData.length === 1 ? '' : 's' }}
            </p>
          </div>
          <span v-if="workoutsData.length" class="text-[10px] font-extrabold text-white bg-red-500 px-2 py-0.5 rounded-full">
            Active
          </span>
        </div>

        <div class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500 flex items-center justify-center shrink-0">
            <Receipt class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Total Payments &amp; Invoices
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
              {{ (paymentsData.length || 0) + (salesData.length || 0) }} record{{ ((paymentsData.length || 0) + (salesData.length || 0)) === 1 ? '' : 's' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3.5 px-4 sm:px-5 py-3.5">
          <div class="w-9 h-9 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-500 flex items-center justify-center shrink-0">
            <CreditCard class="w-4 h-4" :stroke-width="2" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Prepaid Wallet
            </p>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
              {{ formatMoney(meta.current_balance ?? 0) }} available
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Sign Out Action -->
    <section class="pt-2">
      <button
        type="button"
        class="w-full py-3.5 rounded-2xl border border-red-200 dark:border-red-900/60 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 text-sm font-bold hover:bg-red-100 dark:hover:bg-red-950/50 active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer"
        @click="$emit('logout')"
      >
        <LogOut class="w-4 h-4" :stroke-width="2.2" />
        <span>Sign Out of Member Portal</span>
      </button>
    </section>
  </div>
</template>

<script setup>
import {
    User,
    Mail,
    Phone,
    PersonStanding,
    Calendar,
    Zap,
    Receipt,
    CreditCard,
    Activity,
    LogOut,
} from 'lucide-vue-next';
import MemberAvatar from '../../../components/ui/MemberAvatar.vue';

defineProps({
    meta:         { type: Object, default: () => ({}) },
    initials:     { type: String, default: '' },
    workoutsData: { type: Array,  default: () => [] },
    salesData:    { type: Array,  default: () => [] },
    paymentsData: { type: Array,  default: () => [] },
});

defineEmits(['logout']);

function capitalize(val) {
    if (!val) return '-';
    return val.charAt(0).toUpperCase() + val.slice(1);
}

function formatMoney(val) {
    const n = parseFloat(val ?? 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
