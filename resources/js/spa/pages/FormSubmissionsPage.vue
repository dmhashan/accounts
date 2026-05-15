<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" :title="templateTitle ? templateTitle + ' — Submissions' : 'Form Submissions'">
            <template #cta-slot>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-full bg-primary-600 hover:bg-primary-700 text-white transition-colors shrink-0"
                    @click="openFillModal"
                >
                    <Plus class="w-4 h-4" :stroke-width="2.5" />
                    Fill Form
                </button>
            </template>
            <template #extra-slot>
                <AppSearchField v-model="search" placeholder="Search by member…" :disabled="loading" @search="() => {}" />
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="app-alert app-alert-error">{{ errorMessage }}</div>
        <div v-if="successMessage" class="app-alert app-alert-success">{{ successMessage }}</div>

        <div class="min-h-0 flex flex-1 flex-col">
            <div class="app-page-scroll">
                <div class="app-surface rounded-2xl overflow-hidden">

                    <div v-if="loading" class="divide-y divide-secondary-200 dark:divide-secondary-700">
                        <div v-for="i in 5" :key="i" class="p-4 space-y-2">
                            <div class="app-skeleton h-3.5 w-40 rounded"></div>
                            <div class="app-skeleton h-3 w-64 rounded"></div>
                        </div>
                    </div>

                    <template v-else-if="filtered.length === 0">
                        <AppEmptyState :icon="ClipboardCheck" title="No submissions yet" description="Fill in the form for a member to get started." />
                    </template>

                    <template v-else>
                        <!-- Mobile cards -->
                        <div class="md:hidden divide-y divide-secondary-200 dark:divide-secondary-700">
                            <article
                                v-for="s in filtered"
                                :key="s.id"
                                class="p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-secondary-900 dark:text-white truncate">
                                            {{ s.member?.name || '—' }}
                                            <span v-if="s.member?.member_id" class="text-xs font-normal text-secondary-400 dark:text-secondary-500 ml-1.5">{{ s.member.member_id }}</span>
                                        </p>
                                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">
                                            Submitted {{ s.submitted_at }}
                                            <span v-if="s.submitted_by"> &bull; by {{ s.submitted_by.name }}</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button v-if="s.has_pdf" type="button" title="Download PDF" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-secondary-200 dark:border-secondary-700 hover:bg-secondary-100 dark:hover:bg-secondary-800 text-secondary-500 dark:text-secondary-400 transition-colors" @click="openPdf(s)">
                                            <FileDown class="w-4 h-4" :stroke-width="1.75" />
                                        </button>
                                        <button type="button" title="Delete" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 dark:border-red-800/50 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500 dark:text-red-400 transition-colors" @click="confirmDelete(s)">
                                            <Trash2 class="w-4 h-4" :stroke-width="1.75" />
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Desktop table -->
                        <div class="hidden md:block app-table-scroll">
                            <table class="w-full">
                                <thead class="app-table-head-sticky bg-secondary-50 dark:bg-background-dark border-b border-secondary-200 dark:border-secondary-700">
                                    <tr>
                                        <th class="app-table-th">Member</th>
                                        <th class="app-table-th">Member ID</th>
                                        <th class="app-table-th">Submitted At</th>
                                        <th class="app-table-th">Submitted By</th>
                                        <th class="app-table-th"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                    <tr v-for="s in filtered" :key="s.id" class="hover:bg-secondary-50 dark:hover:bg-secondary-800/40 transition-colors">
                                        <td class="app-table-td font-semibold text-secondary-900 dark:text-white">{{ s.member?.name || '—' }}</td>
                                        <td class="app-table-td text-secondary-500 dark:text-secondary-400 font-mono text-xs">{{ s.member?.member_id || '—' }}</td>
                                        <td class="app-table-td text-secondary-500 dark:text-secondary-400 whitespace-nowrap">{{ s.submitted_at }}</td>
                                        <td class="app-table-td text-secondary-500 dark:text-secondary-400">{{ s.submitted_by?.name || '—' }}</td>
                                        <td class="app-table-td">
                                            <div class="flex items-center justify-end gap-1">
                                                <button v-if="s.has_pdf" type="button" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg border border-secondary-200 dark:border-secondary-700 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors" @click="openPdf(s)">
                                                    <FileDown class="w-3.5 h-3.5" :stroke-width="1.75" />
                                                    PDF
                                                </button>
                                                <button type="button" class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" @click="confirmDelete(s)">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                </div>
            </div>
        </div>

        <!-- ── Fill Form Modal ── -->
        <div v-if="fillModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45" @click="closeFillModal"></div>
            <div class="relative z-10 w-full max-w-xl rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-xl flex flex-col max-h-[90vh]">
                <!-- Modal header -->
                <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-secondary-100 dark:border-secondary-800 shrink-0">
                    <div>
                        <h3 class="text-base font-semibold text-secondary-900 dark:text-white">{{ templateTitle }}</h3>
                        <p v-if="fillMember" class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">Filling for: <strong>{{ fillMember.label }}</strong></p>
                    </div>
                    <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200 mt-0.5" @click="closeFillModal">✕</button>
                </div>

                <!-- Scrollable body -->
                <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

                    <!-- Member picker step -->
                    <div v-if="!fillMember" class="space-y-3">
                        <p class="text-sm text-secondary-700 dark:text-secondary-300">Search and select a member to fill this form for:</p>
                        <div class="relative">
                            <input
                                v-model="memberSearch"
                                type="text"
                                placeholder="Search members…"
                                class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                @input="searchMembers"
                            />
                        </div>
                        <div v-if="memberResults.length > 0" class="rounded-xl border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                            <button
                                v-for="m in memberResults"
                                :key="m.id"
                                type="button"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm hover:bg-secondary-50 dark:hover:bg-secondary-800 text-left border-b border-secondary-100 dark:border-secondary-800 last:border-0 transition-colors"
                                @click="selectMember(m)"
                            >
                                <div class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-400 flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ (m.label || '?').charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-secondary-900 dark:text-white truncate">{{ m.label }}</p>
                                    <p class="text-[11px] text-secondary-400 dark:text-secondary-500">{{ m.member_id }}</p>
                                </div>
                            </button>
                        </div>
                        <p v-else-if="memberSearch.trim().length >= 2 && !memberSearching" class="text-xs text-secondary-500 dark:text-secondary-400">No members found.</p>
                    </div>

                    <!-- Form fields step -->
                    <template v-else>
                        <!-- Language switcher (shown only if template has translations) -->
                        <div v-if="availableFormLanguages.length > 1" class="flex flex-wrap gap-1.5 pb-3 border-b border-secondary-200 dark:border-secondary-700">
                            <button
                                v-for="lang in availableFormLanguages"
                                :key="lang.code"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                                :class="selectedLanguage === lang.code
                                    ? 'bg-primary-600 text-white shadow-sm'
                                    : 'border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800'"
                                @click="selectedLanguage = lang.code"
                            >
                                <span>{{ lang.flag }}</span>
                                {{ lang.name }}
                            </button>
                        </div>

                        <div v-if="fillError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">{{ fillError }}</div>

                        <template v-for="field in displayFields" :key="field.id">
                            <!-- Heading -->
                            <div v-if="field.type === 'heading'">
                                <h3 class="text-base font-bold text-secondary-900 dark:text-white border-b border-secondary-200 dark:border-secondary-700 pb-1">{{ field.label }}</h3>
                            </div>
                            <!-- Paragraph -->
                            <div v-else-if="field.type === 'paragraph'">
                                <p class="text-sm text-secondary-500 dark:text-secondary-400 italic">{{ field.label }}</p>
                            </div>
                            <!-- Checkbox -->
                            <div v-else-if="field.type === 'checkbox'" class="flex items-start gap-2.5">
                                <input
                                    :id="field.id"
                                    v-model="fillResponses[field.id]"
                                    type="checkbox"
                                    class="mt-0.5 rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500 shrink-0"
                                />
                                <label :for="field.id" class="text-sm text-secondary-700 dark:text-secondary-300">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                            </div>
                            <!-- Radio -->
                            <div v-else-if="field.type === 'radio'">
                                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </p>
                                <div class="space-y-1.5">
                                    <div v-for="opt in field.options" :key="opt" class="flex items-center gap-2">
                                        <input
                                            :id="`${field.id}_${opt}`"
                                            v-model="fillResponses[field.id]"
                                            type="radio"
                                            :name="field.id"
                                            :value="opt"
                                            class="border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500"
                                        />
                                        <label :for="`${field.id}_${opt}`" class="text-sm text-secondary-700 dark:text-secondary-300">{{ opt }}</label>
                                    </div>
                                </div>
                            </div>
                            <!-- Select -->
                            <div v-else-if="field.type === 'select'">
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <select
                                    v-model="fillResponses[field.id]"
                                    class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                >
                                    <option value="">{{ field.placeholder || 'Select…' }}</option>
                                    <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                            </div>
                            <!-- Date -->
                            <div v-else-if="field.type === 'date'">
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <input
                                    v-model="fillResponses[field.id]"
                                    type="date"
                                    class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                />
                            </div>
                            <!-- Number -->
                            <div v-else-if="field.type === 'number'">
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <input
                                    v-model="fillResponses[field.id]"
                                    type="number"
                                    :placeholder="field.placeholder || ''"
                                    class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                />
                            </div>
                            <!-- Textarea -->
                            <div v-else-if="field.type === 'textarea'">
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <textarea
                                    v-model="fillResponses[field.id]"
                                    rows="3"
                                    :placeholder="field.placeholder || ''"
                                    class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                ></textarea>
                            </div>
                            <!-- Signature -->
                            <div v-else-if="field.type === 'signature'">
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <AppSignaturePad v-model="fillResponses[field.id]" :height="160" />
                            </div>
                            <!-- Text (default) -->
                            <div v-else>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <input
                                    v-model="fillResponses[field.id]"
                                    type="text"
                                    :placeholder="field.placeholder || ''"
                                    class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                />
                            </div>
                        </template>
                    </template>
                </div>

                <!-- Modal footer -->
                <div v-if="fillMember" class="px-5 py-4 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3 shrink-0">
                    <button type="button" class="text-sm text-secondary-500 hover:text-secondary-700 dark:hover:text-secondary-300" @click="fillMember = null; fillResponses = {}">← Change member</button>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeFillModal">Cancel</button>
                        <button
                            type="button"
                            class="px-5 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 transition-colors"
                            :disabled="fillSubmitting"
                            @click="submitFill"
                        >{{ fillSubmitting ? 'Submitting…' : 'Submit & Generate PDF' }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete confirm modal -->
        <div v-if="deleteTarget" class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45" @click="deleteTarget = null"></div>
            <div class="relative z-10 w-full max-w-sm rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-2">Delete Submission?</h3>
                <p class="text-sm text-secondary-500 dark:text-secondary-400 mb-4">This submission and its PDF will be permanently deleted.</p>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="deleteTarget = null">Cancel</button>
                    <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50" :disabled="deleting" @click="executeDelete">
                        {{ deleting ? 'Deleting…' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ClipboardCheck, FileDown, Plus, Trash2 } from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppSignaturePad from '../components/AppSignaturePad.vue';
import AppEmptyState from '../components/AppEmptyState.vue';
import AppHeaderAction from '../components/AppHeaderAction.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSearchField from '../components/AppSearchField.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const submissions = ref([]);
const templateTitle = ref('');
const templateFields = ref([]);
const templateTranslations = ref({});
const search = ref('');

const deleteTarget = ref(null);
const deleting = ref(false);

// Fill form modal
const fillModalOpen = ref(false);
const fillMember = ref(null);
const fillResponses = ref({});
const fillSubmitting = ref(false);
const fillError = ref('');
const memberSearch = ref('');
const memberResults = ref([]);
const memberSearching = ref(false);
let memberSearchTimer = null;

// Language
const selectedLanguage = ref('en');

const AVAILABLE_LANGUAGES = [
    { code: 'en', name: 'English',     flag: '🇬🇧' },
    { code: 'si', name: 'Sinhala',     flag: '🇱🇰' },
    { code: 'ta', name: 'Tamil',       flag: '🇮🇳' },
    { code: 'fr', name: 'French',      flag: '🇫🇷' },
    { code: 'de', name: 'German',      flag: '🇩🇪' },
    { code: 'es', name: 'Spanish',     flag: '🇪🇸' },
    { code: 'pt', name: 'Portuguese',  flag: '🇵🇹' },
    { code: 'zh', name: 'Chinese',     flag: '🇨🇳' },
    { code: 'ja', name: 'Japanese',    flag: '🇯🇵' },
    { code: 'ar', name: 'Arabic',      flag: '🇸🇦' },
];

/** Languages available for this template (EN + any translated langs) */
const availableFormLanguages = computed(() => {
    const langs = [AVAILABLE_LANGUAGES[0]]; // always EN
    for (const [code] of Object.entries(templateTranslations.value)) {
        const meta = AVAILABLE_LANGUAGES.find(l => l.code === code);
        if (meta) langs.push(meta);
    }
    return langs;
});

/** Fields with translated labels merged in for the selected language */
const displayFields = computed(() => {
    if (selectedLanguage.value === 'en' || !templateTranslations.value[selectedLanguage.value]) {
        return templateFields.value;
    }
    const trans = templateTranslations.value[selectedLanguage.value];
    return templateFields.value.map(field => {
        const ft = trans.fields?.[field.id];
        if (!ft) return field;
        return {
            ...field,
            label: ft.label || field.label,
            placeholder: ft.placeholder || field.placeholder,
            options: ft.options?.some(o => o) ? ft.options.map((o, i) => o || field.options[i] || '') : field.options,
        };
    });
});

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return submissions.value;
    return submissions.value.filter(s =>
        (s.member?.name || '').toLowerCase().includes(q) ||
        (s.member?.member_id || '').toLowerCase().includes(q),
    );
});

