<template>
    <section>
        <div class="flex flex-col gap-3 mb-4 md:mb-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary-900 dark:text-white">New Sale</h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">Process a sale using live stock and pricing from REST API.</p>
                </div>
                <RouterLink to="/sales" class="text-sm text-primary-600 dark:text-primary-400">Sales History</RouterLink>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center gap-2 overflow-x-auto">
                <div class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden shrink-0">
                    <button
                        type="button"
                        class="px-3 py-2 text-sm font-medium transition-colors"
                        :class="form.customer_type === 'local'
                            ? 'bg-primary-600 text-white'
                            : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                        @click="form.customer_type = 'local'"
                    >
                        Local
                    </button>
                    <button
                        type="button"
                        class="px-3 py-2 text-sm font-medium transition-colors"
                        :class="form.customer_type === 'foreign'
                            ? 'bg-primary-600 text-white'
                            : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                        @click="form.customer_type = 'foreign'"
                    >
                        Foreign
                    </button>
                </div>

                <div class="min-w-[260px] lg:min-w-[320px]">
                    <label class="sr-only">Select Customer</label>
                    <input
                        v-model="memberSearch"
                        type="text"
                        placeholder="Search customer"
                        class="w-full mb-1 px-3 py-2 text-sm border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"
                    >
                    <select
                        v-model.number="form.customer_member_id"
                        class="w-full px-3 py-2 text-sm border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800"
                    >
                        <option :value="null">Walk-in (optional)</option>
                        <option v-for="member in filteredMembers" :key="member.id" :value="member.id">{{ member.label }}</option>
                    </select>
                </div>

                <div class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden shrink-0">
                    <button
                        type="button"
                        class="px-3 py-2 text-sm font-medium transition-colors"
                        :class="uiMode === 'desktop'
                            ? 'bg-primary-600 text-white'
                            : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                        @click="setUiMode('desktop')"
                    >
                        Desktop
                    </button>
                    <button
                        type="button"
                        class="px-3 py-2 text-sm font-medium transition-colors"
                        :class="uiMode === 'touch'
                            ? 'bg-primary-600 text-white'
                            : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
                        @click="setUiMode('touch')"
                    >
                        Touch
                    </button>
                </div>
            </div>
        </div>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>

        <form class="space-y-4" @submit.prevent="submitSale">
            <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-secondary-700 dark:text-secondary-300 mb-1">Paid Amount</label>
                        <input v-model.number="form.paid_amount" type="number" min="0" step="0.01" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                    </div>
                    <div>
                        <label class="block text-sm text-secondary-700 dark:text-secondary-300 mb-1">Customer (Optional)</label>
                        <input :value="selectedMember?.label || 'Walk-in (optional)'" readonly class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-secondary-50 dark:bg-secondary-800/70 text-secondary-700 dark:text-secondary-300">
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-secondary-900 dark:text-white">Items</h3>
                    <button type="button" class="px-3 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-700" @click="addItem">Add Item</button>
                </div>

                <div v-if="uiMode === 'touch'" class="space-y-3">
                    <article v-for="(item, index) in form.items" :key="item.key" class="border border-secondary-200 dark:border-secondary-700 rounded-lg p-3">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-secondary-500 dark:text-secondary-400 mb-1">Product</label>
                                <select v-model.number="item.product_variation_id" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                                    <option :value="null">Select product</option>
                                    <option v-for="variation in variationOptions" :key="variation.id" :value="variation.id">{{ variation.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-secondary-500 dark:text-secondary-400 mb-1">Quantity</label>
                                <input v-model.number="item.quantity" type="number" min="1" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <p class="text-secondary-500 dark:text-secondary-400">Available</p>
                                    <p class="text-secondary-900 dark:text-white">{{ selectedVariation(item.product_variation_id)?.available_stock ?? 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-secondary-500 dark:text-secondary-400">Unit Price</p>
                                    <p class="text-secondary-900 dark:text-white">{{ money(unitPrice(item)) }}</p>
                                </div>
                                <div>
                                    <p class="text-secondary-500 dark:text-secondary-400">Subtotal</p>
                                    <p class="text-secondary-900 dark:text-white">{{ money(itemSubtotal(item)) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <button v-if="form.items.length > 1" type="button" class="text-sm text-red-600 dark:text-red-400" @click="removeItem(index)">Remove</button>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-secondary-200 dark:border-secondary-700">
                            <tr>
                                <th class="text-left text-xs text-secondary-500 dark:text-secondary-400 py-2">Product</th>
                                <th class="text-left text-xs text-secondary-500 dark:text-secondary-400 py-2">Qty</th>
                                <th class="text-left text-xs text-secondary-500 dark:text-secondary-400 py-2">Available</th>
                                <th class="text-left text-xs text-secondary-500 dark:text-secondary-400 py-2">Unit Price</th>
                                <th class="text-left text-xs text-secondary-500 dark:text-secondary-400 py-2">Subtotal</th>
                                <th class="text-right text-xs text-secondary-500 dark:text-secondary-400 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="item.key" class="border-b border-secondary-100 dark:border-secondary-800">
                                <td class="py-2 pr-2">
                                    <select v-model.number="item.product_variation_id" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                                        <option :value="null">Select product</option>
                                        <option v-for="variation in variationOptions" :key="variation.id" :value="variation.id">{{ variation.label }}</option>
                                    </select>
                                </td>
                                <td class="py-2 pr-2 w-32">
                                    <input v-model.number="item.quantity" type="number" min="1" required class="w-full px-3 py-2 border border-secondary-300 dark:border-secondary-700 rounded-lg bg-white dark:bg-secondary-800">
                                </td>
                                <td class="py-2 pr-2 text-sm text-secondary-700 dark:text-secondary-300">{{ selectedVariation(item.product_variation_id)?.available_stock ?? 0 }}</td>
                                <td class="py-2 pr-2 text-sm text-secondary-700 dark:text-secondary-300">{{ money(unitPrice(item)) }}</td>
                                <td class="py-2 pr-2 text-sm text-secondary-900 dark:text-white font-medium">{{ money(itemSubtotal(item)) }}</td>
                                <td class="py-2 text-right">
                                    <button v-if="form.items.length > 1" type="button" class="text-sm text-red-600 dark:text-red-400" @click="removeItem(index)">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-4 md:p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div>
                        <p class="text-secondary-500 dark:text-secondary-400">Total Amount</p>
                        <p class="text-lg font-semibold text-secondary-900 dark:text-white">{{ money(totalAmount) }}</p>
                    </div>
                    <div>
                        <p class="text-secondary-500 dark:text-secondary-400">Paid Amount</p>
                        <p class="text-lg font-semibold text-secondary-900 dark:text-white">{{ money(form.paid_amount || 0) }}</p>
                    </div>
                    <div>
                        <p class="text-secondary-500 dark:text-secondary-400">Balance</p>
                        <p class="text-lg font-semibold" :class="balanceAmount < 0 ? 'text-red-600 dark:text-red-400' : 'text-secondary-900 dark:text-white'">{{ money(balanceAmount) }}</p>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg disabled:opacity-50" :disabled="submitting || loadingMeta">
                        {{ submitting ? 'Processing...' : 'Complete Sale' }}
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';

const router = useRouter();

const loadingMeta = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const variationOptions = ref([]);
const members = ref([]);
const memberSearch = ref('');
const uiMode = ref('touch');

let rowKey = 0;

const form = ref({
    customer_name: '',
    customer_member_id: null,
    customer_type: 'local',
    paid_amount: 0,
    items: [
        { key: ++rowKey, product_variation_id: null, quantity: 1 },
    ],
});

const selectedMember = computed(() => {
    if (!form.value.customer_member_id) {
        return null;
    }

    return members.value.find((member) => member.id === form.value.customer_member_id) || null;
});

const filteredMembers = computed(() => {
    const term = memberSearch.value.trim().toLowerCase();
    if (!term) {
        return members.value;
    }

    return members.value.filter((member) => member.label.toLowerCase().includes(term));
});

const totalAmount = computed(() => {
    return form.value.items.reduce((sum, item) => sum + itemSubtotal(item), 0);
});

const balanceAmount = computed(() => {
    return Number(form.value.paid_amount || 0) - totalAmount.value;
});

function money(value) {
    return Number(value || 0).toFixed(2);
}

function selectedVariation(variationId) {
    if (!variationId) {
        return null;
    }

    return variationOptions.value.find((variation) => variation.id === variationId) || null;
}

function unitPrice(item) {
    const variation = selectedVariation(item.product_variation_id);
    if (!variation) {
        return 0;
    }

    return Number(variation.prices?.[form.value.customer_type] || 0);
}

function itemSubtotal(item) {
    const quantity = Number(item.quantity || 0);
    if (quantity <= 0) {
        return 0;
    }

    return unitPrice(item) * quantity;
}

function addItem() {
    form.value.items.push({ key: ++rowKey, product_variation_id: null, quantity: 1 });
}

function removeItem(index) {
    form.value.items.splice(index, 1);
}

function setUiMode(mode) {
    uiMode.value = mode;
    localStorage.setItem('saleUiMode', mode);
}

async function loadMeta() {
    loadingMeta.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/sales/meta');
        variationOptions.value = response.variations || [];
        members.value = response.members || [];
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load sale metadata.';
    } finally {
        loadingMeta.value = false;
    }
}

async function submitSale() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const resolvedCustomerName = selectedMember.value?.customer_name || null;

        const payload = {
            customer_name: resolvedCustomerName,
            customer_type: form.value.customer_type,
            paid_amount: Number(form.value.paid_amount || 0),
            items: form.value.items
                .filter((item) => item.product_variation_id && Number(item.quantity) > 0)
                .map((item) => ({
                    product_variation_id: Number(item.product_variation_id),
                    quantity: Number(item.quantity),
                })),
        };

        if (payload.items.length === 0) {
            errorMessage.value = 'Please add at least one valid sale item.';
            submitting.value = false;
            return;
        }

        await apiRequest('/api/sales', {
            method: 'post',
            data: payload,
        });

        router.push('/sales');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to complete sale.';
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    const storedMode = localStorage.getItem('saleUiMode');
    if (storedMode === 'desktop' || storedMode === 'touch') {
        uiMode.value = storedMode;
    }

    loadMeta();
});
</script>
