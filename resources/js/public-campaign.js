import { createApp } from 'vue';
import PublicCampaignPage from './spa/pages/PublicCampaignPage.vue';

const el = document.getElementById('public-campaign-app');

if (el) {
    createApp(PublicCampaignPage, {
        slug: el.dataset.slug || '',
    }).mount(el);
}
