<template>
    <div ref="rootRef" class="relative w-full">
        <!-- Trigger -->
        <button
            ref="triggerRef"
            type="button"
            :disabled="disabled"
            class="group h-12 w-full rounded-2xl border border-secondary-300 bg-white px-4 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition-all
                   flex items-center gap-3
                   hover:border-secondary-400 hover:shadow-[0_2px_6px_rgba(15,23,42,0.08)]
                   focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10
                   disabled:cursor-not-allowed disabled:opacity-60
                   dark:border-secondary-700 dark:bg-secondary-900 dark:hover:border-secondary-600"
            :class="open ? 'border-primary-500 ring-4 ring-primary-500/10 shadow-[0_2px_6px_rgba(15,23,42,0.08)]' : ''"
            @click="toggle"
        >
            <!-- Icon container -->
            <span class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-xl transition-all duration-200"
                  :class="selectedValue === 'member_wallet'
                      ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'
                      : selectedValue
                          ? 'bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400'
                          : 'bg-secondary-100 text-secondary-400 dark:bg-secondary-800 dark:text-secondary-500'">
                <Wallet v-if="selectedValue === 'member_wallet'" class="w-3.5 h-3.5" />
                <CreditCard v-else class="w-3.5 h-3.5" />
            </span>

            <!-- Label -->
            <span class="flex-1 text-left truncate font-medium"
                  :class="selectedLabel ? 'text-secondary-900 dark:text-white' : 'text-secondary-400 dark:text-secondary-500 font-normal'">
                {{ selectedLabel || placeholder }}
            </span>

            <!-- Wallet balance badge (when wallet selected) -->
            <span v-if="selectedValue === 'member_wallet'"
                  class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-xl border transition-colors"
                  :class="walletSufficient
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800'
                      : 'bg-red-50 text-red-600 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'">
                <Loader2 v-if="walletLoading" class="w-3 h-3 animate-spin" />
                <template v-else>{{ formatBalance(walletBalance) }}</template>
            </span>

            <!-- Clear button -->
            <button
                v-if="!required && selectedValue !== null && !disabled"
                type="button"
                class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full text-secondary-400 hover:text-secondary-600 hover:bg-secondary-100 dark:text-secondary-500 dark:hover:text-secondary-200 dark:hover:bg-secondary-700 transition-colors"
                @click.stop="select(null)"
                aria-label="Clear selection"
            >
                <X class="w-3 h-3" />
            </button>

            <!-- Chevron -->
            <ChevronDown class="flex-shrink-0 w-4 h-4 text-secondary-400 transition-transform duration-200 dark:text-secondary-500"
                         :class="open ? 'rotate-180 text-primary-500 dark:text-primary-400' : ''" />
        </button>

        <!-- Dropdown panel -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out origin-top"
                enter-from-class="opacity-0 scale-[0.97] -translate-y-1"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in origin-top"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-[0.97] -translate-y-1"
            >
                <div
                    v-if="open"
                    ref="panelRef"
                    class="fixed z-[9999] flex flex-col overflow-hidden rounded-2xl border border-secondary-200/80 bg-white shadow-2xl ring-1 ring-secondary-900/5 dark:border-secondary-700/80 dark:bg-secondary-900 dark:ring-white/5"
                    :style="panelStyle"
                >
                    <!-- Member Wallet section -->
                    <div v-if="memberId" class="flex-shrink-0">
                        <button
                            type="button"
                            :disabled="!walletSufficient || walletLoading"
                            class="relative w-full flex items-center gap-3 px-4 py-3.5 text-sm text-left transition-all duration-150
                                   hover:bg-secondary-50 dark:hover:bg-secondary-800/60
                                   disabled:cursor-not-allowed disabled:opacity-50
                                   border-l-2"
                            :class="selectedValue === 'member_wallet'
                                ? 'bg-emerald-50/60 border-l-emerald-500 dark:bg-emerald-900/10 dark:border-l-emerald-500'
                                : 'border-l-transparent'"
                            @click="select('member_wallet')"
                        >
                            <!-- Wallet icon -->
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl transition-colors"
                                  :class="walletSufficient
                                      ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'
                                      : 'bg-secondary-100 text-secondary-400 dark:bg-secondary-800 dark:text-secondary-500'">
                                <Wallet class="w-4 h-4" />
                            </span>

                            <!-- Text -->
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-secondary-800 dark:text-secondary-100 text-sm leading-tight">Member Wallet</p>
                                <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5 leading-tight">Pay from member balance</p>
                            </div>

                            <!-- Balance chip -->
                            <span class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-xl border"
                                  :class="walletLoading
                                      ? 'bg-secondary-100 text-secondary-500 border-secondary-200 dark:bg-secondary-800 dark:text-secondary-400 dark:border-secondary-700'
                                      : walletSufficient
                                          ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800'
                                          : 'bg-red-50 text-red-600 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'">
                                <Loader2 v-if="walletLoading" class="w-3 h-3 animate-spin" />
                                <template v-else>
                                    {{ formatBalance(walletBalance) }}
                                    <span v-if="!walletSufficient" class="font-medium opacity-70">· low</span>
                                </template>
                            </span>

                            <!-- Selected check -->
                            <span v-if="selectedValue === 'member_wallet'"
                                  class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white shadow-sm">
                                <Check class="w-3 h-3" />
                            </span>
                        </button>

                        <!-- Section divider with label -->
                        <div v-if="accounts.length" class="flex items-center gap-3 px-4 py-1.5">
                            <div class="flex-1 h-px bg-secondary-100 dark:bg-secondary-800" />
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-600">Accounts</span>
                            <div class="flex-1 h-px bg-secondary-100 dark:bg-secondary-800" />
                        </div>
                    </div>

                    <!-- Account options -->
                    <div class="flex-1 overflow-y-auto overscroll-contain" :class="memberId ? '' : 'py-1.5'">
                        <button
                            v-for="account in accounts"
                            :key="account.id"
                            type="button"
                            class="relative w-full flex items-center gap-3 px-4 py-3 text-sm text-left transition-all duration-150
                                   hover:bg-secondary-50 dark:hover:bg-secondary-800/60
                                   border-l-2"
                            :class="selectedValue === account.id
                                ? 'bg-primary-50/60 border-l-primary-500 dark:bg-primary-900/10 dark:border-l-primary-500'
                                : 'border-l-transparent'"
                            @click="select(account.id)"
                        >
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl transition-colors"
                                  :class="selectedValue === account.id
                                      ? 'bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400'
                                      : 'bg-secondary-100 text-secondary-500 dark:bg-secondary-800 dark:text-secondary-400'">
                                <CreditCard class="w-4 h-4" />
                            </span>
                            <span class="flex-1 font-medium text-secondary-800 dark:text-secondary-200 truncate">{{ account.name }}</span>
                            <span v-if="selectedValue === account.id"
                                  class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full bg-primary-500 text-white shadow-sm">
                                <Check class="w-3 h-3" />
                            </span>
                        </button>

                        <!-- Empty state -->
                        <div v-if="!accounts.length && !memberId" class="flex flex-col items-center justify-center gap-2 px-4 py-8">
                            <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-secondary-100 text-secondary-400 dark:bg-secondary-800 dark:text-secondary-600">
                                <CreditCard class="w-5 h-5" />
                            </span>
                            <p class="text-sm text-secondary-400 dark:text-secondary-500">No accounts available</p>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { CreditCard, Wallet, ChevronDown, X, Check, Loader2 } from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';

