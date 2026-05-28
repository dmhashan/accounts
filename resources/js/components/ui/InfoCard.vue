<template>
  <article class="group bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-700 p-5 md:p-6 shadow-sm hover:shadow-md transition-all duration-200">
    <div class="flex items-center gap-3 mb-4">
      <div class="h-10 w-10 rounded-lg flex items-center justify-center" :class="iconToneClasses">
        <Building2
          v-if="icon === 'tenant'"
          class="h-6 w-6"
          :class="iconColorClasses"
          aria-hidden="true"
        />
        <Globe
          v-else-if="icon === 'domain'"
          class="h-6 w-6"
          :class="iconColorClasses"
          aria-hidden="true"
        />
        <User
          v-else
          class="h-6 w-6"
          :class="iconColorClasses"
          aria-hidden="true"
        />
      </div>
      <h3 class="text-base md:text-lg font-semibold text-secondary-900 dark:text-white">
        {{ title }}
      </h3>
    </div>

    <p :class="valueClasses">
      {{ value }}
    </p>
    <p class="mt-1 text-sm text-secondary-500 dark:text-secondary-400 break-all">
      {{ subtitle }}
    </p>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import { Building2, Globe, User } from 'lucide-vue-next';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: 'user',
    },
});

const iconToneClasses = computed(() => {
    if (props.icon === 'tenant') {
        return 'bg-primary-100 dark:bg-primary-900/50';
    }

    if (props.icon === 'domain') {
        return 'bg-secondary-100 dark:bg-secondary-800/50';
    }

    return 'bg-green-100 dark:bg-green-900/50';
});

const iconColorClasses = computed(() => {
    if (props.icon === 'tenant') {
        return 'text-primary-600 dark:text-primary-400';
    }

    if (props.icon === 'domain') {
        return 'text-secondary-600 dark:text-secondary-400';
    }

    return 'text-green-600 dark:text-green-400';
});

const valueClasses = computed(() => {
    if (props.icon === 'domain') {
        return 'text-sm md:text-base font-mono font-semibold text-secondary-900 dark:text-white break-all';
    }

    if (props.icon === 'account') {
        return 'text-lg md:text-xl font-semibold text-secondary-900 dark:text-white';
    }

    return 'text-2xl font-bold text-secondary-900 dark:text-white';
});
</script>