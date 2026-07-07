<template>
  <div class="app-page-header-compact">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex min-w-0 items-center gap-3">
        <!-- Back button -->
        <button
          v-if="showBack"
          type="button"
          class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full border transition-colors"
          style="border-color: var(--surface-border); color: var(--text-muted);"
          onmouseover="this.style.background='var(--surface-muted)'"
          onmouseout="this.style.background=''"
          @click="goBack"
        >
          <ArrowLeft class="w-4 h-4" :stroke-width="2.25" />
        </button>

        <!-- Title -->
        <h2 class="text-xl md:text-2xl font-bold truncate app-gradient-title flex items-center gap-2">
          <slot name="title-slot">
            <span>{{ resolvedTitle }}</span>
          </slot>
        </h2>
      </div>

      <!-- CTAs slot -->
      <div v-if="$slots['cta-slot']" class="flex min-w-0 flex-wrap items-center gap-2 sm:shrink-0 sm:justify-end">
        <slot name="cta-slot" />
      </div>
    </div>

    <!-- Extra slot: search bar, tabs, etc. -->
    <div v-if="$slots['extra-slot']" class="mt-3">
      <slot name="extra-slot" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    title: { type: String, default: null },
    showBack: { type: Boolean, default: false },
});

const route = useRoute();
const router = useRouter();

const resolvedTitle = computed(() => {
    if (props.title) return props.title;
    if (route.meta?.title) return route.meta.title;
    const segment = route.path.split('/').filter(Boolean).pop() || '';
    return segment.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) || 'Page';
});

function goBack() {
    router.back();
}
</script>
