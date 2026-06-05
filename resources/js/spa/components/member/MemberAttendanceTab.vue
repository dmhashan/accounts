<template>
  <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
    <!-- Header: year nav + total -->
    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="w-7 h-7 flex items-center justify-center rounded-lg border border-secondary-200 dark:border-secondary-700 hover:bg-secondary-100 dark:hover:bg-secondary-800 text-secondary-500 dark:text-secondary-400 transition-colors"
          @click="changeAttendanceYear(-1)"
        >
          <ChevronLeft class="w-3.5 h-3.5" :stroke-width="2.5" />
        </button>
        <span class="text-sm font-bold text-secondary-900 dark:text-white tabular-nums w-10 text-center">{{ attendanceYear }}</span>
        <button
          type="button"
          class="w-7 h-7 flex items-center justify-center rounded-lg border border-secondary-200 dark:border-secondary-700 hover:bg-secondary-100 dark:hover:bg-secondary-800 text-secondary-500 dark:text-secondary-400 transition-colors disabled:opacity-30"
          :disabled="attendanceYear >= currentYear"
          @click="changeAttendanceYear(1)"
        >
          <ChevronRight class="w-3.5 h-3.5" :stroke-width="2.5" />
        </button>
      </div>
      <div class="flex items-center gap-2">
        <span v-if="!attendanceLoading" class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-green-50 dark:bg-green-900/25 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400">
          {{ attendanceTotal }} visit{{ attendanceTotal !== 1 ? 's' : '' }}
        </span>
      </div>
    </div>

    <div v-if="attendanceLoading" class="px-5 py-10 text-center text-sm text-secondary-400">
      Loading...
    </div>

    <div v-else class="p-4 sm:p-5">
      <!-- Legend -->
      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-4">
        <span class="flex items-center gap-1.5 text-xs text-secondary-500 dark:text-secondary-400">
          <span class="w-4 h-4 rounded-full bg-green-500 inline-block shrink-0" />
          Attended
        </span>
        <span class="flex items-center gap-1.5 text-xs text-secondary-500 dark:text-secondary-400">
          <span class="w-4 h-4 rounded-full bg-violet-500 inline-block shrink-0" />
          Joined
        </span>
        <span class="flex items-center gap-1.5 text-xs text-secondary-500 dark:text-secondary-400">
          <span class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/30 ring-1 ring-blue-400 dark:ring-blue-500 inline-block shrink-0" />
          Payment date
        </span>
        <span class="flex items-center gap-1.5 text-xs text-secondary-500 dark:text-secondary-400">
          <span class="w-4 h-4 rounded bg-teal-100 dark:bg-teal-900/30 inline-block shrink-0" />
          Valid period
        </span>
      </div>

      <!-- 12-month grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <div
          v-for="month in attendanceCalendar"
          :key="month.index"
          class="rounded-xl border border-secondary-100 dark:border-secondary-800 overflow-visible"
        >
          <div class="px-2 py-1.5 bg-secondary-50 dark:bg-secondary-800/60 border-b border-secondary-100 dark:border-secondary-800 flex items-center justify-between">
            <span class="text-[11px] font-semibold text-secondary-600 dark:text-secondary-300">{{ month.label }}</span>
            <span v-if="month.count > 0" class="text-[10px] font-bold text-green-600 dark:text-green-400">{{ month.count }}d</span>
          </div>
          <div class="grid grid-cols-7 px-1 pt-1">
            <span
              v-for="dow in ['S','M','T','W','T','F','S']"
              :key="dow"
              class="text-center text-[9px] font-semibold text-secondary-400 dark:text-secondary-600 pb-0.5"
            >{{ dow }}</span>
          </div>
          <div class="grid grid-cols-7 gap-y-0.5 px-1 pb-1.5">
            <div
              v-for="(cell, ci) in month.cells"
              :key="ci"
              class="group/cell relative z-0 flex items-center justify-center rounded-sm hover:z-1000"
              style="aspect-ratio:1"
              :class="cell?.isValidPeriod ? 'bg-teal-50 dark:bg-teal-900/25' : ''"
            >
              <template v-if="cell">
                <span
                  class="w-full h-full flex items-center justify-center rounded-full text-[10px] font-semibold leading-none transition-colors"
                  :class="cell.attended
                    ? (cell.isPaymentDate
                      ? 'bg-green-500 text-white ring-2 ring-blue-400 dark:ring-blue-500'
                      : 'bg-green-500 text-white')
                    : cell.isJoined
                      ? 'bg-violet-500 text-white'
                      : cell.isPaymentDate
                        ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-1 ring-blue-400 dark:ring-blue-500'
                        : cell.today
                          ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-400 dark:ring-primary-600'
                          : 'text-secondary-600 dark:text-secondary-400'"
                  :title="cell.title"
                >{{ cell.day }}</span>

                <div
                  v-if="cell.biometricAccessEventId"
                  class="absolute left-1/2 top-full z-[120] mt-1 hidden w-56 -translate-x-1/2 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-2 text-[11px] text-secondary-700 dark:text-secondary-200 shadow-xl group-hover/cell:block"
                >
                  <p class="font-semibold text-secondary-900 dark:text-white">
                    Biometric record #{{ cell.biometricAccessEventId }}
                  </p>
                  <p v-if="cell.biometricTimeText" class="mt-0.5">
                    Actual time: {{ cell.biometricTimeText }}
                  </p>
                  <img
                    v-if="cell.biometricPictureUrl"
                    :src="cell.biometricPictureUrl"
                    alt="Biometric event photo"
                    class="mt-1.5 h-24 w-full rounded-md object-cover border border-secondary-200 dark:border-secondary-700"
                    loading="lazy"
                  />
                  <p class="mt-0.5">
                    {{ cell.biometricHasPicture ? 'Picture available' : 'No picture' }}
                  </p>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <div v-if="attendanceTotal === 0" class="mt-4 py-6 text-center">
        <p class="text-sm text-secondary-500 dark:text-secondary-400">
          No attendance recorded for {{ attendanceYear }}.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { apiRequest } from '../../composables/useApiClient';

