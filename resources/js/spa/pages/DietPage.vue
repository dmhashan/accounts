<template>
  <LegacyBladeView title="Diet Plan" description="Access nutrition planning from SPA routes while reusing existing Blade diet pages." :url="context.legacyUrls?.diet" />
</template>

<script setup>
import LegacyBladeView from '../components/LegacyBladeView.vue';
import { useAppContext } from '../composables/useAppContext';

const context = useAppContext();
</script>
