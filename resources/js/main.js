import { createApp } from 'vue';
import App from './App.vue';
import { createI18n } from 'vue-i18n';

import enMessages from '../lang/en/en.js';
import arMessages from '../lang/ar/ar.js';

const i18n = createI18n({
  locale: 'en',  // اللغة الافتراضية
  fallbackLocale: 'en',
  messages: {
    en: enMessages,
    ar: arMessages,
  },
});

const app = createApp(App);

app.use(i18n);

app.mount('#app');
