import { createRouter, createWebHistory } from 'vue-router';

import HomeView         from '../components/PublicProfileApp/HomeView.vue';
import WorkoutView      from '../components/PublicProfileApp/WorkoutView.vue';
import TransactionsView from '../components/PublicProfileApp/TransactionsView.vue';
import ProfileView      from '../components/PublicProfileApp/ProfileView.vue';

const routes = [
    { path: '/',              component: HomeView },
    { path: '/workout',       component: WorkoutView },
    { path: '/transactions',  component: TransactionsView },
    { path: '/profile',       component: ProfileView },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

export default createRouter({
    history: createWebHistory('/profile'),
    routes,
});
