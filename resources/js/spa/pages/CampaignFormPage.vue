<template>
  <section class="app-page-frame">
    <AppPageHeader show-back>
      <template #cta-slot>
        <div class="flex flex-wrap gap-2">
          <AppHeaderAction
            v-if="isEdit && campaign?.public_url"
            :icon="ExternalLink"
            label="Open Public Page"
            variant="secondary"
            @click="openPublicPage"
          />
          <AppHeaderAction
            :icon="Save"
            :label="submitting ? 'Saving...' : 'Save'"
            :disabled="submitting"
            @click="submit"
          />
        </div>
      </template>
    </AppPageHeader>

    <div class="app-page-scroll">
      <div class="mx-auto grid max-w-7xl gap-4 xl:grid-cols-[minmax(0,1fr)_20rem] 2xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-4">
          <div v-if="errorMessage" class="app-alert app-alert-error">
            {{ errorMessage }}
          </div>
          <div v-if="successMessage" class="app-alert app-alert-success">
            {{ successMessage }}
          </div>

          <section class="app-surface rounded-2xl p-5 md:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-500 dark:text-secondary-400">
                Campaign Details
              </h2>
              <span v-if="isEdit" :class="statusClass(form.status)">
                {{ statusLabel(form.status) }}
              </span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <AppFormField label="Title" required>
                <AppFormInput v-model="form.title" required @input="syncSlug" />
              </AppFormField>
              <AppFormField label="Slug" required>
                <AppFormInput v-model="form.slug" required @input="slugTouched = true" />
              </AppFormField>
              <AppFormField label="Cover Image" optional>
                <AppFormFileInput
                  accept=".jpg,.jpeg,.png,.webp"
                  @change="setCoverImage"
                />
              </AppFormField>
              <AppFormField label="Public URL" optional>
                <div class="flex gap-2">
                  <AppFormInput :model-value="publicUrlPreview" readonly />
                  <button
                    type="button"
                    class="app-icon-button h-12 w-12"
                    title="Copy URL"
                    @click="copyPublicUrl"
                  >
                    <Copy class="h-4 w-4" />
                  </button>
                </div>
              </AppFormField>
              <AppFormField label="Details" optional wrapper-class="md:col-span-2">
                <AppFormTextarea v-model="form.description" rows="4" />
              </AppFormField>
            </div>
          </section>

          <section class="app-surface rounded-2xl p-5 md:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-500 dark:text-secondary-400">
                Member Fields
              </h2>
              <button
                type="button"
                class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-secondary-300 px-3 text-xs font-semibold text-secondary-700 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
                @click="resetFieldConfig"
              >
                <RotateCcw class="h-3.5 w-3.5" />
                Reset
              </button>
            </div>

            <div class="space-y-5">
              <div v-for="group in groupedConfig" :key="group.name" class="rounded-2xl border border-secondary-200 dark:border-secondary-800">
                <header class="border-b border-secondary-200 px-4 py-3 dark:border-secondary-800">
                  <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
                    {{ group.name }}
                  </h3>
                </header>
                <div class="divide-y divide-secondary-200 dark:divide-secondary-800">
                  <article v-for="row in group.rows" :key="row.field" class="grid gap-3 px-4 py-3 xl:grid-cols-[minmax(9rem,1fr)_minmax(13rem,auto)_minmax(10rem,1fr)] xl:items-center">
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                        {{ definition(row.field)?.label || row.field }}
                      </p>
                      <p class="text-xs text-secondary-500 dark:text-secondary-400">
                        {{ fieldTypeLabel(definition(row.field)?.type) }}
                      </p>
                    </div>

                    <div class="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap xl:justify-center">
                      <AppFormCheckbox
                        v-model="row.visible"
                        label="Show"
                        compact
                        @change="normalizeRow(row)"
                      />

                      <AppFormCheckbox
                        v-model="row.required"
                        label="Required"
                        :disabled="!row.visible && !hasConstant(row)"
                        compact
                      />

                      <AppFormCheckbox
                        v-model="row.editable"
                        label="Editable"
                        :disabled="!row.visible"
                        compact
                        @change="normalizeRow(row)"
                      />
                    </div>

                    <div class="min-w-0">
                      <AppSearchableDropdown
                        v-if="definition(row.field)?.type === 'payment_plan'"
                        :model-value="row.constant_value"
                        :options="paymentPlans"
                        :option-label="option => option.name"
                        :option-key="option => option.id"
                        :button-class="compactDropdownButtonClass"
                        placeholder="No fixed value"
                        search-placeholder="Search plans..."
                        no-results-text="No plans found."
                        clearable
                        @update:model-value="setConstantValue(row, $event)"
                      />

                      <AppSearchableDropdown
                        v-else-if="definition(row.field)?.type === 'select'"
                        :model-value="row.constant_value"
                        :options="definition(row.field)?.options || []"
                        :option-label="option => option.label"
                        :option-key="option => option.value"
                        :button-class="compactDropdownButtonClass"
                        placeholder="No fixed value"
                        search-placeholder="Search options..."
                        no-results-text="No options found."
                        clearable
                        @update:model-value="setConstantValue(row, $event)"
                      />

                      <AppSearchableDropdown
                        v-else-if="definition(row.field)?.type === 'boolean'"
                        :model-value="row.constant_value"
                        :options="booleanConstantOptions"
                        :option-label="option => option.label"
                        :option-key="option => option.value"
                        :button-class="compactDropdownButtonClass"
                        placeholder="No fixed value"
                        :searchable="false"
                        clearable
                        @update:model-value="setConstantValue(row, $event)"
                      />

                      <div
                        v-else-if="definition(row.field)?.type === 'date'"
                        class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]"
                      >
                        <AppFormDateInput
                          :model-value="constantDateValue(row)"
                          :placeholder="row.constant_value === todayToken ? 'Today when submitted' : 'Fixed date'"
                          :input-class="compactDateInputClass"
                          @update:model-value="setDateConstant(row, $event)"
                        />
                        <button
                          v-if="row.field === 'joined_date'"
                          type="button"
                          class="inline-flex h-10 items-center justify-center rounded-xl border border-secondary-300 px-3 text-xs font-semibold text-secondary-700 transition hover:bg-secondary-50 dark:border-secondary-700 dark:text-secondary-200 dark:hover:bg-secondary-800"
                          @click="setTodayConstant(row)"
                        >
                          Today
                        </button>
                      </div>

                      <AppFormInput
                        v-else
                        v-model="row.constant_value"
                        class="h-10 rounded-xl"
                        :placeholder="row.field === 'joined_date' ? todayToken : 'Fixed value'"
                        @input="normalizeRow(row)"
                      />
                    </div>
                  </article>
                </div>
              </div>
            </div>
          </section>

          <section class="app-surface rounded-2xl p-5 md:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-500 dark:text-secondary-400">
                Documents
              </h2>
              <button
                type="button"
                class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-primary-600 px-3 text-xs font-semibold text-white transition hover:bg-primary-700"
                @click="addDocument"
              >
                <Plus class="h-3.5 w-3.5" />
                Add Document
              </button>
            </div>

            <div v-if="form.document_config.length === 0" class="rounded-2xl border border-dashed border-secondary-300 px-4 py-8 text-center text-sm text-secondary-500 dark:border-secondary-700 dark:text-secondary-400">
              No document uploads required.
            </div>

            <div v-else class="space-y-3">
              <article v-for="(doc, index) in form.document_config" :key="doc.local_id" class="rounded-2xl border border-secondary-200 p-4 dark:border-secondary-800">
                <div class="mb-3 flex items-center justify-between gap-3">
                  <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
                    Document {{ index + 1 }}
                  </h3>
                  <button
                    type="button"
                    class="app-icon-button text-red-600 dark:text-red-400"
                    title="Remove document"
                    @click="removeDocument(index)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                  <AppFormField label="Title" required>
                    <AppFormInput v-model="doc.title" required @input="syncDocumentKey(doc)" />
                  </AppFormField>
                  <AppFormField label="Key" required>
                    <AppFormInput v-model="doc.key" required />
                  </AppFormField>
                  <AppFormField label="Max Size MB" required>
                    <AppFormInput
                      v-model.number="doc.max_size_mb"
                      type="number"
                      min="1"
                      max="25"
                      required
                    />
                  </AppFormField>
                  <AppFormField label="Options">
                    <div class="flex h-12 flex-wrap items-center gap-4">
                      <AppFormCheckbox v-model="doc.required" label="Required" compact />
                      <AppFormCheckbox v-model="doc.multiple" label="Multiple" compact />
                    </div>
                  </AppFormField>
                  <AppFormField label="Allowed Types" wrapper-class="md:col-span-2">
                    <div class="flex flex-wrap gap-2">
                      <AppFormCheckbox
                        v-for="(label, type) in documentTypeOptions"
                        :key="type"
                        v-model="doc.allowed_types"
                        :value="type"
                        :label="label"
                      />
                    </div>
                  </AppFormField>
                  <AppFormField label="Description" optional wrapper-class="md:col-span-2">
                    <AppFormTextarea v-model="doc.description" rows="2" />
                  </AppFormField>
                </div>
              </article>
            </div>
          </section>
        </div>

        <aside class="min-w-0 space-y-4 xl:sticky xl:top-4 xl:self-start">
          <section v-if="isEdit" class="app-surface rounded-2xl p-5">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-secondary-500 dark:text-secondary-400">
              Status
            </h2>
            <div class="space-y-2">
              <button
                v-if="form.status === 'draft' && context.permissions?.campaignsPublish"
                type="button"
                class="status-action-button bg-green-600 text-white hover:bg-green-700"
                @click="changeStatus('published')"
              >
                <Play class="h-4 w-4" /> Publish
              </button>
              <button
                v-if="form.status === 'published' && context.permissions?.campaignsClose"
                type="button"
                class="status-action-button bg-amber-600 text-white hover:bg-amber-700"
                @click="changeStatus('closed')"
              >
                <Lock class="h-4 w-4" /> Close
              </button>
              <button
                v-if="form.status === 'closed' && context.permissions?.campaignsPublish"
                type="button"
                class="status-action-button bg-green-600 text-white hover:bg-green-700"
                @click="changeStatus('published')"
              >
                <RefreshCw class="h-4 w-4" /> Reopen
              </button>
            </div>
          </section>

          <section class="app-surface rounded-2xl p-5">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-secondary-500 dark:text-secondary-400">
              Preview
            </h2>
            <div class="overflow-hidden rounded-2xl border border-secondary-200 bg-white dark:border-secondary-800 dark:bg-secondary-900">
              <div v-if="coverPreview || campaign?.cover_image_url" class="h-28 bg-secondary-100 dark:bg-secondary-800">
                <img :src="coverPreview || campaign.cover_image_url" alt="" class="h-full w-full object-cover" />
              </div>
              <div class="space-y-3 p-4">
                <div>
                  <p class="text-lg font-bold text-secondary-900 dark:text-white">
                    {{ form.title || 'Campaign title' }}
                  </p>
                  <p v-if="form.description" class="mt-1 line-clamp-3 text-xs leading-5 text-secondary-500 dark:text-secondary-400">
                    {{ form.description }}
                  </p>
                </div>
                <div class="space-y-2">
                  <div v-for="field in previewFields" :key="field.field" class="rounded-xl border border-secondary-200 px-3 py-2 dark:border-secondary-800">
                    <p class="text-xs font-medium text-secondary-700 dark:text-secondary-300">
                      {{ definition(field.field)?.label }}
                      <span v-if="field.required" class="text-red-500">*</span>
                    </p>
                  </div>
                </div>
                <div v-if="form.document_config.length" class="space-y-2">
                  <p class="text-xs font-semibold uppercase tracking-widest text-secondary-500">
                    Documents
                  </p>
                  <div v-for="doc in form.document_config" :key="doc.local_id" class="rounded-xl border border-dashed border-secondary-300 px-3 py-2 text-xs text-secondary-600 dark:border-secondary-700 dark:text-secondary-300">
                    {{ doc.title || 'Document' }}
                    <span v-if="doc.required" class="text-red-500">*</span>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </aside>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    Copy,
    ExternalLink,
    Lock,
    Play,
    Plus,
    RefreshCw,
    RotateCcw,
    Save,
    Trash2,
} from 'lucide-vue-next';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppFormCheckbox from '../components/forms/AppFormCheckbox.vue';
import AppFormDateInput from '../components/forms/AppFormDateInput.vue';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormFileInput from '../components/forms/AppFormFileInput.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppSearchableDropdown from '../components/forms/AppSearchableDropdown.vue';
import AppFormTextarea from '../components/forms/AppFormTextarea.vue';
import { apiRequest } from '../composables/useApiClient';
import { useAppContext } from '../composables/useAppContext';