async function load() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const [tRes, sRes] = await Promise.all([
            apiRequest(`/api/forms/templates/${route.params.id}`),
            apiRequest(`/api/forms/templates/${route.params.id}/submissions`),
        ]);
        templateTitle.value = tRes.title;
        templateFields.value = tRes.fields ?? [];
        templateTranslations.value = tRes.translations ?? {};
        submissions.value = sRes.data ?? [];
    } catch {
        errorMessage.value = 'Failed to load submissions.';
    } finally {
        loading.value = false;
    }
}

async function openPdf(s) {
    try {
        const res = await apiRequest(`/api/forms/submissions/${s.id}/pdf-url`);
        window.open(res.url, '_blank');
    } catch {
        errorMessage.value = 'Could not retrieve the PDF.';
    }
}

function confirmDelete(s) {
    deleteTarget.value = s;
}

async function executeDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await apiRequest(`/api/forms/submissions/${deleteTarget.value.id}`, { method: 'delete' });
        submissions.value = submissions.value.filter(s => s.id !== deleteTarget.value.id);
        deleteTarget.value = null;
    } catch {
        errorMessage.value = 'Failed to delete submission.';
        deleteTarget.value = null;
    } finally {
        deleting.value = false;
    }
}

function openFillModal() {
    fillMember.value = null;
    fillResponses.value = {};
    fillError.value = '';
    memberSearch.value = '';
    memberResults.value = [];
    selectedLanguage.value = 'en';
    fillModalOpen.value = true;
}

