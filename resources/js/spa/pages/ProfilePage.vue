<template>
    <LegacyBladeView title="Profile" description="Open your member profile inside the SPA while using the existing Blade profile page." :url="context.legacyUrls?.profile" />
</template>

<script setup>
import LegacyBladeView from '../components/LegacyBladeView.vue';
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();
</script>
