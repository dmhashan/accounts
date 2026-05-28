<template>
  <div>
    <!-- Avatar block -->
    <div class="flex flex-col items-center pb-6 pt-4">
      <MemberAvatar
        :src="meta.profile_photo_url"
        :initials="initials"
        size="2xl"
        shape="circle"
        class="shadow-md border-4 border-white"
      />
      <h2 class="mt-4 text-xl font-bold text-gray-900 tracking-tight">
        {{ meta.name }}
      </h2>
      <p class="text-sm text-gray-400 mt-0.5">
        @{{ meta.username }}
      </p>
      <span v-if="meta.member_role" class="mt-3 text-xs font-semibold text-gray-600 bg-white border border-gray-200 px-3 py-1 rounded-full shadow-sm">
        {{ meta.member_role }}
      </span>
    </div>

    <!-- Personal info -->
    <section class="mb-4">
      <div class="flex items-center justify-between px-1 mb-3">
        <h3 class="text-base font-bold text-gray-900">
          Personal info
        </h3>
      </div>
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
        <div class="flex items-center gap-4 px-5 py-4">
          <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
            <User class="w-4 h-4 text-gray-500" :stroke-width="1.8" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-400 leading-none mb-0.5">
              Name
            </p>
            <p class="text-sm font-semibold text-gray-900 truncate">
              {{ meta.name }}
            </p>
          </div>
        </div>
        <div v-if="meta.email" class="flex items-center gap-4 px-5 py-4">
          <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
            <Mail class="w-4 h-4 text-gray-500" :stroke-width="1.8" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-400 leading-none mb-0.5">
              E-mail
            </p>
            <p class="text-sm font-semibold text-gray-900 truncate">
              {{ meta.email }}
            </p>
          </div>
        </div>
        <div v-if="meta.phone_number" class="flex items-center gap-4 px-5 py-4">
          <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
            <Phone class="w-4 h-4 text-gray-500" :stroke-width="1.8" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-400 leading-none mb-0.5">
              Phone number
            </p>
            <p class="text-sm font-semibold text-gray-900 truncate">
              {{ meta.phone_number }}
            </p>
          </div>
        </div>
        <div v-if="meta.gender" class="flex items-center gap-4 px-5 py-4">
          <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
            <PersonStanding class="w-4 h-4 text-gray-500" :stroke-width="1.8" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-400 leading-none mb-0.5">
              Gender
            </p>
            <p class="text-sm font-semibold text-gray-900">
              {{ capitalize(meta.gender) }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-4 px-5 py-4">
          <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
            <Calendar class="w-4 h-4 text-gray-500" :stroke-width="1.8" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-400 leading-none mb-0.5">
              Member since
            </p>
            <p class="text-sm font-semibold text-gray-900">
              {{ meta.joined_date ?? '-' }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Account quick stats -->
    <section class="mb-4">
      <h3 class="text-base font-bold text-gray-900 px-1 mb-3">
        Account info
      </h3>
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
        <div class="flex items-center gap-4 px-5 py-4">
          <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
            <Zap class="w-4 h-4 text-gray-500" :stroke-width="1.8" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-400 leading-none mb-0.5">
              Workout Plans
            </p>
            <p class="text-sm font-semibold text-gray-900">
              {{ workoutsData.length }} assigned
            </p>
          </div>
          <span v-if="workoutsData.length" class="text-xs font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">Active</span>
        </div>
        <div class="flex items-center gap-4 px-5 py-4">
          <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
            <ClipboardList class="w-4 h-4 text-gray-500" :stroke-width="1.8" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-400 leading-none mb-0.5">
              Transactions
            </p>
            <p class="text-sm font-semibold text-gray-900">
              {{ salesData.length }} total
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Sign out -->
    <section class="mb-6">
      <button
        type="button"
        class="w-full py-3.5 rounded-2xl border border-red-200 bg-red-50 text-red-600 text-sm font-bold hover:bg-red-100 active:bg-red-200 transition-colors"
        @click="$emit('logout')"
      >
        Sign out
      </button>
    </section>
  </div>
</template>

<script setup>
import { User, Mail, Phone, PersonStanding, Calendar, Zap, ClipboardList } from 'lucide-vue-next';
import MemberAvatar from '../../../components/ui/MemberAvatar.vue';

defineProps({
    meta:         { type: Object, default: () => ({}) },
    initials:     { type: String, default: '' },
    workoutsData: { type: Array,  default: () => [] },
    salesData:    { type: Array,  default: () => [] },
});

defineEmits(['logout']);

function capitalize(val) {
    if (!val) return '-';
    return val.charAt(0).toUpperCase() + val.slice(1);
}
</script>
