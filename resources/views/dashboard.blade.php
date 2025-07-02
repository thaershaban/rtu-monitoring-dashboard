<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RTU Monitoring Dashboard</title>

    <!--
        يمكنك إبقاء استدعاء CSS هنا في <head> لأنه لا يسبب مشاكل التوقيت
        وغالباً ما يكون من الأفضل تحميل CSS مبكراً.
    -->
    @vite(['resources/css/app.css'])

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        #app {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div id="app">
        <!--
            هذا هو العنصر الذي سيتم تركيب تطبيق Vue.js فيه.
            من المهم أن يكون هذا العنصر موجوداً قبل استدعاء ملف JavaScript الذي يقوم بالـ mount.
        -->
    </div>

    <!--
        نقوم الآن بوضع استدعاء ملف JavaScript هنا (في نهاية <body>)
        للتأكد من أن عنصر <div id="app"> قد تم تحميله وتوفيره في DOM
        قبل أن يحاول Vue.js تركيبه.
    -->
    @vite(['resources/js/app.js'])
</body>
</html>