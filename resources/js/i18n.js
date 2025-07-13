import { createI18n } from 'vue-i18n';

const messages = {
  en: {
    "RTU Monitoring Dashboard": "RTU Monitoring Dashboard",
    "Live Status": "Live Status",
    "Overall Statistics": "Overall Statistics",
    "Welcome to RTU Archive": "Welcome to RTU Archive",
    "Loading data, please wait.": "Loading data, please wait.",
    "Live RTU Status": "Live RTU Status",
    "Export to Excel": "Export to Excel",
    "Print": "Print",
    "Loading live data...": "Loading live data...",
    "No live data available for stations.": "No live data available for stations.",
    "RTU Number": "RTU Number",
    "Arabic Name": "Arabic Name",
    "English Name": "English Name",
    "RTU Status": "RTU Status",
    "Percentage Day": "Percentage Day",
    "Normal": "Normal",
    "Failed": "Failed",
    "Marginal": "Marginal",
    "Alarm": "Alarm",
    "OffNoData": "Off (No Data)",
    "Unknown": "Unknown",
    "Total Stations": "Total Stations",
    "Connected Stations": "Connected Stations",
    "Disconnected Stations": "Disconnected Stations",
    "Status Distribution": "Status Distribution",
    "Average Daily Operation Percentage": "Average Daily Operation Percentage",
    "Top Performing Stations": "Top Performing Stations",
    "No data available for top performing stations.": "No data available for top performing stations.",
    "Bottom Performing Stations": "Bottom Performing Stations",
    "No data available for bottom performing stations.": "No data available for bottom performing stations.",
    "Operations and Control Department": "Operations and Control Department",
    "Failed to export Excel file. Please try again.": "Failed to export Excel file. Please try again.",
    "Average": "Average",
    "No overall statistics available.": "No overall statistics available."
  },
  ar: {
    "RTU Monitoring Dashboard": "لوحة تحكم مراقبة وحدات RTU",
    "Live Status": "الحالة اللحظية",
    "Overall Statistics": "الإحصائيات الشاملة",
    "Welcome to RTU Archive": "مرحباً بك في أرشيف RTU",
    "Loading data, please wait.": "جارٍ تحميل البيانات، يرجى الانتظار.",
    "Live RTU Status": "الحالة اللحظية لوحدات RTU",
    "Export to Excel": "تصدير إلى Excel",
    "Print": "طباعة",
    "Loading live data...": "جارٍ تحميل البيانات اللحظية...",
    "No live data available for stations.": "لا توجد بيانات لحظية للمحطات.",
    "RTU Number": "رقم RTU",
    "Arabic Name": "الاسم العربي",
    "English Name": "الاسم الإنجليزي",
    "RTU Status": "حالة RTU",
    "Percentage Day": "النسبة المئوية اليومية",
    "Normal": "عادي",
    "Failed": "فشل",
    "Marginal": "متوسط",
    "Alarm": "إنذار",
    "OffNoData": "متوقف (لا توجد بيانات)",
    "Unknown": "غير معروف",
    "Total Stations": "إجمالي المحطات",
    "Connected Stations": "المحطات المتصلة",
    "Disconnected Stations": "المحطات غير المتصلة",
    "Status Distribution": "توزيع الحالات",
    "Average Daily Operation Percentage": "متوسط نسبة التشغيل اليومية",
    "Top Performing Stations": "أفضل المحطات أداءً",
    "No data available for top performing stations.": "لا توجد بيانات لأفضل المحطات أداءً.",
    "Bottom Performing Stations": "أقل المحطات أداءً",
    "No data available for bottom performing stations.": "لا توجد بيانات لأقل المحطات أداءً.",
    "Operations and Control Department": "قسم العمليات والتحكم",
    "Failed to export Excel file. Please try again.": "فشل تصدير ملف Excel. يرجى المحاولة مرة أخرى.",
    "Average": "المتوسط",
    "No overall statistics available.": "لا توجد إحصائيات شاملة متاحة."
  }
};

export function setupI18n() {
  const savedLocale = localStorage.getItem('locale') || 'en';
  
  const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'en',
    messages,
    globalInjection: true
  });

  // تعيين لغة HTML أولية
  document.documentElement.setAttribute('lang', savedLocale);
  document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr');

  return i18n;
}