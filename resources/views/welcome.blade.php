<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" class="light"> {{-- أضف class="light" كقيمة افتراضية --}}
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RTU Archive</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{-- تطبيق Vue.js سيتم تركيبه هنا --}}
    <div id="app"></div>

    {{-- تمرير الترجمات الكاملة واللغة الحالية إلى Vue --}}
    <script>
        // تمرير جميع الترجمات (الإنجليزية والعربية) إلى Vue.js
        window.appTranslations = {
            en: @json(Lang::get('messages', [], 'en')),
            ar: @json(Lang::get('messages', [], 'ar'))
        };
        window.currentLocale = '{{ app()->getLocale() }}';

        // منطق حفظ واستعادة الوضع الداكن من localStorage
        // هذا الكود يجب أن يوضع قبل تحميل تطبيق Vue
        if (
            localStorage.theme === 'dark' || 
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // دالة مساعدة يمكن استخدامها من Vue للتبديل بين الوضعين
        window.toggleDarkMode = function () {
            if (localStorage.theme === 'dark') {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        };
    </script>
</body>
</html>
