<template>
  <section class="app-page-frame">
    <AppPageHeader show-back :title="isEdit ? 'Edit Form Template' : 'New Form Template'">
      <template #extra-slot>
        <!-- Builder / Preview tabs -->
        <div class="inline-flex rounded-xl app-surface-soft p-1">
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
            :class="activeTab === 'builder' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
            @click="activeTab = 'builder'"
          >
            Builder
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
            :class="activeTab === 'preview' ? 'bg-gradient-to-r from-primary-500 to-primary-700 text-white shadow-sm' : 'text-secondary-700 dark:text-secondary-300 hover:bg-secondary-200 dark:hover:bg-secondary-700'"
            @click="activeTab = 'preview'"
          >
            Preview
          </button>
        </div>
      </template>
    </AppPageHeader>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
      {{ errorMessage }}
    </div>

    <div v-if="pageLoading" class="p-10 text-center text-sm text-secondary-400">
      Loading…
    </div>

    <!-- ── BUILDER TAB ── -->
    <div v-else-if="activeTab === 'builder'" class="app-page-scroll pr-1">
      <div class="max-w-3xl mx-auto space-y-4 pb-8">
        <!-- Form meta -->
        <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
          <h4 class="text-sm font-semibold text-secondary-900 dark:text-white mb-3">
            Form Details
          </h4>
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
              />
            </div>
            <div class="flex items-center gap-2">
              <input
                id="is_active"
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500"
              />
              <label for="is_active" class="text-sm text-secondary-700 dark:text-secondary-300">Active (members can receive this form)</label>
            </div>
          </div>
        </article>

        <!-- Fields -->
        <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
          <div class="flex items-center justify-between gap-2 mb-4">
            <div>
              <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                Form Fields
              </h4>
              <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                Add fields in order. Drag to reorder.
              </p>
            </div>
            <!-- Add field dropdown -->
            <div ref="addMenuRef" class="relative">
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
                <button
                  type="button"
                  :disabled="idx === 0"
                  class="p-1 rounded text-secondary-400 hover:text-secondary-700 disabled:opacity-30"
                  @click="moveField(idx, -1)"
                >
                  <ChevronUp class="w-3.5 h-3.5" :stroke-width="2.5" />
                </button>
                <button
                  type="button"
                  :disabled="idx === form.fields.length - 1"
                  class="p-1 rounded text-secondary-400 hover:text-secondary-700 disabled:opacity-30"
                  @click="moveField(idx, 1)"
                >
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
                  <textarea
                    v-if="field.type === 'paragraph'"
                    v-model="field.label"
                    rows="6"
                    placeholder="Descriptive text…"
                    class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-y font-mono"
                  />
                  <input
                    v-else
                    v-model="field.label"
                    type="text"
                    maxlength="255"
                    :placeholder="field.type === 'heading' ? 'Section heading…' : 'Field label…'"
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
                  <input
                    :id="`req-${field.id}`"
                    v-model="field.required"
                    type="checkbox"
                    class="rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500"
                  />
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
                    >
                      + Add option
                    </button>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </article>

        <!-- Translations -->
        <article class="rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50/70 dark:bg-secondary-800/40 p-4">
          <div class="flex items-center justify-between gap-2 mb-1">
            <div>
              <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                Translations
              </h4>
              <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                Add translated versions of the form. English is the default and always shown as the fallback.
              </p>
            </div>
            <!-- Add language dropdown -->
            <div ref="langMenuRef" class="relative">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-primary-500 text-primary-600 dark:text-primary-400 px-3 py-1.5 text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                @click="langMenuOpen = !langMenuOpen"
              >
                <Plus class="w-3.5 h-3.5" :stroke-width="2.5" />
                Add Language
              </button>
              <div v-if="langMenuOpen" class="absolute right-0 mt-1 z-20 w-52 rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-lg overflow-hidden">
                <button
                  v-for="lang in AVAILABLE_LANGUAGES"
                  :key="lang.code"
                  type="button"
                  :disabled="translationLangs.includes(lang.code)"
                  class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                  @click="addLanguage(lang.code)"
                >
                  <span class="text-base leading-none">{{ lang.flag }}</span>
                  <span>{{ lang.name }}</span>
                  <span v-if="translationLangs.includes(lang.code)" class="ml-auto text-xs text-secondary-400">Added</span>
                </button>
              </div>
            </div>
          </div>

          <div v-if="translationLangs.length === 0" class="mt-4 rounded-xl border-2 border-dashed border-secondary-300 dark:border-secondary-700 py-8 text-center text-sm text-secondary-400 dark:text-secondary-500">
            No translations added. Click <strong>Add Language</strong> to translate this form.
          </div>

          <template v-else>
            <!-- Language tabs -->
            <div class="mt-3 flex flex-wrap gap-1.5 border-b border-secondary-200 dark:border-secondary-700 pb-3 mb-4">
              <button
                v-for="lang in AVAILABLE_LANGUAGES.filter(l => translationLangs.includes(l.code))"
                :key="lang.code"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                :class="activeLangTab === lang.code
                  ? 'bg-primary-600 text-white shadow-sm'
                  : 'border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800'"
                @click="activeLangTab = lang.code"
              >
                <span>{{ lang.flag }}</span>
                {{ lang.name }}
              </button>
            </div>

            <!-- Active language editor -->
            <template v-if="activeLangTab && form.translations[activeLangTab]">
              <div class="space-y-4">
                <!-- Title & Description -->
                <div class="grid gap-3 md:grid-cols-2">
                  <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                      Form Title
                      <span class="text-secondary-400 font-normal">(translated)</span>
                    </label>
                    <input
                      v-model="form.translations[activeLangTab].title"
                      type="text"
                      maxlength="255"
                      :placeholder="form.title || 'English title as fallback'"
                      class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                      Description
                      <span class="text-secondary-400 font-normal">(translated)</span>
                    </label>
                    <textarea
                      v-model="form.translations[activeLangTab].description"
                      rows="2"
                      maxlength="2000"
                      :placeholder="form.description || 'English description as fallback'"
                      class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                    />
                  </div>
                </div>

                <!-- Per-field translations -->
                <div v-if="form.fields.length > 0" class="space-y-2">
                  <p class="text-xs font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wide">
                    Field Translations
                  </p>
                  <div
                    v-for="field in form.fields"
                    :key="field.id"
                    class="rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-3"
                  >
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-secondary-400 dark:text-secondary-500 mb-2">
                      {{ fieldTypeLabel(field.type) }}
                      <span class="normal-case font-normal ml-1 text-secondary-500 dark:text-secondary-400">— {{ field.label || '(no label)' }}</span>
                    </p>
                    <div class="grid gap-2 md:grid-cols-2">
                      <div :class="['select','radio'].includes(field.type) ? 'md:col-span-2' : ''">
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">
                          {{ field.type === 'heading' ? 'Heading Text' : field.type === 'paragraph' ? 'Paragraph Text' : 'Label' }}
                        </label>
                        <textarea
                          v-if="field.type === 'paragraph'"
                          v-model="form.translations[activeLangTab].fields[field.id].label"
                          rows="6"
                          :placeholder="field.label || 'English label as fallback'"
                          class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-y font-mono"
                        />
                        <input
                          v-else
                          v-model="form.translations[activeLangTab].fields[field.id].label"
                          type="text"
                          maxlength="255"
                          :placeholder="field.label || 'English label as fallback'"
                          class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                      </div>
                      <div v-if="['text','textarea','number','date','select'].includes(field.type)">
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Placeholder</label>
                        <input
                          v-model="form.translations[activeLangTab].fields[field.id].placeholder"
                          type="text"
                          maxlength="255"
                          :placeholder="field.placeholder || 'English placeholder as fallback'"
                          class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                      </div>
                      <!-- Options for select/radio -->
                      <div v-if="['select','radio'].includes(field.type)" class="md:col-span-2">
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Options <span class="font-normal text-secondary-400">(same order as English)</span></label>
                        <div class="space-y-1.5">
                          <div v-for="(_, oi) in field.options" :key="oi" class="flex items-center gap-2">
                            <span class="text-xs text-secondary-400 dark:text-secondary-500 w-4 shrink-0">{{ oi + 1 }}.</span>
                            <input
                              v-model="form.translations[activeLangTab].fields[field.id].options[oi]"
                              type="text"
                              maxlength="255"
                              :placeholder="field.options[oi] || ('Option ' + (oi + 1))"
                              class="flex-1 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-1.5 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Remove language -->
                <div class="flex justify-end">
                  <button
                    type="button"
                    class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors"
                    @click="removeLanguage(activeLangTab)"
                  >
                    Remove this language translation
                  </button>
                </div>
              </div>
            </template>
          </template>
        </article>

        <!-- Save -->
        <div class="flex items-center justify-end gap-3">
          <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="router.push('/settings/forms')">
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
            <h1 class="text-xl font-bold">
              {{ form.title || 'Untitled Form' }}
            </h1>
            <p v-if="form.description" class="mt-1 text-sm text-primary-100/80">
              {{ form.description }}
            </p>
          </div>

          <div class="p-5 space-y-4">
            <!-- Member info row (always shown on real form) -->
            <div class="grid grid-cols-2 gap-3 pb-4 border-b border-secondary-100 dark:border-secondary-800">
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500 mb-1">
                  Member Name
                </p>
                <div class="h-8 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800" />
              </div>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500 mb-1">
                  Member ID
                </p>
                <div class="h-8 rounded-lg border border-secondary-300 dark:border-secondary-600 bg-secondary-50 dark:bg-secondary-800" />
              </div>
            </div>

            <template v-if="form.fields.length === 0">
              <p class="text-sm text-secondary-400 dark:text-secondary-500 text-center py-8">
                No fields added yet.
              </p>
            </template>

            <template v-for="field in form.fields" :key="field.id">
              <!-- Heading -->
              <div v-if="field.type === 'heading'" class="pt-2">
                <h3 class="text-base font-bold text-secondary-900 dark:text-white border-b border-secondary-200 dark:border-secondary-700 pb-1">
                  {{ field.label || 'Heading' }}
                </h3>
              </div>
              <!-- Paragraph -->
              <div v-else-if="field.type === 'paragraph'">
                <pre class="text-sm text-secondary-500 dark:text-secondary-400 italic whitespace-pre-wrap font-sans">{{ field.label || 'Paragraph text…' }}</pre>
              </div>
              <!-- Checkbox -->
              <div v-else-if="field.type === 'checkbox'" class="flex items-start gap-2.5">
                <div class="mt-0.5 w-4 h-4 rounded border-2 border-secondary-400 dark:border-secondary-600 shrink-0" />
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
                  <div v-if="field.options.length === 0" class="text-xs text-secondary-400 italic">
                    No options defined
                  </div>
                  <div v-for="opt in field.options.filter(o => o.trim())" :key="opt" class="flex items-center gap-2">
                    <div class="w-3.5 h-3.5 rounded-full border-2 border-secondary-400 dark:border-secondary-600 shrink-0" />
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
                <div class="h-8 border-b border-secondary-400 dark:border-secondary-600 mb-1" />
                <p class="text-[10px] text-secondary-500">
                  Signature
                </p>
              </div>
              <div>
                <div class="h-8 border-b border-secondary-400 dark:border-secondary-600 mb-1" />
                <p class="text-[10px] text-secondary-500">
                  Date
                </p>
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
const langMenuOpen = ref(false);
const langMenuRef = ref(null);
const translationLangs = ref([]);
const activeLangTab = ref('');

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

