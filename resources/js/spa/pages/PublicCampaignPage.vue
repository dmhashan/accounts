<template>
  <main class="min-h-screen bg-background-light text-secondary-950 dark:bg-background-dark dark:text-white">
    <div v-if="loading" class="mx-auto flex min-h-screen max-w-3xl items-center justify-center px-5 text-sm text-secondary-500 dark:text-secondary-400">
      Loading campaign...
    </div>

    <div v-else-if="notFound" class="mx-auto flex min-h-screen max-w-xl items-center justify-center px-5">
      <section class="w-full rounded-2xl border border-secondary-200 bg-white p-6 text-center shadow-sm dark:border-secondary-800 dark:bg-secondary-900">
        <h1 class="text-xl font-bold text-secondary-950 dark:text-white">
          Campaign not found
        </h1>
        <p class="mt-2 text-sm text-secondary-500 dark:text-secondary-400">
          The registration link is unavailable or has been removed.
        </p>
      </section>
    </div>

    <div v-else class="mx-auto max-w-5xl px-4 py-5 sm:px-6 lg:px-8">
      <header class="mb-5 flex items-center gap-3">
        <img
          v-if="campaign?.tenant?.logo_url"
          :src="campaign.tenant.logo_url"
          alt=""
          class="h-11 w-11 rounded-xl border border-secondary-200 object-cover dark:border-secondary-800"
        />
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-secondary-900 dark:text-white">
            {{ campaign?.tenant?.name || 'Registration' }}
          </p>
          <p v-if="tenantContactLine" class="truncate text-xs text-secondary-500 dark:text-secondary-400">
            {{ tenantContactLine }}
          </p>
        </div>
      </header>

      <section class="overflow-hidden rounded-2xl border border-secondary-200 bg-white shadow-sm dark:border-secondary-800 dark:bg-secondary-900">
        <div
          v-if="campaign?.cover_image_url"
          class="h-52 bg-secondary-100 sm:h-72 dark:bg-secondary-800"
        >
          <img :src="campaign.cover_image_url" alt="" class="h-full w-full object-cover" />
        </div>

        <div class="grid gap-0 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
          <aside class="border-b border-secondary-200 p-5 dark:border-secondary-800 lg:border-b-0 lg:border-r">
            <div class="sticky top-5 space-y-4">
              <span
                v-if="closed"
                class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-900/25 dark:text-amber-300"
              >
                Closed
              </span>
              <h1 class="text-3xl font-bold leading-tight text-secondary-950 dark:text-white">
                {{ campaign?.title }}
              </h1>
              <p v-if="campaign?.description" class="whitespace-pre-line text-sm leading-6 text-secondary-600 dark:text-secondary-300">
                {{ campaign.description }}
              </p>
              <p v-if="closed" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-900/25 dark:text-amber-200">
                {{ closedMessage }}
              </p>
              <p v-else class="rounded-xl border border-secondary-200 bg-secondary-50 p-4 text-sm text-secondary-600 dark:border-secondary-800 dark:bg-secondary-950 dark:text-secondary-300">
                Your registration will be reviewed by our team before your member profile is approved.
              </p>
            </div>
          </aside>

          <section class="p-5">
            <div v-if="successMessage" class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 dark:border-green-800 dark:bg-green-900/25 dark:text-green-200">
              <h2 class="text-base font-semibold">
                Registration submitted
              </h2>
              <p class="mt-2 text-sm leading-6">
                {{ successMessage }}
              </p>
            </div>

            <form v-else-if="!closed" class="space-y-5" @submit.prevent="submit">
              <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/25 dark:text-red-200">
                {{ errorMessage }}
              </div>

              <section
                v-for="group in groupedFields"
                :key="group.name"
                class="rounded-2xl border border-secondary-200 bg-secondary-50/60 p-4 dark:border-secondary-800 dark:bg-secondary-950/70"
              >
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-secondary-500 dark:text-secondary-400">
                  {{ group.name }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                  <template
                    v-for="field in group.fields"
                    :key="field.field"
                  >
                    <AppSearchableDropdown
                      v-if="field.type === 'select' || field.type === 'payment_plan'"
                      :id="fieldId(field)"
                      v-model="form.fields[field.field]"
                      :label="field.label"
                      :required="field.required"
                      :disabled="!field.editable"
                      :options="field.options || []"
                      :option-label="option => option.label"
                      :option-key="option => option.value"
                      placeholder="Select"
                      search-placeholder="Search options..."
                      no-results-text="No options found."
                      :clearable="!field.required"
                    />

                    <AppFormField
                      v-else
                      :label="field.label"
                      :for-id="fieldId(field)"
                      :required="field.required"
                      :wrapper-class="field.type === 'textarea' ? 'sm:col-span-2' : ''"
                    >
                      <AppFormTextarea
                        v-if="field.type === 'textarea'"
                        :id="fieldId(field)"
                        v-model="form.fields[field.field]"
                        rows="3"
                        :required="field.required"
                        :readonly="!field.editable"
                        class="min-h-24"
                      />

                      <AppFormSwitch
                        v-else-if="field.type === 'boolean'"
                        :id="fieldId(field)"
                        v-model="form.fields[field.field]"
                        :disabled="!field.editable"
                      />

                      <AppFormDateInput
                        v-else-if="field.type === 'date'"
                        :id="fieldId(field)"
                        v-model="form.fields[field.field]"
                        :required="field.required"
                        :disabled="!field.editable"
                      />

                      <AppFormInput
                        v-else
                        :id="fieldId(field)"
                        v-model="form.fields[field.field]"
                        :type="inputType(field.type)"
                        :required="field.required"
                        :readonly="!field.editable"
                      />
                    </AppFormField>
                  </template>
                </div>
              </section>

              <section
                v-if="campaign?.documents?.length"
                class="rounded-2xl border border-secondary-200 bg-secondary-50/60 p-4 dark:border-secondary-800 dark:bg-secondary-950/70"
              >
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-secondary-500 dark:text-secondary-400">
                  Documents
                </h2>
                <div class="space-y-4">
                  <div
                    v-for="documentField in campaign.documents"
                    :key="documentField.key"
                    class="rounded-xl border border-dashed border-secondary-300 bg-white p-4 dark:border-secondary-700 dark:bg-secondary-900"
                  >
                    <AppFormField
                      :label="documentField.title"
                      :for-id="documentFieldId(documentField)"
                      :required="documentField.required"
                      :help="documentHelp(documentField)"
                    >
                      <AppFormFileInput
                        :id="documentFieldId(documentField)"
                        :required="documentField.required"
                        :multiple="documentField.multiple"
                        :accept="acceptFor(documentField)"
                        @change="setDocumentFiles(documentField.key, $event)"
                      />
                    </AppFormField>
                  </div>
                </div>
              </section>

              <button
                type="submit"
                :disabled="submitting"
                class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60 sm:w-auto"
              >
                {{ submitting ? 'Submitting...' : 'Submit registration' }}
              </button>
            </form>
          </section>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AppFormDateInput from '../components/forms/AppFormDateInput.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormFileInput from '../components/forms/AppFormFileInput.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';
import AppFormSwitch from '../components/forms/AppFormSwitch.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';

const props = defineProps({
    slug: { type: String, required: true },
});

const loading = ref(true);
const submitting = ref(false);
const notFound = ref(false);
const closed = ref(false);
const closedMessage = ref('Sorry, this campaign has finished or is closed.');
const errorMessage = ref('');
const successMessage = ref('');
const campaign = ref(null);
const form = ref({ fields: {}, documents: {} });

const groupedFields = computed(() => {
    const groups = [];
    const indexByName = new Map();

    (campaign.value?.fields || []).forEach((field) => {
        if (!indexByName.has(field.group)) {
            indexByName.set(field.group, groups.length);
            groups.push({ name: field.group, fields: [] });
        }

        groups[indexByName.get(field.group)].fields.push(field);
    });

    return groups;
});

const tenantContactLine = computed(() => {
    return [
        campaign.value?.tenant?.address,
        campaign.value?.tenant?.email,
        campaign.value?.tenant?.phone,
    ].filter(Boolean).join(' | ');
});

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function inputType(type) {
    if (type === 'tel') return 'tel';
    if (type === 'email') return 'email';
    if (type === 'money') return 'number';
    return 'text';
}

function safeFieldKey(value) {
    return String(value || '').replace(/[^A-Za-z0-9_-]/g, '-');
}

function fieldId(field) {
    return `campaign-field-${safeFieldKey(field.field)}`;
}

function documentFieldId(documentField) {
    return `campaign-document-${safeFieldKey(documentField.key)}`;
}

function documentHelp(documentField) {
    const uploadLimit = `${(documentField.allowed_types || []).join(', ').toUpperCase()} up to ${documentField.max_size_mb} MB`;

    return [documentField.description, uploadLimit].filter(Boolean).join(' ');
}

function acceptFor(documentField) {
    return (documentField.allowed_types || []).map((type) => `.${type}`).join(',');
}

function setDocumentFiles(key, event) {
    form.value.documents[key] = Array.from(event.target.files || []);
}

function applyCampaign(payload) {
    closed.value = payload.status === 'closed';
    closedMessage.value = payload.message || closedMessage.value;
    campaign.value = payload.campaign || null;

    const fields = {};
    (campaign.value?.fields || []).forEach((field) => {
        if (field.type === 'boolean') {
            fields[field.field] = field.value ?? false;
            return;
        }

        fields[field.field] = field.value ?? '';
    });

    form.value = { fields, documents: {} };
}

async function loadCampaign() {
    loading.value = true;
    errorMessage.value = '';
    notFound.value = false;

    try {
        const response = await fetch(`/api/public/campaigns/${props.slug}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();

        if (!response.ok) {
            notFound.value = true;
            return;
        }

        applyCampaign(data);
    } catch {
        errorMessage.value = 'Failed to load this campaign. Please try again.';
    } finally {
        loading.value = false;
    }
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';

    const data = new FormData();
    Object.entries(form.value.fields).forEach(([key, value]) => {
        data.append(`fields[${key}]`, value === true ? '1' : value === false ? '0' : value ?? '');
    });

    Object.entries(form.value.documents).forEach(([key, files]) => {
        files.forEach((file) => data.append(`documents[${key}][]`, file));
    });

    try {
        const response = await fetch(`/api/public/campaigns/${props.slug}/register`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: data,
        });
        const responseData = await response.json();

        if (!response.ok) {
            const firstError = responseData?.errors ? Object.values(responseData.errors)?.[0]?.[0] : null;
            errorMessage.value = firstError || responseData?.message || 'Registration failed. Please check your details.';
            return;
        }

        successMessage.value = responseData.message || 'Thank you for your registration. Your details have been submitted successfully. Our team will review your information and contact you soon.';
    } catch {
        errorMessage.value = 'Network error. Please try again.';
    } finally {
        submitting.value = false;
    }
}

onMounted(loadCampaign);
</script>
