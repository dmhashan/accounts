<template>
  <div class="space-y-5 pb-6">
    <!-- Header -->
    <div class="pt-2 pb-1 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
          Body Measurements
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
          Track weight, BMI progress &amp; body tape measurements
        </p>
      </div>

      <button
        type="button"
        class="px-3 py-2 text-xs font-bold rounded-2xl bg-red-500 hover:bg-red-600 active:scale-95 text-white transition-all shadow-md shadow-red-500/20 flex items-center gap-1.5 cursor-pointer shrink-0"
        @click="showModal = true"
      >
        <Plus class="w-4 h-4" :stroke-width="2.2" />
        <span>Log Entry</span>
      </button>
    </div>

    <!-- ── KEY METRICS (Weight, Height, BMI) ────────────────── -->
    <div class="grid grid-cols-3 gap-3">
      <!-- Weight Card -->
      <div class="pp-glass-card rounded-3xl p-4 flex flex-col justify-between shadow-sm">
        <div class="flex items-center justify-between text-gray-400">
          <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-gray-500">Weight</span>
          <Scale class="w-4 h-4 text-red-500" :stroke-width="2" />
        </div>
        <div class="mt-2">
          <p class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
            {{ latestEntry ? latestEntry.weight : '--' }}
            <span class="text-xs font-bold text-gray-400">kg</span>
          </p>
          <p v-if="weightDiff !== null" class="text-[10px] font-extrabold mt-0.5 flex items-center gap-0.5" :class="weightDiff <= 0 ? 'text-emerald-500' : 'text-rose-500'">
            <span>{{ weightDiff > 0 ? '+' : '' }}{{ weightDiff }} kg</span>
            <span class="text-gray-400 font-normal">vs last</span>
          </p>
        </div>
      </div>

      <!-- Height Card -->
      <div class="pp-glass-card rounded-3xl p-4 flex flex-col justify-between shadow-sm">
        <div class="flex items-center justify-between text-gray-400">
          <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-gray-500">Height</span>
          <Ruler class="w-4 h-4 text-blue-500" :stroke-width="2" />
        </div>
        <div class="mt-2">
          <p class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
            {{ latestEntry ? latestEntry.height : '--' }}
            <span class="text-xs font-bold text-gray-400">cm</span>
          </p>
          <p class="text-[10px] font-semibold text-gray-400 mt-0.5">
            Height
          </p>
        </div>
      </div>

      <!-- BMI Card -->
      <div class="pp-glass-card rounded-3xl p-4 flex flex-col justify-between shadow-sm">
        <div class="flex items-center justify-between text-gray-400">
          <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-gray-500">BMI</span>
          <Activity class="w-4 h-4 text-emerald-500" :stroke-width="2" />
        </div>
        <div class="mt-2">
          <p class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
            {{ bmiValue || '--' }}
          </p>
          <span
            v-if="bmiCategory"
            class="inline-block text-[9px] font-extrabold px-1.5 py-0.2 rounded-full mt-0.5 uppercase tracking-wider"
            :class="bmiCategory.badgeClass"
          >
            {{ bmiCategory.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- ── LATEST TAPE MEASUREMENTS GRID ───────────────────── -->
    <section class="space-y-3">
      <div class="flex items-center justify-between px-1">
        <h2 class="text-sm font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
          Tape Measurements
        </h2>
        <span v-if="latestEntry" class="text-xs font-medium text-gray-400">
          Recorded {{ latestEntry.measurement_date }}
        </span>
      </div>

      <div v-if="latestFields.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div
          v-for="field in latestFields"
          :key="field.key"
          class="pp-glass-card rounded-2xl p-3.5 flex items-center justify-between shadow-sm"
        >
          <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-gray-500 truncate">
              {{ field.label }}
            </p>
            <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">
              {{ field.value !== null ? field.value : '-' }}
              <span v-if="field.value !== null" class="text-xs font-bold text-gray-400">in</span>
            </p>
          </div>
          <div
            v-if="getFieldDiff(field.key) !== null"
            class="text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0"
            :class="getFieldDiff(field.key) <= 0 ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/50 text-rose-500 dark:text-rose-400'"
          >
            {{ getFieldDiff(field.key) > 0 ? '+' : '' }}{{ getFieldDiff(field.key) }}
          </div>
        </div>
      </div>

      <div v-else class="pp-glass-card rounded-3xl p-8 text-center text-gray-400">
        <Scale class="w-8 h-8 mx-auto text-gray-300 dark:text-zinc-600 mb-2" :stroke-width="1.5" />
        <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
          No body measurements recorded yet
        </p>
        <p class="text-xs text-gray-400 mt-0.5">
          Click "Log Entry" above to add your first measurement record.
        </p>
      </div>
    </section>

    <!-- ── HISTORY LOG ──────────────────────────────────────── -->
    <section v-if="records.length" class="space-y-3">
      <h2 class="text-sm font-extrabold text-gray-900 dark:text-white tracking-tight uppercase px-1">
        Measurement History
      </h2>

      <div class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <div
          v-for="rec in records"
          :key="rec.id"
          class="p-4 space-y-2 hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Calendar class="w-4 h-4 text-red-500" :stroke-width="2" />
              <span class="text-sm font-bold text-gray-900 dark:text-white">
                {{ rec.measurement_date }}
              </span>
            </div>
            <div class="flex items-center gap-3 text-xs font-bold">
              <span class="text-gray-900 dark:text-white">{{ rec.weight }} kg</span>
              <span class="text-gray-400">&bull;</span>
              <span class="text-gray-600 dark:text-gray-300">{{ rec.height }} cm</span>
            </div>
          </div>

          <div v-if="rec.notes" class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-zinc-800/50 p-2 rounded-xl">
            {{ rec.notes }}
          </div>
        </div>
      </div>
    </section>

    <!-- ── LOG ENTRY MODAL ──────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md" @click="showModal = false" />

        <div class="relative w-full max-w-lg bg-white dark:bg-zinc-900 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl max-h-[90vh] flex flex-col z-10 overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-zinc-800 shrink-0">
            <h3 class="text-base font-extrabold text-gray-900 dark:text-white">
              Log Body Measurement
            </h3>
            <button
              type="button"
              class="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 text-gray-500 hover:text-gray-800 dark:hover:text-white transition-colors"
              @click="showModal = false"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <form class="p-5 overflow-y-auto space-y-4 flex-1" @submit.prevent="submitMeasurement">
            <div v-if="formError" class="p-3 rounded-2xl bg-red-500/10 text-red-600 text-xs font-bold border border-red-500/20">
              {{ formError }}
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                  Weight (kg) *
                </label>
                <input
                  v-model.number="form.weight"
                  type="number"
                  step="0.1"
                  required
                  placeholder="70.5"
                  class="w-full px-3.5 py-2.5 text-sm rounded-2xl bg-gray-100 dark:bg-zinc-800 border border-transparent focus:border-red-500 text-gray-900 dark:text-white focus:outline-none"
                />
              </div>

              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                  Height (cm) *
                </label>
                <input
                  v-model.number="form.height"
                  type="number"
                  step="0.5"
                  required
                  placeholder="175"
                  class="w-full px-3.5 py-2.5 text-sm rounded-2xl bg-gray-100 dark:bg-zinc-800 border border-transparent focus:border-red-500 text-gray-900 dark:text-white focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                Measurement Date *
              </label>
              <input
                v-model="form.measurement_date"
                type="date"
                required
                class="w-full px-3.5 py-2.5 text-sm rounded-2xl bg-gray-100 dark:bg-zinc-800 border border-transparent focus:border-red-500 text-gray-900 dark:text-white focus:outline-none"
              />
            </div>

            <!-- Tape Measurement Fields -->
            <div v-if="fieldsList.length" class="space-y-2 pt-2 border-t border-gray-100 dark:border-zinc-800">
              <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Tape Measurements (Inches)
              </label>

              <div class="grid grid-cols-2 gap-2.5">
                <div v-for="field in fieldsList" :key="field.key">
                  <label class="block text-[10px] font-semibold text-gray-400 truncate mb-1">
                    {{ field.label }}
                  </label>
                  <input
                    v-model.number="form.measurements[field.key]"
                    type="number"
                    step="0.1"
                    placeholder="0.0"
                    class="w-full px-3 py-2 text-xs rounded-xl bg-gray-100 dark:bg-zinc-800 border border-transparent focus:border-red-500 text-gray-900 dark:text-white focus:outline-none"
                  />
                </div>
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                Notes / Progress Remarks
              </label>
              <textarea
                v-model="form.notes"
                rows="2"
                placeholder="Feeling energetic, reduced body fat..."
                class="w-full px-3.5 py-2.5 text-sm rounded-2xl bg-gray-100 dark:bg-zinc-800 border border-transparent focus:border-red-500 text-gray-900 dark:text-white focus:outline-none"
              />
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="saving"
                class="w-full py-3.5 rounded-2xl bg-red-500 hover:bg-red-600 text-white font-bold text-sm shadow-md active:scale-98 transition-all disabled:opacity-50 cursor-pointer"
              >
                <span v-if="saving">Saving entry...</span>
                <span v-else>Save Body Measurement</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { Scale, Ruler, Activity, Plus, Calendar, X } from 'lucide-vue-next';

const props = defineProps({
    meta:                  { type: Object, default: () => ({}) },
    bodyMeasurements:      { type: Array,  default: () => [] },
    bodyMeasurementFields: { type: Array,  default: () => [] },
    bodyMeasurementLatest: { type: Object, default: () => null },
    bodyMeasurementPrevious: { type: Object, default: () => null },
});

const MEMBER_KEY = 'public_profile_member_id';

const records    = ref([...props.bodyMeasurements]);
const fieldsList = ref([...props.bodyMeasurementFields]);
const showModal  = ref(false);
const saving     = ref(false);
const formError  = ref('');

const form = reactive({
    weight: null,
    height: null,
    measurement_date: new Date().toISOString().split('T')[0],
    measurements: {},
    notes: '',
});

function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const latestEntry = computed(() => records.value[0] || props.bodyMeasurementLatest || null);
const prevEntry   = computed(() => records.value[1] || props.bodyMeasurementPrevious || null);

const weightDiff = computed(() => {
    if (!latestEntry.value || !prevEntry.value) return null;
    const diff = latestEntry.value.weight - prevEntry.value.weight;
    return Math.round(diff * 10) / 10;
});

const bmiValue = computed(() => {
    if (!latestEntry.value || !latestEntry.value.weight || !latestEntry.value.height) return null;
    const heightInMeters = latestEntry.value.height / 100;
    const bmi = latestEntry.value.weight / (heightInMeters * heightInMeters);
    return Math.round(bmi * 10) / 10;
});

const bmiCategory = computed(() => {
    const val = bmiValue.value;
    if (!val) return null;
    if (val < 18.5) return { label: 'Underweight', badgeClass: 'bg-blue-500/10 text-blue-500' };
    if (val < 25.0) return { label: 'Normal Weight', badgeClass: 'bg-emerald-500/10 text-emerald-500' };
    if (val < 30.0) return { label: 'Overweight', badgeClass: 'bg-amber-500/10 text-amber-500' };
    return { label: 'Obese', badgeClass: 'bg-rose-500/10 text-rose-500' };
});

const latestFields = computed(() => {
    if (!latestEntry.value) return [];
    if (latestEntry.value.measurement_fields && latestEntry.value.measurement_fields.length) {
        return latestEntry.value.measurement_fields.filter(f => f.enabled !== false);
    }
    return fieldsList.value.map(f => ({
        ...f,
        value: latestEntry.value.measurements?.[f.key] ?? null,
    }));
});

function getFieldDiff(key) {
    if (!latestEntry.value || !prevEntry.value) return null;
    const cur  = latestEntry.value.measurements?.[key];
    const prev = prevEntry.value.measurements?.[key];
    if (cur === undefined || prev === undefined || cur === null || prev === null) return null;
    const diff = cur - prev;
    return Math.round(diff * 10) / 10;
}

onMounted(() => {
    if (latestEntry.value) {
        form.weight = latestEntry.value.weight;
        form.height = latestEntry.value.height;
    }
});

async function submitMeasurement() {
    formError.value = '';
    saving.value = true;
    try {
        const token = localStorage.getItem(MEMBER_KEY);
        const res = await fetch('/api/public/body-measurements', {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN':     getCsrfToken(),
                'X-PP-Token':       token || '',
            },
            body: JSON.stringify(form),
        });

        const data = await res.json();
        if (!res.ok) {
            formError.value = data.message || 'Failed to save measurement.';
            return;
        }

        if (data.data) {
            records.value.unshift(data.data);
        }
        showModal.value = false;
        form.notes = '';
    } catch {
        formError.value = 'Network error. Please try again.';
    } finally {
        saving.value = false;
    }
}
</script>
