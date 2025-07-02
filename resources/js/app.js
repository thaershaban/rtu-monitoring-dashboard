// هذا السطر يستورد ملف Bootstrap.js إذا كنت تستخدمه.
// إذا لم يكن لديك ملف Bootstrap.js أو لا تستخدمه، يمكنك إزالة هذا السطر أو تركه،
// لكن تأكد من وجود ملف bootstrap.js فارغ على الأقل إذا تركته.
import './bootstrap';

// *** هذا هو السطر الحاسم الذي يحل مشكلة 'Vue is not defined' ***
// استيراد دالة createApp من مكتبة Vue 3. هذه الدالة هي الطريقة الصحيحة لبدء تطبيق Vue.
import { createApp } from 'vue';

// استيراد المكون الرئيسي لتطبيق Vue.js الخاص بك (الواجهة)
import App from './App.vue';

// استيراد مكتبة vue-i18n لإدارة الترجمة
import { createI18n } from 'vue-i18n';

// استيراد ملفات الترجمة (تأكد من وجود هذه الملفات في المسار الصحيح)
import en from './locales/en.json';
import ar from './locales/ar.json';

// تهيئة Vue I18n
const i18n = createI18n({
  locale: 'en', // اللغة الافتراضية عند بدء التطبيق
  fallbackLocale: 'en', // اللغة الاحتياطية إذا لم يتم العثور على ترجمة للغة الحالية
  messages: { // كائنات الرسائل لـ i18n
    en,
    ar,
  },
});

// إنشاء تطبيق Vue باستخدام المكون الرئيسي App (App.vue)
// هذا السطر الآن سيعمل لأن 'createApp' تم استيراده بشكل صحيح.
const app = createApp(App);

// استخدام إضافة i18n مع تطبيق Vue
app.use(i18n);

// ربط تطبيق Vue بعنصر DOM الذي يحمل ID 'app' في ملف Blade (dashboard.blade.php)
app.mount('#app');