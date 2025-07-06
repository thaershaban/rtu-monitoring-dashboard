<template>
  <div :class="['min-h-screen', theme.background, theme.text, 'print:bg-white']">
    <header :class="['py-4', theme.headerBg, 'shadow-md', 'rounded-b-lg', 'sticky', 'top-0', 'z-50', 'mx-auto', 'max-w-7xl', 'px-4', 'sm:px-6', 'lg:px-8', 'print:hidden']">
      <div class="flex items-center justify-between">
        <h1 :class="['text-3xl', 'font-bold', theme.headerText]">{{ t('RTU Monitoring Dashboard') }}</h1>
        <div class="flex items-center space-x-4">
          <select v-model="selectedLocale" @change="changeLanguage" :class="['px-3', 'py-2', 'rounded-md', theme.selectBg, theme.selectText, theme.selectBorder, 'focus:outline-none', 'focus:ring-2', theme.focusRing]">
            <option value="en">English</option>
            <option value="ar">العربية</option>
          </select>

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
      <nav :class="['mt-4', 'flex', 'space-x-4', 'justify-center', 'p-2', 'rounded-lg', theme.tabBg]">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200',
                         activeTab === tab.id ? theme.activeTabBg : theme.inactiveTabBg,
                         activeTab === tab.id ? theme.activeTabText : theme.inactiveTabText,
                         'hover:bg-opacity-80']">
          {{ t(tab.name) }}
        </button>
      </nav>
    </header>

    <main class="container mx-auto px-4 py-8 max-w-7xl">
      <div v-if="!dataLoaded" class="text-center py-10">
        <p :class="['text-xl', 'font-semibold', theme.text]">{{ t('Welcome to RTU Archive') }}...</p>
        <p :class="['text-sm', theme.mutedText]">{{ t('Loading data, please wait.') }}</p>
      </div>

      <div v-if="activeTab === 'live_status'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl', 'live-status-print-area']">
        <h2 :class="['text-2xl', 'font-semibold', 'mb-4', theme.headerText, 'print:text-black', 'print:text-xl', 'print:font-bold', 'print:mb-4', 'print-only-title']">{{ t('Live RTU Status') }}</h2>
        
        <div class="flex justify-end mb-4 space-x-2 print:hidden">
            <button @click="exportToExcel" 
            :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200', theme.buttonBg, theme.buttonText, 'hover:brightness-110']">
            <LucideDownload :class="['inline-block', 'w-5', 'h-5', 'mr-2']" />
            {{ t('Export to Excel') }}
            </button>
            <button @click="printLiveStatus" :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200', theme.buttonBg, theme.buttonText, 'hover:brightness-110']">
                <LucidePrinter :class="['inline-block', 'w-5', 'h-5', 'mr-2']" /> {{ t('Print') }}
            </button>
        </div>

        <p v-if="loading" :class="[theme.text, 'print:hidden']">{{ t('Loading live data...') }}</p>
        <p v-else-if="!liveData.length" :class="[theme.text, 'print:text-black', 'print-only-message']">{{ t('No live data available for stations.') }}</p>
        <div v-else class="overflow-x-auto rounded-lg shadow-inner">
          <table :class="['min-w-full', 'divide-y', theme.divideColor]">
            <thead :class="[theme.tableHeaderBg]">
              <tr>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('RTU Number') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('Station Name') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('RTU Status') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('Connection Status') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ t('Percentage Day') }}</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', theme.divideColor, theme.tableRowBg]">
              <tr v-for="rtu in liveData" :key="rtu.station_id">
                <td :class="['px-6', 'py-4', 'whitespace-nowrap', theme.tableCellText]">{{ rtu.rtu_data_id }}</td>
                <td :class="['px-6', 'py-4', 'whitespace-nowrap', theme.tableCellText]">{{ locale === 'ar' ? rtu.arabic_name : rtu.english_name }}</td>
                <td :class="['px-6', 'py-4', 'whitespace-nowrap']">
                  <span :class="['px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(rtu.rtu_status_color)]">
                    {{ t(rtu.rtu_status_text) }}
                  </span>
                </td>
                <td :class="['px-6', 'py-4', 'whitespace-nowrap']">
                  <span :class="['px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(rtu.connection_status_color)]">
                    {{ t(rtu.connection_status_text) }}
                  </span>
                </td>
                <td :class="['px-6', 'py-4', 'whitespace-nowrap', theme.tableCellText]">
                  {{ (rtu.percentage_day || 0).toFixed(1) }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="activeTab === 'overall_stats'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl', 'print:hidden']">
        <h2 :class="['text-2xl', 'font-semibold', 'mb-4', theme.headerText]">{{ t('Overall Statistics') }}</h2>
        <p v-if="loading" :class="[theme.text]">{{ t('Loading overall statistics...') }}</p>
        <p v-else-if="!overallStats" :class="[theme.text]">No overall statistics available.</p>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Total Stations') }}</h3>
            <p :class="['text-3xl', 'font-bold', theme.tableCellText]">{{ overallStats.total_stations_count || 0 }}</p>
          </div>
          
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Connected Stations') }}</h3>
            <p :class="['text-3xl', 'font-bold', 'text-green-500']">{{ overallStats.connected_stations_count || 0 }}</p>
          </div>
          
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Disconnected Stations') }}</h3>
            <p :class="['text-3xl', 'font-bold', 'text-red-500']">{{ overallStats.disconnected_stations_count || 0 }}</p>
          </div>

          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'md:col-span-2', 'lg:col-span-1']">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ t('Status Distribution') }}</h3>
            <ul>
              <li v-for="(count, status) in overallStats.status_distribution || {}" :key="status" :class="['flex', 'justify-between', 'items-center', 'py-1']">
                <span :class="['font-medium', theme.tableCellText]">{{ t(status) }}</span>
                <span :class="['px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(getDisplayColorForStatus(status))]">
                  {{ count }}
                </span>
              </li>
            </ul>
          </div>

          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'lg:col-span-1']">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ t('Average Daily Operation Percentage') }}</h3>
            <p :class="['text-3xl', 'font-bold', theme.tableCellText]">
              {{ (overallStats.average_daily_operation_percentage || 0).toFixed(1) }}%
            </p>
          </div>

          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.headerText, 'mb-2']">{{ t('Top 5 Performing Stations') }}</h3>
            
            <div v-if="overallStats.top_performing_stations && overallStats.top_performing_stations.length > 0">
              <ol class="list-decimal list-inside space-y-2">
                <li v-for="station in overallStats.top_performing_stations" :key="station.station_id" 
                    :class="['py-1', 'px-2', 'rounded', theme.tableCellText]">
                  {{ locale === 'ar' ? station.arabic_name : station.english_name }} (ID: {{ station.rtu_data_id }})
                  <span class="font-bold text-green-500 ml-2">
                    ({{ (station.percentage_day || 0).toFixed(1) }}%)
                  </span>
                </li>
              </ol>
            </div>
            
            <div v-else :class="['p-2', 'text-center', theme.mutedText]">
              {{ t('No data available for top performing stations.') }}
            </div>
          </div>

          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ t('Bottom Performing Stations') }}</h3>
            <ul v-if="overallStats.bottom_performing_stations && overallStats.bottom_performing_stations.length">
              <li v-for="station in overallStats.bottom_performing_stations" :key="station.station_id" :class="['flex', 'justify-between', 'items-center', 'py-1', theme.tableCellText]">
                <span>{{ locale === 'ar' ? station.arabic_name : station.english_name }} (ID: {{ station.rtu_data_id }})</span>
                <span class="font-bold text-red-500">
                  {{ (station.percentage_day || 0).toFixed(1) }}%
                </span>
              </li>
            </ul>
            <p v-else :class="[theme.mutedText]">{{ t('No data available for bottom performing stations.') }}</p>
          </div>
        </div>
      </div>
    </main>

    <footer :class="['py-4', 'mt-8', theme.footerBg, 'text-center', 'rounded-t-lg', 'mx-auto', 'max-w-7xl', 'print:hidden']">
      <p :class="['text-sm', theme.footerText]">{{ t('Operations and Control Department') }}</p>
    </footer>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'; // تم إضافة computed
