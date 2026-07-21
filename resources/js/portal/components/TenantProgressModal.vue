<template>
  <div v-if="show" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden relative transition-all animate-in fade-in zoom-in-95 duration-200">
      <!-- Modal Header -->
      <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-slate-800/80 flex items-start justify-between">
        <div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold uppercase tracking-wider mb-2" :class="operationBadgeClass">
            <span class="w-1.5 h-1.5 rounded-full" :class="operationDotClass" />
            {{ operationLabel }}
          </span>
          <h3 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            {{ titleText }}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Target subdomain: <span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ subdomain }}</span>
          </p>
        </div>

        <button 
          v-if="jobStatus === 'completed' || jobStatus === 'failed'"
          class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-all cursor-pointer"
          @click="closeModal"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Progress Bar Section -->
      <div class="px-6 pt-5 pb-2">
        <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider mb-2">
          <span class="text-slate-500 dark:text-slate-400 flex items-center gap-2">
            Progress Status
            <span v-if="jobStatus === 'processing' || jobStatus === 'pending'" class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 font-semibold text-[11px] lowercase">
              <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              executing...
            </span>
          </span>
          <span class="font-mono font-extrabold text-sm" :class="percentageTextClass">
            {{ progressPercentage }}%
          </span>
        </div>

        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden relative p-0.5 border border-slate-200/50 dark:border-slate-800">
          <div 
            class="h-full rounded-full transition-all duration-500 ease-out relative overflow-hidden" 
            :class="progressBarClass"
            :style="{ width: `${progressPercentage}%` }"
          >
            <div v-if="jobStatus === 'processing' || jobStatus === 'pending'" class="absolute inset-0 bg-white/20 animate-pulse" />
          </div>
        </div>
      </div>

      <!-- Step List -->
      <div class="px-6 py-4 max-h-[340px] overflow-y-auto space-y-3">
        <div 
          v-for="(step, index) in steps" 
          :key="step.key"
          class="flex items-start gap-3.5 p-3.5 rounded-xl border transition-all"
          :class="getStepContainerClass(step)"
        >
          <!-- Step Icon -->
          <div class="mt-0.5 flex-shrink-0">
            <!-- Completed -->
            <div v-if="step.status === 'completed'" class="w-6 h-6 rounded-full bg-emerald-500/15 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/30">
              <svg
                class="w-3.5 h-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="3"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>

            <!-- Processing -->
            <div v-else-if="step.status === 'processing'" class="w-6 h-6 rounded-full bg-indigo-500/15 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-500/30 relative">
              <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
            </div>

            <!-- Failed -->
            <div v-else-if="step.status === 'failed'" class="w-6 h-6 rounded-full bg-rose-500/15 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-500/30">
              <svg
                class="w-3.5 h-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="3"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>

            <!-- Pending -->
            <div v-else class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center border border-slate-200 dark:border-slate-700 text-xs font-mono font-bold">
              {{ index + 1 }}
            </div>
          </div>

          <!-- Step Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <h4 class="text-xs font-bold tracking-wide text-slate-800 dark:text-slate-200">
                {{ step.title }}
              </h4>
              <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="getStepBadgeClass(step)">
                {{ getStepStatusLabel(step) }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ step.description }}
            </p>
            <div v-if="step.error" class="mt-2 p-2.5 bg-rose-500/10 border border-rose-500/20 rounded-lg text-xs text-rose-600 dark:text-rose-400 font-mono">
              {{ step.error }}
            </div>
          </div>
        </div>
      </div>

      <!-- Error Summary Alert if Failed -->
      <div v-if="jobStatus === 'failed' && errorMessage" class="mx-6 mb-4 p-3.5 bg-rose-500/10 border border-rose-500/20 rounded-xl space-y-1">
        <div class="flex items-center gap-2 text-rose-700 dark:text-rose-400 font-bold text-xs">
          <svg
            class="w-4 h-4 flex-shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <span>Job Execution Failed</span>
        </div>
        <p class="text-xs text-rose-600 dark:text-rose-400/90 leading-relaxed font-mono">
          {{ errorMessage }}
        </p>
      </div>

      <!-- Success Summary Alert if Completed -->
      <div v-if="jobStatus === 'completed'" class="mx-6 mb-4 p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            />
          </svg>
        </div>
        <div class="text-xs">
          <h5 class="font-bold text-emerald-800 dark:text-emerald-400">
            Operation Successful
          </h5>
          <p class="text-emerald-600 dark:text-emerald-400/80">
            Tenant queue job has executed all {{ steps.length }} steps without errors.
          </p>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 bg-slate-50/80 dark:bg-slate-950/60 border-t border-slate-100 dark:border-slate-800/80 flex justify-end gap-3">
        <button 
          v-if="jobStatus === 'completed' || jobStatus === 'failed'"
          class="px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all cursor-pointer"
          :class="jobStatus === 'completed' ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-500/20' : 'bg-slate-800 hover:bg-slate-700 text-white'"
          @click="closeModal"
        >
          Close & Refresh
        </button>
        <div v-else class="flex items-center gap-2 text-xs font-semibold text-slate-400 py-1">
          <span>Please leave this modal open until processing completes</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onUnmounted } from 'vue';
