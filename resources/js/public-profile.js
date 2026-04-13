import { createApp } from 'vue';
import PublicProfileApp from './spa/pages/PublicProfilePage.vue';
import router from './spa/router/publicProfile.js';

const el = document.getElementById('public-profile-app');
if (el) {
    createApp(PublicProfileApp).use(router).mount(el);
}
