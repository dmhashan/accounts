<template>
  <div class="app-overlay-panel rounded-2xl w-full max-w-md my-8">
    <div class="flex items-center justify-between p-5 border-b border-secondary-200 dark:border-secondary-700">
      <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
        Record Other Payment
      </h3>
      <button type="button" class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200" @click="$emit('cancel')">
        <X class="w-5 h-5" />
      </button>
    </div>

    <form class="p-5 space-y-4" @submit.prevent="submit">
      <div v-if="error" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ error }}
      </div>

      <!-- Member selector — shown only when memberId is not fixed -->
      <div v-if="!memberId">
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Member <span class="text-secondary-400 font-normal">(Optional)</span></label>
        <AppSearchableDropdown
          v-model="form.member_id"
          :options="members"
          :option-label="o => o.label"
          :option-key="o => o.id"
          placeholder="Select member..."
          search-placeholder="Search member..."
          no-results-text="No members found."
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Payment Method <span class="text-red-500">*</span></label>
        <AppCompanyAccountSelect
          v-model="accountValue"
          :accounts="accounts"
          :member-id="effectiveMemberId ?? undefined"
          :amount="parseFloat(form.amount) || 0"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Payment Plan <span class="text-secondary-400 font-normal">(Optional)</span></label>
        <select
          v-model="form.plan_id"
          class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          @change="onPlanSelect"
        >
          <option :value="null">
            No plan
          </option>
          <option v-for="p in plans" :key="p.id" :value="p.id">
            {{ p.name }} — {{ formatPlanDuration(p) }} ({{ money(p.price) }})
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Amount <span class="text-red-500">*</span></label>
        <input
          v-model="form.amount"
          type="number"
          min="0.01"
          step="0.01"
          required
          class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Payment Date <span class="text-red-500">*</span></label>
        <AppFormDateInput
          v-model="form.payment_date"
          required
          input-class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <template v-if="form.plan_id">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Membership Start</label>
            <AppFormDateInput
              v-model="form.start_date"
              input-class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              @change="onStartDateChange"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Membership End</label>
            <AppFormDateInput
              v-model="form.end_date"
              input-class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </div>
      </template>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Reference <span class="text-secondary-400 font-normal">(Optional)</span></label>
        <input
          v-model="form.reference_number"
          type="text"
          maxlength="255"
          placeholder="Receipt ID, transaction reference, etc."
          class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">Notes <span class="text-secondary-400 font-normal">(Optional)</span></label>
        <textarea
          v-model="form.notes"
          rows="3"
          maxlength="1000"
          class="app-form-control w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
        />
      </div>

      <div class="flex justify-end gap-2 pt-1">
        <button
          type="button"
          class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800"
          @click="$emit('cancel')"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 transition-colors"
          :disabled="saving || !form.amount || !accountValue"
        >
          {{ saving ? 'Saving...' : 'Record Payment' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import AppCompanyAccountSelect from './AppCompanyAccountSelect.vue';
import AppSearchableDropdown from './AppSearchableDropdown.vue';
import AppFormDateInput from './AppFormDateInput.vue';
import { formatPlanDuration, calcPlanEndDate } from '../../composables/usePlanDuration.js';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    memberId: { type: [Number, String], default: null },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

function todayStr() {
    return new Date().toISOString().slice(0, 10);
}

function money(value) {
    return Number(value || 0).toFixed(2);
}

const form = ref({ member_id: null, plan_id: null, amount: '', payment_date: todayStr(), start_date: '', end_date: '', reference_number: '', notes: '' });
const accountValue = ref(null);

const effectiveMemberId = computed(() => props.memberId ? Number(props.memberId) : (form.value.member_id ?? null));
const selectedPlan = computed(() => props.plans.find(p => p.id === form.value.plan_id) || null);

// Set first account when accounts become available
watch(() => props.accounts, (accs) => {
    if (!accountValue.value && accs.length > 0) {
        accountValue.value = accs[0].id;
    }
}, { immediate: true });

function onPlanSelect() {
    const plan = selectedPlan.value;
    if (plan) {
        if (!form.value.amount) form.value.amount = String(plan.price);
        if (!form.value.start_date) form.value.start_date = form.value.payment_date || todayStr();
        form.value.end_date = calcPlanEndDate(form.value.start_date, plan);
    }
}

function onStartDateChange() {
    const plan = selectedPlan.value;
    if (plan) form.value.end_date = calcPlanEndDate(form.value.start_date, plan);
}

function submit() {
    if (!form.value.amount || !accountValue.value) return;
    const isWallet = accountValue.value === 'member_wallet';
    emit('submit', {
        member_id: effectiveMemberId.value || null,
        company_account_id: isWallet ? null : accountValue.value,
        payment_method: isWallet ? 'member_wallet' : 'cash',
        payment_plan_id: form.value.plan_id || null,
        amount: parseFloat(form.value.amount),
        payment_date: form.value.payment_date,
        start_date: form.value.start_date || null,
        end_date: form.value.end_date || null,
        reference_number: form.value.reference_number || null,
        notes: form.value.notes || null,
    });
}
</script>
