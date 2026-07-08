<template>
  <div class="app-overlay-panel rounded-2xl w-full max-w-md my-8">
    <div class="flex items-start justify-between p-5 border-b border-secondary-200 dark:border-secondary-700">
      <div>
        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
          Record Member Payment
        </h3>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">
          Select the plan and record the payment.
        </p>
      </div>
      <button type="button" class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 mt-0.5 shrink-0" @click="$emit('cancel')">
        <X class="w-5 h-5" />
      </button>
    </div>

    <form class="p-5 space-y-4" @submit.prevent="submit">
      <div v-if="error" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ error }}
      </div>

      <!-- Member selector — shown only when memberId is not fixed -->
      <div v-if="!memberId">
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Member <span class="text-red-500">*</span></label>
        <AppSearchableDropdown
          v-model="form.member_id"
          :options="members"
          :option-label="o => o.label"
          :option-key="o => o.id"
          placeholder="Select member..."
          search-placeholder="Search member..."
          no-results-text="No members found."
          @update:model-value="onMemberSelect"
        />
      </div>

      <div v-if="memberInfoLoading" class="text-center py-3 text-sm text-secondary-500 dark:text-secondary-400">
        Loading member info...
      </div>

      <div v-else-if="memberInfo" class="rounded-xl border border-secondary-200 dark:border-secondary-700 overflow-hidden text-sm">
        <div class="bg-secondary-50 dark:bg-secondary-800/60 px-4 py-3 flex flex-wrap gap-2">
          <span v-if="memberInfo.member_id" class="inline-flex items-center text-xs font-semibold bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 px-2.5 py-1 rounded-full">
            Member ID: {{ memberInfo.member_id }}
          </span>
          <span v-if="memberInfo.current_plan" class="inline-flex items-center text-xs font-semibold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 px-2.5 py-1 rounded-full">
            {{ memberInfo.current_plan.name }}
          </span>
        </div>
        <div class="px-4 py-3 space-y-2">
          <div v-if="memberInfo.name" class="flex justify-between gap-3">
            <span class="text-secondary-500 dark:text-secondary-400 shrink-0">Member:</span>
            <span class="font-medium text-secondary-900 dark:text-white text-right">{{ memberInfo.name }}</span>
          </div>
          <div v-if="memberInfo.address" class="flex justify-between gap-3">
            <span class="text-secondary-500 dark:text-secondary-400 shrink-0">Address:</span>
            <span class="font-medium text-secondary-900 dark:text-white text-right">{{ memberInfo.address }}</span>
          </div>
          <div v-if="memberInfo.joined_date" class="flex justify-between gap-3">
            <span class="text-secondary-500 dark:text-secondary-400 shrink-0">Date of Join:</span>
            <span class="font-medium text-secondary-900 dark:text-white">{{ formatDate(memberInfo.joined_date) }}</span>
          </div>
          <div v-if="memberInfo.last_payment?.payment_date" class="flex justify-between gap-3">
            <span class="text-secondary-500 dark:text-secondary-400 shrink-0">Last Payment:</span>
            <span class="font-medium text-secondary-900 dark:text-white">{{ formatDate(memberInfo.last_payment.payment_date) }}</span>
          </div>
          <div v-if="memberInfo.last_payment?.end_date" class="flex justify-between gap-3">
            <span class="text-secondary-500 dark:text-secondary-400 shrink-0">Due Date:</span>
            <span class="font-medium text-secondary-900 dark:text-white">{{ formatDate(memberInfo.last_payment.end_date) }}</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Membership <span class="text-red-500">*</span></label>
          <select
            v-model="form.plan_id"
            required
            class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option :value="null" disabled>
              Select plan
            </option>
            <option v-for="p in plans" :key="p.id" :value="p.id">
              {{ p.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Duration</label>
          <div class="px-3 py-2 h-[38px] flex items-center rounded-lg border border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800 text-sm text-secondary-700 dark:text-secondary-300">
            {{ selectedPlan ? formatPlanDuration(selectedPlan) : '—' }}
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Membership Payment <span class="text-red-500">*</span></label>
        <input
          v-model="form.amount"
          type="number"
          min="0.01"
          step="0.01"
          required
          class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Payment Date <span class="text-red-500">*</span></label>
          <AppFormDateInput
            v-model="form.payment_date"
            :max="todayStr()"
            required
            input-class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Effective Date <span class="text-red-500">*</span></label>
          <AppFormDateInput
            v-model="form.start_date"
            required
            input-class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>
      </div>


      <div v-if="selectedPlan && form.start_date" class="p-3 rounded-lg border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-950/20 text-xs text-primary-700 dark:text-primary-300">
        This payment valid from {{ startDate }} to {{ endDate }}. dont miss next payment date is {{ nextPaymentDate }}
      </div>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Note (Optional)</label>
        <textarea
          v-model="form.notes"
          rows="3"
          maxlength="1000"
          placeholder="Add a note about this payment..."
          class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
        />
      </div>

      <div class="grid grid-cols-2 gap-2">
        <button
          type="button"
          class="w-full py-2.5 border border-secondary-300 dark:border-secondary-600 text-secondary-800 dark:text-secondary-100 rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 hover:bg-secondary-50 dark:hover:bg-secondary-800"
          :disabled="saving || !effectiveMemberId || !form.plan_id"
          @click="submitOutstanding"
        >
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
        <button
          type="button"
          class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50"
          :disabled="saving || !effectiveMemberId || !form.plan_id"
          @click="openPayNow"
        >
          {{ saving ? 'Processing...' : 'Pay Now' }}
        </button>
      </div>
    </form>

    <!-- Pay Now Modal -->
    <div v-if="payNowOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closePayNow" />
      <div class="relative z-10 w-full max-w-md rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-4 md:p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Select Payment Method
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
              Choose how this membership payment is collected.
            </p>
          </div>
          <button type="button" class="text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closePayNow">
            ✕
          </button>
        </div>

        <div class="mt-4">
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Payment Method</label>
          <AppPaymentMethodSelect
            v-model="accountValue"
            :methods="paymentMethods"
            :member-id="effectiveMemberId ?? undefined"
            :amount="parseFloat(form.amount) || 0"
          />
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closePayNow">
            Cancel
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50"
            :disabled="saving || !accountValue"
            @click="submitPaid"
          >
            {{ saving ? 'Processing...' : 'Confirm Payment' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';
import AppPaymentMethodSelect from './AppPaymentMethodSelect.vue';
import AppSearchableDropdown from './AppSearchableDropdown.vue';
import AppFormDateInput from './AppFormDateInput.vue';
import { formatPlanDuration, calcPlanEndDate, calcNextStartDate } from '../../composables/usePlanDuration.js';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    memberId: { type: [Number, String], default: null },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const { formatDate } = useMemberFormatters();

function todayStr() {
    return new Date().toISOString().slice(0, 10);
}

const form = ref({ member_id: null, plan_id: null, amount: '', payment_date: todayStr(), start_date: todayStr(), notes: '' });
const accountValue = ref(null);
const memberInfoLoading = ref(false);
const memberInfo = ref(null);

const effectiveMemberId = computed(() => props.memberId ? Number(props.memberId) : (form.value.member_id ?? null));
const selectedPlan = computed(() => props.plans.find(p => p.id === form.value.plan_id) || null);
const startDate = computed(() => form.value.start_date || todayStr());
const endDate = computed(() => calcPlanEndDate(startDate.value, selectedPlan.value));
const nextPaymentDate = computed(() => calcNextStartDate(startDate.value, selectedPlan.value));

watch(() => form.value.plan_id, () => {
    if (selectedPlan.value) {
        form.value.amount = String(selectedPlan.value.price.toFixed(2));
    }
});

// Set first payment method when methods become available
watch(() => props.paymentMethods, (methods) => {
    if (!accountValue.value && methods.length > 0) {
        accountValue.value = methods[0].id;
    }
}, { immediate: true });

// Auto-load member info when a fixed memberId is provided
watch(() => props.memberId, async (id) => {
    if (id) await loadMemberInfo(Number(id));
}, { immediate: true });

async function loadMemberInfo(id) {
    memberInfoLoading.value = true;
    memberInfo.value = null;
    try {
        const info = await apiRequest(`/api/payments/member/${id}/payment-info`);
        memberInfo.value = info;
        // Default start_date to the next start date, and payment_date to today
        form.value.start_date = info.next_start_date || todayStr();
        form.value.payment_date = todayStr();
        if (info.current_plan) {
            form.value.plan_id = info.current_plan.id;
            form.value.amount = String(info.current_plan.price.toFixed(2));
        }
    } catch { /* ignore */ } finally {
        memberInfoLoading.value = false;
    }
}

async function onMemberSelect(id) {
    memberInfo.value = null;
    form.value.plan_id = null;
    form.value.amount = '';
    form.value.start_date = todayStr();
    form.value.payment_date = todayStr();
    if (id) await loadMemberInfo(id);
}

const payNowOpen = ref(false);

function openPayNow() {
    if (!effectiveMemberId.value || !form.value.plan_id) return;
    if (!accountValue.value && props.paymentMethods.length > 0) {
        accountValue.value = props.paymentMethods[0].id;
    }
    payNowOpen.value = true;
}

function closePayNow() {
    payNowOpen.value = false;
}

function submitOutstanding() {
    if (!effectiveMemberId.value || !form.value.plan_id || !form.value.amount) return;
    emit('submit', {
        member_id: effectiveMemberId.value,
        payment_plan_id: form.value.plan_id,
        amount: parseFloat(form.value.amount),
        payment_date: form.value.payment_date,
        start_date: startDate.value,
        end_date: endDate.value,
        notes: form.value.notes || null,
        is_paid: false,
    });
}

function submitPaid() {
    if (!effectiveMemberId.value || !form.value.plan_id || !form.value.amount || !accountValue.value) return;
    const isWallet = accountValue.value === 'member_wallet';
    emit('submit', {
        member_id: effectiveMemberId.value,
        payment_method_id: isWallet ? null : accountValue.value,
        payment_method: isWallet ? 'member_wallet' : null,
        payment_plan_id: form.value.plan_id,
        amount: parseFloat(form.value.amount),
        payment_date: form.value.payment_date,
        start_date: startDate.value,
        end_date: endDate.value,
        notes: form.value.notes || null,
        is_paid: true,
    });
    payNowOpen.value = false;
}
</script>
