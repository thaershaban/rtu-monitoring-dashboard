<template>
  <div :class="['min-h-screen', theme.background, theme.text]">
    <!-- Header Section -->
    <header :class="['py-4', theme.headerBg, 'shadow-md', 'rounded-b-lg', 'sticky', 'top-0', 'z-50', 'mx-auto', 'max-w-7xl', 'px-4', 'sm:px-6', 'lg:px-8', 'print:hidden']">
      <div class="flex items-center justify-between">
        <h1 :class="['text-3xl', 'font-bold', theme.headerText]">{{ $t('RTU Monitoring Dashboard') }}</h1>
        <div class="flex items-center space-x-4">
          <!-- Language Switch -->
          <select v-model="$i18n.locale" @change="changeLanguage" :class="['px-3', 'py-2', 'rounded-md', theme.selectBg, theme.selectText, theme.selectBorder, 'focus:outline-none', 'focus:ring-2', theme.focusRing]">
            <option value="en">English</option>
            <option value="ar">العربية</option>
          </select>

          <!-- Theme Switch Button -->
          <button @click="toggleTheme" :class="['p-2', 'rounded-full', theme.buttonBg, theme.buttonText, 'hover:brightness-110', 'transition-all', 'duration-300']">
            <template v-if="currentTheme === 'light'">
              <lucide-sun :class="['w-6', 'h-6', theme.buttonText]" />
            </template>
            <template v-else>
              <lucide-moon :class="['w-6', 'h-6', theme.buttonText]" />
            </template>
          </button>
        </div>
      </div>
      <!-- Tab Navigation -->
      <nav :class="['mt-4', 'flex', 'space-x-4', 'justify-center', 'p-2', 'rounded-lg', theme.tabBg]">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200',
                          activeTab === tab.id ? theme.activeTabBg : theme.inactiveTabBg,
                          activeTab === tab.id ? theme.activeTabText : theme.inactiveTabText,
                          'hover:bg-opacity-80']">
          {{ $t(tab.name) }}
        </button>
      </nav>
    </header>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8 max-w-7xl">
      <!-- Welcome Message -->
      <div v-if="!dataLoaded" class="text-center py-10">
        <p :class="['text-xl', 'font-semibold', theme.text]">{{ $t('Welcome to RTU Archive') }}...</p>
        <p :class="['text-sm', theme.mutedText]">{{ $t('Loading data, please wait.') }}</p>
      </div>

      <!-- Live Status Tab Content -->
      <div v-if="activeTab === 'live_status'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl']">
        <h2 :class="['text-2xl', 'font-semibold', 'mb-4', theme.headerText]">{{ $t('Live RTU Status') }}</h2>
        
        <!-- Print Button -->
        <div class="flex justify-end mb-4 print:hidden">
            <button @click="printLiveStatus" :class="['px-4', 'py-2', 'rounded-md', 'font-medium', 'transition-colors', 'duration-200', theme.buttonBg, theme.buttonText, 'hover:brightness-110']">
                <lucide-printer :class="['inline-block', 'w-5', 'h-5', 'mr-2']" /> {{ $t('Print') }}
            </button>
        </div>

        <p v-if="loading" :class="[theme.text]">Loading live data...</p>
        <p v-else-if="!liveData.length" :class="[theme.text]">{{ $t('No live data available for stations 450-511') }}</p>
        <div v-else class="overflow-x-auto rounded-lg shadow-inner">
          <table :class="['min-w-full', 'divide-y', theme.divideColor]">
            <thead :class="[theme.tableHeaderBg]">
              <tr>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ $t('RTU Number') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ $t('Station Name') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ $t('RTU Status') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ $t('Connection Status') }}</th>
                <th :class="['px-6', 'py-3', 'text-left', 'text-xs', 'font-medium', theme.tableHeaderText, 'uppercase', 'tracking-wider']">{{ $t('Percentage Day') }}</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', theme.divideColor, theme.tableRowBg]">
              <tr v-for="rtu in liveData" :key="rtu.station_id">
                <td :class="['px-6', 'py-4', 'whitespace-nowrap', theme.tableCellText]">{{ rtu.rtu_data_id }}</td>
                <td :class="['px-6', 'py-4', 'whitespace-nowrap', theme.tableCellText]">{{ $i18n.locale === 'ar' ? rtu.arabic_name : rtu.english_name }}</td>
                <td :class="['px-6', 'py-4', 'whitespace-nowrap']">
                  <span :class="['px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(rtu.rtu_status_color)]">
                    {{ $t(rtu.rtu_status_text) }}
                  </span>
                </td>
                <td :class="['px-6', 'py-4', 'whitespace-nowrap']">
                  <span :class="['px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(rtu.connection_status_color)]">
                    {{ $t(rtu.connection_status_text) }}
                  </span>
                </td>
                <!-- Percentage Day with one decimal place -->
                <td :class="['px-6', 'py-4', 'whitespace-nowrap', theme.tableCellText]">{{ rtu.percentage_day.toFixed(1) }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Overall Statistics Tab Content -->
      <div v-if="activeTab === 'overall_stats'" :class="[theme.cardBg, 'p-6', 'rounded-lg', 'shadow-xl', 'print:hidden']">
        <h2 :class="['text-2xl', 'font-semibold', 'mb-4', theme.headerText]">{{ $t('Overall Statistics') }}</h2>
        <p v-if="loading" :class="[theme.text]">Loading overall statistics...</p>
        <p v-else-if="!overallStats" :class="[theme.text]">No overall statistics available.</p>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Total Stations -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ $t('Total Stations') }}</h3>
            <p :class="['text-3xl', 'font-bold', theme.tableCellText]">{{ overallStats.total_stations_count }}</p>
          </div>
          <!-- Connected Stations -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ $t('Connected Stations') }}</h3>
            <p :class="['text-3xl', 'font-bold', 'text-green-500']">{{ overallStats.connected_stations_count }}</p>
          </div>
          <!-- Disconnected Stations -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ $t('Disconnected Stations') }}</h3>
            <p :class="['text-3xl', 'font-bold', 'text-red-500']">{{ overallStats.disconnected_stations_count }}</p>
          </div>

          <!-- Status Distribution -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'md:col-span-2', 'lg:col-span-1']">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ $t('Status Distribution') }}</h3>
            <ul>
              <li v-for="(count, status) in overallStats.status_distribution" :key="status" :class="['flex', 'justify-between', 'items-center', 'py-1']">
                <span :class="['font-medium', theme.tableCellText]">{{ $t(status) }}</span>
                <span :class="['px-2', 'inline-flex', 'text-xs', 'leading-5', 'font-semibold', 'rounded-full', getStatusClass(getDisplayColorForStatus(status))]">
                  {{ count }}
                </span>
              </li>
            </ul>
          </div>

          <!-- Average Daily Operation Percentage -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg, 'lg:col-span-1']">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText]">{{ $t('Average Daily Operation Percentage') }}</h3>
            <p :class="['text-3xl', 'font-bold', theme.tableCellText]">{{ overallStats.average_daily_operation_percentage.toFixed(1) }}%</p>
          </div>

          <!-- Top Performing Stations -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ $t('Top Performing Stations') }}</h3>
            <ul v-if="overallStats.top_performing_stations.length">
              <li v-for="station in overallStats.top_performing_stations" :key="station.station_id" :class="['flex', 'justify-between', 'items-center', 'py-1', theme.tableCellText]">
                <span>{{ $i18n.locale === 'ar' ? station.arabic_name : station.english_name }} (ID: {{ station.station_id }})</span>
                <span class="font-bold text-green-500">{{ station.daily_operation_percentage.toFixed(1) }}%</span>
              </li>
            </ul>
            <p v-else :class="[theme.mutedText]">{{ $t('No data available for top performing stations.') }}</p>
          </div>

          <!-- Bottom Performing Stations -->
          <div :class="['p-4', 'rounded-lg', 'shadow-md', theme.tableHeaderBg]">
            <h3 :class="['text-lg', 'font-medium', theme.tableHeaderText, 'mb-2']">{{ $t('Bottom Performing Stations') }}</h3>
            <ul v-if="overallStats.bottom_performing_stations.length">
              <li v-for="station in overallStats.bottom_performing_stations" :key="station.station_id" :class="['flex', 'justify-between', 'items-center', 'py-1', theme.tableCellText]">
                <span>{{ $i18n.locale === 'ar' ? station.arabic_name : station.english_name }} (ID: {{ station.station_id }})</span>
                <span class="font-bold text-red-500">{{ station.daily_operation_percentage.toFixed(1) }}%</span>
              </li>
            </ul>
            <p v-else :class="[theme.mutedText]">{{ $t('No data available for bottom performing stations.') }}</p>
          </div>

        </div>
      </div>
    </main>

    <!-- Footer Section -->
    <footer :class="['py-4', 'mt-8', theme.footerBg, 'text-center', 'rounded-t-lg', 'mx-auto', 'max-w-7xl', 'print:hidden']">
      <p :class="['text-sm', theme.footerText]">{{ $t('Operations and Control Department') }}</p>
    </footer>
  </div>
