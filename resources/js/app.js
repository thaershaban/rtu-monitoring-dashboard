// resources/js/app.js

import './bootstrap'; // هذا السطر يستورد ملف Bootstrap.js إذا كنت تستخدمه.
                   // لا تقم بتعديله إذا كان موجوداً ويعمل لديك.

import { createApp } from 'vue'; // استيراد دالة createApp من مكتبة Vue 3
import App from './App.vue';     // استيراد المكون الرئيسي لتطبيقك

// *** هذا هو التعديل الرئيسي: استيراد كائن i18n الذي قمت بتهيئته في ملف i18n.js ***
import { i18n } from './i18n'; // تأكد أن المسار صحيح (المسار النسبي لملف i18n.js من app.js)

// إنشاء تطبيق Vue باستخدام المكون الرئيسي App
const app = createApp(App);

// *** استخدام إضافة i18n مع تطبيق Vue ***
app.use(i18n);

// ربط تطبيق Vue بعنصر DOM الذي يحمل ID 'app' في ملف Blade أو HTML الخاص بك
app.mount('#app');