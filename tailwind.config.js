/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class', // <--- أضف هذا السطر لتمكين الوضع الداكن بناءً على وجود فئة 'dark'
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: { // <--- أضف هذا الجزء لتعريف الخط
        sans: ['Inter', 'sans-serif'],
      },
      colors: { // <--- يمكنك إضافة ألوان مخصصة هنا إذا أردت
        // على سبيل المثال:
        // primary: '#3490dc', 
        // secondary: '#6cb2eb',
        // darkBackground: '#1a202c', // لون خلفية داكن مخصص
        // darkCard: '#2d3748',      // لون بطاقات داكن مخصص
      }
    },
  },
  plugins: [],
}