const route = useRoute();
const router = useRouter();
const context = useAppContext();
const isEdit = computed(() => Boolean(route.params.id));

const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const campaign = ref(null);
const coverFile = ref(null);
const coverPreview = ref('');
const slugTouched = ref(false);

const fieldCatalog = ref([]);
const defaultFieldConfig = ref([]);
const paymentPlans = ref([]);
const documentTypeOptions = ref({});
const todayToken = ref('__today__');
const compactDateInputClass = 'app-form-control h-10 w-full rounded-xl border px-3 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60';
const compactDropdownButtonClass = 'app-form-control flex h-10 w-full min-w-0 items-center gap-2 rounded-xl border px-3 text-left text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 disabled:cursor-not-allowed disabled:opacity-60';
const booleanConstantOptions = [
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
];

const form = ref({
    title: '',
    slug: '',
    description: '',
    status: 'draft',
    field_config: [],
    document_config: [],
});

const publicUrlPreview = computed(() => {
    const slug = form.value.slug || slugify(form.value.title) || 'campaign-slug';
    return `${window.location.origin}/campaigns/${slug}`;
});

const fieldDefinitions = computed(() => new Map(fieldCatalog.value.map((field) => [field.key, field])));

const groupedConfig = computed(() => {
    const groups = [];
    const indexByName = new Map();

    form.value.field_config.forEach((row) => {
        const group = definition(row.field)?.group || 'Other';

        if (!indexByName.has(group)) {
            indexByName.set(group, groups.length);
            groups.push({ name: group, rows: [] });
        }

        groups[indexByName.get(group)].rows.push(row);
    });

    return groups;
});