const props = defineProps({
    modelValue: { default: null },
    accounts: { type: Array, default: () => [] },
    /** Pass member ID to enable the "Member Wallet" option */
    memberId: { type: Number, default: null },
    /** Amount that will be deducted — used to disable wallet option when balance is insufficient */
    amount: { type: Number, default: 0 },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    placeholder: { type: String, default: 'Select account...' },
});

const emit = defineEmits(['update:modelValue']);

// ── State ─────────────────────────────────────────────────────────────────────
const open = ref(false);
const walletBalance = ref(0);
const walletLoading = ref(false);

const rootRef = ref(null);
const triggerRef = ref(null);
const panelRef = ref(null);
const panelStyle = ref({});

// ── Derived ───────────────────────────────────────────────────────────────────
const selectedValue = computed(() => props.modelValue);

const selectedLabel = computed(() => {
    if (props.modelValue === 'member_wallet') return 'Member Wallet';
    const account = props.accounts.find(a => a.id === props.modelValue);
    return account ? account.name : '';
});

const walletSufficient = computed(() => {
    if (!props.memberId) return false;
    if (props.amount <= 0) return walletBalance.value > 0;
    return walletBalance.value >= props.amount;
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatBalance(value) {
    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function computePanelStyle() {
    if (!triggerRef.value) return;
    const rect = triggerRef.value.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceBelow = viewportHeight - rect.bottom - 12;
    const spaceAbove = rect.top - 12;
    const openBelow = spaceBelow >= 160 || spaceBelow >= spaceAbove;
    const maxHeight = Math.min(
        openBelow ? spaceBelow : spaceAbove,
        viewportHeight * 0.55,
    );
    panelStyle.value = {
        top: openBelow ? `${rect.bottom + 6}px` : undefined,
        bottom: !openBelow ? `${viewportHeight - rect.top + 6}px` : undefined,
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        maxHeight: `${Math.max(maxHeight, 160)}px`,
    };
}

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) computePanelStyle();
}

function select(value) {
    emit('update:modelValue', value);
    open.value = false;
}

function handleOutsideClick(e) {
    if (!open.value) return;
    if (rootRef.value?.contains(e.target)) return;
    if (panelRef.value?.contains(e.target)) return;
    open.value = false;
}

function handleScrollOrResize() {
    if (open.value) computePanelStyle();
}

// ── Wallet fetch ──────────────────────────────────────────────────────────────
async function fetchWalletBalance(memberId) {
    if (!memberId) { walletBalance.value = 0; return; }
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
    if (props.modelValue === 'member_wallet') emit('update:modelValue', null);
    fetchWalletBalance(newId);
}, { immediate: true });

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    document.addEventListener('click', handleOutsideClick, true);
    window.addEventListener('scroll', handleScrollOrResize, true);
    window.addEventListener('resize', handleScrollOrResize);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick, true);
    window.removeEventListener('scroll', handleScrollOrResize, true);
    window.removeEventListener('resize', handleScrollOrResize);
});
</script>
