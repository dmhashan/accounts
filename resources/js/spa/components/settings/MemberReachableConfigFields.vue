<template>
  <div class="space-y-5">
    <div class="grid grid-cols-1 gap-4">
      <AppFormField label="Member Portal URL" for-id="member-reachable-login-url" optional>
        <AppFormInput
          id="member-reachable-login-url"
          v-model="localConfig.member_login_url"
          type="url"
          placeholder="https://members.example.com/profile"
          maxlength="500"
          @input="emitChange"
        />
      </AppFormField>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import AppFormField from '../forms/AppFormField.vue';
import AppFormInput from '../forms/AppFormInput.vue';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

let lastEmittedSnapshot = '';
const localConfig = reactive(normalizeConfig(props.modelValue));

watch(
    () => props.modelValue,
    (value) => {
        const incomingSnapshot = snapshotConfig(value);

        if (incomingSnapshot === lastEmittedSnapshot) {
            return;
        }

        Object.assign(localConfig, normalizeConfig(value));
    },
    { deep: true },
);

function normalizeConfig(config) {
    return {
        member_login_url: config?.member_login_url || '',
    };
}

function emitChange() {
    const payload = {
        member_login_url: localConfig.member_login_url,
    };

    lastEmittedSnapshot = snapshotConfig(payload);
    emit('update:modelValue', payload);
}

function snapshotConfig(config) {
    return JSON.stringify({
        member_login_url: config?.member_login_url || '',
    });
}
</script>
