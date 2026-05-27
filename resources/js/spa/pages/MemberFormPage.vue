<template>
  <section class="app-page-frame">
    <AppPageHeader show-back />

    <div class="app-page-scroll">
      <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <!-- Personal Details -->
        <div class="app-surface rounded-2xl p-5 md:p-6">
          <h2 class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-widest mb-4">
            Personal Details
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <AppFormField label="First Name" required>
              <AppFormInput v-model="form.first_name" required />
            </AppFormField>
            <AppFormField label="Last Name" required>
              <AppFormInput v-model="form.last_name" required />
            </AppFormField>
            <AppFormField label="NIC" optional>
              <AppFormInput v-model="form.nic" placeholder="Old (9+V/X) or New (12 digits)" />
              <div v-if="nicValidation.status === 'valid'" class="mt-1.5 flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3.5 w-3.5 shrink-0"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span>{{ nicValidation.gender === 'male' ? 'Male' : 'Female' }} · Born {{ nicValidation.dateOfBirth }}</span>
              </div>
              <div v-else-if="nicValidation.status === 'invalid'" class="mt-1.5 flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3.5 w-3.5 shrink-0"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ nicValidation.message }}</span>
              </div>
            </AppFormField>
            <AppFormField label="Gender" required>
              <AppSearchableDropdown
                v-model="form.gender"
                :options="[
                  { id: 'male', label: 'Male' },
                  { id: 'female', label: 'Female' }
                ]"
                :option-label="option => option.label"
                :option-key="option => option.id"
                placeholder="Select gender"
                no-results-text="No gender found."
                :searchable="false"
                required
              />
            </AppFormField>
            <AppFormField label="Date of Birth" required>
              <AppFormInput v-model="form.date_of_birth" type="date" required />
            </AppFormField>
          </div>
        </div>

        <!-- Contact Details -->
        <div class="app-surface rounded-2xl p-5 md:p-6">
          <h2 class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-widest mb-4">
            Contact Details
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <AppFormField label="Email" required>
              <AppFormInput v-model="form.email" type="email" required />
            </AppFormField>
            <AppFormField label="Phone Number" required>
              <AppFormInput v-model="form.phone_number" required />
            </AppFormField>
            <div class="md:col-span-2 flex flex-col sm:flex-row gap-4">
              <label class="flex items-center gap-3 cursor-pointer select-none group">
                <button
                  type="button"
                  role="switch"
                  :aria-checked="form.allow_sms"
                  class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                  :class="form.allow_sms ? 'bg-primary-600' : 'bg-secondary-300 dark:bg-secondary-600'"
                  @click="form.allow_sms = !form.allow_sms"
                >
                  <span
                    class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200"
                    :class="form.allow_sms ? 'translate-x-4' : 'translate-x-0'"
                  />
                </button>
                <span class="text-sm text-secondary-700 dark:text-secondary-300">
                  Receives SMS
                  <span class="block text-xs text-secondary-400 dark:text-secondary-500 font-normal">Mobile number can receive SMS notifications</span>
                </span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer select-none group">
                <button
                  type="button"
                  role="switch"
                  :aria-checked="form.allow_whatsapp"
                  class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                  :class="form.allow_whatsapp ? 'bg-green-500' : 'bg-secondary-300 dark:bg-secondary-600'"
                  @click="form.allow_whatsapp = !form.allow_whatsapp"
                >
                  <span
                    class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200"
                    :class="form.allow_whatsapp ? 'translate-x-4' : 'translate-x-0'"
                  />
                </button>
                <span class="text-sm text-secondary-700 dark:text-secondary-300">
                  Has WhatsApp
                  <span class="block text-xs text-secondary-400 dark:text-secondary-500 font-normal">Mobile number has WhatsApp</span>
                </span>
              </label>
            </div>
            <AppFormField
              v-if="!form.allow_whatsapp"
              label="WhatsApp Number"
              optional
              class="md:col-span-2"
            >
              <AppFormInput v-model="form.whatsapp_number" placeholder="e.g. +94771234567" />
              <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                Provide a separate WhatsApp number if different from mobile
              </p>
            </AppFormField>
            <AppFormField label="Address" class="md:col-span-2" optional>
              <AppFormTextarea v-model="form.address" rows="2" />
            </AppFormField>
          </div>
        </div>

        <!-- Payment Details -->
        <div class="app-surface rounded-2xl p-5 md:p-6">
          <h2 class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-widest mb-4">
            Payment Details
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <AppFormField label="Payment Plan" required>
              <AppSearchableDropdown
                v-model="form.payment_plan_id"
                :options="paymentPlans"
                :option-label="option => option.name"
                :option-key="option => option.id"
                placeholder="Select payment plan"
                no-results-text="No plans found."
                searchable
                required
              />
            </AppFormField>
            <AppFormField label="Price" required>
              <AppFormInput
                v-model="form.price"
                type="number"
                step="0.01"
                min="0"
                readonly
                required
              />
            </AppFormField>
            <AppFormField label="Admission Fee" optional>
              <AppFormInput
                v-model="form.admission_fee"
                type="number"
                step="0.01"
                min="0"
              />
            </AppFormField>
          </div>
        </div>

        <!-- Other Details -->
        <div class="app-surface rounded-2xl p-5 md:p-6">
          <h2 class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-widest mb-4">
            Other Details
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <AppFormField label="Username" required>
              <AppFormInput v-model="form.username" required @input="onUsernameInput" />
              <p class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">
                Auto-filled from email. You can override it.
              </p>
            </AppFormField>
            <AppFormField label="Joined Date" required>
              <AppFormInput v-model="form.joined_date" type="date" required />
            </AppFormField>
            <AppFormField label="Comment" class="md:col-span-2" optional>
              <AppFormTextarea v-model="form.comment" rows="2" />
            </AppFormField>
          </div>
        </div>

        <div class="flex justify-end pb-2">
          <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg" :disabled="submitting">
            {{ submitting ? 'Saving...' : (isEdit ? 'Update Member' : 'Create Member') }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');
const paymentPlans = ref([]);
const usernameAutoSync = ref(true);

const today = new Date().toISOString().slice(0, 10);

const form = ref({
    first_name: '',
    last_name: '',
    username: '',
    gender: '',
    email: '',
    phone_number: '',
    allow_sms: true,
    allow_whatsapp: true,
    whatsapp_number: '',
    nic: '',
    date_of_birth: '',
    address: '',
    admission_fee: '',
    payment_plan_id: '',
    price: '',
    joined_date: today,
    comment: '',
});

// ── Sri Lanka NIC parser ──────────────────────────────────────────────────────
// Old NIC: 9 digits + V or X  →  YY (birth year, 1900+) + DDD (day-of-year) + SSSS + V|X
// New NIC: 12 digits           →  YYYY + DDD + NNNNN
// Day-of-year ≥ 500 → female (subtract 500 to get actual day)
function parseSriLankaNIC(nic) {
    if (!nic) return null;
    nic = nic.trim().toUpperCase();

    let year, dayOfYear;

    if (/^\d{9}[VX]$/.test(nic)) {
        year = 1900 + parseInt(nic.substring(0, 2), 10);
        dayOfYear = parseInt(nic.substring(2, 5), 10);
    } else if (/^\d{12}$/.test(nic)) {
        year = parseInt(nic.substring(0, 4), 10);
        dayOfYear = parseInt(nic.substring(4, 7), 10);
    } else {
        return null;
    }

    const gender = dayOfYear >= 500 ? 'female' : 'male';
    if (gender === 'female') dayOfYear -= 500;

    if (dayOfYear < 1 || dayOfYear > 366) return null;

    const date = new Date(year, 0, dayOfYear);
    if (date.getFullYear() !== year) return null; // day 366 in non-leap year overflows

    return { dateOfBirth: date.toISOString().slice(0, 10), gender };
}

const nicValidation = computed(() => {
    const nic = (form.value.nic || '').trim();
    if (!nic) return { status: 'empty' };

    const len = nic.length;
    if (len > 12) return { status: 'invalid', message: 'NIC is too long (max 12 characters).' };
    if (len !== 10 && len !== 12) return { status: 'typing' };

    const parsed = parseSriLankaNIC(nic);
    if (!parsed) return { status: 'invalid', message: 'Invalid NIC — check the number and try again.' };

    return { status: 'valid', ...parsed };
});

watch(nicValidation, (validation) => {
    if (validation.status === 'valid') {
        form.value.date_of_birth = validation.dateOfBirth;
        form.value.gender = validation.gender;
    }
});

watch(() => form.value.payment_plan_id, (planId) => {
    const plan = paymentPlans.value.find(p => p.id === planId);
    form.value.price = plan ? plan.price : '';
});

watch(() => form.value.email, (email) => {
    if (usernameAutoSync.value) {
        const atIndex = (email ?? '').indexOf('@');
        form.value.username = atIndex > 0 ? email.slice(0, atIndex) : (email ?? '');
    }
});

function onUsernameInput() {
    usernameAutoSync.value = false;
}

async function loadMember() {
    if (!isEdit.value) return;
    const response = await apiRequest(`/api/members/${route.params.id}`);
    form.value = {
        ...form.value,
        ...response.data,
        allow_sms: response.data?.allow_sms ?? true,
        allow_whatsapp: response.data?.allow_whatsapp ?? true,
        whatsapp_number: response.data?.whatsapp_number ?? '',
        admission_fee: response.data?.admission_fee ?? '',
        payment_plan_id: response.data?.payment_plan_id ?? '',
        price: response.data?.price ?? '',
    };
    usernameAutoSync.value = false;
}

async function loadPaymentPlans() {
    const response = await apiRequest('/api/members/form/payment-plans');
    paymentPlans.value = response?.data ?? [];
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        let memberId = route.params.id;

        if (isEdit.value) {
            await apiRequest(`/api/members/${route.params.id}`, { method: 'put', data: form.value });
        } else {
            const response = await apiRequest('/api/members', { method: 'post', data: form.value });
            memberId = response?.data?.id;
        }

        router.push(memberId ? `/members/${memberId}` : '/members');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save member.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await Promise.all([loadPaymentPlans(), loadMember()]);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load member.';
    }
});
</script>