function closeFillModal() {
    fillModalOpen.value = false;
}

function searchMembers() {
    clearTimeout(memberSearchTimer);
    if (memberSearch.value.trim().length < 2) {
        memberResults.value = [];
        return;
    }
    memberSearchTimer = setTimeout(async () => {
        memberSearching.value = true;
        try {
            const res = await apiRequest(`/api/members?search=${encodeURIComponent(memberSearch.value.trim())}&per_page=10`);
            memberResults.value = (res.data || []).map(m => ({
                id: m.id,
                label: [m.first_name, m.last_name].filter(Boolean).join(' ') || m.name || '',
                member_id: m.member_id,
            }));
        } catch {
            memberResults.value = [];
        } finally {
            memberSearching.value = false;
        }
    }, 350);
}

function selectMember(m) {
    fillMember.value = m;
    memberResults.value = [];
    // Initialise responses
    const responses = {};
    templateFields.value.forEach(f => {
        if (!['heading', 'paragraph'].includes(f.type)) {
            responses[f.id] = f.type === 'checkbox' ? false : '';
        }
    });
    fillResponses.value = responses;
}

async function submitFill() {
    fillError.value = '';
    // Validate required fields using displayFields (translated labels for error messages)
    for (const field of displayFields.value) {
        if (['heading', 'paragraph'].includes(field.type)) continue;
        if (field.required) {
            const val = fillResponses.value[field.id];
            if (val === '' || val === null || val === undefined || val === false) {
                fillError.value = `"${field.label}" is required.`;
                return;
            }
        }
    }

    fillSubmitting.value = true;
    try {
        const res = await apiRequest(
            `/api/forms/templates/${route.params.id}/members/${fillMember.value.id}/submit`,
            { method: 'post', data: { responses: fillResponses.value, language: selectedLanguage.value } },
        );
        submissions.value.unshift(res.data);
        closeFillModal();
        successMessage.value = 'Form submitted and PDF generated.';
        setTimeout(() => { successMessage.value = ''; }, 4000);
    } catch (err) {
        fillError.value = err?.response?.data?.message ?? 'Failed to submit form.';
    } finally {
        fillSubmitting.value = false;
    }
}

onMounted(load);
</script>
