<template>
    <section>
        <FlashAlert v-if="successMessage" :message="successMessage" tone="success" />

        <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl shadow-xl overflow-hidden mb-6">
            <div class="px-6 md:px-8 py-8 md:py-12 text-white">
                <h2 class="text-2xl md:text-3xl font-bold">Welcome back, {{ userName }}! 👋</h2>
                <p class="mt-2 text-primary-100 text-sm md:text-base">You're logged into {{ tenantName }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            <InfoCard
                title="Tenant"
                :value="tenantName"
                :subtitle="`ID: #${tenantId}`"
                icon="tenant"
            />

            <InfoCard
                title="Domain"
                :value="fullDomain"
                :subtitle="`Subdomain: ${tenantDomain}`"
                icon="domain"
            />

            <InfoCard
                title="Account"
                :value="userEmail"
                :subtitle="`Tenant User ID: #${userId}`"
                icon="account"
            />
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import FlashAlert from '../ui/FlashAlert.vue';
import InfoCard from '../ui/InfoCard.vue';

const props = defineProps({
    tenant: {
        type: Object,
        default: () => ({}),
    },
    user: {
        type: Object,
        default: () => ({}),
    },
    appDomain: {
        type: String,
        default: '',
    },
    successMessage: {
        type: String,
        default: '',
    },
});

const tenantName = computed(() => props.tenant.name ?? 'Tenant');
const tenantId = computed(() => props.tenant.id ?? '-');
const tenantDomain = computed(() => props.tenant.domain ?? '-');

const userName = computed(() => props.user.name ?? 'User');
const userId = computed(() => props.user.id ?? '-');
const userEmail = computed(() => props.user.email ?? '-');

const fullDomain = computed(() => {
    if (!props.tenant.domain) {
        return '-';
    }

    return props.appDomain ? `${props.tenant.domain}.${props.appDomain}` : props.tenant.domain;
});
</script>