<template>
  <div class="space-y-4">
    <div v-if="errorMessage" class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>
    <div v-if="successMessage" class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
      {{ successMessage }}
    </div>

    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
          <TrendingUp class="w-4 h-4 text-primary-500 shrink-0" :stroke-width="2" />
          <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500 truncate">
            Transformation Tracker
          </h2>
        </div>
        <button
          v-if="canManage"
          type="button"
          class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white transition-colors"
          @click="openCreateModal"
        >
          <Plus class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Measurement</span>
        </button>
      </div>

      <div v-if="loading" class="px-5 py-10 text-center text-sm text-secondary-400">
        Loading...
      </div>

      <div v-else-if="!latestRecord" class="px-5 py-10 text-center text-sm text-secondary-400 dark:text-secondary-500">
        No body measurements recorded yet.
      </div>

      <div v-else class="p-5 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 rounded-xl border border-secondary-100 dark:border-secondary-800 overflow-hidden">
          <div class="px-4 py-3 border-b sm:border-b-0 sm:border-r border-secondary-100 dark:border-secondary-800">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500">
              Date
            </p>
            <p class="mt-1 text-sm font-semibold text-secondary-900 dark:text-white">
              {{ formatDate(latestRecord.measurement_date) }}
            </p>
          </div>
          <div class="px-4 py-3 border-b sm:border-b-0 sm:border-r border-secondary-100 dark:border-secondary-800">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500">
              Weight
            </p>
            <p class="mt-1 text-sm font-semibold text-secondary-900 dark:text-white">
              {{ formatMeasurement(latestRecord.weight) }}
            </p>
          </div>
          <div class="px-4 py-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500">
              Height
            </p>
            <p class="mt-1 text-sm font-semibold text-secondary-900 dark:text-white">
              {{ formatMeasurement(latestRecord.height) }}
            </p>
          </div>
        </div>

        <div v-if="trackerTrend.hasData" class="rounded-xl border border-secondary-100 dark:border-secondary-800 px-4 py-3">
          <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500">
              Progress Graph
            </p>
            <div class="flex items-center gap-3 text-[10px] font-medium text-secondary-500 dark:text-secondary-400">
              <span class="inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-primary-500" />
                Weight
              </span>
              <span class="inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Height
              </span>
            </div>
          </div>

          <svg
            class="mt-3 h-24 w-full text-secondary-300 dark:text-secondary-700"
            viewBox="0 0 160 56"
            preserveAspectRatio="none"
            aria-hidden="true"
          >
            <line
              x1="6"
              y1="48"
              x2="154"
              y2="48"
              stroke="currentColor"
              stroke-width="1"
              stroke-dasharray="4 4"
            />
            <polyline
              v-if="trackerTrend.weightPoints"
              :points="trackerTrend.weightPoints"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="text-primary-500 dark:text-primary-400"
            />
            <polyline
              v-if="trackerTrend.heightPoints"
              :points="trackerTrend.heightPoints"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="text-emerald-500 dark:text-emerald-400"
            />
            <circle
              v-if="trackerTrend.latestWeightPoint"
              :cx="trackerTrend.latestWeightPoint.x"
              :cy="trackerTrend.latestWeightPoint.y"
              r="3"
              class="fill-primary-600 dark:fill-primary-300"
            />
            <circle
              v-if="trackerTrend.latestHeightPoint"
              :cx="trackerTrend.latestHeightPoint.x"
              :cy="trackerTrend.latestHeightPoint.y"
              r="2.5"
              class="fill-emerald-600 dark:fill-emerald-300"
            />
          </svg>
        </div>

        <div v-if="comparisonRows.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-secondary-100 dark:divide-secondary-800 text-sm">
            <thead>
              <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500">
                <th class="py-2 pr-4">
                  Measurement
                </th>
                <th class="py-2 pr-4">
                  {{ formatDate(latestRecord.measurement_date) }}
                </th>
                <th class="py-2 pr-4">
                  {{ previousRecord ? formatDate(previousRecord.measurement_date) : 'Previous' }}
                </th>
                <th class="py-2 text-right">
                  Change
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
              <tr v-for="row in comparisonRows" :key="row.key">
                <td class="py-2 pr-4 font-medium text-secondary-700 dark:text-secondary-200">
                  {{ row.label }}
                </td>
                <td class="py-2 pr-4 text-secondary-700 dark:text-secondary-300">
                  {{ formatMeasurement(row.latest) }}
                </td>
                <td class="py-2 pr-4 text-secondary-500 dark:text-secondary-400">
                  {{ formatMeasurement(row.previous) }}
                </td>
                <td class="py-2 text-right font-semibold" :class="deltaClass(row.delta)">
                  {{ formatDelta(row.delta) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Measurement History
        </h2>
      </div>

      <div v-if="loading" class="px-5 py-6 text-center text-sm text-secondary-400">
        Loading...
      </div>

      <div v-else-if="records.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">
        No measurement history.
      </div>

      <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
        <div class="hidden sm:grid grid-cols-[minmax(0,1fr)_auto] gap-4 px-5 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500">
          <span>Record</span>
          <span class="text-right">Actions</span>
        </div>
        <div v-for="record in records" :key="record.id" class="px-5 py-4 grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-4 sm:items-start">
          <div class="min-w-0">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-secondary-900 dark:text-white">
                {{ formatDate(record.measurement_date) }}
              </p>
              <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">
                Weight: <span class="font-medium text-secondary-700 dark:text-secondary-300">{{ formatMeasurement(record.weight) }}</span>
                <span class="mx-1.5 text-secondary-300 dark:text-secondary-600">|</span>
                Height: <span class="font-medium text-secondary-700 dark:text-secondary-300">{{ formatMeasurement(record.height) }}</span>
              </p>
            </div>

            <div v-if="record.measurement_fields?.length" class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2">
              <div v-for="field in record.measurement_fields" :key="field.key" class="min-w-0">
                <p class="text-[11px] font-medium text-secondary-400 dark:text-secondary-500 truncate">
                  {{ field.label }}
                </p>
                <p class="text-sm text-secondary-700 dark:text-secondary-300">
                  {{ formatMeasurement(field.value) }}
                </p>
              </div>
            </div>

            <p v-if="record.notes" class="mt-3 text-xs text-secondary-500 dark:text-secondary-400">
              {{ record.notes }}
            </p>
          </div>

          <div v-if="canManage" class="flex items-center justify-end gap-1 shrink-0">
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-secondary-200 dark:border-secondary-700 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800"
              title="Edit measurement"
              @click="openEditModal(record)"
            >
              <Pencil class="h-3.5 w-3.5" />
            </button>
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20"
              title="Delete measurement"
              @click="recordPendingDelete = record"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="meta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
        <p class="text-xs text-secondary-500 dark:text-secondary-400">
          Page {{ meta.current_page }} of {{ meta.last_page }}
        </p>
        <div class="flex gap-1">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
            :disabled="meta.current_page <= 1"
            @click="loadMeasurements(meta.current_page - 1)"
          >
            Prev
          </button>
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40"
            :disabled="meta.current_page >= meta.last_page"
            @click="loadMeasurements(meta.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>

  <Teleport to="body">
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-black/60 overflow-y-auto">
      <div class="w-full max-w-3xl rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-xl">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-secondary-200 dark:border-secondary-700">
          <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
            {{ editingRecord ? 'Edit Measurement' : 'Add Measurement' }}
          </h3>
          <button
            type="button"
            class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 disabled:opacity-50"
            :disabled="submitting"
            @click="closeModal"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="p-5 space-y-4" @submit.prevent="submitMeasurement">
          <div v-if="formError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
            {{ formError }}
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <AppFormField label="Weight" required>
              <AppFormInput
                v-model.number="form.weight"
                type="number"
                min="0.01"
                max="1000"
                step="0.01"
                required
                placeholder="0.00"
              />
            </AppFormField>
            <AppFormField label="Height" required>
              <AppFormInput
                v-model.number="form.height"
                type="number"
                min="0.01"
                max="300"
                step="0.01"
                required
                placeholder="0.00"
              />
            </AppFormField>
            <AppFormField label="Measurement Date" required>
              <AppFormDateInput
                v-model="form.measurement_date"
                required
                :max="today"
              />
            </AppFormField>
          </div>

          <div v-if="fields.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <AppFormField
              v-for="field in fields"
              :key="field.key"
              :label="field.label"
              optional
            >
              <AppFormInput
                v-model.number="form.measurements[field.key]"
                type="number"
                min="0"
                max="1000"
                step="0.01"
                placeholder="0.00"
              />
            </AppFormField>
          </div>

          <AppFormField label="Notes" optional>
            <textarea
              v-model.trim="form.notes"
              rows="3"
              maxlength="1000"
              class="app-form-control w-full rounded-2xl border px-4 py-3 text-sm shadow-[0_1px_2px_rgba(15,23,42,0.04)] outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 resize-none"
            />
          </AppFormField>

          <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-1">
            <button
              type="button"
              class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 disabled:opacity-50"
              :disabled="submitting"
              @click="closeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50"
              :disabled="submitting || !form.weight || !form.height || !form.measurement_date"
            >
              {{ submitting ? 'Saving...' : 'Save Measurement' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <AppConfirmModal
    v-if="recordPendingDelete"
    title="Delete Measurement"
    message="Are you sure you want to delete this body measurement?"
    confirm-label="Delete"
    loading-label="Deleting..."
    :loading="deleting"
    @confirm="deleteMeasurement"
    @cancel="recordPendingDelete = null"
  />
</template>

<script setup>
import { computed, ref } from 'vue';
import { Pencil, Plus, Trash2, TrendingUp, X } from 'lucide-vue-next';
import AppConfirmModal from '../AppConfirmModal.vue';
import AppFormDateInput from '../forms/AppFormDateInput.vue';
import AppFormField from '../forms/AppFormField.vue';
import AppFormInput from '../forms/AppFormInput.vue';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
    canManage: { type: Boolean, default: false },
});

const { formatDate } = useMemberFormatters();

const loading = ref(false);
const records = ref([]);
const fields = ref([]);
const latestRecord = ref(null);
const previousRecord = ref(null);
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const errorMessage = ref('');
const successMessage = ref('');

const today = currentDate();
const modalOpen = ref(false);
const editingRecord = ref(null);
const submitting = ref(false);
const formError = ref('');
const form = ref(blankForm());
const recordPendingDelete = ref(null);
const deleting = ref(false);

const comparisonRows = computed(() => {
    if (!latestRecord.value) return [];

    const rows = [
        {
            key: 'weight',
            label: 'Weight',
            latest: latestRecord.value.weight,
            previous: previousRecord.value?.weight ?? null,
        },
        {
            key: 'height',
            label: 'Height',
            latest: latestRecord.value.height,
            previous: previousRecord.value?.height ?? null,
        },
        ...fields.value.map((field) => ({
            key: field.key,
            label: field.label,
            latest: latestRecord.value.measurements?.[field.key] ?? null,
            previous: previousRecord.value?.measurements?.[field.key] ?? null,
        })),
    ];

    return rows.map((row) => ({
        ...row,
        delta: numericValue(row.latest) !== null && numericValue(row.previous) !== null
            ? numericValue(row.latest) - numericValue(row.previous)
            : null,
    }));
});

const trackerTrend = computed(() => {
    const chronological = [...records.value].sort(compareRecordsAscending);
    const weightScale = measurementScale(chronological.map((record) => numericValue(record.weight)));
    const heightScale = measurementScale(chronological.map((record) => numericValue(record.height)));
    const weightPoints = sparklinePoints(chronological, 'weight', weightScale);
    const heightPoints = sparklinePoints(chronological, 'height', heightScale);

    return {
        hasData: Boolean(weightPoints || heightPoints),
        weightPoints,
        heightPoints,
        latestWeightPoint: latestRecord.value ? sparklinePointForRecord(chronological, latestRecord.value, 'weight', weightScale) : null,
        latestHeightPoint: latestRecord.value ? sparklinePointForRecord(chronological, latestRecord.value, 'height', heightScale) : null,
    };
});

function currentDate() {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function blankForm() {
    return {
        weight: '',
        height: '',
        measurement_date: currentDate(),
        measurements: {},
        notes: '',
    };
}

async function loadMeasurements(page = 1) {
    loading.value = true;
    errorMessage.value = '';
    try {
        const response = await apiRequest(`/api/members/${props.memberId}/body-measurements?page=${page}&per_page=15`);
        fields.value = response.fields || [];
        records.value = response.data || [];
        meta.value = response.meta || meta.value;
        latestRecord.value = response.latest || null;
        previousRecord.value = response.previous || null;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load body measurements.';
    } finally {
        loading.value = false;
    }
}

function openCreateModal() {
    editingRecord.value = null;
    form.value = withMeasurementFields(formFromLatestRecord());
    formError.value = '';
    modalOpen.value = true;
}

function openEditModal(record) {
    editingRecord.value = record;
    form.value = withMeasurementFields({
        weight: record.weight,
        height: record.height,
        measurement_date: record.measurement_date,
        measurements: { ...(record.measurements || {}) },
        notes: record.notes || '',
    });
    formError.value = '';
    modalOpen.value = true;
}

function closeModal(force = false) {
    if (submitting.value && !force) return;

    modalOpen.value = false;
    editingRecord.value = null;
    formError.value = '';
}

function withMeasurementFields(source) {
    const next = { ...source, measurements: { ...(source.measurements || {}) } };

    fields.value.forEach((field) => {
        if (!Object.prototype.hasOwnProperty.call(next.measurements, field.key)) {
            next.measurements[field.key] = '';
        }
    });

    return next;
}

async function submitMeasurement() {
    submitting.value = true;
    formError.value = '';
    successMessage.value = '';

    const payload = {
        weight: form.value.weight,
        height: form.value.height,
        measurement_date: form.value.measurement_date,
        measurements: cleanMeasurements(form.value.measurements),
        notes: form.value.notes || null,
    };

    try {
        const isEditing = Boolean(editingRecord.value);
        const endpoint = isEditing
            ? `/api/members/${props.memberId}/body-measurements/${editingRecord.value.id}`
            : `/api/members/${props.memberId}/body-measurements`;
        const method = isEditing ? 'put' : 'post';

        await apiRequest(endpoint, { method, data: payload });
        closeModal(true);
        successMessage.value = isEditing ? 'Body measurement updated successfully.' : 'Body measurement saved successfully.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
        await loadMeasurements(1);
    } catch (error) {
        formError.value = error?.response?.data?.message || 'Failed to save body measurement.';
    } finally {
        submitting.value = false;
    }
}

async function deleteMeasurement() {
    if (!recordPendingDelete.value) return;

    deleting.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await apiRequest(`/api/members/${props.memberId}/body-measurements/${recordPendingDelete.value.id}`, { method: 'delete' });
        recordPendingDelete.value = null;
        successMessage.value = 'Body measurement deleted successfully.';
        setTimeout(() => { successMessage.value = ''; }, 3000);
        await loadMeasurements(1);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete body measurement.';
    } finally {
        deleting.value = false;
    }
}

function cleanMeasurements(values) {
    return fields.value.reduce((carry, field) => {
        const value = values?.[field.key];

        if (value !== null && value !== undefined && value !== '') {
            carry[field.key] = value;
        }

        return carry;
    }, {});
}

function numericValue(value) {
    if (value === null || value === undefined || value === '') return null;
    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function formatMeasurement(value) {
    const number = numericValue(value);

    return number === null ? 'Not recorded' : number.toFixed(2);
}

function formatDelta(value) {
    const number = numericValue(value);

    if (number === null) return 'No baseline';
    if (number === 0) return '0.00';

    return `${number > 0 ? '+' : ''}${number.toFixed(2)}`;
}

function deltaClass(value) {
    const number = numericValue(value);

    if (number === null || number === 0) return 'text-secondary-400 dark:text-secondary-500';

    return number > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
}

function formFromLatestRecord() {
    if (!latestRecord.value) return blankForm();

    return {
        weight: latestRecord.value.weight ?? '',
        height: latestRecord.value.height ?? '',
        measurement_date: currentDate(),
        measurements: { ...(latestRecord.value.measurements || {}) },
        notes: '',
    };
}

function compareRecordsAscending(a, b) {
    const dateCompare = String(a.measurement_date || '').localeCompare(String(b.measurement_date || ''));

    if (dateCompare !== 0) return dateCompare;

    return Number(a.id || 0) - Number(b.id || 0);
}

function measurementScale(values) {
    const valid = values.filter((value) => value !== null);
    const min = valid.length ? Math.min(...valid) : 0;
    const max = valid.length ? Math.max(...valid) : 0;
    const range = max - min || 1;

    return (value) => {
        if (value === null) return null;

        return 48 - ((value - min) / range) * 40;
    };
}

function sparklineX(index, total) {
    if (total <= 1) return 80;

    return 6 + (index / (total - 1)) * 148;
}

function sparklinePoints(chronological, key, scale) {
    return chronological
        .map((record, index) => {
            const value = numericValue(record[key]);
            const y = scale(value);

            return y === null ? null : `${sparklineX(index, chronological.length).toFixed(2)},${y.toFixed(2)}`;
        })
        .filter(Boolean)
        .join(' ');
}

function sparklinePointForRecord(chronological, record, key, scale) {
    const index = chronological.findIndex((item) => Number(item.id) === Number(record.id));

    if (index < 0) return null;

    const value = numericValue(record[key]);
    const y = scale(value);

    if (y === null) return null;

    return {
        x: sparklineX(index, chronological.length).toFixed(2),
        y: y.toFixed(2),
    };
}

defineExpose({ loadMeasurements });
</script>
