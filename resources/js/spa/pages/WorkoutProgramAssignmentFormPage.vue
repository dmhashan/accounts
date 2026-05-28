<template>
  <section class="app-page-frame">
    <AppPageHeader show-back title="Assign Program to Member(s)" />

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div class="app-page-scroll">
      <form class="space-y-5" @submit.prevent="submit">
        <!-- Program selector -->
        <div class="app-surface rounded-2xl p-4 space-y-4">
          <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
            Program Details
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <AppFormField label="Workout Program" required>
              <AppFormSelect v-model="form.program_id" required>
                <option value="">
                  — Select program —
                </option>
                <option v-for="p in programs" :key="p.id" :value="p.id">
                  {{ p.title }}
                </option>
              </AppFormSelect>
            </AppFormField>
            <AppFormField label="Effective Date" required>
              <AppFormInput v-model="form.effective_date" type="date" required />
            </AppFormField>
          </div>
        </div>

        <!-- Member multi-picker -->
        <div class="app-surface rounded-2xl p-4 space-y-3">
          <h3 class="text-sm font-semibold text-secondary-900 dark:text-white">
            Select Members
          </h3>

          <AppFormInput
            v-model="memberSearchQuery"
            type="text"
            placeholder="Search members by name or code..."
            @input="searchMembers"
          />

          <div v-if="memberOptions.length > 0" class="max-h-52 overflow-y-auto rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800 divide-y divide-secondary-100 dark:divide-secondary-700">
            <button
              v-for="m in memberOptions"
              :key="m.id"
              type="button"
              class="w-full flex items-center justify-between px-3 py-2 text-sm text-left hover:bg-secondary-50 dark:hover:bg-secondary-700/50"
              :class="form.member_ids.includes(m.id) ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-secondary-800 dark:text-secondary-200'"
              @click="toggleMember(m)"
            >
              <span>{{ m.name }} <span class="text-secondary-400 font-normal">({{ m.phone_number }})</span></span>
              <span v-if="form.member_ids.includes(m.id)" class="text-primary-500">✓</span>
            </button>
          </div>
          <p v-else-if="memberSearchQuery.trim().length > 0" class="text-xs text-secondary-400">
            No members found.
          </p>

          <!-- Selected chips -->
          <div v-if="selectedMemberObjects.length > 0" class="flex flex-wrap gap-2 pt-1">
            <span
              v-for="m in selectedMemberObjects"
              :key="m.id"
              class="inline-flex items-center gap-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs px-3 py-1"
            >
              {{ m.name }}
              <button type="button" class="ml-1 text-primary-400 hover:text-primary-600" @click="toggleMember(m)">×</button>
            </span>
          </div>
        </div>

        <div v-if="formError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
          {{ formError }}
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="submitting"
            class="inline-flex items-center px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white text-sm font-semibold"
          >
            {{ submitting ? 'Assigning…' : 'Assign Program' }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppPageHeader from '../components/AppPageHeader.vue';
import { apiRequest } from '../composables/useApiClient';
import AppFormField from '../components/forms/AppFormField.vue';
import AppFormInput from '../components/forms/AppFormInput.vue';
import AppFormSelect from '../components/forms/AppFormSelect.vue';

const router = useRouter();

const programs = ref([]);
const errorMessage = ref('');
const formError = ref('');
const submitting = ref(false);

const form = ref({
    program_id: '',
    member_ids: [],
    effective_date: '',
});

const memberOptions = ref([]);
const memberSearchQuery = ref('');
const memberSearchCache = ref({});

const selectedMemberObjects = computed(() => {
    const ids = form.value.member_ids;
    const seen = new Map();
    for (const list of Object.values(memberSearchCache.value)) {
        for (const m of list) {
            if (ids.includes(m.id)) seen.set(m.id, m);
        }
    }
    return ids.map((id) => seen.get(id)).filter(Boolean);
});

async function loadPrograms() {
    try {
        const response = await apiRequest('/api/workout-programs', { params: { per_page: 100 } });
        programs.value = response?.data?.data || [];
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load programs.';
    }
}

async function loadMembers(query = '') {
    const cacheKey = query.trim().toLowerCase();
    if (memberSearchCache.value[cacheKey]) {
        memberOptions.value = memberSearchCache.value[cacheKey];
        return;
    }

    try {
        const response = await apiRequest('/api/workout-program-assignment-members', {
            params: { per_page: 30, search: query },
        });
        const list = response?.data?.data || [];
        memberSearchCache.value[cacheKey] = list;
        memberOptions.value = list;
    } catch (error) {
        memberOptions.value = [];
        errorMessage.value = error?.response?.data?.message || 'Failed to load members.';
    }
}

let memberSearchTimer = null;
function searchMembers() {
    clearTimeout(memberSearchTimer);
    memberSearchTimer = setTimeout(() => {
        loadMembers(memberSearchQuery.value);
    }, 300);
}

function toggleMember(member) {
    const ids = form.value.member_ids;
    const idx = ids.indexOf(member.id);
    if (idx === -1) {
        ids.push(member.id);
        const key = memberSearchQuery.value.trim().toLowerCase();
        if (!memberSearchCache.value[key]) memberSearchCache.value[key] = [];
        if (!memberSearchCache.value[key].find((m) => m.id === member.id)) {
            memberSearchCache.value[key].push(member);
        }
    } else {
        ids.splice(idx, 1);
    }
}

async function submit() {
    formError.value = '';

    if (!form.value.program_id) {
        formError.value = 'Please select a program.';
        return;
    }
    if (!form.value.effective_date) {
        formError.value = 'Please select an effective date.';
        return;
    }
    if (form.value.member_ids.length === 0) {
        formError.value = 'Please select at least one member.';
        return;
    }

    submitting.value = true;
    try {
        await apiRequest('/api/workout-program-assignments', {
            method: 'post',
            data: {
                program_id: form.value.program_id,
                member_ids: form.value.member_ids,
                effective_date: form.value.effective_date,
            },
        });
        router.push('/workout?tab=assignments');
    } catch (error) {
        formError.value = error?.response?.data?.message || 'Failed to create assignment.';
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    loadPrograms();
    loadMembers('');
});
</script>
