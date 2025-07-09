<template>
  <div :class="['min-h-screen', theme.background, theme.text, 'print:bg-white']">
    <!-- قسم الرأس: يحتوي على العنوان، تبديل اللغة، وتبديل السمة -->
    <header :class="['py-4', theme.headerBg, 'shadow-md', 'rounded-b-lg', 'sticky', 'top-0', 'z-50', 'mx-auto', 'max-w-7xl', 'px-4', 'sm:px-6', 'lg:px-8', 'print:hidden']">
      <div class="flex flex-col sm:flex-row items-center justify-between"> <!-- لجعل المحتوى يتكدس عمودياً على الموبايل وأفقياً على الأكبر -->
        <h1 :class="['text-2xl', 'sm:text-3xl', 'font-bold', theme.headerText, 'mb-2', 'sm:mb-0']">{{ t('RTU Monitoring Dashboard') }}</h1> <!-- حجم خط متجاوب وهامش سفلي -->
        <div class="flex items-center space-x-4">
          <!-- تبديل اللغة -->
          <select v-model="locale" @change="changeLanguage" :class="['px-3', 'py-2', 'rounded-md', theme.selectBg, theme.selectText, theme.selectBorder, 'focus:outline-none', 'focus:ring-2', theme.focusRing]">
            <option value="en">English</option>
            <option value="ar">العربية</option>
          </select>

          <!-- زر تبديل السمة (الوضع الفاتح/الداكن) -->
          <button @click="toggleTheme" :class="['p-2', 'rounded-full', theme.buttonBg, theme.buttonText, 'hover:brightness-110', 'transition-all', 'duration-300']">
            <template v-if="currentTheme === 'light'">
              <LucideSun :class="['w-6', 'h-6', theme.buttonText]" />
            </template>
            <template v-else>
              <LucideMoon :class="['w-6', 'h-6', theme.buttonText]" />
            </template>
          </button>
        </div>
      </div>
      <!-- التنقل بين التبويبات (الحالة اللحظية والإحصائيات الشاملة) -->
      <nav :class="['mt-4', 'flex', 'flex-col', 'sm:flex-row', 'space-y-2', 'sm:space-y-0', 'sm:space-x-4', 'justify-center', 'p-2', 'rounded-lg', theme.tabBg]"> <!-- تبويبات متجاوبة -->
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200',
                         activeTab === tab.id ? theme.activeTabBg : theme.inactiveTabBg,
                         activeTab === tab.id ? theme.activeTabText : theme.inactiveTabText,
                         'hover:bg-opacity-80', 'w-full', 'sm:w-auto']"> <!-- عرض كامل على الموبايل، تلقائي على الأكبر -->
          {{ t(tab.name) }}
        </button>
      </nav>
    </header>

    <!-- منطقة المحتوى الرئيسية -->
    <main class="container mx-auto px-4 py-8 max-w-7xl">
      <!-- رسالة الترحيب عند عدم تحميل البيانات -->
      <div v-if="!dataLoaded" class="text-center py-10">
        <p :class="['text-xl', 'font-semibold', theme.text]">{{ t('Welcome to RTU Archive') }}...</p>
        <p :class="['text-sm', theme.mutedText]">{{ t('Loading data, please wait.') }}</p>
      </div>

      <!-- محتوى تبويب الحالة اللحظية -->
      <div v-if="activeTab === 'live_status'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl', 'live-status-print-area']">
        <h2 :class="['text-xl', 'sm:text-2xl', 'font-semibold', 'mb-4', theme.headerText, 'print:text-black', 'print:text-xl', 'print:font-bold', 'print:mb-4', 'print-only-title']">{{ t('Live RTU Status') }}</h2> <!-- حجم خط متجاوب -->
        
        <!-- أزرار التصدير والطباعة -->
        <div class="flex flex-col sm:flex-row justify-end mb-4 space-y-2 sm:space-y-0 sm:space-x-2 print:hidden"> <!-- أزرار متجاوبة -->
            <button @click="exportToExcel" 
            :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200', theme.buttonBg, theme.buttonText, 'hover:brightness-110', 'w-full', 'sm:w-auto']"> <!-- عرض كامل على الموبايل، تلقائي على الأكبر -->
            <LucideDownload :class="['inline-block', 'w-5', 'h-5', 'mr-2']" />
            {{ t('Export to Excel') }}
            </button>
            <button @click="printLiveStatus" :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200', theme.buttonBg, theme.buttonText, 'hover:brightness-110', 'w-full', 'sm:w-auto']"> <!-- عرض كامل على الموبايل، تلقائي على الأكبر -->
                <LucidePrinter :class="['inline-block', 'w-5', 'h-5', 'mr-2']" /> {{ t('Print') }}
            </button>
        </div>

        <p v-if="loading" :class="[theme.text, 'print:hidden']">{{ t('Loading live data...') }}</p>
        <p v-else-if="!liveData.length" :class="[theme.text, 'print:text-black', 'print-only-message']">{{ t('No live data available for stations.') }}</p>
        <div v-else class="overflow-x-auto rounded-lg shadow-inner">
          <table :class="['min-w-full', 'divide-y', theme.divideColor]">
            <thead :class="[theme.tableHeaderBg]">
              <tr>
                <!-- حقل RTU Number سيعرض الآن الأرقام التسلسلية (1، 2، 3، ...) -->
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('RTU Number') }}</th> <!-- حشو وحجم خط متجاوب -->
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('Station Name') }}</th> <!-- حشو وحجم خط متجاوب -->
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('RTU Status') }}</th> <!-- حشو وحجم خط متجاوب -->
                <!-- تم حذف عمود "Connection Status" من هنا -->
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('Percentage Day') }}</th> <!-- حشو وحجم خط متجاوب -->
              </tr>
            </thead>
            <tbody :class="['divide-y', theme.divideColor, theme.tableRowBg]">
              <tr v-for="rtu in liveData" :key="rtu.station_id">
                <!-- عرض sequential_rtu_number لحقل RTU Number -->
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap', 'text-xs', 'sm:text-sm', theme.tableCellText]">{{ rtu.sequential_rtu_number }}</td> <!-- حشو وحجم خط متجاوب -->
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap', 'text-xs', 'sm:text-sm', theme.tableCellText]">{{ locale === 'ar' ? rtu.arabic_name : rtu.english_name }}</td> <!-- حشو وحجم خط متجاوب -->
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap']">
                  <span :class="['px-1', 'sm:px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(rtu.rtu_status_color)]"> <!-- حشو متجاوب -->
                    {{ t(rtu.rtu_status_text) }}
                  </span>
                </td>
                <!-- تم حذف خلية "Connection Status" من هنا -->
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap', 'text-xs', 'sm:text-sm', theme.tableCellText]">
                  {{ (rtu.percentage_day || 0).toFixed(1) }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- محتوى تبويب الإحصائيات الشاملة -->
      <div v-if="activeTab === 'overall_stats'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl', 'print:hidden']">
        <h2 :class="['text-xl', 'sm:text-2xl', 'font-semibold', 'mb-4', theme.headerText]">{{ t('Overall Statistics') }}</h2> <!-- حجم خط متجاوب -->
        <p v-if="loading" :class="[theme.text]">{{ t('Loading overall statistics...') }}</p>
        <p v-else-if="!overallStats" :class="[theme.text]">No overall statistics available.</p>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- إجمالي عدد المحطات -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Total Stations') }}</h3> <!-- حجم خط متجاوب -->
            <p :class="['text-2xl', 'sm:text-3xl', 'font-bold', theme.tableCellText]">{{ overallStats.total_stations_count || 0 }}</p> <!-- حجم خط متجاوب -->
          </div>
          
          <!-- عدد المحطات المتصلة -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Connected Stations') }}</h3> <!-- حجم خط متجاوب -->
            <p :class="['text-2xl', 'sm:text-3xl', 'font-bold', 'text-green-500']">{{ overallStats.connected_stations_count || 0 }}</p> <!-- حجم خط متجاوب -->
          </div>
          
          <!-- عدد المحطات غير المتصلة -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Disconnected Stations') }}</h3> <!-- حجم خط متجاوب -->
            <p :class="['text-2xl', 'sm:text-3xl', 'font-bold', 'text-red-500']">{{ overallStats.disconnected_stations_count || 0 }}</p> <!-- حجم خط متجاوب -->
          </div>

          <!-- توزيع الحالات -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'md:col-span-2', 'lg:col-span-1']">
            <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ t('Status Distribution') }}</h3> <!-- حجم خط متجاوب -->
            <ul>
              <li v-for="(count, status) in overallStats.status_distribution || {}" :key="status" :class="['flex', 'justify-between', 'items-center', 'py-1', 'text-sm', 'sm:text-base']"> <!-- حجم خط متجاوب -->
                <span :class="['font-medium', theme.tableCellText]">{{ t(status) }}</span>
                <span :class="['px-1', 'sm:px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(getDisplayColorForStatus(status))]">
                  {{ count }}
                </span>
              </li>
            </ul>
          </div>

          <!-- متوسط نسبة التشغيل اليومية -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'lg:col-span-1']">
            <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Average Daily Operation Percentage') }}</h3> <!-- حجم خط متجاوب -->
            <p :class="['text-2xl', 'sm:text-3xl', 'font-bold', theme.tableCellText]">
              {{ (overallStats.average_daily_operation_percentage || 0).toFixed(1) }}%
            </p>
          </div>

          <!-- أفضل المحطات أداءً -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.headerText, 'mb-2']">{{ t('Top Performing Stations') }}</h3> <!-- حجم خط متجاوب -->
            
            <div v-if="overallStats.top_performing_stations && overallStats.top_performing_stations.length > 0">
              <ol class="list-decimal list-inside space-y-2">
                <li v-for="station in overallStats.top_performing_stations" :key="station.station_id" 
                    :class="['py-1', 'px-2', 'rounded', theme.tableCellText, 'text-sm', 'sm:text-base']"> <!-- حجم خط متجاوب -->
                  {{ locale === 'ar' ? station.arabic_name : station.english_name }} (ID: {{ station.rtu_data_id }})
                  <span class="font-bold text-green-500 ml-2">
                    ({{ (station.percentage_day || 0).toFixed(1) }}%)
                  </span>
                </li>
              </ol>
            </div>
            
            <div v-else :class="['p-2', 'text-center', theme.mutedText, 'text-sm', 'sm:text-base']"> <!-- حجم خط متجاوب -->
              {{ t('No data available for top performing stations.') }}
            </div>
          </div>

          <!-- أقل المحطات أداءً -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ t('Bottom Performing Stations') }}</h3> <!-- حجم خط متجاوب -->
            <ul v-if="overallStats.bottom_performing_stations && overallStats.bottom_performing_stations.length">
              <li v-for="station in overallStats.bottom_performing_stations" :key="station.station_id" :class="['flex', 'justify-between', 'items-center', 'py-1', theme.tableCellText, 'text-sm', 'sm:text-base']"> <!-- حجم خط متجاوب -->
                <span>{{ locale === 'ar' ? station.arabic_name : station.english_name }} (ID: {{ station.rtu_data_id }})</span>
                <span class="font-bold text-red-500">
                  {{ (station.percentage_day || 0).toFixed(1) }}%
                </span>
              </li>
            </ul>
            <p v-else :class="[theme.mutedText, 'text-sm', 'sm:text-base']">{{ t('No data available for bottom performing stations.') }}</p> <!-- حجم خط متجاوب -->
          </div>
        </div>
      </div>
    </main>

    <!-- قسم التذييل -->
    <footer :class="['py-4', 'mt-8', theme.footerBg, 'text-center', 'rounded-t-lg', 'mx-auto', 'max-w-7xl', 'print:hidden']">
      <p :class="['text-sm', theme.footerText]">{{ t('Operations and Control Department') }}</p>
    </footer>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useI18n } from 'vue-i18n';
