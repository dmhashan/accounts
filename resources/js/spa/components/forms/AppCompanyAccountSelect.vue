<template>
    <select
        :value="modelValue"
        :disabled="disabled"
        class="h-12 w-full rounded-2xl border border-secondary-300 bg-white px-4 text-sm text-secondary-900 shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-secondary-700 dark:bg-secondary-900 dark:text-white"
        @change="handleChange"
    >
        <option :value="null">{{ placeholder }}</option>
        <option
            v-if="memberId"
            value="member_wallet"
            :disabled="!walletSufficient || walletLoading"
        >Member Wallet — {{ walletLoading ? 'Loading…' : formatBalance(walletBalance) }}{{ !walletSufficient && !walletLoading ? ' (insufficient)' : '' }}</option>
        <option v-for="account in accounts" :key="account.id" :value="account.id">
            {{ account.name }}
        </option>
    </select>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { apiRequest } from '../../composables/useApiClient';

const props = defineProps({
    modelValue: { default: null },
    accounts: { type: Array, default: () => [] },
    /** Pass member ID to enable the "Member Wallet" option */
    memberId: { type: Number, default: null },
    /** Amount that will be deducted — used to disable wallet option when balance is insufficient */
    amount: { type: Number, default: 0 },
    disabled: { type: Boolean, default: false },
    placeholder: { type: String, default: 'Select account...' },
});

const emit = defineEmits(['update:modelValue']);

const walletBalance = ref(0);
const walletLoading = ref(false);

const walletSufficient = computed(() => {
    if (!props.memberId) return false;
    if (props.amount <= 0) return walletBalance.value > 0;
    return walletBalance.value >= props.amount;
});

function formatBalance(value) {
    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function handleChange(event) {
    const val = event.target.value;
    if (!val) {
        emit('update:modelValue', null);
        return;
    }
    if (val === 'member_wallet') {
        emit('update:modelValue', 'member_wallet');
        return;
    }
    emit('update:modelValue', Number(val));
}

async function fetchWalletBalance(memberId) {
    if (!memberId) {
        walletBalance.value = 0;
        return;
    }
    walletLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${memberId}`);
        walletBalance.value = parseFloat(res.data?.current_balance ?? 0);
    } catch {
        walletBalance.value = 0;
    } finally {
        walletLoading.value = false;
    }
}

watch(() => props.memberId, (newId) => {
    // If wallet was selected and member changed, clear it
    if (props.modelValue === 'member_wallet') {
        emit('update:modelValue', null);
    }
    fetchWalletBalance(newId);
}, { immediate: true });
</script>
