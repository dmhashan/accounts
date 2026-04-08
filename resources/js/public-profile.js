import { createApp } from 'vue';
import PublicProfileApp from './spa/pages/PublicProfilePage.vue';

const el = document.getElementById('public-profile-app');
if (el) {
    createApp(PublicProfileApp).mount(el);
}
