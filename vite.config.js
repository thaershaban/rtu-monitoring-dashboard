import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // تأكد من وجود هذا الاستيراد

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', // تأكد أن هذا المسار صحيح إذا كان لديك ملف CSS
                'resources/js/app.js'
            ],
            refresh: true, // مهم لـ HMR (Hot Module Replacement)
        }),
        vue({ // هذا ضروري لمعالجة ملفات .vue
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    // إعدادات إضافية قد تكون مفيدة أحياناً للتعامل مع IPv6 أو المنفذ
    server: {
        host: '0.0.0.0', // يسمح بالوصول من أي عنوان IP
        port: 5173,      // تأكد أن هذا هو المنفذ الذي يظهر في npm run dev
        hmr: {
            host: 'localhost', // استخدم localhost لتجنب مشاكل HMR مع IPv6 (::1)
        },
    },
    // ** الحل المقترح لخطأ "require is not defined" **
    // هذا يخبر Vite بتحسين (pre-bundle) مكتبات معينة تستخدم CommonJS
    optimizeDeps: {
        include: [
            'vue', // Vue نفسها
            'vue-i18n', // مكتبة الترجمة التي تستخدمها
            // 'lucide-vue-next', // إذا كانت Lucide تسبب مشكلة (أقل شيوعاً)
            // أضف هنا أي مكتبات أخرى تسبب لك هذا الخطأ (مثل 'some-other-commonjs-library')
        ],
    },
});
