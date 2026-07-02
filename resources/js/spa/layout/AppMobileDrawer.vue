<template>
  <div>
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 bg-slate-950/65 backdrop-blur-sm z-40 lg:hidden" @click="$emit('close')" />
    </Transition>

    <aside
      class="app-surface app-sidebar-panel fixed inset-y-0 left-0 z-50 w-80 max-w-[92vw] border-r-0 transform transition-transform duration-300 lg:hidden flex flex-col"
      :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Header -->
      <div class="h-16 px-4 border-b border-secondary-200/70 dark:border-secondary-700/70 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3 min-w-0">
          <div v-if="context.tenant?.logo_url" class="app-logo-tile w-8 h-8 rounded-lg overflow-hidden shrink-0">
            <img :src="context.tenant.logo_url" :alt="context.tenant.name" class="w-full h-full object-contain" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-[0.12em]" style="color: var(--text-muted)">
              Navigation
            </p>
            <h2 class="text-lg font-bold truncate" style="color: var(--text-strong)">
              {{ context.tenant?.name || 'Tenant App' }}
            </h2>
          </div>
        </div>
        <button
          type="button"
          class="p-2 rounded-xl transition-colors hover:bg-secondary-100 dark:hover:bg-secondary-700/50"
          style="color: var(--text-muted)"
          @click="$emit('close')"
        >
          <X class="h-5 w-5" :stroke-width="2" />
        </button>
      </div>

      <!-- Nav -->
      <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-3 space-y-0.5">
        <template v-for="item in menuItems" :key="item.path">
          <!-- Parent nav item -->
          <RouterLink
            :to="item.children?.length ? item.children[0].path : item.path"
            class="app-nav-link"
            :class="isActive(item.path) ? 'app-nav-link-active' : ''"
            @click="!item.children?.length && $emit('close')"
          >
            <component :is="item.icon" class="h-[18px] w-[18px] flex-shrink-0" :stroke-width="isActive(item.path) ? 2.25 : 2" />
            <span class="truncate flex-1">{{ item.label }}</span>
            <ChevronDown
              v-if="item.children?.length"
              class="h-3.5 w-3.5 flex-shrink-0 transition-transform duration-200"
              :class="isActive(item.path) ? 'rotate-180' : ''"
              :stroke-width="2"
            />
          </RouterLink>

          <!-- Children submenu -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="item.children?.length && isActive(item.path)" class="ml-6 mt-0.5 mb-1 space-y-0.5 border-l-2 border-secondary-200 dark:border-secondary-700 pl-3">
              <RouterLink
                v-for="child in item.children"
                :key="child.path"
                :to="child.path"
                class="w-full flex items-center rounded-xl px-3 py-2 text-sm font-medium transition-colors truncate"
                :class="isChildActive(child) ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-semibold' : 'text-secondary-600 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700/50'"
                @click="$emit('close')"
              >
                {{ child.label }}
              </RouterLink>
            </div>
          </Transition>
        </template>
      </nav>

      <!-- Footer -->
      <div class="p-3 border-t border-secondary-200/70 dark:border-secondary-700/70 shrink-0 [padding-bottom:calc(0.75rem+env(safe-area-inset-bottom))]">
        <!-- Expanded options panel -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <div v-if="profileOpen" class="mb-2 rounded-2xl overflow-hidden border border-secondary-200/70 dark:border-secondary-700/70">
            <button class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors" @click="toggleTheme">
              <Sun v-if="isDark" class="h-4 w-4 text-amber-500" :stroke-width="2" />
              <Moon v-else class="h-4 w-4 text-indigo-500" :stroke-width="2" />
              {{ isDark ? 'Switch to Light' : 'Switch to Dark' }}
            </button>
            <div class="border-t border-secondary-200/50 dark:border-secondary-700/50" />
            <form :action="context.legacyUrls?.logout" method="POST">
              <input type="hidden" name="_token" :value="csrfToken" />
              <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <LogOut class="h-4 w-4" :stroke-width="2" />
                Sign out
              </button>
            </form>
          </div>
        </Transition>

        <!-- Profile button -->
        <button class="w-full flex items-center gap-3 p-3 rounded-2xl hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors text-left" @click="profileOpen = !profileOpen">
          <MemberAvatar :initials="initials" size="sm" class="flex-shrink-0" />
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold truncate" style="color: var(--text-strong)">
              {{ context.user?.name }}
            </p>
            <p class="text-xs truncate" style="color: var(--text-muted)">
              {{ context.user?.email }}
            </p>
          </div>
          <ChevronDown
            class="h-4 w-4 flex-shrink-0 transition-transform duration-200"
            :class="profileOpen ? 'rotate-180' : ''"
            style="color: var(--text-muted)"
            :stroke-width="2"
          />
        </button>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { X, Sun, Moon, LogOut, ChevronDown } from 'lucide-vue-next';
import { useNavigation } from '../composables/useNavigation';
import { useTheme } from '../composables/useTheme';
import MemberAvatar from '../../components/ui/MemberAvatar.vue';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const route = useRoute();
const { context, menuItems } = useNavigation();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const { isDark, toggleTheme } = useTheme();
const profileOpen = ref(false);

const initials = computed(() => {
    const name = context.user?.name || '';
    return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() || '').join('') || '?';
});

function isChildActive(child) {
    return route.path === child.path || (child.activePrefix && route.path.startsWith(child.activePrefix + '/'));
}

function isActive(path) {
    if (path === '/settings') {
        return route.path.startsWith('/settings') || route.path.startsWith('/users') || route.path.startsWith('/roles');
    }
    if (path === '/reports') {
        return route.path === '/reports' || route.path.startsWith('/reports/') || route.path === '/stats';
    }
    return route.path === path || route.path.startsWith(path + '/');
}
</script>
