import { createApp } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import App from './App.vue';
import './style.css';

import Mining from './views/Mining.vue';
import Pools from './views/Pools.vue';
import AboutKoto from './views/AboutKoto.vue';
import Guide from './views/Guide.vue';
import Faucet from './views/Faucet.vue';
import Games from './views/Games.vue';

const routes = [
  { path: '/', name: 'mining', component: Mining },
  { path: '/pools', name: 'pools', component: Pools },
  { path: '/about', name: 'about', component: AboutKoto },
  { path: '/guide', name: 'guide', component: Guide },
  { path: '/faucet', name: 'faucet', component: Faucet },
  { path: '/games', name: 'games', component: Games },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

createApp(App).use(router).mount('#app');