const previewFields = computed(() => form.value.field_config.filter((row) => row.visible));

function definition(field) {
    return fieldDefinitions.value.get(field);
}

function slugify(value) {
    return (value || '')
        .toString()
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function syncSlug() {
    if (!slugTouched.value) {
        form.value.slug = slugify(form.value.title);
    }
}

function statusLabel(status) {
    return { draft: 'Draft', published: 'Published', closed: 'Closed' }[status] || status;
}

function statusClass(status) {
    const base = 'inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold';
    if (status === 'published') return `${base} border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/25 dark:text-green-300`;
    if (status === 'closed') return `${base} border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/25 dark:text-amber-300`;
    return `${base} border-secondary-200 bg-secondary-100 text-secondary-600 dark:border-secondary-700 dark:bg-secondary-800 dark:text-secondary-300`;
}

function fieldTypeLabel(type) {
    return {
        text: 'Text',
        textarea: 'Long text',
        select: 'Select',
        date: 'Date',
        tel: 'Phone',
        email: 'Email',
        boolean: 'Yes or no',
        payment_plan: 'Payment plan',
        money: 'Amount',
    }[type] || 'Field';
}

function hasConstant(row) {
    return row.constant_value !== null && row.constant_value !== '';
}

function normalizeRow(row) {
    if (!row.visible) {
        row.editable = false;
    }

    if (!row.visible && !hasConstant(row)) {
        row.required = false;
    }
}

function setConstantValue(row, value) {
    row.constant_value = value ?? '';
    normalizeRow(row);
}

function constantDateValue(row) {
    return row.constant_value === todayToken.value ? '' : row.constant_value || '';
}

function setDateConstant(row, value) {
    row.constant_value = value || '';
    normalizeRow(row);
}

function setTodayConstant(row) {
    row.constant_value = todayToken.value;
    normalizeRow(row);
}

function resetFieldConfig() {
    form.value.field_config = clone(defaultFieldConfig.value);
}

function setCoverImage(event) {
    const file = event.target.files?.[0] || null;
    coverFile.value = file;

    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
        coverPreview.value = '';
    }

    if (file) {
        coverPreview.value = URL.createObjectURL(file);
    }
}

