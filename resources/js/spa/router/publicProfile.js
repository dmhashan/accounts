import { createRouter, createWebHistory } from 'vue-router';

import HomeView         from '../components/PublicProfileApp/HomeView.vue';
import WorkoutView      from '../components/PublicProfileApp/WorkoutView.vue';
import TransactionsView from '../components/PublicProfileApp/TransactionsView.vue';
import ProfileView      from '../components/PublicProfileApp/ProfileView.vue';
import EventView        from '../components/PublicProfileApp/EventView.vue';
import NotificationsView from '../components/PublicProfileApp/NotificationsView.vue';

const routes = [
    { path: '/',              component: HomeView },
    { path: '/workout',       component: WorkoutView },
    { path: '/transactions',  component: TransactionsView },
    { path: '/profile',       component: ProfileView },
    { path: '/notifications', component: NotificationsView },
    { path: '/event/:slug',   component: EventView, meta: { public: true } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

export default createRouter({
    history: createWebHistory('/profile'),
    routes,
});
