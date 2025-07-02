<template>
  <div class="container mt-5">
    <h2 class="mb-4 text-center">لوحة مراقبة المحطات</h2>
    <table class="table table-bordered table-striped text-center">
      <thead class="thead-dark">
        <tr>
          <th>رقم المحطة</th>
          <th>الاسم العربي</th>
          <th>الاسم الإنجليزي</th>
          <th>الحالة</th>
          <th>الاتصال</th>
          <th>آخر وقت اتصال</th>
          <th>نسبة التشغيل %</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="station in stations" :key="station.station_id">
          <td>{{ station.station_id }}</td>
          <td>{{ station.station_name_ar || '---' }}</td>
          <td>{{ station.station_name_en || '---' }}</td>
          <td>{{ station.status_description }}</td>
          <td :class="station.connection_status === 'On' ? 'text-success' : 'text-danger'">
            {{ station.connection_status }}
          </td>
          <td>{{ station.last_seen }}</td>
          <td>{{ station.operation_percentage }}%</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  data() {
    return {
      stations: [],
    };
  },
  mounted() {
    this.fetchStations();
    setInterval(this.fetchStations, 60000); // تحديث كل دقيقة
  },
  methods: {
    fetchStations() {
      fetch('/rtu-dashboard')
        .then(res => res.json())
        .then(data => {
          this.stations = data;
        });
    },
  },
};
</script>

<style scoped>
.table {
  direction: rtl;
  font-family: 'Tajawal', sans-serif;
}
</style>