const props = defineProps({
    memberId: { type: [Number, String], required: true },
    joinedDate: { type: String, default: null },
});

const currentYear = new Date().getFullYear();
const attendanceYear = ref(currentYear);
const attendanceLoading = ref(false);
const attendanceRecords = ref([]);
const attendanceTotal = ref(0);
const calendarPayments = ref([]);

const attendanceCalendar = computed(() => {
  const attendanceByDate = new Map(
    attendanceRecords.value.map(r => [String(r.attended_date).slice(0, 10), r])
  );
  const attendedSet = new Set(attendanceByDate.keys());
    const todayStr = (() => {
        const n = new Date();
        return `${n.getFullYear()}-${String(n.getMonth() + 1).padStart(2, '0')}-${String(n.getDate()).padStart(2, '0')}`;
    })();

    const joinedDateStr = props.joinedDate ? String(props.joinedDate).slice(0, 10) : null;

    const paymentDateSet = new Set(
        calendarPayments.value.map(p => String(p.payment_date).slice(0, 10))
    );

    const validRanges = calendarPayments.value
        .filter(p => p.start_date && p.end_date)
        .map(p => ({
            start: String(p.start_date).slice(0, 10),
            end: String(p.end_date).slice(0, 10),
        }));

    return Array.from({ length: 12 }, (_, m) => {
        const firstDay = new Date(attendanceYear.value, m, 1);
        const daysInMonth = new Date(attendanceYear.value, m + 1, 0).getDate();
        const startDow = firstDay.getDay();
        const cells = [];
        for (let i = 0; i < startDow; i++) cells.push(null);
        let count = 0;
        for (let d = 1; d <= daysInMonth; d++) {
            const ds = `${attendanceYear.value}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
          const attendanceInfo = attendanceByDate.get(ds) || null;
            const attended = attendedSet.has(ds);
            if (attended) count++;
            const isJoined = ds === joinedDateStr;
            const isPaymentDate = paymentDateSet.has(ds);
            const isValidPeriod = validRanges.some(r => ds >= r.start && ds <= r.end);
          const biometricEventId = attendanceInfo?.biometric_access_event_id || null;
          const biometricTimeRaw = attendanceInfo?.biometric_access_event_time || null;
          const biometricHasPicture = Boolean(attendanceInfo?.biometric_access_event_has_picture);
          const biometricPictureUrl = attendanceInfo?.biometric_access_event_picture_url || null;
          let biometricTimeText = null;

          if (biometricTimeRaw) {
            const parsed = new Date(biometricTimeRaw);

            if (!Number.isNaN(parsed.getTime())) {
              biometricTimeText = parsed.toLocaleTimeString(undefined, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
              });
            }
          }

            const titleParts = [
                attended && 'Attended',
                isJoined && 'Joined',
                isPaymentDate && 'Payment',
                isValidPeriod && 'Valid period',
            biometricEventId && `Biometric record #${biometricEventId}`,
            biometricTimeText && `Actual time: ${biometricTimeText}`,
            biometricEventId && (biometricHasPicture ? 'Picture available' : 'No picture'),
            ].filter(Boolean);
              cells.push({
                day: d,
                dateStr: ds,
                attended,
                today: ds === todayStr,
                isJoined,
                isPaymentDate,
                isValidPeriod,
                title: titleParts.join(' · '),
                biometricAccessEventId: biometricEventId,
                biometricTimeText,
                biometricHasPicture,
                biometricPictureUrl,
              });
        }
        return {
            index: m,
            label: firstDay.toLocaleDateString(undefined, { month: 'long' }),
            cells,
            count,
        };
    });
});

async function loadAttendance() {
    attendanceLoading.value = true;
    try {
        const promises = [
          apiRequest(`/api/members/${props.memberId}/attendance?year=${attendanceYear.value}&include_picture_urls=1`),
        ];
        if (calendarPayments.value.length === 0) {
            promises.push(apiRequest(`/api/members/${props.memberId}/payments?per_page=500`));
        }
        const [attendanceRes, paymentsRes] = await Promise.all(promises);
        attendanceRecords.value = attendanceRes.data || [];
        attendanceTotal.value = attendanceRes.total ?? 0;
        if (paymentsRes) {
            calendarPayments.value = paymentsRes.data || [];
        }
    } catch { /* ignore */ } finally {
        attendanceLoading.value = false;
    }
}

function changeAttendanceYear(delta) {
    attendanceYear.value += delta;
    loadAttendance();
}

defineExpose({ loadAttendance });
</script>
