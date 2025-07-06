import { createApp } from 'vue';
import App from './App.vue';
import { createI18n } from 'vue-i18n'; // <-- تأكد من هذا الاستيراد

// رسائل الترجمة
const messages = {
    en: {
        'RTU Monitoring Dashboard': 'RTU Monitoring Dashboard',
        'Live Status': 'Live Status',
        'Overall Statistics': 'Overall Statistics',
        'Export to Excel': 'Export to Excel',
        'Print': 'Print',
        'Welcome to RTU Archive': 'Welcome to RTU Archive',
        'Loading data, please wait.': 'Loading data, please wait.',
        'Loading live data...': 'Loading live data...',
        'No live data available for stations.': 'No live data available for stations.',
        'RTU Number': 'RTU Number',
        'Station Name': 'Station Name',
        'RTU Status': 'RTU Status',
        'Connection Status': 'Connection Status',
        'Percentage Day': 'Percentage Day',
        'Total Stations': 'Total Stations',
        'Connected Stations': 'Connected Stations',
        'Disconnected Stations': 'Disconnected Stations',
        'Status Distribution': 'Status Distribution',
        'Average Daily Operation Percentage': 'Average Daily Operation Percentage',
        'Top 5 Performing Stations': 'Top 5 Performing Stations',
        'No data available for top performing stations.': 'No data available for top performing stations.',
        'Bottom Performing Stations': 'Bottom Performing Stations',
        'No data available for bottom performing stations.': 'No data available for bottom performing stations.',
        'Operations and Control Department': 'Operations and Control Department',
        'Normal': 'Normal',
        'Failed': 'Failed',
        'Marginal': 'Marginal',
        'Alarm': 'Alarm',
        'OffNoData': 'OffNoData',
        'On': 'On',
        'Unknown': 'Unknown',
        // أضف هنا أي نصوص أخرى تحتاج إلى ترجمة
    },
    ar: {
        'RTU Monitoring Dashboard': 'لوحة مراقبة RTU',
        'Live Status': 'الحالة المباشرة',
        'Overall Statistics': 'الإحصائيات الكلية',
        'Export to Excel': 'تصدير إلى Excel',
        'Print': 'طباعة',
        'Welcome to RTU Archive': 'مرحبًا بك في أرشيف RTU',
        'Loading data, please wait.': 'جاري تحميل البيانات، يرجى الانتظار.',
        'Loading live data...': 'جاري تحميل البيانات المباشرة...',
        'No live data available for stations.': 'لا توجد بيانات حية متاحة للمحطات.',
        'RTU Number': 'رقم RTU',
        'Station Name': 'اسم المحطة',
        'RTU Status': 'حالة RTU',
        'Connection Status': 'حالة الاتصال',
        'Percentage Day': 'النسبة المئوية للتشغيل اليومي',
        'Total Stations': 'إجمالي المحطات',
        'Connected Stations': 'المحطات المتصلة',
        'Disconnected Stations': 'المحطات غير المتصلة',
        'Status Distribution': 'توزيع الحالات',
        'Average Daily Operation Percentage': 'متوسط نسبة التشغيل اليومي',
        'Top 5 Performing Stations': 'أفضل 5 محطات أداءً',
        'No data available for top performing stations.': 'لا توجد بيانات متاحة لأفضل المحطات أداءً.',
        'Bottom Performing Stations': 'أسوأ المحطات أداءً',
        'No data available for bottom performing stations.': 'لا توجد بيانات متاحة لأسوأ المحطات أداءً.',
        'Operations and Control Department': 'دائرة العمليات والتحكم',
        'Normal': 'عادي',
        'Failed': 'فشل',
        'Marginal': 'هامشي',
        'Alarm': 'إنذار',
        'OffNoData': 'خارج الخدمة / لا توجد بيانات',
        'On': 'متصل',
        'Unknown': 'غير معروف',
        // أضف هنا ترجمات الأسماء العربية للمحطات إذا لم تكن موجودة في البيانات
        // الأمثلة:
        '4NAJF': 'نجف 400 ك.ف',
        'SLDP': 'محطة كهرباء الصدر',
        // ... والمزيد
    }
};

// تحديد اللغة الافتراضية من التخزين المحلي أو 'en'
const savedLocale = localStorage.getItem('locale') || 'en';

const i18n = createI18n({
    legacy: false, // <-- تأكد من وجود هذه الخاصية
    locale: savedLocale,
    fallbackLocale: 'en',
    messages,
    globalInjection: true, // <-- تأكد من وجود هذه الخاصية
});

const app = createApp(App);
app.use(i18n); // <-- تأكد من حقن i18n في التطبيق
app.mount('#app');

// تعيين سمة اللغة والاتجاه للجذر HTML
document.documentElement.setAttribute('lang', savedLocale);
document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr');