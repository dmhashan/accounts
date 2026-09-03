<template>
  <div class="space-y-5 pb-6">
    <!-- Header -->
    <div class="pt-2 pb-1">
      <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
        Calendar &amp; Schedule
      </h1>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
        Gym check-in history, workout days &amp; upcoming club events
      </p>
    </div>

    <!-- ── MONTHLY CALENDAR CARD ────────────────────────────── -->
    <div class="pp-glass-card rounded-3xl p-4 sm:p-5 shadow-sm space-y-4">
      <!-- Calendar Month Header -->
      <div class="flex items-center justify-between">
        <h2 class="text-base font-extrabold text-gray-900 dark:text-white tracking-tight">
          {{ monthYearTitle }}
        </h2>

        <div class="flex items-center gap-1">
          <button
            type="button"
            class="p-1.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors"
            @click="prevMonth"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>
          <button
            type="button"
            class="px-2.5 py-1 rounded-xl bg-gray-100 dark:bg-zinc-800 text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors"
            @click="goToToday"
          >
            Today
          </button>
          <button
            type="button"
            class="p-1.5 rounded-xl bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors"
            @click="nextMonth"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Day Names Bar -->
      <div class="grid grid-cols-7 text-center">
        <span v-for="d in dayNames" :key="d" class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-500 py-1">
          {{ d }}
        </span>
      </div>

      <!-- Calendar Days Grid -->
      <div class="grid grid-cols-7 gap-1">
        <button
          v-for="(day, idx) in calendarDays"
          :key="idx"
          type="button"
          :disabled="!day.inMonth"
          class="relative aspect-square rounded-2xl p-1 flex flex-col items-center justify-between transition-all focus:outline-none cursor-pointer"
          :class="[
            !day.inMonth ? 'opacity-20 cursor-default' : '',
            day.isSelected ? 'bg-red-500 text-white font-extrabold shadow-md shadow-red-500/20' : (day.isToday ? 'bg-red-500/10 text-red-600 dark:text-red-400 font-bold border border-red-500/30' : 'hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-800 dark:text-gray-200 font-medium')
          ]"
          @click="selectDate(day)"
        >
          <span class="text-xs mt-0.5">{{ day.dateNumber }}</span>

          <!-- Indicator Dots -->
          <div v-if="day.inMonth" class="flex items-center gap-0.5 mb-1">
            <span v-if="day.hasAttendance" class="w-1.5 h-1.5 rounded-full bg-emerald-400" title="Attendance" />
            <span v-if="day.hasWorkout" class="w-1.5 h-1.5 rounded-full bg-red-400" title="Workout" />
            <span v-if="day.hasEvent" class="w-1.5 h-1.5 rounded-full bg-blue-400" title="Event" />
          </div>
        </button>
      </div>

      <!-- Legend -->
      <div class="flex items-center justify-center gap-4 pt-2 border-t border-gray-100 dark:border-zinc-800/80 text-[10px] font-bold text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-500" />
          <span>Gym Attendance</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-red-500" />
          <span>Workout Day</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-blue-500" />
          <span>Events</span>
        </div>
      </div>
    </div>

    <!-- ── SELECTED DATE DETAILS & FEED ────────────────────── -->
    <section class="space-y-3">
      <div class="flex items-center justify-between px-1">
        <h2 class="text-sm font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
          Activity for {{ formattedSelectedDate }}
        </h2>
        <span v-if="selectedDayActivities.length" class="text-xs font-semibold text-gray-400">
          {{ selectedDayActivities.length }} event{{ selectedDayActivities.length === 1 ? '' : 's' }}
        </span>
      </div>

      <!-- Activity Items -->
      <div v-if="selectedDayActivities.length" class="pp-glass-card rounded-3xl overflow-hidden divide-y divide-gray-100 dark:divide-zinc-800/60 shadow-sm">
        <div
          v-for="act in selectedDayActivities"
          :key="act.id"
          class="p-4 flex items-center gap-3.5 hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors"
        >
          <div
            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-sm"
            :class="act.bgClass"
          >
            <component :is="act.icon" class="w-5 h-5" :stroke-width="2" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                {{ act.title }}
              </p>
              <span v-if="act.time" class="text-[11px] font-mono font-semibold text-gray-400 shrink-0">
                {{ act.time }}
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
              {{ act.subtitle }}
            </p>
          </div>
        </div>
      </div>

      <div v-else class="pp-glass-card rounded-3xl p-8 flex flex-col items-center justify-center text-center gap-2 text-gray-400">
        <CalendarIcon class="w-8 h-8 text-gray-300 dark:text-zinc-600" :stroke-width="1.5" />
        <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
          No events scheduled for this day
        </p>
        <p class="text-xs text-gray-400">
          Select another date on the calendar to view past check-ins or workouts.
        </p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    ChevronLeft,
    ChevronRight,
    Calendar as CalendarIcon,
    CheckCircle2,
    Zap,
} from 'lucide-vue-next';

