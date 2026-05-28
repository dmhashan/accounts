<template>
  <div class="flex-1 flex items-center justify-center">
    <div class="flex flex-col items-center gap-3 text-gray-400">
      <Loader2 class="w-8 h-8 animate-spin" />
      <p class="text-sm">
        Loading&hellip;
      </p>
    </div>
  </div>
</template>

<script setup>
import { Loader2 } from 'lucide-vue-next';
</script>