import { apiRequest } from '../composables/usePortalApi';

export default {
  name: 'TenantProgressModal',
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    jobId: {
      type: String,
      default: '',
    },
    subdomain: {
      type: String,
      default: '',
    },
    operation: {
      type: String,
      default: 'create', // create | update | delete
    },
  },
  emits: ['close', 'complete', 'fail'],
  setup(props, { emit }) {
    const steps = ref([]);
    const jobStatus = ref('pending'); // pending | processing | completed | failed
    const progressPercentage = ref(0);
    const errorMessage = ref(null);
    let timer = null;

    const operationLabel = computed(() => {
      switch (props.operation) {
        case 'create': return 'Tenant Provisioning Job';
        case 'update': return 'Tenant Update Job';
        case 'delete': return 'Tenant Deletion Job';
        default: return 'Tenant Queue Job';
      }
    });

    const titleText = computed(() => {
      switch (props.operation) {
        case 'create': return 'Provisioning Tenant';
        case 'update': return 'Updating Tenant';
        case 'delete': return 'Deleting Tenant';
        default: return 'Processing Job';
      }
    });

    const operationBadgeClass = computed(() => {
      switch (props.operation) {
        case 'create': return 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/30';
        case 'update': return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/30';
        case 'delete': return 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200/50 dark:border-rose-800/30';
        default: return 'bg-slate-100 text-slate-700';
      }
    });

    const operationDotClass = computed(() => {
      switch (props.operation) {
        case 'create': return 'bg-indigo-500';
        case 'update': return 'bg-amber-500';
        case 'delete': return 'bg-rose-500';
        default: return 'bg-slate-500';
      }
    });

    const percentageTextClass = computed(() => {
      if (jobStatus.value === 'completed') return 'text-emerald-600 dark:text-emerald-400';
      if (jobStatus.value === 'failed') return 'text-rose-600 dark:text-rose-400';
      return 'text-indigo-600 dark:text-indigo-400';
    });

    const progressBarClass = computed(() => {
      if (jobStatus.value === 'completed') return 'bg-emerald-500';
      if (jobStatus.value === 'failed') return 'bg-rose-500';
      return 'bg-gradient-to-r from-indigo-500 to-indigo-600';
    });

    const getStepContainerClass = (step) => {
      if (step.status === 'completed') {
        return 'bg-emerald-50/40 dark:bg-emerald-950/10 border-emerald-200/60 dark:border-emerald-900/30';
      }
      if (step.status === 'processing') {
        return 'bg-indigo-50/60 dark:bg-indigo-950/20 border-indigo-300 dark:border-indigo-800 shadow-sm shadow-indigo-500/5 ring-1 ring-indigo-500/20';
      }
      if (step.status === 'failed') {
        return 'bg-rose-50/50 dark:bg-rose-950/20 border-rose-300 dark:border-rose-800';
      }
      return 'bg-slate-50/50 dark:bg-slate-950/30 border-slate-200/60 dark:border-slate-800/60 opacity-60';
    };

    const getStepBadgeClass = (step) => {
      if (step.status === 'completed') return 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400';
      if (step.status === 'processing') return 'bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 animate-pulse';
      if (step.status === 'failed') return 'bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400';
      return 'bg-slate-200/60 dark:bg-slate-800 text-slate-500 dark:text-slate-400';
    };

    const getStepStatusLabel = (step) => {
      switch (step.status) {
        case 'completed': return 'Done';
        case 'processing': return 'In Progress';
        case 'failed': return 'Failed';
        default: return 'Pending';
      }
    };

    const pollJobStatus = async () => {
      if (!props.jobId) return;

      try {
        const data = await apiRequest(`/tenants/jobs/${props.jobId}`);
        jobStatus.value = data.status;
        progressPercentage.value = data.progress_percentage || 0;
        steps.value = data.steps || [];
        errorMessage.value = data.error_message || null;

        if (data.status === 'completed') {
          stopPolling();
          emit('complete', data);
        } else if (data.status === 'failed') {
          stopPolling();
          emit('fail', data);
        }
      } catch {
        // Continue polling if network error
      }
    };

    const startPolling = () => {
      stopPolling();
      pollJobStatus();
      timer = setInterval(pollJobStatus, 1000);
    };

    const stopPolling = () => {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    };

    const closeModal = () => {
      stopPolling();
      emit('close');
    };

    watch(() => props.show, (newVal) => {
      if (newVal && props.jobId) {
        jobStatus.value = 'pending';
        progressPercentage.value = 0;
        steps.value = [];
        errorMessage.value = null;
        startPolling();
      } else {
        stopPolling();
      }
    }, { immediate: true });

    onUnmounted(stopPolling);

    return {
      steps,
      jobStatus,
      progressPercentage,
      errorMessage,
      operationLabel,
      titleText,
      operationBadgeClass,
      operationDotClass,
      percentageTextClass,
      progressBarClass,
      getStepContainerClass,
      getStepBadgeClass,
      getStepStatusLabel,
      closeModal,
    };
  }
};
</script>
