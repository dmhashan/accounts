<template>
    <LegacyBladeView title="Payments" description="Review payment workflows in SPA routing while preserving existing Blade pages." :url="context.legacyUrls?.payments" />
</template>

<script setup>
import LegacyBladeView from '../components/LegacyBladeView.vue';
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();
</script>
