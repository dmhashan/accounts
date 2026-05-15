<template>
    <section class="app-page-frame">
        <AppPageHeader :show-back="true" :title="isEdit ? 'Edit Form Template' : 'New Form Template'">
            <template #extra-slot>
                <!-- Builder / Preview tabs -->
                <div class="inline-flex rounded-xl app-surface-soft p-1">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                        :class="activeTab === 'builder' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                        @click="activeTab = 'builder'"
                    >Builder</button>
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                        :class="activeTab === 'preview' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
                        @click="activeTab = 'preview'"
                    >Preview</button>
                </div>
            </template>
        </AppPageHeader>

        <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">{{ errorMessage }}</div>

        <div v-if="pageLoading" class="p-10 text-center text-sm text-secondary-400">Loading…</div>

        <!-- ── BUILDER TAB ── -->
        <div v-else-if="activeTab === 'builder'" class="app-page-scroll pr-1">
            <div class="max-w-3xl mx-auto space-y-4 pb-8">

                <!-- Form meta -->
                <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
                    <h4 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">Form Details</h4>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Title <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.title"
                                type="text"
                                maxlength="255"
                                placeholder="e.g. PAR-Q Questionnaire"
                                class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Description <span class="text-xs text-secondary-400">(optional)</span></label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                maxlength="2000"
                                placeholder="Brief description shown on the filled form…"
                                class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                            ></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500" />
                            <label for="is_active" class="text-sm text-secondary-700 dark:text-secondary-300">Active (members can receive this form)</label>
                        </div>
                    </div>
                </article>

                <!-- Fields -->
                <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <div>
                            <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">Form Fields</h4>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">Add fields in order. Drag to reorder.</p>
                        </div>
                        <!-- Add field dropdown -->
                        <div class="relative" ref="addMenuRef">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 px-3 py-2 text-sm font-semibold text-white transition-colors"
                                @click="addMenuOpen = !addMenuOpen"
                            >
                                <Plus class="w-4 h-4" :stroke-width="2.5" />
                                Add Field
                            </button>
                            <div v-if="addMenuOpen" class="absolute right-0 mt-1 z-20 w-48 rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-lg overflow-hidden">
                                <button
                                    v-for="ft in FIELD_TYPES"
                                    :key="ft.type"
                                    type="button"
                                    class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                                    @click="addField(ft.type)"
                                >
                                    <component :is="ft.icon" class="w-4 h-4 text-secondary-400" :stroke-width="1.75" />
                                    {{ ft.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="form.fields.length === 0" class="rounded-xl border-2 border-dashed border-secondary-300 dark:border-secondary-700 py-10 text-center text-sm text-secondary-400 dark:text-secondary-500">
                        No fields yet. Click <strong>Add Field</strong> to start building.
                    </div>

                    <!-- Field list -->
                    <div v-else class="space-y-3">
                        <article
                            v-for="(field, idx) in form.fields"
                            :key="field.id"
                            class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 overflow-hidden"
                        >
                            <!-- Field header -->
                            <div class="flex items-center gap-2 px-4 py-2.5 border-b border-secondary-100 dark:border-secondary-800 bg-secondary-50 dark:bg-secondary-800/50">
                                <GripVertical class="w-4 h-4 text-secondary-400 cursor-grab shrink-0" :stroke-width="1.75" />
                                <span class="text-xs font-semibold uppercase tracking-wide text-secondary-500 dark:text-secondary-400 flex-1">
                                    {{ fieldTypeLabel(field.type) }}
                                    <span v-if="field.required" class="ml-1 text-red-500">*</span>
                                </span>
                                <!-- Move up/down -->
                                <button type="button" :disabled="idx === 0" class="p-1 rounded text-secondary-400 hover:text-secondary-700 disabled:opacity-30" @click="moveField(idx, -1)">
                                    <ChevronUp class="w-3.5 h-3.5" :stroke-width="2.5" />
                                </button>
                                <button type="button" :disabled="idx === form.fields.length - 1" class="p-1 rounded text-secondary-400 hover:text-secondary-700 disabled:opacity-30" @click="moveField(idx, 1)">
                                    <ChevronDown class="w-3.5 h-3.5" :stroke-width="2.5" />
                                </button>
                                <button type="button" class="p-1 rounded text-red-400 hover:text-red-600" @click="removeField(idx)">
                                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                </button>
                            </div>

                            <!-- Field settings -->
                            <div class="p-4 grid gap-3 md:grid-cols-2">
                                <!-- Label (all types) -->
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                        {{ field.type === 'heading' ? 'Heading Text' : field.type === 'paragraph' ? 'Paragraph Text' : 'Field Label' }}
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="field.label"
                                        type="text"
                                        maxlength="255"
                                        :placeholder="field.type === 'heading' ? 'Section heading…' : field.type === 'paragraph' ? 'Descriptive text…' : 'Field label…'"
                                        class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    />
                                </div>

                                <!-- Placeholder for input types -->
                                <div v-if="['text','textarea','number','date'].includes(field.type)">
                                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Placeholder <span class="text-xs text-secondary-400">(optional)</span></label>
                                    <input
                                        v-model="field.placeholder"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Placeholder text…"
                                        class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    />
                                </div>

                                <!-- Required toggle -->
                                <div v-if="!['heading','paragraph'].includes(field.type)" class="flex items-center gap-2">
                                    <input :id="`req-${field.id}`" v-model="field.required" type="checkbox" class="rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500" />
                                    <label :for="`req-${field.id}`" class="text-sm text-secondary-700 dark:text-secondary-300">Required</label>
                                </div>

                                <!-- Options for select/radio -->
                                <div v-if="['select','radio'].includes(field.type)" class="md:col-span-2">
                                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Options <span class="text-red-500">*</span></label>
                                    <div class="space-y-2">
                                        <div v-for="(opt, oi) in field.options" :key="oi" class="flex items-center gap-2">
                                            <input
                                                v-model="field.options[oi]"
                                                type="text"
                                                maxlength="255"
                                                :placeholder="'Option ' + (oi + 1)"
                                                class="flex-1 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                            />
                                            <button type="button" class="text-red-400 hover:text-red-600" @click="removeOption(field, oi)">
                                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                                            @click="field.options.push('')"
                                        >+ Add option</button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <!-- Save -->
                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="router.push('/forms')">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="px-5 py-2 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 transition-colors"
                        :disabled="saving || !form.title.trim()"
                        @click="save"
                    >
                        {{ saving ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Form') }}
                    </button>
                </div>

            </div>
        </div>

        <!-- ── PREVIEW TAB ── -->
        <div v-else class="app-page-scroll pr-1">
            <div class="max-w-2xl mx-auto pb-8">
                <div class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 overflow-hidden shadow-sm">
                    <!-- Preview header -->
                    <div class="bg-gradient-to-br from-primary-700 via-primary-600 to-indigo-600 px-6 py-5 text-white">
                        <h1 class="text-xl font-bold">{{ form.title || 'Untitled Form' }}</h1>
                        <p v-if="form.description" class="mt-1 text-sm text-primary-100/80">{{ form.description }}</p>
                    </div>

                    <div class="p-5 space-y-4">
                        <!-- Member info row (always shown on real form) -->
                        <div class="grid grid-cols-2 gap-3 pb-4 border-b border-secondary-100 dark:border-secondary-800">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500 mb-1">Member Name</p>
                                <div class="h-8 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800"></div>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500 mb-1">Member ID</p>
                                <div class="h-8 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800"></div>
                            </div>
                        </div>

                        <template v-if="form.fields.length === 0">
                            <p class="text-sm text-secondary-400 dark:text-secondary-500 text-center py-8">No fields added yet.</p>
                        </template>

                        <template v-for="field in form.fields" :key="field.id">
                            <!-- Heading -->
                            <div v-if="field.type === 'heading'" class="pt-2">
                                <h3 class="text-base font-bold text-secondary-900 dark:text-white border-b border-secondary-200 dark:border-secondary-700 pb-1">{{ field.label || 'Heading' }}</h3>
                            </div>
                            <!-- Paragraph -->
                            <div v-else-if="field.type === 'paragraph'">
                                <p class="text-sm text-secondary-500 dark:text-secondary-400 italic">{{ field.label || 'Paragraph text…' }}</p>
                            </div>
                            <!-- Checkbox -->
                            <div v-else-if="field.type === 'checkbox'" class="flex items-start gap-2.5">
                                <div class="mt-0.5 w-4 h-4 rounded border-2 border-secondary-400 dark:border-secondary-600 shrink-0"></div>
                                <label class="text-sm text-secondary-700 dark:text-secondary-300">
                                    {{ field.label || 'Checkbox label' }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                            </div>
                            <!-- Radio -->
                            <div v-else-if="field.type === 'radio'">
                                <p class="text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">
                                    {{ field.label || 'Radio label' }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </p>
                                <div class="space-y-1.5">
                                    <div v-if="field.options.length === 0" class="text-xs text-secondary-400 italic">No options defined</div>
                                    <div v-for="opt in field.options.filter(o => o.trim())" :key="opt" class="flex items-center gap-2">
                                        <div class="w-3.5 h-3.5 rounded-full border-2 border-secondary-400 dark:border-secondary-600 shrink-0"></div>
                                        <span class="text-sm text-secondary-700 dark:text-secondary-300">{{ opt }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Select -->
                            <div v-else-if="field.type === 'select'">
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label || 'Select label' }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <div class="w-full h-9 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800 flex items-center px-3">
                                    <span class="text-sm text-secondary-400">{{ field.placeholder || 'Select an option…' }}</span>
                                </div>
                            </div>
                            <!-- Signature -->
                            <div v-else-if="field.type === 'signature'">
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label || 'Signature' }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <div class="w-full h-24 rounded-lg border border-dashed border-secondary-400 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800 flex flex-col items-center justify-center gap-1">
                                    <PenLine class="w-5 h-5 text-secondary-300" />
                                    <span class="text-xs text-secondary-400">Signature pad (touch / mouse)</span>
                                </div>
                            </div>
                            <!-- Text/Textarea/Number/Date -->
                            <div v-else>
                                <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                                    {{ field.label || 'Field label' }}
                                    <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                                </label>
                                <div v-if="field.type === 'textarea'" class="w-full h-20 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800 px-3 py-2">
                                    <span class="text-sm text-secondary-400">{{ field.placeholder || '' }}</span>
                                </div>
                                <div v-else class="w-full h-9 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800 flex items-center px-3">
                                    <span class="text-sm text-secondary-400">{{ field.placeholder || '' }}</span>
                                </div>
                            </div>
                        </template>

                        <!-- Signature preview -->
                        <div class="pt-4 border-t border-dashed border-secondary-300 dark:border-secondary-600 grid grid-cols-2 gap-6">
                            <div>
                                <div class="h-8 border-b border-secondary-400 dark:border-secondary-600 mb-1"></div>
                                <p class="text-[10px] text-secondary-500">Signature</p>
                            </div>
                            <div>
                                <div class="h-8 border-b border-secondary-400 dark:border-secondary-600 mb-1"></div>
                                <p class="text-[10px] text-secondary-500">Date</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    AlignLeft,
    Calendar,
    ChevronDown,
    ChevronUp,
    GripVertical,
    Hash,
    List,
    PenLine,
    Plus,
    Square,
    Text,
    Trash2,
    Type,
} from 'lucide-vue-next';
import { apiRequest } from '../composables/useApiClient';
import AppPageHeader from '../components/AppPageHeader.vue';

const route = useRoute();
const router = useRouter();

const isEdit = ref(false);
const pageLoading = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const activeTab = ref('builder');
const addMenuOpen = ref(false);
const addMenuRef = ref(null);

const FIELD_TYPES = [
    { type: 'text',      label: 'Short Text',       icon: Text },
    { type: 'textarea',  label: 'Long Text',        icon: AlignLeft },
    { type: 'number',    label: 'Number',           icon: Hash },
    { type: 'date',      label: 'Date',             icon: Calendar },
    { type: 'select',    label: 'Dropdown',         icon: List },
    { type: 'radio',     label: 'Multiple Choice',  icon: Square },
    { type: 'checkbox',  label: 'Checkbox',         icon: Square },
    { type: 'heading',   label: 'Heading',          icon: Type },
    { type: 'paragraph', label: 'Paragraph',        icon: AlignLeft },
    { type: 'signature', label: 'Signature',        icon: PenLine },
];

const form = ref({
    title: '',
    description: '',
    is_active: true,
    fields: [],
});

function fieldTypeLabel(type) {
    return FIELD_TYPES.find(f => f.type === type)?.label ?? type;
}

function addField(type) {
    addMenuOpen.value = false;
    form.value.fields.push({
        id: crypto.randomUUID(),
        type,
        label: '',
        placeholder: '',
        required: false,
        options: ['select', 'radio'].includes(type) ? ['Yes', 'No'] : [],
    });
}

function removeField(idx) {
    form.value.fields.splice(idx, 1);
}

function moveField(idx, dir) {
    const arr = form.value.fields;
    const newIdx = idx + dir;
    if (newIdx < 0 || newIdx >= arr.length) return;
    [arr[idx], arr[newIdx]] = [arr[newIdx], arr[idx]];
}

function removeOption(field, oi) {
    field.options.splice(oi, 1);
}

function closeAddMenu(e) {
    if (addMenuRef.value && !addMenuRef.value.contains(e.target)) {
        addMenuOpen.value = false;
    }
}

async function load() {
    pageLoading.value = true;
    try {
        const res = await apiRequest(`/api/forms/templates/${route.params.id}`);
        const t = res;
        form.value = {
            title: t.title,
            description: t.description ?? '',
            is_active: t.is_active,
            fields: (t.fields ?? []).map(f => ({
                id: f.id ?? crypto.randomUUID(),
                type: f.type,
                label: f.label,
                placeholder: f.placeholder ?? '',
                required: f.required ?? false,
                options: f.options ?? [],
            })),
        };
    } catch {
        errorMessage.value = 'Failed to load form template.';
    } finally {
        pageLoading.value = false;
    }
}

async function save() {
    errorMessage.value = '';
    if (!form.value.title.trim()) {
        errorMessage.value = 'Title is required.';
        return;
    }
    saving.value = true;
    try {
        const payload = {
            title: form.value.title.trim(),
            description: form.value.description.trim() || null,
            is_active: form.value.is_active,
            fields: form.value.fields.map((f, i) => ({
                id: f.id,
                type: f.type,
                label: f.label.trim(),
                placeholder: f.placeholder?.trim() || null,
                required: f.required,
                options: f.options.filter(o => o.trim()),
                order: i,
            })),
        };

        if (isEdit.value) {
            await apiRequest(`/api/forms/templates/${route.params.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/forms/templates', { method: 'post', data: payload });
        }

        router.push('/forms');
    } catch (err) {
        errorMessage.value = err?.response?.data?.message ?? 'Failed to save form template.';
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', closeAddMenu);
    if (route.params.id) {
        isEdit.value = true;
        load();
    }
});

onUnmounted(() => {
    document.removeEventListener('click', closeAddMenu);
});
</script>
