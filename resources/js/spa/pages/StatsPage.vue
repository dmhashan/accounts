<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(() => {
    router.replace({ path: '/reports', query: { tab: 'stats' } });
});
</script>

<template>
    <div />
</template>