const props = defineProps({
    meta:         { type: Object, default: () => ({}) },
    attendances:  { type: Array,  default: () => [] },
    workoutsData: { type: Array,  default: () => [] },
});

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const currentDate  = ref(new Date());
const selectedDate = ref(new Date().toISOString().split('T')[0]);

const currentYear  = computed(() => currentDate.value.getFullYear());
const currentMonth = computed(() => currentDate.value.getMonth());

const monthYearTitle = computed(() => {
    return currentDate.value.toLocaleString('default', { month: 'long', year: 'numeric' });
});

const formattedSelectedDate = computed(() => {
    const d = new Date(selectedDate.value + 'T00:00:00');
    return d.toLocaleDateString('default', { month: 'short', day: 'numeric', year: 'numeric' });
});

function prevMonth() {
    currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
}

function nextMonth() {
    currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
}

function goToToday() {
    currentDate.value = new Date();
    selectedDate.value = new Date().toISOString().split('T')[0];
}

// Sets of dates with events
const attendanceDates = computed(() => {
    const set = new Set();
    for (const a of props.attendances) {
        if (a.date) set.add(a.date);
    }
    return set;
});

const workoutDaysSet = computed(() => {
    // Collect active workout days
    const set = new Set();
    for (const w of props.workoutsData) {
        if (w.effective_date) set.add(w.effective_date);
    }
    return set;
});

const calendarDays = computed(() => {
    const year  = currentYear.value;
    const month = currentMonth.value;

    const firstDayIndex = new Date(year, month, 1).getDay();
    const totalDaysInMonth = new Date(year, month + 1, 0).getDate();
    const prevMonthDays = new Date(year, month, 0).getDate();

    const todayStr = new Date().toISOString().split('T')[0];
    const days = [];

    // Prev month padding
    for (let i = firstDayIndex - 1; i >= 0; i--) {
        days.push({
            dateStr: '',
            dateNumber: prevMonthDays - i,
            inMonth: false,
        });
    }

    // Current month
    for (let d = 1; d <= totalDaysInMonth; d++) {
        const dStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            dateStr: dStr,
            dateNumber: d,
            inMonth: true,
            isToday: dStr === todayStr,
            isSelected: dStr === selectedDate.value,
            hasAttendance: attendanceDates.value.has(dStr),
            hasWorkout: workoutDaysSet.value.has(dStr),
            hasEvent: false,
        });
    }

    // Next month padding to fill 42 cells (6 rows)
    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        days.push({
            dateStr: '',
            dateNumber: i,
            inMonth: false,
        });
    }

    return days;
});

function selectDate(day) {
    if (day.inMonth && day.dateStr) {
        selectedDate.value = day.dateStr;
    }
}

const selectedDayActivities = computed(() => {
    const list = [];
    const date = selectedDate.value;

    // Attendance
    const dayAtts = props.attendances.filter(a => a.date === date);
    for (const att of dayAtts) {
        list.push({
            id: `att_${att.id}`,
            title: 'Gym Attendance Check-in',
            subtitle: 'Biometric Access Gate',
            time: att.time || 'Check-in recorded',
            icon: CheckCircle2,
            bgClass: 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400',
        });
    }

    // Workouts
    const dayWorkouts = props.workoutsData.filter(w => w.effective_date === date || (!w.effective_date && props.workoutsData.length > 0));
    for (const w of dayWorkouts) {
        list.push({
            id: `wrk_${w.id}`,
            title: w.title || 'Scheduled Workout Routine',
            subtitle: w.creator_name ? `Assigned by ${w.creator_name}` : 'Personal Program',
            time: 'Scheduled',
            icon: Zap,
            bgClass: 'bg-red-50 dark:bg-red-950/50 text-red-500',
        });
    }

    return list;
});
</script>
