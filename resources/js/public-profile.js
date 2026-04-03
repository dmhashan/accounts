import { createApp } from 'vue';
import PublicProfileApp from './spa/components/PublicProfileApp.vue';

const el = document.getElementById('public-profile-app');
if (el) {
    createApp(PublicProfileApp, {
        workoutsData: window.__profileWorkouts || [],
        salesData: window.__profileSales || [],
        meta: window.__profileMeta || {},
    }).mount(el);
}