// في مشروع Vue حقيقي، ستحتاج إلى تثبيت lucide-vue-next: npm install lucide-vue-next
// ثم استيراد الأيقونات كما يلي:
import { Sun as LucideSun, Moon as LucideMoon, Download as LucideDownload, Printer as LucidePrinter } from 'lucide-vue-next';

export default {
  name: 'App',
  components: {
    LucideSun,
    LucideMoon,
    LucideDownload,
    LucidePrinter,
  },
  setup() {
    // تهيئة الترجمة باستخدام vue-i18n
    const { t, locale } = useI18n();

    // تعريف حالات البيانات والمتغيرات التفاعلية
    const activeTab = ref('live_status');
    const loading = ref(true);
    const dataLoaded = ref(false);
    const liveData = ref([]);
    const overallStats = ref({
      total_stations_count: 0,
      connected_stations_count: 0,
      disconnected_stations_count: 0,
      status_distribution: {},
      average_daily_operation_percentage: 0,
      top_performing_stations: [],
      bottom_performing_stations: []
    });
    const pollingInterval = ref(null); // معرف setInterval للتحديث الدوري
    const currentTheme = ref('dark'); // السمة الحالية (فاتح/داكن)

    // تعريف التبويبات المتاحة في لوحة التحكم
    const tabs = ref([
      { id: 'live_status', name: 'Live Status' },
      { id: 'overall_stats', name: 'Overall Statistics' }
    ]);

    // تعريفات السمات (الألوان والفئات لـ Tailwind CSS)
    const themeDefinitions = {
      light: {
        background: 'bg-gray-100', text: 'text-gray-800', mutedText: 'text-gray-600',
        headerBg: 'bg-white', headerText: 'text-gray-900', tabBg: 'bg-gray-200',
        activeTabBg: 'bg-blue-600', activeTabText: 'text-white', inactiveTabBg: 'bg-gray-200',
        inactiveTabText: 'text-gray-700', buttonBg: 'bg-gray-300', buttonText: 'text-gray-800',
        selectBg: 'bg-gray-50', selectText: 'text-gray-900', selectBorder: 'border-gray-300',
        focusRing: 'focus:ring-blue-500', cardBg: 'bg-white', divideColor: 'divide-gray-200',
        tableHeaderBg: 'bg-gray-50', tableHeaderText: 'text-gray-500', tableRowBg: 'bg-white',
        tableCellText: 'text-gray-900', footerBg: 'bg-white', footerText: 'text-gray-600',
      },
      dark: {
        background: 'bg-gray-900', text: 'text-gray-100', mutedText: 'text-gray-400',
        headerBg: 'bg-gray-800', headerText: 'text-gray-50', tabBg: 'bg-gray-700',
        activeTabBg: 'bg-blue-700', activeTabText: 'text-white', inactiveTabBg: 'bg-gray-700',
        inactiveTabText: 'text-gray-200', buttonBg: 'bg-gray-700', buttonText: 'text-gray-50',
        selectBg: 'bg-gray-800', selectText: 'text-gray-50', selectBorder: 'border-gray-600',
        focusRing: 'focus:ring-blue-600', cardBg: 'bg-gray-800', divideColor: 'divide-gray-700',
        tableHeaderBg: 'bg-gray-700', tableHeaderText: 'text-gray-300', tableRowBg: 'bg-gray-800',
        tableCellText: 'text-gray-100', footerBg: 'bg-gray-800', footerText: 'text-gray-400',
      }
    };
    // خاصية computed لربط السمة النشطة بتعريفات السمة
    const theme = computed(() => themeDefinitions[currentTheme.value]);

    /**
     * وظيفة لتغيير لغة الواجهة.
     * @param {Event} event - حدث التغيير من عنصر select.
     */
    const changeLanguage = (event) => {
      locale.value = event.target.value; // تحديث لغة vue-i18n
      localStorage.setItem('locale', event.target.value); // حفظ اللغة في التخزين المحلي
      
      const newLang = event.target.value;
      document.documentElement.setAttribute('lang', newLang); // تحديث سمة lang في HTML
      document.documentElement.setAttribute('dir', newLang === 'ar' ? 'rtl' : 'ltr'); // تحديث اتجاه النص (يمين لليسار للعربية)
    };

    /**
     * وظيفة لتبديل السمة بين الوضع الفاتح والداكن.
     */
    const toggleTheme = () => {
      currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
      localStorage.setItem('theme', currentTheme.value); // حفظ السمة في التخزين المحلي
    };

    /**
     * ترجع فئات Tailwind CSS المناسبة للون الحالة.
     * @param {string} color - لون الحالة (مثل 'green', 'red', 'Normal', 'Failed').
     * @returns {string} - فئات Tailwind CSS.
     */
    const getStatusClass = (color) => {
      switch (color) {
        case 'green': return 'bg-green-100 text-green-800';
        case 'red': return 'bg-red-100 text-red-800';
        case 'orange': return 'bg-orange-100 text-orange-800';
        case 'blue': return 'bg-blue-100 text-blue-800';
        case 'gray': return 'bg-gray-100 text-gray-800';
        case 'Normal': return 'bg-green-100 text-green-800';
        case 'Failed': return 'bg-red-100 text-red-800';
        case 'Marginal': return 'bg-orange-100 text-orange-800';
        case 'Alarm': return 'bg-blue-100 text-blue-800';
        case 'OffNoData': return 'bg-red-100 text-red-800';
        case 'Unknown': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
      }
    };

    /**
     * ترجع اسم الحالة المناسب للعرض بناءً على الحالة الخام.
     * (تستخدم لربط مفاتيح الترجمة بالألوان في توزيع الحالات).
     * @param {string} status - الحالة الخام.
     * @returns {string} - الحالة المناسبة للعرض.
     */
    const getDisplayColorForStatus = (status) => {
      switch (status) {
        case 'Normal': return 'Normal';
        case 'Failed': return 'Failed';
        case 'Marginal': return 'Marginal';
        case 'Alarm': return 'Alarm';
        case 'OffNoData': return 'OffNoData';
        case 'Unknown': return 'Unknown';
        default: return 'Unknown';
      }
    };

    /**
     * وظيفة لجلب البيانات (محاكاة لاستدعاء API).
     */
    const fetchData = async () => {
      try {
        loading.value = true;
        // ملاحظة: /rtu-data هو مسار وهمي. في تطبيق حقيقي، ستحتاج إلى نقطة نهاية API حقيقية في Laravel.
        // قم بإلغاء التعليق على الأسطر التالية واستخدامها لجلب البيانات من خادمك الحقيقي:
        const response = await fetch('/rtu-data'); 
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        // استخدام البيانات الوهمية مباشرة لأغراض العرض في Canvas.
        // في مشروعك الحقيقي، قم بتعليق أو حذف هذا الجزء واستخدم استدعاء fetch أعلاه.
        // const data = {
        //   "stations_data": [
        //     { "station_id": "ST001", "rtu_data_id": "RTU-001", "arabic_name": "المحطة الأولى", "english_name": "Station One", "rtu_status_text": "Normal", "rtu_status_color": "green", "connection_status_text": "Connected", "connection_status_color": "green", "percentage_day": 98.5 },
        //     { "station_id": "ST002", "rtu_data_id": "RTU-002", "arabic_name": "المحطة الثانية", "english_name": "Station Two", "rtu_status_text": "Failed", "rtu_status_color": "red", "connection_status_text": "Disconnected", "connection_status_color": "red", "percentage_day": 10.2 },
        //     { "station_id": "ST003", "rtu_data_id": "RTU-003", "arabic_name": "المحطة الثالثة", "english_name": "Station Three", "rtu_status_text": "Marginal", "rtu_status_color": "orange", "connection_status_text": "Connected", "connection_status_color": "green", "percentage_day": 75.0 },
        //     { "station_id": "ST004", "arabic_name": "المحطة الرابعة", "english_name": "Station Four", "rtu_status_text": "Alarm", "rtu_status_color": "blue", "connection_status_text": "Connected", "connection_status_color": "green", "percentage_day": 88.1 },
        //     { "station_id": "ST005", "arabic_name": "المحطة الخامسة", "english_name": "Station Five", "rtu_status_text": "OffNoData", "rtu_status_color": "red", "connection_status_text": "Disconnected", "connection_status_color": "red", "percentage_day": 0.0 },
        //     { "station_id": "ST006", "arabic_name": "المحطة السادسة", "english_name": "Station Six", "rtu_status_text": "Normal", "rtu_status_color": "green", "connection_status_text": "Connected", "connection_status_color": "green", "percentage_day": 99.9 },
        //     { "station_id": "ST007", "arabic_name": "المحطة السابعة", "english_name": "Station Seven", "rtu_status_text": "Failed", "rtu_status_color": "red", "connection_status_text": "Disconnected", "connection_status_color": "red", "percentage_day": 5.0 },
        //     { "station_id": "ST008", "arabic_name": "المحطة الثامنة", "english_name": "Station Eight", "rtu_status_text": "Normal", "rtu_status_color": "green", "connection_status_text": "Connected", "connection_status_color": "green", "percentage_day": 95.0 },
        //     { "station_id": "ST009", "arabic_name": "المحطة التاسعة", "english_name": "Station Nine", "rtu_status_text": "Marginal", "rtu_status_color": "orange", "connection_status_text": "Connected", "connection_status_color": "green", "percentage_day": 60.0 },
        //     { "station_id": "ST010", "arabic_name": "المحطة العاشرة", "english_name": "Station Ten", "rtu_status_text": "Alarm", "rtu_status_color": "blue", "connection_status_text": "Connected", "connection_status_color": "green", "percentage_day": 78.0 }
        //   ],
        //   "overall_stats": {
        //     "total_stations_count": 10,
        //     "connected_stations_count": 7,
        //     "disconnected_stations_count": 3,
        //     "status_distribution": { "Normal": 3, "Failed": 2, "Marginal": 2, "Alarm": 2, "OffNoData": 1 },
        //     "average_daily_operation_percentage": 60.97,
        //     "top_performing_stations": [
        //       { "station_id": "ST006", "rtu_data_id": "RTU-006", "arabic_name": "المحطة السادسة", "english_name": "Station Six", "percentage_day": 99.9 },
        //       { "station_id": "ST001", "rtu_data_id": "RTU-001", "arabic_name": "المحطة الأولى", "english_name": "Station One", "percentage_day": 98.5 },
        //       { "station_id": "ST008", "rtu_data_id": "RTU-008", "arabic_name": "المحطة الثامنة", "english_name": "Station Eight", "percentage_day": 95.0 }
        //     ],
        //     "bottom_performing_stations": [
        //       { "station_id": "ST005", "rtu_data_id": "RTU-005", "arabic_name": "المحطة الخامسة", "english_name": "Station Five", "percentage_day": 0.0 },
        //       { "station_id": "ST007", "rtu_data_id": "RTU-007", "arabic_name": "المحطة السابعة", "english_name": "Station Seven", "percentage_day": 5.0 },
        //       { "station_id": "ST002", "rtu_data_id": "RTU-002", "arabic_name": "المحطة الثانية", "english_name": "Station Two", "percentage_day": 10.2 }
        //     ]
        //   }
        // };

        // معالجة البيانات: إضافة الرقم التسلسلي
        liveData.value = (data.stations_data || []).map((item, index) => ({
          ...item, // احتفظ بجميع الخصائص الأصلية، بما في ذلك rtu_data_id
          sequential_rtu_number: index + 1 // إضافة الرقم التسلسلي (يبدأ من 1)
        }));

        if (data.overall_stats) {
            overallStats.value = { 
                total_stations_count: data.overall_stats.total_stations_count || 0,
                connected_stations_count: data.overall_stats.connected_stations_count || 0,
                disconnected_stations_count: data.overall_stats.disconnected_stations_count || 0,
                status_distribution: data.overall_stats.status_distribution || {},
                average_daily_operation_percentage: parseFloat(data.overall_stats.average_daily_operation_percentage) || 0,
                top_performing_stations: (data.overall_stats.top_performing_stations || []).map(station => ({
                    ...station,
                    percentage_day: parseFloat(station.percentage_day) || 0
                })),
                bottom_performing_stations: (data.overall_stats.bottom_performing_stations || []).map(station => ({
                    ...station,
                    percentage_day: parseFloat(station.percentage_day) || 0
                }))
            };
        } else {
            overallStats.value = { 
              total_stations_count: 0,
              connected_stations_count: 0,
              disconnected_stations_count: 0,
              status_distribution: {},
              average_daily_operation_percentage: 0, 
              top_performing_stations: [],
              bottom_performing_stations: []
            };
        }

        dataLoaded.value = true;
      } catch (error) {
        console.error("Error fetching data:", error);
        liveData.value = [];
        overallStats.value = {
          total_stations_count: 0,
          connected_stations_count: 0,
          disconnected_stations_count: 0,
          status_distribution: {},
          average_daily_operation_percentage: 0,
          top_performing_stations: [],
          bottom_performing_stations: []
        };
      } finally {
        loading.value = false;
      }
    };

    /**
     * وظيفة لتصدير بيانات الحالة اللحظية إلى ملف CSV.
     */
    const exportToExcel = async () => {
        try {
            // إنشاء رأس ملف CSV بدون عمود "Connection Status"
            const csvHeader = `${t('RTU Number')},${t('Station Name')},${t('RTU Status')},${t('Percentage Day')}\n`;
            // إنشاء صفوف CSV، مع التأكد من إحاطة القيم التي قد تحتوي على فواصل بعلامات اقتباس مزدوجة
            const csvRows = liveData.value.map(rtu => {
              const stationName = locale.value === 'ar' ? rtu.arabic_name : rtu.english_name;
              // استخدام rtu.sequential_rtu_number هنا
              return `${rtu.sequential_rtu_number},"${stationName}",${t(rtu.rtu_status_text)},${rtu.percentage_day.toFixed(1)}%`;
            }).join('\n');

            const blob = new Blob([csvHeader + csvRows], { type: 'text/csv;charset=utf-8;' });
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');

            // إنشاء اسم ملف فريد بتاريخ ووقت التصدير
            const now = new Date();
            const dateString = now.getFullYear() + '-' + 
                                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(now.getDate()).padStart(2, '0');
            const timeString = String(now.getHours()).padStart(2, '0') + 
                                String(now.getMinutes()).padStart(2, '0') + 
                                String(now.getSeconds()).padStart(2, '0');
            const filename = `rtu_live_status_excel_${dateString}_${timeString}.csv`; // تغيير الامتداد إلى .csv

            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error("Error exporting Excel:", error);
            // استخدام مربع رسالة مخصص بدلاً من alert()
            const messageBox = document.createElement('div');
            messageBox.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50';
            messageBox.innerHTML = `
              <div class="bg-white p-6 rounded-lg shadow-lg text-center ${theme.value.cardBg} ${theme.value.text}">
                <p class="text-lg font-semibold text-red-600 mb-4">${t('Failed to export Excel file. Please try again.')}</p>
                <button id="closeMessageBox" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">OK</button>
              </div>
            `;
            document.body.appendChild(messageBox);
            document.getElementById('closeMessageBox').onclick = () => document.body.removeChild(messageBox);
        }
    };

    /**
     * وظيفة لطباعة محتوى الحالة اللحظية.
     */
    const printLiveStatus = () => {
      window.print();
    };

    /**
     * وظيفة لبدء جلب البيانات بشكل دوري.
     */
    const startPolling = () => {
      fetchData(); // جلب البيانات فوراً عند البدء
      if (pollingInterval.value) {
        clearInterval(pollingInterval.value); // مسح أي فترات سابقة
      }
      pollingInterval.value = setInterval(fetchData, 60000); // تحديث كل 60 ثانية
    };

    // دورة حياة المكون: يتم تشغيلها عند تحميل المكون
    onMounted(() => {
      // استعادة السمة المحفوظة من التخزين المحلي
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme) {
        currentTheme.value = savedTheme;
      }
      // استعادة اللغة المحفوظة من التخزين المحلي وتحديث سمات HTML
      const savedLocale = localStorage.getItem('locale');
      if (savedLocale) {
        locale.value = savedLocale;
        // تم إزالة selectedLocale.value = savedLocale;
        document.documentElement.setAttribute('lang', savedLocale);
        document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr');
      } else {
          // تم إزالة selectedLocale.value = locale.value;
          document.documentElement.setAttribute('lang', locale.value);
          document.documentElement.setAttribute('dir', locale.value === 'ar' ? 'rtl' : 'ltr');
      }
      startPolling(); // بدء جلب البيانات الدوري
    });

    // دورة حياة المكون: يتم تشغيلها قبل إلغاء تحميل المكون
    onBeforeUnmount(() => {
      // مسح الفاصل الزمني للتحديث الدوري لتجنب تسرب الذاكرة
      if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
      }
    });

    // إرجاع جميع المتغيرات والوظائف لتكون متاحة في القالب (template)
    return {
      t,
      locale,
      activeTab,
      loading,
      dataLoaded,
      liveData,
      overallStats,
      // تم إزالة selectedLocale من هنا
      currentTheme,
      tabs,
      theme,
      changeLanguage,
      toggleTheme,
      getStatusClass,
      getDisplayColorForStatus,
      fetchData,
      exportToExcel,
      printLiveStatus,
      startPolling
    };
  },
};
</script>

