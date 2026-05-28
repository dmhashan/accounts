<template>
  <LegacyBladeView title="Attendance" description="View attendance module in SPA navigation while using existing Blade attendance views." :url="context.legacyUrls?.attendance" />
</template>

<script setup>
import LegacyBladeView from '../components/LegacyBladeView.vue';
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();
</script>
