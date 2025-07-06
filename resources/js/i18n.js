// resources/js/i18n.js

import { createI18n } from 'vue-i18n';

// استيراد ملفات اللغات
import en from './locales/en.json';
import ar from './locales/ar.json';

const messages = {
  en,
  ar
};

// تحديد اللغة الافتراضية من localStorage أو الإنجليزية
const savedLocale = localStorage.getItem('locale');
const defaultLocale = savedLocale || 'en';

// إنشاء نسخة i18n
export const i18n = createI18n({
  locale: defaultLocale, // تعيين اللغة الأولية من localStorage أو الافتراضية
  fallbackLocale: 'en',
  messages,
  legacy: false, // مهم جداً: استخدم الوضع "composition API" إذا كنت تستخدم Vue 3
  globalInjection: true, // يتيح لك استخدام $t و $i18n في القوالب دون استيراد صريح
});