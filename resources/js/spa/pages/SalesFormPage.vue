<template>
  <section class="flex h-full min-h-0 flex-col overflow-y-auto">
    <AppPageHeader show-back>
      <template #extra-slot>
        <div class="flex flex-wrap items-center gap-2 pb-1">
          <div class="inline-flex shrink-0 rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden">
            <button
              type="button"
              class="px-3 py-2"
              :class="form.customer_type === 'local' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
              title="Local"
              aria-label="Local"
              @click="form.customer_type = 'local'"
            >
              <House class="h-4 w-4" />
            </button>
            <button
              type="button"
              class="px-3 py-2"
              :class="form.customer_type === 'foreign' ? 'bg-primary-600 text-white' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700'"
              title="Foreign"
              aria-label="Foreign"
              @click="form.customer_type = 'foreign'"
            >
              <Globe class="h-4 w-4" />
            </button>
          </div>

          <div class="min-w-[12rem] flex-1 md:max-w-md">
            <AppSearchableDropdown
              v-model="form.customer_member_id"
              :options="[...members]"
              :option-label="option => option.label"
              :option-key="option => option.id"
              placeholder="Walk-in (optional)"
              search-placeholder="Search customer..."
              no-results-text="No customers found."
              @update:model-value="selectCustomer"
            />
          </div>
        </div>
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="handleFormSubmit">
      <div class="grid min-h-0 flex-1 grid-cols-1 gap-3 lg:min-h-[calc(100vh-280px)] lg:grid-cols-12 lg:gap-4">
        <div class="flex h-[30dvh] min-h-[12rem] flex-col rounded-xl border border-secondary-200 bg-white p-3 dark:border-secondary-700 dark:bg-secondary-900 md:h-[32dvh] md:min-h-[14rem] md:p-4 lg:col-span-6 lg:h-auto lg:min-h-[14rem]">
          <div class="mb-3 flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
              Products
            </h3>
          </div>
          <div class="mb-3">
            <AppFormInput
              v-model="productSearch"
              type="text"
              placeholder="Search product..."
            />
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto pr-1">
            <div class="space-y-2">
              <article
                v-for="variation in filteredVariationOptions"
                :key="variation.id"
                role="button"
                tabindex="0"
                class="border border-secondary-200 dark:border-secondary-700 rounded-lg p-2 md:p-3 flex items-center justify-between gap-2 md:gap-3 transition-colors"
                :class="[
                  variation.available_stock > 0 ? 'cursor-pointer hover:bg-secondary-100 dark:hover:bg-secondary-800' : 'cursor-not-allowed opacity-60',
                  activeProductId === variation.id ? 'ring-2 ring-primary-500/60 bg-primary-50 dark:bg-primary-900/20' : ''
                ]"
                @click="handleProductActivate(variation)"
                @keydown.enter.prevent="handleProductActivate(variation)"
                @keydown.space.prevent="handleProductActivate(variation)"
              >
                <div class="min-w-0">
                  <p class="text-xs md:text-sm font-semibold text-secondary-900 dark:text-white truncate">
                    {{ variation.label }}
                  </p>
                  <p class="text-[11px] md:text-xs text-secondary-500 dark:text-secondary-400">
                    Stock: {{ variation.available_stock }} • {{ money(variationPrice(variation)) }}
                  </p>
                </div>
              </article>
              <p v-if="filteredVariationOptions.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">
                No products found.
              </p>
            </div>
          </div>
        </div>

        <div class="flex max-h-[34dvh] min-h-[9rem] flex-col rounded-t-xl border border-secondary-200 bg-white p-3 dark:border-secondary-700 dark:bg-secondary-900 md:min-h-[12rem] md:p-4 lg:col-span-6 lg:max-h-none lg:min-h-[14rem] lg:rounded-xl">
          <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-3">
            Cart
          </h3>

          <div ref="cartListRef" class="min-h-0 flex-1 overflow-y-auto space-y-2 pr-1">
            <article
              v-for="item in form.items"
              :key="item.key"
              :ref="(element) => setCartItemRef(item.key, element)"
              class="border border-secondary-200 dark:border-secondary-700 rounded-lg p-3 transition-colors"
              :class="activeCartKey === item.key ? 'ring-2 ring-primary-500/50 bg-primary-50/60 dark:bg-primary-900/20' : ''"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ selectedVariation(item.product_variation_id)?.label || 'Item' }}
                  </p>
                  <p class="text-xs text-secondary-500 dark:text-secondary-400">
                    Unit: {{ money(unitPrice(item)) }}
                  </p>
                </div>
                <button type="button" class="text-xs text-red-600 dark:text-red-400" @click="removeByKey(item.key)">
                  Remove
                </button>
              </div>

              <div class="mt-2 flex items-center justify-between">
                <div class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                  <button type="button" class="px-3 py-1.5 text-sm" @click="decrementQty(item)">
                    -
                  </button>
                  <div class="px-3 py-1.5 text-sm min-w-10 text-center">
                    {{ item.quantity }}
                  </div>
                  <button type="button" class="px-3 py-1.5 text-sm" @click="incrementQty(item)">
                    +
                  </button>
                </div>
                <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                  {{ money(itemSubtotal(item)) }}
                </p>
              </div>
            </article>

            <div v-if="form.items.length === 0" class="text-sm text-secondary-500 dark:text-secondary-400">
              No items in cart.
            </div>
          </div>
        </div>
      </div>

      <div class="z-10 mt-0 flex flex-col gap-3 rounded-b-xl border border-t-0 border-secondary-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-secondary-700 dark:bg-secondary-900/95 sm:flex-row sm:items-center sm:justify-between lg:sticky lg:bottom-0 lg:mt-8 lg:rounded-xl lg:border-t">
        <div>
          <p class="text-xs text-secondary-500 dark:text-secondary-400">
            Grand Total
          </p>
          <p class="text-xl font-bold text-secondary-900 dark:text-white">
            {{ money(totalAmount) }}
          </p>
        </div>
        <button
          v-if="isEdit && canEditSale"
          type="submit"
          class="px-6 py-3 text-base font-semibold bg-primary-600 hover:bg-primary-700 text-white rounded-lg disabled:opacity-50"
          :disabled="submitDisabled"
        >
          {{ submitting ? 'Updating...' : 'Update Sale' }}
        </button>
        <div v-else-if="canCreateSale" class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:items-center">
          <button
            type="button"
            class="px-5 py-3 text-base font-semibold border border-secondary-300 dark:border-secondary-600 text-secondary-800 dark:text-secondary-100 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 disabled:opacity-50"
            :disabled="saveDisabled"
            @click="submitSale('save')"
          >
            {{ submitting ? 'Saving...' : 'Save' }}
          </button>
          <button
            type="button"
            class="px-6 py-3 text-base font-semibold bg-primary-600 hover:bg-primary-700 text-white rounded-lg disabled:opacity-50"
            :disabled="submitDisabled"
            @click="openPayNowModal"
          >
            {{ submitting ? 'Processing...' : 'Pay Now' }}
          </button>
        </div>
      </div>
    </form>

    <div v-if="payNowModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/45" @click="closePayNowModal" />
      <div class="relative z-10 w-full max-w-md rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-4 md:p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">
              Select Company Account
            </h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
              Choose where this sale payment should be recorded.
            </p>
          </div>
          <button type="button" class="text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closePayNowModal">
            ✕
          </button>
        </div>

        <div class="mt-4">
          <AppFormField label="Company Account">
            <AppCompanyAccountSelect
              v-model="selectedPayNowAccountId"
              :accounts="companyAccounts"
              :member-id="selectedMember?.id ?? undefined"
              :amount="totalAmount"
            />
          </AppFormField>
          <p v-if="companyAccounts.length === 0 && !selectedMember" class="mt-2 text-sm text-red-600 dark:text-red-400">
            No company account found. Add one before using Pay Now.
          </p>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closePayNowModal">
            Cancel
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50"
            :disabled="submitting || !selectedPayNowAccountId"
            @click="submitSale('pay_now')"
          >
            {{ submitting ? 'Processing...' : 'Confirm Payment' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Post-sale success modal -->
    <div v-if="successModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" />
      <div class="relative z-10 w-full max-w-lg rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
        <!-- Header -->
        <div class="px-5 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center gap-3 shrink-0">
          <div class="h-9 w-9 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center shrink-0">
            <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
              {{ saleResult?.isPaid ? 'Payment Complete' : 'Sale Saved' }}
            </h3>
            <p class="text-xs text-secondary-500 dark:text-secondary-400">
              {{ saleResult?.isPaid ? 'Sale has been paid and recorded.' : 'Sale saved as outstanding.' }}
            </p>
          </div>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 p-5">
          <!-- Available Stock — large -->
          <div v-if="updatedStockItems.length > 0" class="grid grid-cols-2 gap-2">
            <div
              v-for="stock in updatedStockItems"
              :key="stock.id"
              class="rounded-xl bg-secondary-50 dark:bg-secondary-800 px-4 py-3"
            >
              <p class="text-sm font-medium text-secondary-600 dark:text-secondary-300 leading-tight mb-1 truncate">
                {{ stock.label }}
              </p>
              <p
                class="text-4xl font-bold leading-none"
                :class="stock.available_stock > 0 ? 'text-secondary-900 dark:text-white' : 'text-red-500 dark:text-red-400'"
              >
                {{ stock.available_stock }}
              </p>
              <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5">
                units left
              </p>
            </div>
          </div>
          <p v-else class="text-sm text-secondary-500 dark:text-secondary-400">
            No stock data available.
          </p>
        </div>

        <!-- Footer actions -->
        <div class="px-5 py-4 border-t border-secondary-200 dark:border-secondary-700 flex items-center gap-2 shrink-0">
          <button
            type="button"
            class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800"
            @click="resetForNewSale"
          >
            New Sale
          </button>
          <button
            type="button"
            class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white"
            @click="goToSales"
          >
            View Sales
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { CheckCircle, Globe, House } from 'lucide-vue-next';
import { useAppContext } from '../composables/useAppContext';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';
import AppCompanyAccountSelect from '../components/forms/AppCompanyAccountSelect.vue';

const router = useRouter();
const route = useRoute();
const context = useAppContext();

const loadingMeta = ref(false);
const loadingSale = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const variationOptions = ref([]);
const productSearch = ref('');
const members = ref([]);
const activeProductId = ref(null);
const activeCartKey = ref(null);
const cartListRef = ref(null);
const cartItemRefs = ref({});
const companyAccounts = ref([]);
const payNowModalOpen = ref(false);
const selectedPayNowAccountId = ref(null);
const saleLocked = ref(false);
const successModalOpen = ref(false);
const saleResult = ref(null);
const soldVariationIds = ref([]);

let rowKey = 0;
let productUiTimer = null;
let cartFocusTimer = null;

const isEdit = computed(() => Boolean(route.params.id));
const canCreateSale = computed(() => Boolean(context.permissions?.salesCreate));
const canEditSale = computed(() => Boolean(context.permissions?.salesEdit));
const hasActionPermission = computed(() => (isEdit.value ? canEditSale.value : canCreateSale.value));

const form = ref({
    customer_name: '',
    customer_member_id: null,
    customer_type: 'local',
    payment_method: 'cash',
    reference_number: '',
    paid_amount: 0,
    items: [],
});

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank', label: 'Bank' },
    { value: 'card', label: 'Card' },
];

const selectedMember = computed(() => {
    if (!form.value.customer_member_id) {
        return null;
    }

    return members.value.find((member) => member.id === form.value.customer_member_id) || null;
});

const filteredVariationOptions = computed(() => {
    const term = productSearch.value.trim().toLowerCase();

    return variationOptions.value.filter((variation) => {
        const stock = Number(variation?.available_stock || 0);
        if (stock <= 0) {
            return false;
        }

        if (!term) {
            return true;
        }

        return String(variation?.label || '').toLowerCase().includes(term);
    });
});

const showReferenceField = computed(() => {
    return form.value.payment_method === 'bank' || form.value.payment_method === 'card';
});

const totalAmount = computed(() => {
    return form.value.items.reduce((sum, item) => sum + itemSubtotal(item), 0);
});

const updatedStockItems = computed(() => {
    if (!soldVariationIds.value.length) return [];
    return variationOptions.value.filter((v) => soldVariationIds.value.includes(v.id));
});

const submitDisabled = computed(() => {
    if (saleLocked.value) return true;
    if (!hasActionPermission.value) return true;
    if (submitting.value || loadingMeta.value || form.value.items.length === 0) return true;
    return false;
});

const saveDisabled = computed(() => {
    return !hasActionPermission.value || submitting.value || loadingMeta.value || form.value.items.length === 0;
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

function variationPrice(variation) {
    return Number(variation?.prices?.[form.value.customer_type] || 0);
}

function unitPrice(item) {
    const variation = selectedVariation(item.product_variation_id);
    if (!variation) {
        return 0;
    }

    return variationPrice(variation);
}

function itemSubtotal(item) {
    const quantity = Number(item.quantity || 0);
    if (quantity <= 0) {
        return 0;
    }

    return unitPrice(item) * quantity;
}

function addVariationToCart(variation) {
    const existing = form.value.items.find((item) => item.product_variation_id === variation.id);

    if (existing) {
        incrementQty(existing);
        focusCartItem(variation.id);
        return;
    }

    form.value.items.push({
        key: ++rowKey,
        product_variation_id: variation.id,
        quantity: 1,
    });

    focusCartItem(variation.id);
}

function handleProductActivate(variation) {
    if (Number(variation?.available_stock || 0) <= 0) {
        return;
    }

    activeProductId.value = variation.id;
    if (productUiTimer) {
        clearTimeout(productUiTimer);
    }

    productUiTimer = setTimeout(() => {
        activeProductId.value = null;
    }, 220);

    addVariationToCart(variation);
}

function setCartItemRef(key, element) {
    if (element) {
        cartItemRefs.value[key] = element;
        return;
    }

    delete cartItemRefs.value[key];
}

async function focusCartItem(variationId) {
    await nextTick();

    const cartItem = form.value.items.find((item) => item.product_variation_id === variationId);
    if (!cartItem) {
        return;
    }

    activeCartKey.value = cartItem.key;
    const targetElement = cartItemRefs.value[cartItem.key];

    if (targetElement && typeof targetElement.scrollIntoView === 'function') {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else if (cartListRef.value) {
        cartListRef.value.scrollTo({ top: cartListRef.value.scrollHeight, behavior: 'smooth' });
    }

    if (cartFocusTimer) {
        clearTimeout(cartFocusTimer);
    }

    cartFocusTimer = setTimeout(() => {
        activeCartKey.value = null;
    }, 900);
}

function incrementQty(item) {
    const stock = Number(selectedVariation(item.product_variation_id)?.available_stock || 0);
    if (item.quantity < stock) {
        item.quantity += 1;
    }
}

function decrementQty(item) {
    if (item.quantity > 1) {
        item.quantity -= 1;
    }
}

function removeByKey(key) {
    form.value.items = form.value.items.filter((item) => item.key !== key);
}

function selectCustomer(memberId) {
    form.value.customer_member_id = memberId;
}

// Removed: handleDocumentClick

async function loadMeta() {
    if (!hasActionPermission.value) {
        return;
    }

    loadingMeta.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest('/api/sales/meta');
        variationOptions.value = response.variations || [];
        members.value = response.members || [];
        companyAccounts.value = response.accounts || [];

        if (companyAccounts.value.length > 0 && !selectedPayNowAccountId.value) {
            selectedPayNowAccountId.value = companyAccounts.value[0].id;
        }
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load sale metadata.';
    } finally {
        loadingMeta.value = false;
    }
}

async function loadSale() {
    if (!isEdit.value || !canEditSale.value) return;
    
    loadingSale.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest(`/api/sales/${route.params.id}`);
        const saleData = response.data;

        form.value.customer_name = saleData.customer_name || '';
        form.value.customer_member_id = saleData.customer_member_id || null;
        form.value.customer_type = saleData.customer_type;
        form.value.payment_method = saleData.payment_method;
        form.value.reference_number = saleData.reference_number || '';
        form.value.paid_amount = saleData.paid_amount;
        selectedPayNowAccountId.value = saleData.account_id || selectedPayNowAccountId.value;
        saleLocked.value = Boolean(saleData.is_paid);

        if (saleLocked.value) {
            errorMessage.value = 'Paid sales cannot be edited or deleted.';
        }

        // Load items
        form.value.items = saleData.items.map((item) => ({
            key: ++rowKey,
            product_variation_id: item.product_variation_id,
            quantity: item.quantity,
        }));
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load sale details.';
    } finally {
        loadingSale.value = false;
    }
}

async function submitSale(mode = 'update') {
    if (!hasActionPermission.value) {
        errorMessage.value = isEdit.value
            ? 'You do not have permission to edit sales.'
            : 'You do not have permission to create sales.';
        return;
    }

    submitting.value = true;
    errorMessage.value = '';

    try {
        const resolvedCustomerName = selectedMember.value?.customer_name || null;
        const isPayNowMode = mode === 'pay_now';
        const isWalletPayment = isPayNowMode && selectedPayNowAccountId.value === 'member_wallet';

        const paidAmount = isEdit.value
            ? (isWalletPayment ? totalAmount.value : Number(form.value.paid_amount || 0))
            : (isPayNowMode ? totalAmount.value : 0);

        const payload = {
            customer_name: resolvedCustomerName,
            customer_member_id: selectedMember.value?.id || null,
            customer_type: form.value.customer_type,
            payment_method: isPayNowMode ? (isWalletPayment ? 'member_wallet' : form.value.payment_method) : form.value.payment_method,
            reference_number: showReferenceField.value ? (form.value.reference_number || null) : null,
            paid_amount: paidAmount,
            is_paid: isEdit.value ? undefined : isPayNowMode,
            account_id: isEdit.value ? undefined : (isPayNowMode && !isWalletPayment ? Number(selectedPayNowAccountId.value) : null),
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

        // Capture sale info before form is reset
        const soldItems = form.value.items.map((item) => {
            const variation = selectedVariation(item.product_variation_id);
            return {
                variationId: item.product_variation_id,
                label: variation?.label || 'Item',
                quantity: item.quantity,
                unitPrice: unitPrice(item),
                subtotal: itemSubtotal(item),
            };
        });
        const capturedTotal = totalAmount.value;
        const capturedCustomerName = selectedMember.value?.label || form.value.customer_name || null;
        const capturedCustomerType = form.value.customer_type;
        const capturedPaymentMethodLabel = paymentMethods.find((m) => m.value === form.value.payment_method)?.label || form.value.payment_method;
        const capturedIsPaid = mode === 'pay_now';

        if (isEdit.value) {
            await apiRequest(`/api/sales/${route.params.id}`, {
                method: 'put',
                data: payload,
            });
            router.push('/sales');
        } else {
            await apiRequest('/api/sales', {
                method: 'post',
                data: payload,
            });

            payNowModalOpen.value = false;

            // Build result and show success modal
            saleResult.value = {
                items: soldItems,
                totalAmount: capturedTotal,
                customerName: capturedCustomerName,
                customerType: capturedCustomerType,
                paymentMethodLabel: capturedPaymentMethodLabel,
                isPaid: capturedIsPaid,
            };
            soldVariationIds.value = soldItems.map((item) => item.variationId);
            successModalOpen.value = true;

            // Reload meta in background to get fresh stock numbers
            loadMeta();
        }
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to complete sale.';
    } finally {
        submitting.value = false;
    }
}

function handleFormSubmit() {
    if (isEdit.value) {
        submitSale('update');
    }
}

function openPayNowModal() {
    if (submitDisabled.value) return;
    if (!selectedPayNowAccountId.value && companyAccounts.value.length > 0) {
        selectedPayNowAccountId.value = companyAccounts.value[0].id;
    }
    payNowModalOpen.value = true;
}

function closePayNowModal() {
    if (submitting.value) {
        return;
    }

    payNowModalOpen.value = false;
}

function resetForNewSale() {
    successModalOpen.value = false;
    saleResult.value = null;
    soldVariationIds.value = [];
    form.value = {
        customer_name: '',
        customer_member_id: null,
        customer_type: 'local',
        payment_method: 'cash',
        reference_number: '',
        paid_amount: 0,
        items: [],
    };
    selectedPayNowAccountId.value = companyAccounts.value[0]?.id ?? null;
    productSearch.value = '';
    errorMessage.value = '';
}

function goToSales() {
    router.push('/sales');
}

onMounted(async () => {
    if (!hasActionPermission.value) {
        errorMessage.value = isEdit.value
            ? 'You do not have permission to edit sales.'
            : 'You do not have permission to create sales.';
        return;
    }

    await loadMeta();
    loadSale();

    const qMemberId = route.query.member_id ? Number(route.query.member_id) : null;
    if (qMemberId && !isEdit.value) {
        selectCustomer(qMemberId);
    }
});

onBeforeUnmount(() => {
    if (productUiTimer) {
        clearTimeout(productUiTimer);
    }

    if (cartFocusTimer) {
        clearTimeout(cartFocusTimer);
    }
});

watch(
    () => form.value.customer_member_id,
    (_newId) => {
        if (!selectedMember.value && form.value.payment_method === 'member_wallet') {
            form.value.payment_method = 'cash';
        }
    }
);

watch(
    () => totalAmount.value,
    (value) => {
        if (form.value.payment_method === 'member_wallet') {
            form.value.paid_amount = value;
        }
    }
);
</script>