function syncDocumentKey(doc) {
    if (!doc.key || doc.key === slugify(doc.previous_title || '')) {
        doc.key = slugify(doc.title);
    }
    doc.previous_title = doc.title;
}

function addDocument() {
    const id = Date.now() + Math.random();
    form.value.document_config.push({
        local_id: id,
        key: `document-${form.value.document_config.length + 1}`,
        title: '',
        description: '',
        required: false,
        allowed_types: ['pdf', 'jpg', 'jpeg', 'png'],
        max_size_mb: 10,
        multiple: false,
        sort_order: form.value.document_config.length,
    });
}

function removeDocument(index) {
    form.value.document_config.splice(index, 1);
}

function clone(value) {
    return JSON.parse(JSON.stringify(value || []));
}

function applyCampaign(data) {
    campaign.value = data;
    form.value = {
        title: data.title || '',
        slug: data.slug || '',
        description: data.description || '',
        status: data.status || 'draft',
        field_config: clone(data.field_config || defaultFieldConfig.value),
        document_config: clone(data.document_config || []).map((doc, index) => ({ ...doc, local_id: `${doc.key}-${index}` })),
    };
    slugTouched.value = true;
}

async function loadMeta() {
    const response = await apiRequest('/api/campaigns/meta');
    fieldCatalog.value = response.field_catalog || [];
    defaultFieldConfig.value = response.default_field_config || [];
    paymentPlans.value = response.payment_plans || [];
    documentTypeOptions.value = response.document_type_options || {};
    todayToken.value = response.today_token || '__today__';

    if (!isEdit.value) {
        resetFieldConfig();
    }
}

