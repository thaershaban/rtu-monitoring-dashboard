<template>
  <div :class="['min-h-screen', theme.background, theme.text, 'print:bg-white']">
    <!-- Header section: Contains title, language toggle, and theme toggle -->
    <header :class="['py-4', theme.headerBg, 'shadow-md', 'rounded-b-lg', 'sticky', 'top-0', 'z-50', 'mx-auto', 'max-w-7xl', 'px-4', 'sm:px-6', 'lg:px-8', 'print:hidden']">
      <div class="flex flex-col sm:flex-row items-center justify-between">
        <h1 :class="['text-2xl', 'sm:text-3xl', 'font-bold', theme.headerText, 'mb-2', 'sm:mb-0']">{{ t('RTU Monitoring Dashboard') }}</h1>
        <div class="flex items-center space-x-4">
          <!-- Language toggle -->
          <select v-model="locale" @change="changeLanguage" :class="['px-3', 'py-2', 'rounded-md', theme.selectBg, theme.selectText, theme.selectBorder, 'focus:outline-none', 'focus:ring-2', theme.focusRing]">
            <option value="en">English</option>
            <option value="ar">العربية</option>
          </select>

          <!-- Theme toggle button (Light/Dark mode) -->
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
      <!-- Tab navigation (Live Status and Overall Statistics) -->
      <nav :class="['mt-4', 'flex', 'flex-col', 'sm:flex-row', 'space-y-2', 'sm:space-y-0', 'sm:space-x-4', 'justify-center', 'p-2', 'rounded-lg', theme.tabBg]">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200',
                         activeTab === tab.id ? theme.activeTabBg : theme.inactiveTabBg,
                         activeTab === tab.id ? theme.activeTabText : theme.inactiveTabText,
                         'hover:bg-opacity-80', 'w-full', 'sm:w-auto']">
          {{ t(tab.name) }}
        </button>
      </nav>
    </header>

    <!-- Main content area -->
    <main class="container mx-auto px-4 py-8 max-w-7xl">
      <!-- Welcome message when data is not loaded -->
      <div v-if="!dataLoaded" class="text-center py-10">
        <p :class="['text-xl', 'font-semibold', theme.text]">{{ t('Welcome to RTU Archive') }}...</p>
        <p :class="['text-sm', theme.mutedText]">{{ t('Loading data, please wait.') }}</p>
      </div>

      <!-- Live Status tab content -->
      <div v-if="activeTab === 'live_status'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl', 'live-status-print-area']">
        <h2 :class="['text-xl', 'sm:text-2xl', 'font-semibold', 'mb-4', theme.headerBg, 'print:text-black', 'print:text-xl', 'print:font-bold', 'print:mb-4', 'print-only-title']">{{ t('Live RTU Status') }}</h2>
        
        <!-- Export and Print buttons -->
        <div class="flex flex-col sm:flex-row justify-end mb-4 space-y-2 sm:space-y-0 sm:space-x-2 print:hidden">
            <button @click="exportToExcel" 
            :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200', theme.buttonBg, theme.buttonText, 'hover:brightness-110', 'w-full', 'sm:w-auto']">
            <LucideDownload :class="['inline-block', 'w-5', 'h-5', 'mr-2']" />
            {{ t('Export to Excel') }}
            </button>
            <button @click="printLiveStatus" :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200', theme.buttonBg, theme.buttonText, 'hover:brightness-110', 'w-full', 'sm:w-auto']">
                <LucidePrinter :class="['inline-block', 'w-5', 'h-5', 'mr-2']" /> {{ t('Print') }}
            </button>
        </div>

        <p v-if="loading" :class="[theme.text, 'print:hidden']">{{ t('Loading live data...') }}</p>
        <p v-else-if="!liveData.length" :class="[theme.text, 'print:text-black', 'print-only-message']">{{ t('No live data available for stations.') }}</p>
        <div v-else class="overflow-x-auto rounded-lg shadow-inner">
          <table :class="['min-w-full', 'divide-y', theme.divideColor]">
            <thead :class="[theme.tableHeaderBg]">
              <tr>
                <!-- RTU Number field will now display sequential numbers (1, 2, 3, ...) -->
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('RTU Number') }}</th>
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('Arabic Name') }}</th>
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('English Name') }}</th>
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('RTU Status') }}</th>
                <!-- Removed Connection Status Column Header -->
                <th :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-3', 'text-left', 'text-xs', 'sm:text-sm', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('Percentage Day') }}</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', theme.divideColor, theme.tableRowBg]">
              <tr v-for="rtu in liveData" :key="rtu.station_id">
                <!-- Display sequential_rtu_number for RTU Number field -->
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap', 'text-xs', 'sm:text-sm', theme.tableCellText]">{{ rtu.sequential_rtu_number }}</td>
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap', 'text-xs', 'sm:text-sm', theme.tableCellText, 'font-semibold']">{{ rtu.arabic_name }}</td>
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap', 'text-xs', 'sm:text-sm', theme.tableCellText]">{{ rtu.english_name }}</td>
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap']">
                  <span :class="['px-1', 'sm:px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(rtu.rtu_status_color)]">
                    {{ t(rtu.rtu_status_text) }}
                  </span>
                </td>
                <!-- Removed Connection Status Data Cell -->
                <td :class="['px-3', 'py-2', 'sm:px-6', 'sm:py-4', 'whitespace-nowrap', 'text-xs', 'sm:text-sm', theme.tableCellText]">
                  {{ (rtu.percentage_day || 0).toFixed(1) }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Overall Statistics tab content -->
      <div v-if="activeTab === 'overall_stats'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl', 'print:hidden']">
        <h2 :class="['text-xl', 'sm:text-2xl', 'font-semibold', 'mb-4', theme.headerBg]">{{ t('Overall Statistics') }}</h2>
        <p v-if="loading" :class="[theme.text]">{{ t('Loading overall statistics...') }}</p>
        <div v-else>
          <!-- Display message if no overall statistics are available based on total_stations_count -->
          <p v-if="overallStats.total_stations_count === 0" :class="[theme.text, 'text-center', 'py-4', theme.mutedText]">
            {{ t('No overall statistics available.') }}
          </p>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Total Stations -->
            <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
              <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Total Stations') }}</h3>
              <p :class="['text-2xl', 'sm:text-3xl', 'font-bold', theme.tableCellText]">{{ overallStats.total_stations_count || 0 }}</p>
            </div>
            
            <!-- Connected Stations -->
            <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
              <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Connected Stations') }}</h3>
              <p :class="['text-2xl', 'sm:text-3xl', 'font-bold', 'text-green-500']">{{ overallStats.connected_stations_count || 0 }}</p>
            </div>
            
            <!-- Disconnected Stations -->
            <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
              <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Disconnected Stations') }}</h3>
              <p :class="['text-2xl', 'sm:text-3xl', 'font-bold', 'text-red-500']">{{ overallStats.disconnected_stations_count || 0 }}</p>
            </div>

            <!-- Status Distribution (Pie Chart) -->
            <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'md:col-span-2', 'lg:col-span-1', 'flex', 'flex-col', 'items-center']">
              <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ t('Status Distribution') }}</h3>
              <div class="w-full h-64 flex justify-center items-center">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      :data="statusDistributionChartData"
                      dataKey="value"
                      nameKey="name"
                      cx="50%"
                      cy="50%"
                      outerRadius="80%"
                      fill="#8884d8"
                      label
                    >
                      <Cell v-for="(entry, index) in statusDistributionChartData" :key="`cell-${index}`" :fill="getChartColor(entry.name)" />
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>

            <!-- Average Daily Operation Percentage (Bar Chart) -->
            <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'lg:col-span-1', 'flex', 'flex-col', 'items-center']">
              <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Average Daily Operation Percentage') }}</h3>
              <div class="w-full h-64 flex justify-center items-center">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    :data="[{ name: t('Average'), value: overallStats.average_daily_operation_percentage || 0 }]"
                    margin="{ top: 20, right: 30, left: 20, bottom: 5 }"
                  >
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" />
                    <YAxis domain={[0, 100]} />
                    <Tooltip />
                    <Bar dataKey="value" :fill="getChartColor('Average')">
                      <LabelList dataKey="value" position="top" :formatter="(value) => `${value.toFixed(1)}%`" />
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>

            <!-- Top Performing Stations -->
            <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
              <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.headerBg, 'mb-2']">{{ t('Top Performing Stations') }}</h3>
              
              <div v-if="overallStats.top_performing_stations && overallStats.top_performing_stations.length > 0">
                <ol class="list-decimal list-inside space-y-2">
                  <li v-for="station in overallStats.top_performing_stations" :key="station.station_id" 
                      :class="['py-1', 'px-2', 'rounded', theme.tableCellText, 'text-sm', 'sm:text-base', 'flex', 'justify-between', 'items-center']">
                    <div>
                      <span class="font-semibold">{{ station.arabic_name }}</span>
                      <span :class="['ml-2', 'text-gray-500', 'dark:text-gray-400']">({{ station.english_name }})</span>
                    </div>
                    <span class="font-bold text-green-500">
                      {{ (station.percentage_day || 0).toFixed(1) }}%
                    </span>
                  </li>
                </ol>
              </div>
              
              <div v-else :class="['p-2', 'text-center', theme.mutedText, 'text-sm', 'sm:text-base']">
                {{ t('No data available for top performing stations.') }}
              </div>
            </div>

            <!-- Bottom Performing Stations -->
            <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
              <h3 :class="['text-base', 'sm:text-lg', 'font-medium', theme.tableHeaderBg, 'mb-2']">{{ t('Bottom Performing Stations') }}</h3>
              <ul v-if="overallStats.bottom_performing_stations && overallStats.bottom_performing_stations.length">
                <li v-for="station in overallStats.bottom_performing_stations" :key="station.station_id" 
                    :class="['py-1', 'px-2', 'rounded', theme.tableCellText, 'text-sm', 'sm:text-base', 'flex', 'justify-between', 'items-center']">
                  <div>
                    <span class="font-semibold">{{ station.arabic_name }}</span>
                    <span :class="['ml-2', 'text-gray-500', 'dark:text-gray-400']">({{ station.english_name }})</span>
                  </div>
                  <span class="font-bold text-red-500">
                    {{ (station.percentage_day || 0).toFixed(1) }}%
                  </span>
                </li>
              </ul>
              <p v-else :class="[theme.mutedText, 'text-sm', 'sm:text-base']">{{ t('No data available for bottom performing stations.') }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer section -->
    <footer :class="['py-4', 'mt-8', theme.footerBg, 'text-center', 'rounded-t-lg', 'mx-auto', 'max-w-7xl', 'print:hidden']">
      <p :class="['text-sm', theme.footerText]">{{ t('Operations and Control Department') }}</p>
    </footer>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useI18n, createI18n } from 'vue-i18n';
