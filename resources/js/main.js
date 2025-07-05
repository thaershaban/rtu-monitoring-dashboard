import { createApp } from 'vue';
import App from './App.vue';
import { i18n } from './App.vue'; // استيراد i18n من App.vue أو مكان مستقل لاحقًا

const app = createApp(App);
app.use(i18n);
app.mount('#app');