const AVAILABLE_LANGUAGES = [
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

const form = ref({
    title: '',
    description: '',
    is_active: true,
    fields: [],
    translations: {},
});

function fieldTypeLabel(type) {
    return FIELD_TYPES.find(f => f.type === type)?.label ?? type;
}

/** Build an empty translations field entry for one field */
function emptyFieldTrans(field) {
    return {
        label: '',
        placeholder: '',
        options: ['select', 'radio'].includes(field.type) ? field.options.map(() => '') : [],
    };
}

/** Ensure all languages have an entry for every current field */
function syncTranslationFields() {
    for (const lang of translationLangs.value) {
        if (!form.value.translations[lang]) continue;
        for (const field of form.value.fields) {
            if (!form.value.translations[lang].fields[field.id]) {
                form.value.translations[lang].fields[field.id] = emptyFieldTrans(field);
            }
            // Sync option count for select/radio
            if (['select', 'radio'].includes(field.type)) {
                const opts = form.value.translations[lang].fields[field.id].options;
                while (opts.length < field.options.length) opts.push('');
                while (opts.length > field.options.length) opts.pop();
            }
        }
        // Remove entries for fields that no longer exist
        const ids = new Set(form.value.fields.map(f => f.id));
        for (const key of Object.keys(form.value.translations[lang].fields)) {
            if (!ids.has(key)) delete form.value.translations[lang].fields[key];
        }
    }
}

function addLanguage(code) {
    langMenuOpen.value = false;
    if (translationLangs.value.includes(code)) return;
    // Build initial fields map
    const fields = {};
    for (const field of form.value.fields) {
        fields[field.id] = emptyFieldTrans(field);
    }
    form.value.translations[code] = { title: '', description: '', fields };
    translationLangs.value.push(code);
    activeLangTab.value = code;
}

function removeLanguage(code) {
    translationLangs.value = translationLangs.value.filter(l => l !== code);
    delete form.value.translations[code];
    activeLangTab.value = translationLangs.value[0] ?? '';
}

function addField(type) {
    addMenuOpen.value = false;
    const newField = {
        id: crypto.randomUUID(),
        type,
        label: '',
        placeholder: '',
        required: false,
        options: ['select', 'radio'].includes(type) ? ['Yes', 'No'] : [],
    };
    form.value.fields.push(newField);
    // Add entry in all active translation languages
    for (const lang of translationLangs.value) {
        if (form.value.translations[lang]) {
            form.value.translations[lang].fields[newField.id] = emptyFieldTrans(newField);
        }
    }
}

function removeField(idx) {
    const removed = form.value.fields.splice(idx, 1)[0];
    // Remove from translations
    for (const lang of translationLangs.value) {
        if (form.value.translations[lang]?.fields) {
            delete form.value.translations[lang].fields[removed.id];
        }
    }
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
    if (langMenuRef.value && !langMenuRef.value.contains(e.target)) {
        langMenuOpen.value = false;
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
            translations: {},
        };

        // Restore translations
        const raw = t.translations ?? {};
        for (const [lang, data] of Object.entries(raw)) {
            const fields = {};
            for (const field of form.value.fields) {
                const ft = data.fields?.[field.id] ?? {};
                fields[field.id] = {
                    label: ft.label ?? '',
                    placeholder: ft.placeholder ?? '',
                    options: ['select', 'radio'].includes(field.type)
                        ? field.options.map((_, i) => ft.options?.[i] ?? '')
                        : [],
                };
            }
            form.value.translations[lang] = {
                title: data.title ?? '',
                description: data.description ?? '',
                fields,
            };
            if (!translationLangs.value.includes(lang)) translationLangs.value.push(lang);
        }
        if (translationLangs.value.length > 0) activeLangTab.value = translationLangs.value[0];
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
    // Sync option counts in translations before saving
    syncTranslationFields();
    saving.value = true;
    try {
        const normalizedFields = form.value.fields.map((f, i) => ({
            id: f.id,
            type: f.type,
            label: f.label.trim(),
            placeholder: f.placeholder?.trim() || null,
            required: f.required,
            options: f.options.filter(o => o.trim()),
            order: i,
        }));

        // Build translations payload — only include non-empty entries
        const translations = {};
        for (const lang of translationLangs.value) {
            const t = form.value.translations[lang];
            if (!t) continue;
            const fields = {};
            for (const [fid, ft] of Object.entries(t.fields ?? {})) {
                fields[fid] = {
                    label: ft.label?.trim() || null,
                    placeholder: ft.placeholder?.trim() || null,
                    options: ft.options ?? [],
                };
            }
            translations[lang] = {
                title: t.title?.trim() || null,
                description: t.description?.trim() || null,
                fields,
            };
        }

        const payload = {
            title: form.value.title.trim(),
            description: form.value.description.trim() || null,
            is_active: form.value.is_active,
            fields: normalizedFields,
            translations,
        };

        if (isEdit.value) {
            await apiRequest(`/api/forms/templates/${route.params.id}`, { method: 'put', data: payload });
        } else {
            await apiRequest('/api/forms/templates', { method: 'post', data: payload });
        }

        router.push('/settings/forms');
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