</template>

<script>
import { Sun as LucideSun, Moon as LucideMoon, Printer as LucidePrinter } from 'lucide-vue-next';
import { createI18n } from 'vue-i18n';

import en from './locales/en.json';
import ar from './locales/ar.json';

export const i18n = createI18n({
  locale: 'en',
  fallbackLocale: 'en',
  messages: {
    en,
    ar
  }
});

export default {
  name: 'App',
  components: {
    LucideSun,
    LucideMoon,
    LucidePrinter, // Import the Printer icon
  },
  data() {
    return {
      activeTab: 'live_status',
      loading: true,
      dataLoaded: false,
      liveData: [],
      overallStats: null,

      themes: {
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
      },
      currentTheme: 'dark',

      tabs: [
        { id: 'live_status', name: 'Live Status' },
        { id: 'overall_stats', name: 'Overall Statistics' }
      ],
    };
  },
  computed: {
    theme() {
      return this.themes[this.currentTheme];
    },
  },
  methods: {
    changeLanguage(event) {
      i18n.global.locale.value = event.target.value;
      localStorage.setItem('locale', event.target.value);
    },
    toggleTheme() {
      this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
      localStorage.setItem('theme', this.currentTheme);
    },
    getStatusClass(color) {
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
    },
    getDisplayColorForStatus(status) {
      switch (status) {
        case 'Normal': return 'Normal';
        case 'Failed': return 'Failed';
        case 'Marginal': return 'Marginal';
        case 'Alarm': return 'Alarm';
        case 'OffNoData': return 'OffNoData';
        case 'Unknown': return 'Unknown';
        default: return 'Unknown';
      }
    },
    async fetchData() {
      try {
        this.loading = true;
        const response = await fetch('/rtu-data');
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        this.liveData = data.stations_data.map(item => {
          return {
            station_id: item.station_id,
            rtu_data_id: item.rtu_data_id,
            arabic_name: item.arabic_name,
            english_name: item.english_name,
            rtu_status_text: item.rtu_status_text,
            rtu_status_color: item.rtu_status_color,
            connection_status_text: item.connection_status_text,
            connection_status_color: item.connection_status_color,
            total_value: item.total_value,
            rtu_operation_percentage: item.rtu_operation_percentage,
            percentage_day: item.percentage_day,
          };
        });

        this.overallStats = data.overall_stats;

        this.dataLoaded = true;
      } catch (error) {
        console.error("Error fetching data:", error);
      } finally {
        this.loading = false;
      }
    },
    startPolling() {
      this.fetchData();
      setInterval(this.fetchData, 60000);
    },
    printLiveStatus() {
      window.print();
    }
  },
  created() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      this.currentTheme = savedTheme;
    }

    const savedLocale = localStorage.getItem('locale');
    if (savedLocale) {
      i18n.global.locale.value = savedLocale;
    }

    this.startPolling();
  },
};
</script>

