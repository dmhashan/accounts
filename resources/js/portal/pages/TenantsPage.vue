<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">
          Tenants Registry
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          Add, update, or remove active client subdomains and database isolation systems.
        </p>
      </div>
      <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-semibold shadow-md shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all flex items-center gap-2 cursor-pointer" @click="openCreateModal">
        <svg
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        New Tenant
      </button>
    </div>

    <!-- Search / Filter bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-xl p-4 flex gap-4">
      <div class="relative flex-1">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </span>
        <input 
          v-model="search" 
          type="text" 
          placeholder="Search by name, subdomain, or email..."
          class="w-full pl-10 pr-4 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
          @input="fetchTenants"
        />
      </div>
    </div>

    <!-- Tenants Table -->
    <div class="bg-white/70 dark:bg-slate-900/60 backdrop-blur-md border border-slate-200/80 dark:border-slate-800/80 rounded-xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-950/50">
              <th class="px-6 py-4">
                Tenant Name
              </th>
              <th class="px-6 py-4">
                Subdomain
              </th>
              <th class="px-6 py-4">
                Status
              </th>
              <th class="px-6 py-4 text-right">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
            <tr v-if="loading && tenants.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                Loading tenants...
              </td>
            </tr>
            <tr v-else-if="tenants.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                No tenants registered yet.
              </td>
            </tr>
            <tr v-for="t in tenants" :key="t.subdomain" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all">
              <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                {{ t.name }}
              </td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 rounded-md font-mono text-xs border border-indigo-100/50 dark:border-indigo-900/30">
                  {{ t.subdomain }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="t.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="t.is_active ? 'bg-emerald-500' : 'bg-rose-500'" />
                  {{ t.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <router-link :to="`/tenants/${t.subdomain}`" class="text-xs font-semibold px-3 py-1.5 border border-slate-200 dark:border-slate-800 hover:border-indigo-500/50 rounded-lg transition-all inline-block hover:text-indigo-600 dark:hover:text-indigo-400">
                  View Detail
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="pagination.total > pagination.per_page" class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <span class="text-xs text-slate-500">Showing page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="pagination.current_page === 1" class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 cursor-pointer" @click="changePage(pagination.current_page - 1)">
            Previous
          </button>
          <button :disabled="pagination.current_page === pagination.last_page" class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 cursor-pointer" @click="changePage(pagination.current_page + 1)">
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Form Modal -->
    <div v-if="modal.show" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-md shadow-2xl p-6 relative">
        <h3 class="text-lg font-bold mb-4">
          {{ modal.mode === 'create' ? 'Register New Tenant' : 'Edit Tenant Details' }}
        </h3>
        
        <form class="space-y-4" @submit.prevent="submitForm">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tenant Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="e.g. CoreX Fitness"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Subdomain</label>
            <input
              v-model="form.domain"
              type="text"
              required
              :disabled="modal.mode === 'edit'"
              placeholder="e.g. gymname"
              class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all disabled:opacity-50 disabled:bg-slate-200/10"
            />
            <span class="text-xs text-slate-400 mt-1 block">Subdomain forms the URL: subdomain.gymname.com</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Email</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="e.g. contact@gym.com"
                class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
              />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Phone</label>
              <input
                v-model="form.phone"
                type="text"
                placeholder="e.g. 0779600296"
                class="w-full px-3.5 py-2 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-lg text-sm outline-none transition-all"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-4">
            <button type="button" class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-medium rounded-lg transition-all cursor-pointer" @click="modal.show = false">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition-all cursor-pointer">
              Continue
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reusable Action OTP Verification Overlay -->
    <div v-if="otpModal.show" class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-sm shadow-2xl p-6 relative text-center">
        <span class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 mb-4">
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
            />
          </svg>
        </span>
        <h3 class="text-lg font-bold mb-2">
          Verification Required
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
          An OTP is required to complete this action. Click below to receive a code on your mobile phone.
        </p>

        <!-- Sent Step -->
        <div v-if="otpModal.codeSent" class="space-y-4">
          <div class="relative">
            <input
              v-model="otpModal.code"
              type="text"
              maxlength="6"
              placeholder="Enter 6-digit code"
              class="w-full py-2 px-3 bg-slate-100/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 text-center tracking-widest font-semibold focus:border-indigo-500 rounded-lg outline-none transition-all"
            />
          </div>
          <div class="flex justify-between items-center text-[11px]">
            <span v-if="otpModal.debugCode" class="text-indigo-500 dark:text-indigo-400 font-mono font-semibold">Dev OTP: {{ otpModal.debugCode }}</span>
            <span v-else />
            <button class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold" @click="sendActionOtp">
              Resend OTP
            </button>
          </div>
          <p v-if="otpModal.error" class="text-xs text-rose-500 text-left font-medium mt-1">
            {{ otpModal.error }}
          </p>
          
          <div class="flex gap-2 pt-2">
            <button class="flex-1 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-xs font-semibold transition-all cursor-pointer" @click="otpModal.show = false">
              Cancel
            </button>
            <button :disabled="otpModal.loading" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-lg text-xs font-bold shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/25 transition-all cursor-pointer" @click="confirmActionWithOtp">
              {{ otpModal.loading ? 'Confirming...' : 'Verify & Confirm' }}
            </button>
          </div>
        </div>

        <!-- Initial Request Button -->
        <div v-else class="space-y-3">
          <button :disabled="otpModal.loading" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-sm transition-all flex items-center justify-center gap-2 cursor-pointer" @click="sendActionOtp">
            <svg
              v-if="otpModal.loading"
              class="animate-spin h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Send Verification SMS
          </button>
          <button class="w-full py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-xs font-medium transition-all cursor-pointer" @click="otpModal.show = false">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, inject } from 'vue';
import { apiRequest } from '../composables/usePortalApi';

export default {
  setup() {
    const tenants = ref([]);
    const search = ref('');
    const loading = ref(false);
    const showToast = inject('showToast');

    const pagination = reactive({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 15,
    });

    const modal = reactive({
      show: false,
      mode: 'create', // create | edit
      subdomain: '',
    });

    const form = reactive({
      name: '',
      domain: '',
      email: '',
      phone: '',
    });

    // Reusable Action OTP Modal State
    const otpModal = reactive({
      show: false,
      codeSent: false,
      code: '',
      loading: false,
      error: null,
      debugCode: null,
      onVerifySuccess: null, // Callback function to execute after OTP matches
    });

    const fetchTenants = async (page = 1) => {
      loading.value = true;
      try {
        const res = await apiRequest('/tenants', {
          params: {
            page,
            search: search.value,
          }
        });
        tenants.value = res.data;
        pagination.current_page = res.current_page;
        pagination.last_page = res.last_page;
        pagination.total = res.total;
        pagination.per_page = res.per_page;
      } catch {
        showToast('Failed to fetch tenants registry.', 'error');
      } finally {
        loading.value = false;
      }
    };

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.last_page) {
        fetchTenants(page);
      }
    };

    const openCreateModal = () => {
      modal.mode = 'create';
      modal.subdomain = '';
      form.name = '';
      form.domain = '';
      form.email = '';
      form.phone = '';
      modal.show = true;
    };

    const openEditModal = (tenant) => {
      modal.mode = 'edit';
      modal.subdomain = tenant.subdomain;
      form.name = tenant.name;
      form.domain = tenant.subdomain;
      form.email = tenant.email || '';
      form.phone = tenant.phone || '';
      modal.show = true;
    };

    // Prompt OTP verification before executing form submit
    const submitForm = () => {
      otpModal.show = true;
      otpModal.codeSent = false;
      otpModal.code = '';
      otpModal.error = null;
      otpModal.debugCode = null;

      otpModal.onVerifySuccess = async (otpCode) => {
        try {
          if (modal.mode === 'create') {
            await apiRequest('/tenants', {
              method: 'post',
              headers: { 'X-Portal-OTP': otpCode },
              data: {
                name: form.name,
                domain: form.domain,
                email: form.email,
                phone: form.phone,
              }
            });
            showToast('Tenant successfully registered and database initialized!');
          } else {
            await apiRequest(`/tenants/${modal.subdomain}`, {
              method: 'put',
              headers: { 'X-Portal-OTP': otpCode },
              data: {
                name: form.name,
                email: form.email,
                phone: form.phone,
              }
            });
            showToast('Tenant profile updated successfully.');
          }
          modal.show = false;
          otpModal.show = false;
          fetchTenants(pagination.current_page);
        } catch (err) {
          // If error is field validation, let the user know. If it is OTP error, middleware caught it.
          const errorMsg = err.response?.data?.message || 'Failed to save tenant information.';
          if (err.response?.status === 422 && !err.response?.data?.otp_required) {
            // Re-route error to forms
            modal.show = true;
            otpModal.show = false;
            showToast(errorMsg, 'error');
          } else {
            otpModal.error = errorMsg;
          }
        }
      };
    };

    // Request Action OTP (Mutating Edit Verification)
    const sendActionOtp = async () => {
      otpModal.loading = true;
      otpModal.error = null;
      try {
        const res = await apiRequest('/auth/action-otp', { method: 'post' });
        otpModal.codeSent = true;
        showToast(res.message);
        if (res.otp_debug) {
          otpModal.debugCode = res.otp_debug;
        }
      } catch {
        otpModal.error = 'Failed to send OTP verification code.';
      } finally {
        otpModal.loading = false;
      }
    };

    // Execute the action once OTP is entered
    const confirmActionWithOtp = async () => {
      if (!otpModal.code || otpModal.code.length !== 6) {
        otpModal.error = 'Please enter a valid 6-digit verification code.';
        return;
      }

      otpModal.loading = true;
      otpModal.error = null;

      if (otpModal.onVerifySuccess) {
        await otpModal.onVerifySuccess(otpModal.code);
      }
      otpModal.loading = false;
    };

    onMounted(fetchTenants);

    return {
      tenants,
      search,
      loading,
      pagination,
      modal,
      form,
      otpModal,
      fetchTenants,
      changePage,
      openCreateModal,
      openEditModal,
      submitForm,
      sendActionOtp,
      confirmActionWithOtp,
    };
  }
};
</script>
