<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Personal Info -->
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Personal
        </h2>
      </div>
      <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            First Name
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ displayValue(member.first_name) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Last Name
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ displayValue(member.last_name) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Gender
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ normalizedGender }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Date of Birth
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ formatDate(member.date_of_birth) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Age
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ displayValue(member.age) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            NIC
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ displayValue(member.nic) }}
          </dd>
        </div>
      </dl>
    </div>

    <!-- Contact & Access -->
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Contact & Access
        </h2>
      </div>
      <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
        <div class="flex items-center justify-between px-5 py-3 gap-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Email
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right break-all">
            {{ displayValue(member.email) }}
          </dd>
        </div>
        <div class="flex items-start justify-between px-5 py-3 gap-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28 pt-0.5">
            Phone
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            <span>{{ displayValue(member.phone_number) }}</span>
            <span class="ml-1.5 inline-flex items-center gap-1">
              <span v-if="member.allow_sms" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-primary-50 dark:bg-primary-900/25 text-primary-600 dark:text-primary-400 border border-primary-200 dark:border-primary-800" title="Receives SMS">SMS</span>
              <span v-if="member.allow_whatsapp" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-green-50 dark:bg-green-900/25 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800" title="Has WhatsApp">WA</span>
            </span>
          </dd>
        </div>
        <div v-if="!member.allow_whatsapp && member.whatsapp_number" class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            WhatsApp
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ member.whatsapp_number }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Username
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ displayValue(member.username) }}
          </dd>
        </div>
        <div class="flex items-start justify-between px-5 py-3 gap-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28 pt-0.5">
            Address
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right whitespace-pre-line">
            {{ displayValue(member.address) }}
          </dd>
        </div>
      </dl>
    </div>

    <!-- Plan & Billing -->
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Plan & Billing
        </h2>
      </div>
      <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Role
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ displayValue(member.member_role) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Payment Plan
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ displayValue(member.payment_plan) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Monthly Fee
          </dt>
          <dd class="text-sm font-semibold text-secondary-900 dark:text-white text-right">
            {{ formatMoney(member.price) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Admission Fee
          </dt>
          <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">
            {{ formatMoney(member.admission_fee) }}
          </dd>
        </div>
        <div class="flex items-center justify-between px-5 py-3">
          <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">
            Balance
          </dt>
          <dd class="text-sm font-semibold text-secondary-900 dark:text-white text-right">
            {{ formatMoney(member.current_balance) }}
          </dd>
        </div>
      </dl>
    </div>

    <!-- Notes -->
    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Notes
        </h2>
      </div>
      <div class="px-5 py-4">
        <p class="text-sm text-secondary-700 dark:text-secondary-300 whitespace-pre-line leading-relaxed">
          {{ member.comment || 'No notes added for this member.' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useMemberFormatters } from '../../composables/useMemberFormatters';

const props = defineProps({
    member: { type: Object, required: true },
});

const { capitalize, displayValue, formatDate, formatMoney } = useMemberFormatters();

const normalizedGender = computed(() => {
    if (!props.member?.gender) return 'Not provided';
    return capitalize(props.member.gender);
});
</script>