<style scoped>
/* Scoped styles for App.vue */

/* Print styles for responsive printing */
@media print {
  /* Hide header, footer, and other tabs */
  header, footer, .tabs, .print\:hidden {
    display: none !important;
  }

  /* Ensure main content takes full width */
  main {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
  }

  /* Ensure table and its content are fully visible */
  table {
    width: 100% !important;
    border-collapse: collapse !important;
  }

  th, td {
    border: 1px solid #ccc !important; /* Add borders for print clarity */
    padding: 8px !important;
    text-align: left !important;
    font-size: 10px !important; /* Smaller font for print */
  }

  /* Adjust text colors for print (usually black on white) */
  .text-gray-800, .text-gray-900, .text-gray-500, .text-gray-700, .text-gray-100 {
    color: #000 !important;
  }

  /* Ensure background colors are white for print */
  .bg-gray-100, .bg-white, .bg-gray-50, .bg-gray-200, .bg-gray-300, .bg-gray-700, .bg-gray-800, .bg-gray-900 {
    background-color: #fff !important;
  }

  /* Keep status colors but ensure readability */
  .bg-green-100 { background-color: #d4edda !important; color: #155724 !important; }
  .bg-red-100 { background-color: #f8d7da !important; color: #721c24 !important; }
  .bg-orange-100 { background-color: #ffeeba !important; color: #856404 !important; }
  .bg-blue-100 { background-color: #d1ecf1 !important; color: #0c5460 !important; }
  .bg-gray-100 { background-color: #f8f9fa !important; color: #343a40 !important; }

  /* Hide scrollbars for print */
  .overflow-x-auto {
    overflow: visible !important;
  }
}
</style>
