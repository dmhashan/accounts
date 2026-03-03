<template>
    <LegacyBladeView title="Workout Schedule" description="Track workout schedules through SPA routes using the existing Blade workout module." :url="context.legacyUrls?.workout" />
</template>

<script setup>
import LegacyBladeView from '../components/LegacyBladeView.vue';
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();
</script>
