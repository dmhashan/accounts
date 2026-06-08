<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

onMounted(() => {
    router.replace({ path: '/reports', query: { ...route.query, tab: 'stats' } });
});
</script>

<template>
  <div />
</template>
