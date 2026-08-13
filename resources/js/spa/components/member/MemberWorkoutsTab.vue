<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">
          Workouts &amp; Training Routines
        </h2>
        <span
          v-if="memberWorkouts.length"
          class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 border border-primary-200 dark:border-primary-800"
        >
          {{ workoutsMeta.total || memberWorkouts.length }}
        </span>
      </div>

      <button
        v-if="canManage"
        type="button"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 active:scale-95 text-white transition-all shadow-sm"
        @click="openAddWorkoutModal"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>Add Workout</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="workoutsLoading" class="px-5 py-10 text-center text-sm text-secondary-400 flex flex-col items-center gap-2">
      <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin" />
      <span>Loading workouts...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="memberWorkouts.length === 0" class="px-5 py-12 text-center text-sm text-secondary-400 dark:text-secondary-500 flex flex-col items-center gap-3">
      <div class="w-12 h-12 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-secondary-400">
        <Dumbbell class="w-6 h-6" />
      </div>
      <div>
        <p class="font-semibold text-secondary-700 dark:text-secondary-300">
          No workout plans assigned
        </p>
        <p class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5">
          Assign a configured workout program or upload/enter a custom workout for this member.
        </p>
      </div>
      <button
        v-if="canManage"
        type="button"
        class="mt-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 hover:bg-primary-100 transition-colors"
        @click="openAddWorkoutModal"
      >
        + Add Workout
      </button>
    </div>

    <!-- Workout List -->
    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
      <div
        v-for="wa in memberWorkouts"
        :key="wa.id"
        class="flex items-start justify-between px-5 py-4 gap-3 hover:bg-secondary-50 dark:hover:bg-secondary-800/50 transition-colors group"
      >
        <div
          class="min-w-0 flex items-start gap-3 flex-1 cursor-pointer"
          @click="previewWorkout(wa)"
        >
          <!-- Type Icon -->
          <div
            class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center mt-0.5 border"
            :class="getWorkoutIconClasses(wa)"
          >
            <component :is="getWorkoutIcon(wa)" class="w-5 h-5" />
          </div>

          <!-- Info -->
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-sm font-bold text-secondary-900 dark:text-white truncate">
                {{ wa.title || wa.assigned_program_title || wa.source_program_title || 'Workout Routine' }}
              </p>

              <!-- Type Badge -->
              <span
                class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border"
                :class="getWorkoutBadgeClasses(wa)"
              >
                {{ getWorkoutTypeLabel(wa) }}
              </span>
            </div>

            <div class="flex items-center gap-2 text-xs text-secondary-500 dark:text-secondary-400 mt-1 flex-wrap">
              <span>Start: <strong class="font-medium text-secondary-700 dark:text-secondary-300">{{ formatDate(wa.effective_date) }}</strong></span>
              <span v-if="wa.created_by_name" class="opacity-70">&bull; by {{ wa.created_by_name }}</span>
              <span v-if="wa.file_name" class="text-secondary-400 opacity-90">&bull; {{ wa.file_name }}</span>
            </div>

            <!-- Notes preview if available -->
            <p v-if="wa.notes" class="text-xs text-secondary-400 dark:text-secondary-500 mt-1 line-clamp-1 italic">
              "{{ wa.notes }}"
            </p>
          </div>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-1 shrink-0 mt-1">
          <button
            type="button"
            class="p-1.5 rounded-lg text-secondary-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors"
            title="Preview Workout"
            @click="previewWorkout(wa)"
          >
            <Eye class="w-4 h-4" />
          </button>

          <a
            v-if="wa.file_url"
            :href="wa.file_url"
            target="_blank"
            download
            class="p-1.5 rounded-lg text-secondary-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors"
            title="Download / Open File"
          >
            <Download class="w-4 h-4" />
          </a>

          <button
            v-if="canManage"
            type="button"
            class="p-1.5 rounded-lg text-secondary-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
            title="Delete Workout"
            @click="confirmDelete(wa)"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="workoutsMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
      <p class="text-xs text-secondary-500 dark:text-secondary-400">
        Page {{ workoutsMeta.current_page }} of {{ workoutsMeta.last_page }}
      </p>
      <div class="flex gap-1">
        <button
          type="button"
          class="px-2.5 py-1 text-xs font-semibold rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40 hover:bg-secondary-50 dark:hover:bg-secondary-800"
          :disabled="workoutsMeta.current_page <= 1"
          @click="loadMemberWorkouts(workoutsMeta.current_page - 1)"
        >
          Prev
        </button>
        <button
          type="button"
          class="px-2.5 py-1 text-xs font-semibold rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40 hover:bg-secondary-50 dark:hover:bg-secondary-800"
          :disabled="workoutsMeta.current_page >= workoutsMeta.last_page"
          @click="loadMemberWorkouts(workoutsMeta.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>

  <!-- ── ADD / ASSIGN WORKOUT MODAL ───────────────────────── -->
  <Teleport to="body">
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto">
      <div class="bg-white dark:bg-secondary-900 rounded-3xl shadow-2xl w-full max-w-xl my-8 overflow-hidden border border-secondary-200 dark:border-secondary-700 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 shrink-0">
          <div>
            <h3 class="text-lg font-bold text-secondary-900 dark:text-white">
              Add or Assign Workout
            </h3>
            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
              Assign a configured program or upload/create a custom routine
            </p>
          </div>
          <button type="button" class="p-1.5 rounded-xl text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors" @click="closeAddWorkoutModal">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Mode Switcher Tabs -->
        <div class="px-6 pt-3 shrink-0">
          <div class="grid grid-cols-2 p-1 bg-secondary-100 dark:bg-secondary-800 rounded-xl gap-1 text-xs font-bold">
            <button
              type="button"
              class="py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5"
              :class="activeMode === 'assign'
                ? 'bg-white dark:bg-secondary-700 text-primary-600 dark:text-primary-400 shadow-xs'
                : 'text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white'"
              @click="activeMode = 'assign'"
            >
              <FolderOpen class="w-3.5 h-3.5" />
              <span>Assign Configured Plan</span>
            </button>

            <button
              type="button"
              class="py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5"
              :class="activeMode === 'custom'
                ? 'bg-white dark:bg-secondary-700 text-primary-600 dark:text-primary-400 shadow-xs'
                : 'text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-white'"
              @click="activeMode = 'custom'"
            >
              <FilePlus class="w-3.5 h-3.5" />
              <span>Add Custom Workout</span>
            </button>
          </div>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto flex-1 space-y-4">
          <div v-if="formError" class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ formError }}
          </div>

          <!-- ── FORM: ASSIGN CONFIGURED PROGRAM ── -->
          <form
            v-if="activeMode === 'assign'"
            id="assignForm"
            class="space-y-4"
            @submit.prevent="submitAssignProgram"
          >
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Configured Program <span class="text-red-500">*</span>
              </label>
              <select
                v-model="assignForm.program_id"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option value="" disabled>
                  Select a configured program
                </option>
                <option v-for="p in programs" :key="p.id" :value="p.id">
                  {{ p.title }} ({{ p.duration_weeks || '-' }} weeks)
                </option>
              </select>
              <p v-if="programsLoading" class="mt-1 text-xs text-secondary-400">
                Loading programs...
              </p>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Start Date <span class="text-red-500">*</span>
              </label>
              <AppFormDateInput
                v-model="assignForm.effective_date"
                required
                input-class="w-full px-3.5 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Notes (Optional)
              </label>
              <textarea
                v-model="assignForm.notes"
                rows="2"
                placeholder="Specific guidance for this member..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
          </form>

          <!-- ── FORM: ADD CUSTOM WORKOUT (FILE / RICH TEXT) ── -->
          <form
            v-else
            id="customForm"
            class="space-y-4"
            @submit.prevent="submitCustomWorkout"
          >
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Workout Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="customForm.title"
                type="text"
                required
                placeholder="e.g. 4-Week Hypertrophy Routine / Chest & Back Split"
                class="w-full px-3.5 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Start Date <span class="text-red-500">*</span>
              </label>
              <AppFormDateInput
                v-model="customForm.effective_date"
                required
                input-class="w-full px-3.5 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <!-- Custom Format Choice -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Workout Format <span class="text-red-500">*</span>
              </label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="p-3 rounded-xl border-2 text-left transition-all flex items-start gap-2.5 cursor-pointer"
                  :class="customForm.format === 'file'
                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 text-primary-900 dark:text-primary-200'
                    : 'border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 text-secondary-700 dark:text-secondary-300'"
                  @click="customForm.format = 'file'"
                >
                  <UploadCloud class="w-5 h-5 shrink-0 text-primary-600 mt-0.5" />
                  <div>
                    <p class="text-xs font-bold">
                      Upload PDF / Image
                    </p>
                    <p class="text-[11px] text-secondary-400 mt-0.5">
                      PDF plan or workout photo
                    </p>
                  </div>
                </button>

                <button
                  type="button"
                  class="p-3 rounded-xl border-2 text-left transition-all flex items-start gap-2.5 cursor-pointer"
                  :class="customForm.format === 'text'
                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 text-primary-900 dark:text-primary-200'
                    : 'border-secondary-200 dark:border-secondary-700 hover:border-secondary-300 text-secondary-700 dark:text-secondary-300'"
                  @click="customForm.format = 'text'"
                >
                  <FileText class="w-5 h-5 shrink-0 text-emerald-600 mt-0.5" />
                  <div>
                    <p class="text-xs font-bold">
                      Rich Text Editor
                    </p>
                    <p class="text-[11px] text-secondary-400 mt-0.5">
                      Enter formatted routine
                    </p>
                  </div>
                </button>
              </div>
            </div>

            <!-- Option A: File Upload Dropzone -->
            <div v-if="customForm.format === 'file'">
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Workout File (PDF or Image) <span class="text-red-500">*</span>
              </label>

              <div
                class="border-2 border-dashed rounded-2xl p-6 text-center transition-all flex flex-col items-center justify-center cursor-pointer"
                :class="selectedFile
                  ? 'border-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/10'
                  : 'border-secondary-300 dark:border-secondary-700 hover:border-primary-400 bg-secondary-50/50 dark:bg-secondary-800/50'"
                @click="$refs.fileInputRef?.click()"
                @dragover.prevent
                @drop.prevent="handleFileDrop"
              >
                <input
                  ref="fileInputRef"
                  type="file"
                  accept="application/pdf,image/png,image/jpeg,image/jpg,image/webp"
                  class="hidden"
                  @change="handleFileSelect"
                />

                <template v-if="selectedFile">
                  <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center mb-2">
                    <FileCheck class="w-6 h-6" />
                  </div>
                  <p class="text-sm font-bold text-secondary-900 dark:text-white">
                    {{ selectedFile.name }}
                  </p>
                  <p class="text-xs text-secondary-400 mt-0.5">
                    {{ formatFileSize(selectedFile.size) }} &bull; Click to change file
                  </p>
                </template>

                <template v-else>
                  <div class="w-12 h-12 rounded-2xl bg-primary-50 dark:bg-primary-900/30 text-primary-600 flex items-center justify-center mb-2">
                    <UploadCloud class="w-6 h-6" />
                  </div>
                  <p class="text-sm font-bold text-secondary-900 dark:text-white">
                    Click to upload or drag &amp; drop
                  </p>
                  <p class="text-xs text-secondary-400 mt-1">
                    Supports PDF, PNG, JPG, JPEG, WEBP (Max 8MB)
                  </p>
                </template>
              </div>
            </div>

            <!-- Option B: Rich Text Editor -->
            <div v-if="customForm.format === 'text'">
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Workout Routine Content <span class="text-red-500">*</span>
              </label>
              <AppRichTextEditor
                v-model="customForm.formatted_text"
                placeholder="Type your workout routine here. Include exercise names, sets, reps, tempo, and rest intervals..."
              />
            </div>

            <!-- Common Notes -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-secondary-700 dark:text-secondary-300 mb-1.5">
                Trainer Notes (Optional)
              </label>
              <textarea
                v-model="customForm.notes"
                rows="2"
                placeholder="Additional instructions, warm-up notes, cooldown..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
          </form>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-secondary-200 dark:border-secondary-700 bg-secondary-50/50 dark:bg-secondary-900/50 shrink-0">
          <button
            type="button"
            class="px-4 py-2 border border-secondary-300 dark:border-secondary-700 rounded-xl text-sm font-semibold text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
            @click="closeAddWorkoutModal"
          >
            Cancel
          </button>

          <button
            v-if="activeMode === 'assign'"
            type="submit"
            form="assignForm"
            class="px-5 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl text-sm font-bold disabled:opacity-50 transition-all shadow-sm"
            :disabled="formSaving || !assignForm.program_id || !assignForm.effective_date"
          >
            {{ formSaving ? 'Assigning...' : 'Assign Program' }}
          </button>

          <button
            v-else
            type="submit"
            form="customForm"
            class="px-5 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl text-sm font-bold disabled:opacity-50 transition-all shadow-sm"
            :disabled="formSaving || !customForm.title || !customForm.effective_date || (customForm.format === 'file' && !selectedFile) || (customForm.format === 'text' && !customForm.formatted_text)"
          >
            {{ formSaving ? 'Saving...' : 'Save &amp; Assign' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── WORKOUT PREVIEW MODAL ────────────────────────────── -->
  <Teleport to="body">
    <div
      v-if="activePreview"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs overflow-y-auto"
    >
      <div class="bg-white dark:bg-secondary-900 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col my-6 overflow-hidden border border-secondary-200 dark:border-secondary-700">
        <!-- Preview Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 shrink-0">
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center border"
              :class="getWorkoutIconClasses(activePreview)"
            >
              <component :is="getWorkoutIcon(activePreview)" class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-secondary-900 dark:text-white">
                {{ activePreview.title || activePreview.assigned_program_title || 'Workout Details' }}
              </h3>
              <p class="text-xs text-secondary-400">
                Effective from {{ formatDate(activePreview.effective_date) }}
                <span v-if="activePreview.created_by_name">&bull; by {{ activePreview.created_by_name }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a
              v-if="activePreview.file_url"
              :href="activePreview.file_url"
              target="_blank"
              download
              class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Download</span>
            </a>

            <button
              type="button"
              class="p-2 rounded-xl text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
              @click="activePreview = null"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Preview Body -->
        <div class="p-6 overflow-y-auto flex-1">
          <!-- Type 1: Configured Workout Program (Structured table) -->
          <div v-if="activePreview.type === 'program' || (!activePreview.type && activePreview.assigned_program_id)">
            <WorkoutProgramPreviewCard
              v-if="previewProgramDetails"
              :program="previewProgramDetails"
            />
            <div v-else class="text-center py-10 text-secondary-400">
              <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
              Loading program routine...
            </div>
          </div>

          <!-- Type 2: Uploaded PDF -->
          <div v-else-if="activePreview.type === 'file' && isPdf(activePreview)" class="space-y-4">
            <div v-if="activePreview.notes" class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-200 dark:border-secondary-700 text-sm">
              <span class="font-bold text-secondary-700 dark:text-secondary-300">Notes: </span>
              <span class="text-secondary-600 dark:text-secondary-400">{{ activePreview.notes }}</span>
            </div>

            <div class="rounded-2xl border border-secondary-200 dark:border-secondary-700 overflow-hidden bg-secondary-950 aspect-[4/3] max-h-[500px]">
              <iframe
                v-if="activePreview.file_url"
                :src="activePreview.file_url"
                class="w-full h-full border-0"
                title="Workout PDF Viewer"
              />
            </div>
          </div>

          <!-- Type 3: Uploaded Image -->
          <div v-else-if="activePreview.type === 'file' && isImage(activePreview)" class="space-y-4 text-center">
            <div v-if="activePreview.notes" class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-200 dark:border-secondary-700 text-sm text-left">
              <span class="font-bold text-secondary-700 dark:text-secondary-300">Notes: </span>
              <span class="text-secondary-600 dark:text-secondary-400">{{ activePreview.notes }}</span>
            </div>

            <div class="rounded-2xl border border-secondary-200 dark:border-secondary-700 overflow-hidden bg-secondary-50 dark:bg-secondary-800/40 p-2 inline-block max-w-full">
              <img
                :src="activePreview.file_url"
                :alt="activePreview.title"
                class="max-h-[600px] w-auto rounded-xl object-contain mx-auto"
              />
            </div>
          </div>

          <!-- Type 4: Rich Formatted Text -->
          <div v-else-if="activePreview.type === 'text'" class="space-y-4">
            <div v-if="activePreview.notes" class="p-4 rounded-xl bg-secondary-50 dark:bg-secondary-800/50 border border-secondary-200 dark:border-secondary-700 text-sm">
              <span class="font-bold text-secondary-700 dark:text-secondary-300">Notes: </span>
              <span class="text-secondary-600 dark:text-secondary-400">{{ activePreview.notes }}</span>
            </div>

            <div class="p-6 rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-800/60 shadow-xs">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div class="app-rich-editor-content text-sm text-secondary-900 dark:text-white" v-html="activePreview.formatted_text" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ── DELETE CONFIRM MODAL ─────────────────────────────── -->
  <AppConfirmModal
    v-if="deleteModalOpen"
    title="Delete Workout"
    message="Are you sure you want to delete this workout? If this was a custom uploaded file, it will also be removed."
    confirm-label="Delete"
    loading-label="Deleting..."
    :loading="deleteSaving"
    @confirm="submitDelete"
    @cancel="deleteModalOpen = false"
  />
</template>

<script setup>
import { computed, ref } from 'vue';
import {
    Plus,
    X,
    FolderOpen,
    FileText,
    FileSpreadsheet,
    FileCheck,
    FilePlus,
    Image as ImageIcon,
    Dumbbell,
    Eye,
    Download,
    Trash2,
    UploadCloud,
} from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';
import { useMemberFormatters } from '../../composables/useMemberFormatters';
import { useAppContext } from '../../composables/useAppContext';
import AppFormDateInput from '../forms/AppFormDateInput.vue';
import AppRichTextEditor from '../forms/AppRichTextEditor.vue';
import AppConfirmModal from '../AppConfirmModal.vue';
import WorkoutProgramPreviewCard from '../WorkoutProgramPreviewCard.vue';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
});

const context = useAppContext();
const canManage = computed(() => Boolean(
    context.permissions?.workout ||
    context.permissions?.workoutAssignments ||
    context.permissions?.workoutPrograms ||
    context.permissions?.membersEdit ||
    context.permissions?.usersEdit
));

const { formatDate } = useMemberFormatters();

const workoutsLoading = ref(false);
const memberWorkouts = ref([]);
const workoutsMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

async function loadMemberWorkouts(page = 1) {
    workoutsLoading.value = true;
    try {
        const res = await apiRequest(`/api/members/${props.memberId}/workouts?page=${page}&per_page=10`);
        const payload = res.data ?? res;
        memberWorkouts.value = Array.isArray(payload) ? payload : (payload.data || []);
        workoutsMeta.value = payload.meta || workoutsMeta.value;
    } catch { /* ignore */ } finally {
        workoutsLoading.value = false;
    }
}

// ── Icon & Badge Helpers ────────────────────────────────────
function getWorkoutTypeLabel(wa) {
    if (wa.type === 'file') {
        if (isPdf(wa)) return 'PDF Plan';
        if (isImage(wa)) return 'Image Routine';
        return 'File Upload';
    }
    if (wa.type === 'text') return 'Custom Routine';
    return 'Program';
}

function getWorkoutIcon(wa) {
    if (wa.type === 'file') {
        if (isPdf(wa)) return FileSpreadsheet;
        return ImageIcon;
    }
    if (wa.type === 'text') return FileText;
    return FolderOpen;
}

function getWorkoutIconClasses(wa) {
    if (wa.type === 'file') {
        if (isPdf(wa)) return 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800';
        return 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-800';
    }
    if (wa.type === 'text') return 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
    return 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 border-primary-200 dark:border-primary-800';
}

function getWorkoutBadgeClasses(wa) {
    if (wa.type === 'file') {
        if (isPdf(wa)) return 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20';
        return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20';
    }
    if (wa.type === 'text') return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    return 'bg-primary-500/10 text-primary-600 dark:text-primary-400 border-primary-500/20';
}

function isPdf(wa) {
    return wa.mime_type === 'application/pdf' || (wa.file_name && wa.file_name.toLowerCase().endsWith('.pdf'));
}

function isImage(wa) {
    return wa.mime_type?.startsWith('image/') || (wa.file_name && /\.(jpg|jpeg|png|webp)$/i.test(wa.file_name));
}

function formatFileSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function todayStr() {
    return new Date().toISOString().slice(0, 10);
}

function defaultWorkoutTitle() {
    const today = new Date();
    const formattedDate = today.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    return `Workout Routine - ${formattedDate}`;
}

// ── Modal State & Management ────────────────────────────────
const modalOpen = ref(false);
const activeMode = ref('assign'); // 'assign' | 'custom'
const formSaving = ref(false);
const formError = ref('');

const assignForm = ref({ program_id: '', effective_date: '', notes: '' });
const customForm = ref({ title: defaultWorkoutTitle(), effective_date: '', format: 'file', formatted_text: '', notes: '' });

const selectedFile = ref(null);
const fileInputRef = ref(null);

const programs = ref([]);
const programsLoading = ref(false);

async function openAddWorkoutModal() {
    formError.value = '';
    formSaving.value = false;
    assignForm.value = { program_id: '', effective_date: todayStr(), notes: '' };
    customForm.value = { title: defaultWorkoutTitle(), effective_date: todayStr(), format: 'file', formatted_text: '', notes: '' };
    selectedFile.value = null;
    modalOpen.value = true;

    if (programs.value.length === 0) {
        programsLoading.value = true;
        try {
            const res = await apiRequest('/api/workout-programs?per_page=200');
            programs.value = res.data?.data || res.data || res;
        } catch { /* ignore */ } finally {
            programsLoading.value = false;
        }
    }
}

function closeAddWorkoutModal() {
    modalOpen.value = false;
}

const MAX_FILE_SIZE_BYTES = 8 * 1024 * 1024; // 8MB
const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

function autoSetTitleFromFile(file) {
    if (!file) return;
    const baseName = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ').trim();
    if (!baseName) return;
    const formatted = baseName.replace(/\b\w/g, (c) => c.toUpperCase());
    if (!customForm.value.title || customForm.value.title.startsWith('Workout Routine -')) {
        customForm.value.title = formatted;
    }
}

function validateAndSetFile(file) {
    formError.value = '';
    if (!file) return;

    if (file.size > MAX_FILE_SIZE_BYTES) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
        formError.value = `The selected file "${file.name}" (${sizeMb} MB) exceeds the maximum allowed size of 8 MB. Please compress or choose a smaller file.`;
        selectedFile.value = null;
        if (fileInputRef.value) fileInputRef.value.value = '';
        return;
    }

    const ext = file.name.split('.').pop()?.toLowerCase();
    const isAllowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(ext);
    const isAllowedMime = ALLOWED_MIME_TYPES.includes(file.type) || isAllowedExt;

    if (!isAllowedMime) {
        formError.value = `The file "${file.name}" is not supported. Please upload a PDF or image file (PDF, PNG, JPG, JPEG, WEBP).`;
        selectedFile.value = null;
        if (fileInputRef.value) fileInputRef.value.value = '';
        return;
    }

    selectedFile.value = file;
    autoSetTitleFromFile(file);
}

