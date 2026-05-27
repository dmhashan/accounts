<template>
  <!-- Clickable avatar shell -->
  <div class="relative group inline-block">
    <!-- Avatar image or initials -->
    <MemberAvatar
      :src="photoUrl"
      :initials="initials"
      size="xl"
      shape="square"
      variant="glass"
      class="shadow-xl"
    />

    <!-- Edit overlay — only shown when edit is permitted -->
    <button
      v-if="canEdit"
      type="button"
      class="absolute inset-0 rounded-2xl flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity cursor-pointer"
      :disabled="uploading"
      :title="photoUrl ? 'Change photo' : 'Upload photo'"
      @click="triggerPicker"
    >
      <svg
        v-if="!uploading"
        class="w-6 h-6 text-white drop-shadow"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
        />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
      <!-- Spinner while uploading -->
      <svg
        v-else
        class="w-5 h-5 text-white animate-spin"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
      </svg>
    </button>

    <!-- Remove badge — shown when photo exists and edit is permitted -->
    <button
      v-if="canEdit && photoUrl && !uploading"
      type="button"
      title="Remove photo"
      class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 hover:bg-red-600 border-2 border-white flex items-center justify-center shadow-md transition-colors z-10"
      :disabled="uploading"
      @click.stop="removePhoto"
    >
      <svg
        class="w-2.5 h-2.5 text-white"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="3"
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>

    <!-- Hidden file input -->
    <input
      ref="fileInput"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="hidden"
      @change="onFileSelected"
    />
  </div>

  <!-- Error message below avatar -->
  <p v-if="error" class="mt-1.5 text-xs text-red-300 max-w-[12rem] text-center">
    {{ error }}
  </p>

  <!-- Crop modal -->
  <AvatarCropModal
    v-if="cropSrc"
    :image-src="cropSrc"
    @confirm="onCropConfirm"
    @cancel="onCropCancel"
  />

  <!-- Remove photo confirmation modal -->
  <AppConfirmModal
    v-if="removePhotoConfirm"
    title="Remove Profile Photo"
    message="Remove this profile photo? This cannot be undone."
    confirm-label="Remove"
    loading-label="Removing..."
    @confirm="confirmRemovePhoto"
    @cancel="removePhotoConfirm = false"
  />
</template>

<script setup>
import { ref } from 'vue';
import { apiRequest } from '../composables/useApiClient';
import AppConfirmModal from './AppConfirmModal.vue';
import AvatarCropModal from './AvatarCropModal.vue';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';

const props = defineProps({
    memberId: { type: Number, required: true },
    photoUrl: { type: String, default: null },
    initials: { type: String, default: 'MB' },
    canEdit: { type: Boolean, default: false },
});

const emit = defineEmits(['update:photoUrl']);

const fileInput = ref(null);
const uploading = ref(false);
const error     = ref('');
const cropSrc   = ref(null);

// ── Step 1: file picked → open crop modal ────────────────────────────────────

function triggerPicker() {
    error.value = '';
    fileInput.value?.click();
}

function onFileSelected(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        error.value = 'Please select a valid image file.';
        return;
    }

    // Load as data URL so the crop modal can display it without a network request
    const reader = new FileReader();
    reader.onload = (e) => { cropSrc.value = e.target.result; };
    reader.readAsDataURL(file);
}

// ── Step 2: crop confirmed → resize to 320×320 and upload ───────────────────

async function onCropConfirm(blob) {
    cropSrc.value = null;
    uploading.value = true;
    error.value = '';

    const formData = new FormData();
    formData.append('avatar', blob, 'avatar.jpg');
    if (props.photoUrl) {
        formData.append('_method', 'PUT');
    }

    try {
        const res = await apiRequest(`/api/members/${props.memberId}/avatar`, {
            method: 'post',
            data: formData,
        });
        emit('update:photoUrl', res.profile_photo_url);
    } catch (err) {
        error.value = err?.response?.data?.message || 'Upload failed. Please try again.';
    } finally {
        uploading.value = false;
    }
}

function onCropCancel() {
    cropSrc.value = null;
}

// ── Remove ───────────────────────────────────────────────────────────────────

const removePhotoConfirm = ref(false);

function removePhoto() {
    removePhotoConfirm.value = true;
}

async function confirmRemovePhoto() {
    removePhotoConfirm.value = false;
    uploading.value = true;
    error.value = '';
    try {
        await apiRequest(`/api/members/${props.memberId}/avatar`, {
            method: 'post',
            data: { _method: 'DELETE' },
        });
        emit('update:photoUrl', null);
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to remove photo.';
    } finally {
        uploading.value = false;
    }
}
</script>
