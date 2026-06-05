<template>
  <!-- eslint-disable-next-line vue/valid-v-on -->
  <div class="fixed inset-0 z-40 flex items-center justify-center p-4" @keydown.escape.window="close">
    <div class="absolute inset-0 bg-black/45" @click="close" />

    <div class="relative z-10 w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl app-surface shadow-xl">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-secondary-200 dark:border-secondary-700">
        <div>
          <h3 class="text-base font-semibold text-secondary-900 dark:text-white">
            Prepare &amp; Sign Report
          </h3>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
            Add your name, signature and a selfie to take responsibility for this report.
          </p>
        </div>
        <button
          type="button"
          class="text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 mt-0.5"
          aria-label="Close"
          @click="close"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <form class="px-5 py-4 space-y-5" @submit.prevent="submit">
        <div v-if="changeCount > 0" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-xs text-red-700 dark:text-red-200">
          {{ changeCount }} value{{ changeCount === 1 ? '' : 's' }} changed from the system figures. Edited values will be highlighted in red on the PDF.
        </div>

        <!-- Prepared by name -->
        <AppFormField label="Prepared By (full name)" required>
          <AppFormInput
            v-model.trim="form.prepared_by_name"
            placeholder="e.g. John Silva"
            autocomplete="off"
          />
          <p v-if="errors.prepared_by_name" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ errors.prepared_by_name }}
          </p>
        </AppFormField>

        <!-- Signature -->
        <div>
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-200 mb-1.5">
            Signature <span class="text-red-500">*</span>
          </label>
          <AppSignaturePad v-model="form.signature" />
          <p v-if="errors.signature" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ errors.signature }}
          </p>
        </div>

        <!-- Selfie -->
        <div>
          <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-200 mb-1.5">
            Selfie <span class="text-red-500">*</span>
          </label>

          <div class="rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden bg-secondary-50 dark:bg-background-dark">
            <!-- Captured preview -->
            <div v-if="form.selfie" class="relative">
              <img :src="form.selfie" alt="Selfie" class="w-full max-h-64 object-contain bg-black" />
              <button
                type="button"
                class="absolute top-2 right-2 rounded-lg bg-black/60 text-white text-xs px-2.5 py-1 hover:bg-black/80"
                @click="retakeSelfie"
              >
                Retake
              </button>
            </div>

            <!-- Live camera -->
            <div v-else-if="cameraOn" class="relative">
              <video
                ref="videoRef"
                autoplay
                playsinline
                muted
                class="w-full max-h-64 object-contain bg-black"
              />
              <div class="absolute inset-x-0 bottom-0 flex justify-center gap-3 p-3 bg-gradient-to-t from-black/60 to-transparent">
                <button
                  type="button"
                  class="rounded-full bg-white text-secondary-900 text-sm font-semibold px-4 py-2 shadow hover:brightness-95"
                  @click="capture"
                >
                  Capture
                </button>
                <button
                  type="button"
                  class="rounded-full bg-black/60 text-white text-sm font-semibold px-4 py-2 hover:bg-black/80"
                  @click="stopCamera"
                >
                  Cancel
                </button>
              </div>
            </div>

            <!-- Idle -->
            <div v-else class="flex flex-col items-center justify-center gap-2 py-8 px-4 text-center">
              <Camera class="w-8 h-8 text-secondary-300" />
              <button
                type="button"
                class="rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white text-sm font-semibold px-4 py-2 hover:brightness-110"
                @click="startCamera"
              >
                Open Camera
              </button>
            </div>
          </div>
          <canvas ref="canvasRef" class="hidden" />
          <p v-if="cameraError" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ cameraError }}
          </p>
          <p v-else-if="errors.selfie" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ errors.selfie }}
          </p>
        </div>

        <div v-if="submitError" class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">
          {{ submitError }}
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-1">
          <button
            type="button"
            class="px-4 py-2 rounded-xl app-surface-soft text-sm font-semibold text-secondary-700 dark:text-secondary-200 hover:brightness-105"
            @click="close"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 text-white text-sm font-semibold disabled:opacity-60 disabled:cursor-not-allowed hover:brightness-110"
          >
            {{ submitting ? 'Generating...' : 'Generate Report' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref } from 'vue';
import { Camera, X } from 'lucide-vue-next';
import AppFormField from '../forms/AppFormField.vue';
import AppFormInput from '../forms/AppFormInput.vue';
import AppSignaturePad from '../AppSignaturePad.vue';

defineProps({
    changeCount: { type: Number, default: 0 },
    submitting: { type: Boolean, default: false },
    submitError: { type: String, default: '' },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({ prepared_by_name: '', signature: '', selfie: '' });
const errors = ref({});

const videoRef = ref(null);
const canvasRef = ref(null);
const cameraOn = ref(false);
const cameraError = ref('');
let stream = null;

async function startCamera() {
    cameraError.value = '';
    cameraOn.value = true;
    await nextTick();
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        if (videoRef.value) videoRef.value.srcObject = stream;
    } catch {
        cameraError.value = 'Camera access denied. Please allow camera permission.';
        cameraOn.value = false;
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(t => t.stop());
        stream = null;
    }
    cameraOn.value = false;
}

function capture() {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    if (!video || !canvas) return;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    form.value.selfie = canvas.toDataURL('image/png');
    stopCamera();
}

function retakeSelfie() {
    form.value.selfie = '';
    startCamera();
}

function validate() {
    const e = {};
    if (!form.value.prepared_by_name) e.prepared_by_name = 'Name is required.';
    if (!form.value.signature) e.signature = 'Signature is required.';
    if (!form.value.selfie) e.selfie = 'A selfie is required.';
    errors.value = e;
    return Object.keys(e).length === 0;
}

function submit() {
    if (!validate()) return;
    emit('submit', {
        prepared_by_name: form.value.prepared_by_name,
        signature: form.value.signature,
        selfie: form.value.selfie,
    });
}

function close() {
    stopCamera();
    emit('close');
}

onBeforeUnmount(stopCamera);
</script>