import { Sun as LucideSun, Moon as LucideMoon, Download as LucideDownload, Printer as LucidePrinter } from 'lucide-vue-next';

// Import Recharts components
import {
  PieChart, Pie, Cell, Tooltip, Legend, ResponsiveContainer,
  BarChart, Bar, XAxis, YAxis, CartesianGrid, LabelList
} from 'recharts'; // Using 'recharts' as the standard package

// Define translation messages here directly in App.vue
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
    "Station Name": "Station Name",
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
    "Station Name": "اسم المحطة",
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

// Create i18n instance here, in the global scope of the script block
const i18nInstance = createI18n({
  locale: localStorage.getItem('locale') || 'en',
  fallbackLocale: 'en',
  messages,
  legacy: false,
  globalInjection: true,
});

// REMOVE THIS ENTIRE MOCK DATA OBJECT
// THIS IS THE CRITICAL STEP TO ENSURE REAL DATA IS USED
// If you uncomment the fetch call in fetchData(), this mock data will be ignored.
// If you want to use real data, it's best to remove this to avoid confusion.


export default {
  name: 'App',
  components: {
    LucideSun,
    LucideMoon,
    LucideDownload,
    LucidePrinter,
    // Recharts components
    PieChart,
    Pie,
    Cell,
    Tooltip,
    Legend,
    ResponsiveContainer,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    LabelList
  },
  // Declare props that ResponsiveContainer might expect to silence Vue warnings
  props: {
    width: { type: [String, Number], default: '100%' },
    height: { type: [String, Number], default: '100%' },
    aspect: { type: Number, default: undefined },
    initialDimension: { type: Object, default: undefined },
    minWidth: { type: [Number, String], default: undefined },
    minHeight: { type: [Number, String], default: undefined },
    maxHeight: { type: [Number, String], default: undefined },
    debounce: { type: Number, default: 0 },
    id: { type: String, default: undefined },
    className: { type: String, default: undefined },
    onResize: { type: Function, default: undefined },
    children: { type: [Array, Object], default: undefined },
    style: { type: [String, Object], default: undefined }
  },
  setup() {
    // Access t and locale directly from the i18n instance
    const t = i18nInstance.global.t;
    const locale = i18nInstance.global.locale;

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
    const pollingInterval = ref(null);
    const currentTheme = ref(localStorage.getItem('theme') || 'dark'); // Initialize from localStorage

    const tabs = ref([
      { id: 'live_status', name: 'Live Status' },
      { id: 'overall_stats', name: 'Overall Statistics' }
    ]);

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
    const theme = computed(() => themeDefinitions[currentTheme.value]);

    // Computed property for Status Distribution Chart Data
    const statusDistributionChartData = computed(() => {
      if (!overallStats.value?.status_distribution) return [];
      return Object.entries(overallStats.value.status_distribution).map(([status, count]) => ({
        name: t(status), // Translate status name for display
        value: count,
      }));
    });

    // Function to get consistent colors for charts based on status
    const getChartColor = (name) => {
      switch (name) {
        case t('Normal'): return '#4CAF50'; // Green
        case t('Failed'): return '#F44336'; // Red
        case t('Marginal'): return '#FFC107'; // Amber
        case t('Alarm'): return '#2196F3'; // Blue
        case t('Off (No Data)'): return '#9E9E9E'; // Grey
        case t('Unknown'): return '#607D8B'; // Blue Grey
        case t('Average'): return '#8884d8'; // Purple for average bar
        default: return '#CCCCCC'; // Default light grey
      }
    };

    const changeLanguage = (event) => {
      const newLocale = event.target.value;
      locale.value = newLocale;
      localStorage.setItem('locale', newLocale);
      
      document.documentElement.setAttribute('lang', newLocale);
      document.documentElement.setAttribute('dir', newLocale === 'ar' ? 'rtl' : 'ltr');
    };

    const toggleTheme = () => {
      currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
      localStorage.setItem('theme', currentTheme.value);
    };

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

    const fetchData = async () => {
      try {
        loading.value = true;
        
        // UNCOMMENT THE FOLLOWING LINES TO FETCH REAL DATA FROM LARAVEL BACKEND
        const response = await fetch('http://127.0.0.1:8001/rtu-data'); // Adjust URL if different
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();


        liveData.value = (data.stations_data || []).map((item, index) => ({
          ...item,
          sequential_rtu_number: index + 1
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
     * Function to export live status data to a CSV file.
     */
    const exportToExcel = async () => {
        try {
            // Add UTF-8 BOM to ensure Arabic characters are displayed correctly in Excel
            const BOM = "\uFEFF"; 
            // Enclose all header fields in double quotes to handle spaces and special characters correctly
            // Removed 'Connection Status' from headers
            const csvHeader = `${BOM}"${t('RTU Number')}", "${t('Arabic Name')}", "${t('English Name')}", "${t('RTU Status')}", "${t('Percentage Day')}"\n`;
            // Create CSV rows, ensuring values that might contain commas are enclosed in double quotes
            const csvRows = liveData.value.map(rtu => {
              // Removed 'rtu.connection_status_text' from data row
              return `${rtu.sequential_rtu_number},"${rtu.arabic_name}","${rtu.english_name}","${t(rtu.rtu_status_text)}","${(rtu.percentage_day || 0).toFixed(1)}%"`;
            }).join('\n');

            const blob = new Blob([csvHeader + csvRows], { type: 'text/csv;charset=utf-8;' });
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');

            // Create a unique filename with export date and time
            const now = new Date();
            const dateString = now.getFullYear() + '-' + 
                                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(now.getDate()).padStart(2, '0');
            const timeString = String(now.getHours()).padStart(2, '0') + 
                                String(now.getMinutes()).padStart(2, '0') + 
                                String(now.getSeconds()).padStart(2, '0');
            const filename = `rtu_live_status_${dateString}_${timeString}.csv`;

            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error("Error exporting Excel:", error);
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

    const printLiveStatus = () => {
      window.print();
    };

    const startPolling = () => {
      fetchData();
      if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
      }
      pollingInterval.value = setInterval(fetchData, 60000);
    };

    onMounted(() => {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme) {
        currentTheme.value = savedTheme;
      }
      const savedLocale = localStorage.getItem('locale');
      if (savedLocale) {
        locale.value = savedLocale;
        document.documentElement.setAttribute('lang', savedLocale);
        document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr');
      } else {
          document.documentElement.setAttribute('lang', locale.value);
          document.documentElement.setAttribute('dir', locale.value === 'ar' ? 'rtl' : 'ltr');
      }
      startPolling();
    });

    onBeforeUnmount(() => {
      if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
      }
    });

    return {
      t,
      locale,
      activeTab,
      loading,
      dataLoaded,
      liveData,
      overallStats,
      currentTheme,
      tabs,
      theme,
      statusDistributionChartData,
      getChartColor,
      changeLanguage,
      toggleTheme,
      getStatusClass,
      exportToExcel,
      printLiveStatus,
      startPolling
    };
  },
};
</script>

<style>
/* Custom print styles: hides unnecessary UI elements for printing and formats the table */
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
    font-size: 6px !important; /* Reduce font size for printing */
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

  /* Ensure text and background colors are suitable for printing */
  .text-gray-800, .text-gray-900, .text-gray-500, .text-gray-700, .text-gray-100 {
    color: #000 !important;
  }

  .bg-gray-100, .bg-white, .bg-gray-50, .bg-gray-200, .bg-gray-300, .bg-gray-700, .bg-gray-800, .bg-gray-900,
  .theme\.cardBg, .theme\.tableHeaderBg, .theme\.tableRowBg {
    background-color: #fff !important;
  }

  /* Background colors for RTU statuses for printing */
  .bg-green-100 { background-color: #d4edda !important; color: #155724 !important; }
  .bg-red-100 { background-color: #f8d7da !important; color: #721c24 !important; }
  .bg-orange-100 { background-color: #ffeeba !important; color: #856404 !important; }
  .bg-blue-100 { background-color: #d1ecf1 !important; color: #0c5460 !important; }
  .bg-gray-100 { background-color: #f8f9fa !important; color: #343a40 !important; }

  .overflow-x-auto {
    overflow: visible !important; /* Show full table content when printing */
  }

  /* Hide loading/no data messages when printing */
  .live-status-print-area p:not(.print-only-message) {
      display: none !important;
  }

  /* Page size and margin settings for printing */
  @page {
    size: A4 landscape; /* Landscape A4 print */
    margin: 0.5cm; /* Small margins */
  }
}
</style>