function handleFileSelect(e) {
    const file = e.target.files?.[0];
    if (file) {
        validateAndSetFile(file);
    }
}

function handleFileDrop(e) {
    const file = e.dataTransfer?.files?.[0];
    if (file) {
        validateAndSetFile(file);
    }
}

async function submitAssignProgram() {
    if (!assignForm.value.program_id || !assignForm.value.effective_date) return;
    formSaving.value = true;
    formError.value = '';

    try {
        await apiRequest(`/api/members/${props.memberId}/workouts`, {
            method: 'post',
            data: {
                type: 'program',
                program_id: assignForm.value.program_id,
                effective_date: assignForm.value.effective_date,
                notes: assignForm.value.notes,
            },
        });
        closeAddWorkoutModal();
        loadMemberWorkouts(1);
    } catch (err) {
        if (err?.response?.data?.errors) {
            const firstMsg = Object.values(err.response.data.errors).flat()[0];
            formError.value = firstMsg || err.response.data.message || 'Failed to assign program.';
        } else {
            formError.value = err?.response?.data?.message || 'Failed to assign program.';
        }
    } finally {
        formSaving.value = false;
    }
}

async function submitCustomWorkout() {
    if (!customForm.value.title || !customForm.value.effective_date) return;
    formSaving.value = true;
    formError.value = '';

    try {
        if (customForm.value.format === 'file') {
            if (!selectedFile.value) {
                formError.value = 'Please select a workout file (PDF or image) to upload.';
                formSaving.value = false;
                return;
            }

            if (selectedFile.value.size > MAX_FILE_SIZE_BYTES) {
                formError.value = `The selected file (${(selectedFile.value.size / (1024 * 1024)).toFixed(1)} MB) exceeds the maximum allowed size of 8 MB.`;
                formSaving.value = false;
                return;
            }

            const formData = new FormData();
            formData.append('type', 'file');
            formData.append('title', customForm.value.title);
            formData.append('effective_date', customForm.value.effective_date);
            formData.append('file', selectedFile.value);
            if (customForm.value.notes) {
                formData.append('notes', customForm.value.notes);
            }

            await apiRequest(`/api/members/${props.memberId}/workouts`, {
                method: 'post',
                data: formData,
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        } else {
            if (!customForm.value.formatted_text) {
                formError.value = 'Please enter workout routine content.';
                formSaving.value = false;
                return;
            }

            await apiRequest(`/api/members/${props.memberId}/workouts`, {
                method: 'post',
                data: {
                    type: 'text',
                    title: customForm.value.title,
                    effective_date: customForm.value.effective_date,
                    formatted_text: customForm.value.formatted_text,
                    notes: customForm.value.notes,
                },
            });
        }

        closeAddWorkoutModal();
        loadMemberWorkouts(1);
    } catch (err) {
        if (err?.response?.status === 413 || err?.message?.includes('413') || err?.response?.data?.message?.includes('too large') || err?.response?.data?.message?.includes('exceeds the limit')) {
            formError.value = 'The uploaded file exceeds the server maximum upload limit (8MB). Please choose a smaller or compressed file.';
        } else if (err?.response?.data?.errors) {
            const firstMsg = Object.values(err.response.data.errors).flat()[0];
            formError.value = firstMsg || err.response.data.message || 'Failed to save workout.';
        } else {
            formError.value = err?.response?.data?.message || err?.message || 'Failed to create workout.';
        }
    } finally {
        formSaving.value = false;
    }
}

// ── Preview Modal ───────────────────────────────────────────
const activePreview = ref(null);
const previewProgramDetails = ref(null);

async function previewWorkout(wa) {
    activePreview.value = wa;
    previewProgramDetails.value = null;

    if (wa.type === 'program' || (!wa.type && wa.assigned_program_id)) {
        try {
            const programId = wa.assigned_program_id || wa.source_program_id;
            if (programId) {
                const res = await apiRequest(`/api/workout-programs/${programId}`);
                previewProgramDetails.value = res.data ?? res;
            }
        } catch { /* ignore */ }
    }
}

// ── Delete Workout ──────────────────────────────────────────
const deleteModalOpen = ref(false);
const deleteSaving = ref(false);
const itemToDelete = ref(null);

function confirmDelete(wa) {
    itemToDelete.value = wa;
    deleteModalOpen.value = true;
}

async function submitDelete() {
    if (!itemToDelete.value) return;
    deleteSaving.value = true;
    try {
        await apiRequest(`/api/workout-program-assignments/${itemToDelete.value.id}`, {
            method: 'delete',
        });
        loadMemberWorkouts(1);
    } catch {
        // failed to delete
    } finally {
        deleteSaving.value = false;
        deleteModalOpen.value = false;
    }
}

defineExpose({ loadMemberWorkouts });
</script>