<style>
/* أنماط الطباعة المخصصة: تخفي عناصر الواجهة غير الضرورية للطباعة وتنسق الجدول */
@media print {
  header, footer, nav, .print\:hidden {
    display: none !important;
  }

  .overall-stats-tab-content {
      display: none !important;
  }

  .live-status-print-area {
    display: block !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    background-color: #fff !important;
  }

  main {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
    width: 100% !important;
  }

  table {
    width: 100% !important;
    border-collapse: collapse !important;
    table-layout: fixed;
  }

  th, td {
    border: 1px solid #ccc !important;
    padding: 1px 2px !important;
    text-align: left !important;
    font-size: 6px !important; /* تقليل حجم الخط للطباعة */
    word-wrap: break-word;
    overflow: hidden;
  }

  h2.print-only-title {
    color: #000 !important;
    font-size: 12px !important;
    text-align: center !important;
    margin-bottom: 3px !important;
    padding-top: 3px !important;
  }

  /* ضمان أن ألوان النصوص والخلفيات تكون مناسبة للطباعة */
  .text-gray-800, .text-gray-900, .text-gray-500, .text-gray-700, .text-gray-100 {
    color: #000 !important;
  }

  .bg-gray-100, .bg-white, .bg-gray-50, .bg-gray-200, .bg-gray-300, .bg-gray-700, .bg-gray-800, .bg-gray-900,
  .theme\.cardBg, .theme\.tableHeaderBg, .theme\.tableRowBg {
    background-color: #fff !important;
  }

  /* ألوان خلفية لحالات RTU للطباعة */
  .bg-green-100 { background-color: #d4edda !important; color: #155724 !important; }
  .bg-red-100 { background-color: #f8d7da !important; color: #721c24 !important; }
  .bg-orange-100 { background-color: #ffeeba !important; color: #856404 !important; }
  .bg-blue-100 { background-color: #d1ecf1 !important; color: #0c5460 !important; }
  .bg-gray-100 { background-color: #f8f9fa !important; color: #343a40 !important; }

  .overflow-x-auto {
    overflow: visible !important; /* إظهار المحتوى الكامل للجدول عند الطباعة */
  }

  /* إخفاء رسائل التحميل/لا توجد بيانات عند الطباعة */
  .live-status-print-area p:not(.print-only-message) {
      display: none !important;
  }

  /* إعدادات حجم الصفحة والهوامش للطباعة */
  @page {
    size: A4 landscape; /* طباعة أفقية بحجم A4 */
    margin: 0.5cm; /* هوامش صغيرة */
  }
}
</style>
