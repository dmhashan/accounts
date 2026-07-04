<template>
  <section class="app-page-frame">
    <AppPageHeader show-back />

    <div class="app-page-scroll">
      <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
        {{ errorMessage }}
      </div>

      <form class="app-surface rounded-2xl p-5 md:p-6" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <AppFormField label="Employee code" optional>
            <AppFormInput v-model="form.employee_code" type="text" placeholder="Auto generated when blank" />
          </AppFormField>
          <AppFormField label="Status" required>
            <AppFormSelect v-model="form.status" required>
              <option v-for="option in meta.employee_statuses" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </AppFormSelect>
          </AppFormField>
          <AppFormField label="Full name" required>
            <AppFormInput v-model="form.name" type="text" required />
          </AppFormField>
          <AppFormField label="Email" optional>
            <AppFormInput v-model="form.email" type="email" />
          </AppFormField>
          <AppFormField label="Phone" optional>
            <AppFormInput v-model="form.phone" type="text" />
          </AppFormField>
          <AppFormField label="NIC" optional>
            <AppFormInput v-model="form.nic" type="text" />
          </AppFormField>
          <AppFormField label="Gender" optional>
            <AppFormSelect v-model="form.gender">
              <option v-for="option in genderOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </AppFormSelect>
          </AppFormField>
          <AppFormField label="Date of birth" optional>
            <AppFormInput v-model="form.date_of_birth" type="date" />
          </AppFormField>
          <AppFormField label="Joined date" required>
            <AppFormInput v-model="form.joined_date" type="date" required />
          </AppFormField>
          <AppFormField label="Left date" optional>
            <AppFormInput v-model="form.left_date" type="date" />
          </AppFormField>
          <AppFormField label="Employment type" required>
            <AppFormSelect v-model="form.employment_type" required>
              <option v-for="option in meta.employment_types" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </AppFormSelect>
          </AppFormField>
          <AppFormField label="Job title" optional>
            <AppFormInput v-model="form.job_title" type="text" />
          </AppFormField>
          <AppFormField label="Department" optional>
            <AppFormInput v-model="form.department" type="text" />
          </AppFormField>
          <AppFormField label="Emergency contact name" optional>
            <AppFormInput v-model="form.emergency_contact_name" type="text" />
          </AppFormField>
          <AppFormField label="Emergency contact phone" optional>
            <AppFormInput v-model="form.emergency_contact_phone" type="text" />
          </AppFormField>
          <AppFormField label="Pay method" required>
            <AppFormSelect v-model="form.pay_method" required>
              <option v-for="option in meta.pay_methods" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </AppFormSelect>
          </AppFormField>
          <AppFormField label="Daily rate" required>
            <AppFormInput
              v-model="form.daily_rate"
              type="number"
              min="0"
              step="0.01"
              required
            />
          </AppFormField>
          <AppFormField label="Annual leave days" optional>
            <AppFormInput
              v-model="form.annual_leave_days"
              type="number"
              min="0"
              max="365"
              step="0.5"
            />
          </AppFormField>
          <AppFormField label="Address" class="md:col-span-2" optional>
            <AppFormTextarea v-model="form.address" rows="3" />
          </AppFormField>
          <AppFormField label="Employee Pay Sheet notes" class="md:col-span-2" optional>
            <AppFormTextarea v-model="form.pay_sheet_notes" rows="3" />
          </AppFormField>
        </div>

        <div class="mt-5 flex justify-end">
          <button type="submit" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60" :disabled="submitting">
            {{ submitting ? 'Saving...' : (isEdit ? 'Update Employee' : 'Create Employee') }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const submitting = ref(false);
const errorMessage = ref('');
const meta = ref({ employment_types: [], employee_statuses: [], pay_methods: [] });
const genderOptions = [
    { value: '', label: 'Select gender' },
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
];
const form = ref(defaultForm());

function defaultForm() {
    return {
        employee_code: '',
        name: '',
        email: '',
        phone: '',
        nic: '',
        gender: '',
        date_of_birth: '',
        address: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        job_title: '',
        department: '',
        employment_type: 'full_time',
        status: 'active',
        joined_date: new Date().toISOString().slice(0, 10),
        left_date: '',
        pay_method: 'daily',
        daily_rate: '0',
        annual_leave_days: '0',
        pay_sheet_notes: '',
    };
}

async function loadMeta() {
    meta.value = await apiRequest('/api/employees/meta');
}

async function loadEmployee() {
    if (!isEdit.value) return;
    const response = await apiRequest(`/api/employees/${route.params.id}`);
    form.value = { ...defaultForm(), ...response.data };
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';
    try {
        const payload = {
            ...form.value,
            gender: form.value.gender || null,
            date_of_birth: form.value.date_of_birth || null,
            left_date: form.value.left_date || null,
            daily_rate: Number(form.value.daily_rate || 0),
            annual_leave_days: Number(form.value.annual_leave_days || 0),
        };
        if (isEdit.value) {
            await apiRequest(`/api/employees/${route.params.id}`, { method: 'put', data: payload });
            router.push(`/employees/${route.params.id}`);
        } else {
            const response = await apiRequest('/api/employees', { method: 'post', data: payload });
            router.push(`/employees/${response.data.id}`);
        }
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to save employee.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadMeta();
        await loadEmployee();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load employee form.';
    }
});
</script>
