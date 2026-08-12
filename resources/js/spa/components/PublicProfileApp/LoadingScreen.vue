<template>
  <div class="flex-1 flex flex-col items-center justify-center min-h-[60vh] px-4">
    <div class="flex flex-col items-center gap-4">
      <div class="relative w-16 h-16 rounded-3xl bg-red-500/10 text-red-500 flex items-center justify-center shadow-lg shadow-red-500/10">
        <Dumbbell class="w-8 h-8 animate-bounce" :stroke-width="1.8" />
        <span class="absolute inset-0 rounded-3xl border-2 border-red-500/30 border-t-red-500 animate-spin" />
      </div>
      <div class="text-center">
        <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
          Loading Member Portal
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-medium">
          Please wait a moment&hellip;
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Dumbbell } from 'lucide-vue-next';
</script>
