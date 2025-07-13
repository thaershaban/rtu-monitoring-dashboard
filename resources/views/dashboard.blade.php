<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RTU Monitoring Dashboard</title>

    <!-- Vite CSS assets -->
    <!-- هذا يضمن تحميل ملفات CSS المترجمة لتطبيق Vue.js الخاص بك في رأس الصفحة -->
    @vite(['resources/css/app.css'])

    <style>
        /* أنماط أساسية للجسم والتطبيق */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f7f6; /* لون خلفية خفيف */
            color: #333; /* لون نص داكن */
        }
        #app {
            padding: 20px; /* مسافة داخلية لعنصر التطبيق الرئيسي */
        }
    </style>
</head>
<body>
    <!-- هذا هو العنصر الذي سيقوم تطبيق Vue.js بالتحميل فيه. -->
    <!-- من المهم أن يكون هذا العنصر موجوداً قبل استدعاء ملف JavaScript الذي يقوم بالـ mount. -->
    <div id="app"></div>

    <!-- Vite JS assets -->
    <!-- نقوم الآن بوضع استدعاء ملف JavaScript هنا (في نهاية <body>) -->
    <!-- للتأكد من أن عنصر <div id="app"> قد تم تحميله وتوفيره في DOM -->
    <!-- قبل أن يحاول Vue.js تركيبه، مما يمنع مشاكل التركيب. -->
    @vite(['resources/js/app.js'])
</body>
</html>