import { useI18n } from 'vue-i18n';
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
    const { t, locale } = useI18n();

    // نقل جميع حالات البيانات والمتغيرات التفاعلية إلى setup
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
    const selectedLocale = ref('en');

    const tabs = ref([
      { id: 'live_status', name: 'Live Status' },
      { id: 'overall_stats', name: 'Overall Statistics' }
    ]);

    const themeDefinitions = { // تم تغيير الاسم لتجنب تضارب محتمل مع 'theme' المرجعة
      light: {
        background: 'bg-gray-100',
        text: 'text-gray-800',
        mutedText: 'text-gray-600',
        headerBg: 'bg-white',
        headerText: 'text-gray-900',
        tabBg: 'bg-gray-200',
        activeTabBg: 'bg-blue-600',
        activeTabText: 'text-white',
        inactiveTabBg: 'bg-gray-200',
        inactiveTabText: 'text-gray-700',
        buttonBg: 'bg-gray-300',
        buttonText: 'text-gray-800',
        selectBg: 'bg-gray-50',
        selectText: 'text-gray-900',
        selectBorder: 'border-gray-300',
        focusRing: 'focus:ring-blue-500',
        cardBg: 'bg-white',
        divideColor: 'divide-gray-200',
        tableHeaderBg: 'bg-gray-50',
        tableHeaderText: 'text-gray-500',
        tableRowBg: 'bg-white',
        tableCellText: 'text-gray-900',
        footerBg: 'bg-white',
        footerText: 'text-gray-600',
      },
      dark: {
        background: 'bg-gray-900',
        text: 'text-gray-100',
        mutedText: 'text-gray-400',
        headerBg: 'bg-gray-800',
        headerText: 'text-gray-50',
        tabBg: 'bg-gray-700',
        activeTabBg: 'bg-blue-700',
        activeTabText: 'text-white',
        inactiveTabBg: 'bg-gray-700',
        inactiveTabText: 'text-gray-200',
        buttonBg: 'bg-gray-700',
        buttonText: 'text-gray-50',
        selectBg: 'bg-gray-800',
        selectText: 'text-gray-50',
        selectBorder: 'border-gray-600',
        focusRing: 'focus:ring-blue-600',
        cardBg: 'bg-gray-800',
        divideColor: 'divide-gray-700',
        tableHeaderBg: 'bg-gray-700',
        tableHeaderText: 'text-gray-300',
        tableRowBg: 'bg-gray-800',
        tableCellText: 'text-gray-100',
        footerBg: 'bg-gray-800',
        footerText: 'text-gray-400',
      }
    };
    const currentTheme = ref('dark');

    // استخدام computed property لـ theme
    const theme = computed(() => themeDefinitions[currentTheme.value]);

    const changeLanguage = (event) => {
      locale.value = event.target.value;
      localStorage.setItem('locale', event.target.value);
      
      const newLang = event.target.value;
      document.documentElement.setAttribute('lang', newLang);
      document.documentElement.setAttribute('dir', newLang === 'ar' ? 'rtl' : 'ltr');
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

    const fetchData = async () => {
      try {
        loading.value = true;
        const response = await fetch('/rtu-data'); 
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        liveData.value = (data.stations_data || []).map(item => ({
          station_id: item.station_id,
          rtu_data_id: item.rtu_data_id,
          arabic_name: item.arabic_name,
          english_name: item.english_name,
          rtu_status_text: item.rtu_status_text || 'Unknown',
          rtu_status_color: item.rtu_status_color || 'gray',
          connection_status_text: item.connection_status_text || 'Unknown',
          connection_status_color: item.connection_status_color || 'gray',
          percentage_day: parseFloat(item.percentage_day) || 0 
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

    const exportToExcel = async () => {
        try {
            const response = await fetch('/export-rtu-data-excel');
            if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');

            const now = new Date();
            const dateString = now.getFullYear() + '-' + 
                                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(now.getDate()).padStart(2, '0');
            const timeString = String(now.getHours()).padStart(2, '0') + 
                                String(now.getMinutes()).padStart(2, '0') + 
                                String(now.getSeconds()).padStart(2, '0');
            const filename = `rtu_live_status_excel_${dateString}_${timeString}.xlsx`;

            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error("Error exporting Excel:", error);
            alert(t('Failed to export Excel file. Please try again.'));
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
        selectedLocale.value = savedLocale;
        document.documentElement.setAttribute('lang', savedLocale);
        document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr');
      } else {
          selectedLocale.value = locale.value;
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

    // ارجع جميع المتغيرات والوظائف لتكون متاحة في القالب
    return {
      t,
      locale,
      activeTab,
      loading,
      dataLoaded,
      liveData,
      overallStats,
      selectedLocale,
      currentTheme,
      tabs,
      theme, // الآن يتم إرجاع Computed property 'theme'
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

<style scoped>
/* أضف أي أنماط Tailwind CSS مخصصة هنا إذا لزم الأمر */
/* مثال:
@media print {
  body * {
    visibility: hidden;
  }
  .live-status-print-area, .live-status-print-area * {
    visibility: visible;
  }
  .live-status-print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 20px;
  }
  .print-only-title {
    display: block !important;
  }
  .print-only-message {
    display: block !important;
  }
}
*/
</style>