async function loadCampaign() {
    if (!isEdit.value) return;

    const response = await apiRequest(`/api/campaigns/${route.params.id}`);
    applyCampaign(response.data);
}

function buildFormData() {
    const data = new FormData();
    data.append('title', form.value.title);
    data.append('slug', slugify(form.value.slug));
    data.append('description', form.value.description || '');
    data.append('field_config', JSON.stringify(form.value.field_config));
    data.append('document_config', JSON.stringify(form.value.document_config.map(({ local_id: _localId, previous_title: _previousTitle, ...doc }) => doc)));

    if (coverFile.value) {
        data.append('cover_image', coverFile.value);
    }

    if (isEdit.value) {
        data.append('_method', 'PUT');
    }

    return data;
}

async function submit() {
    submitting.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await apiRequest(isEdit.value ? `/api/campaigns/${route.params.id}` : '/api/campaigns', {
            method: 'post',
            data: buildFormData(),
        });

        successMessage.value = response.message || 'Campaign saved successfully.';

        if (!isEdit.value) {
            router.push(`/settings/campaigns/${response.data.id}/edit`);
            return;
        }

        if (response.data) {
            applyCampaign(response.data);
        }
    } catch (error) {
        const firstError = error?.response?.data?.errors ? Object.values(error.response.data.errors)?.[0]?.[0] : null;
        errorMessage.value = firstError || error?.response?.data?.message || 'Failed to save campaign.';
    } finally {
        submitting.value = false;
    }
}

async function changeStatus(status) {
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await apiRequest(`/api/campaigns/${route.params.id}/status`, {
            method: 'patch',
            data: { status },
        });
        successMessage.value = response.message || 'Campaign status updated.';
        applyCampaign(response.data);
    } catch (error) {
        const firstError = error?.response?.data?.errors ? Object.values(error.response.data.errors)?.[0]?.[0] : null;
        errorMessage.value = firstError || error?.response?.data?.message || 'Failed to update campaign status.';
    }
}

async function copyPublicUrl() {
    try {
        await navigator.clipboard.writeText(publicUrlPreview.value);
        successMessage.value = 'Public URL copied.';
    } catch {
        errorMessage.value = 'Could not copy the public URL.';
    }
}

function openPublicPage() {
    window.open(publicUrlPreview.value, '_blank', 'noopener,noreferrer');
}

onMounted(async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        await loadMeta();
        await loadCampaign();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load campaign.';
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.status-action-button {
    display: inline-flex;
    min-height: 2.75rem;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: 0.875rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 700;
    transition: background-color 150ms ease;
}
</style>
