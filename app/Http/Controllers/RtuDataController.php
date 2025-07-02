<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RtuData;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection; // تأكد من وجود هذا الاستخدام

class RtuDataController extends Controller
{
    public function index()
    {
        // 1. تحديد قائمة بجميع station_id المطلوبة بشكل صريح (64 محطة).
        $expectedStationIds = array_merge(range(450, 511), [63, 64]);

        // 2. جلب جميع المحطات التي يمكن لـ Laravel رؤيتها من جدول stations_new.
        // سنقوم بفلترتها لاحقاً لضمان وجود الـ 64 فقط.
        $fetchedStations = Station::whereIn('station_id', $expectedStationIds) // لا يزال نستخدم whereIn لتقليل حجم الجلب
                                  ->orderBy('station_id')
                                  ->get()
                                  ->keyBy('station_id'); // نستخدم keyBy هنا للوصول السريع

        // 3. بناء مجموعة المحطات النهائية لضمان وجود الـ 64 محطة.
        $allStations = new Collection();
        foreach ($expectedStationIds as $stationId) {
            if ($fetchedStations->has($stationId)) {
                // إذا كانت المحطة موجودة في النتائج المجلوبة، أضفها.
                $allStations->put($stationId, $fetchedStations->get($stationId));
            } else {
                // إذا كانت المحطة مفقودة (مثل 63 أو 64)، قم بإنشاء كائن وهمي لها.
                $placeholderStation = new Station();
                $placeholderStation->station_id = $stationId;
                
                // تعيين أسماء افتراضية أو محددة للمحطات المفقودة
                if ($stationId == 63) {
                    $placeholderStation->name = 'DWNG';
                    $placeholderStation->arabic_name = 'داونج';
                } elseif ($stationId == 64) {
                    $placeholderStation->name = 'STBGC';
                    $placeholderStation->arabic_name = 'ستابجك';
                } else {
                    $placeholderStation->name = 'Station ' . $stationId;
                    $placeholderStation->arabic_name = 'محطة ' . $stationId;
                }
                
                $allStations->put($stationId, $placeholderStation);
            }
        }

        Log::info('Stations fetched from stations_new table (after placeholder check): ' . $allStations->count());

        // 4. إنشاء خريطة لربط station_id (من stations_new) بـ address (من rtu_data).
        $stationRtuAddressMap = $allStations->mapWithKeys(function ($station) {
            $rtuAddress = $station->station_id;

            // تعديل خاص للمحطات التي لديها عدم تطابق في المعرفات
            if ($station->station_id == 63) { // DWNG
                $rtuAddress = 4207; // الـ address الفعلي لـ DWNG في rtu_data
            } elseif ($station->station_id == 64) { // STBGC
                $rtuAddress = 4208; // الـ address الفعلي لـ STBGC في rtu_data
            }
            return [$station->station_id => $rtuAddress];
        });

        // 5. جلب البيانات الحية من جدول `rtu_data` باستخدام الـ addresses الصحيحة.
        $rtuAddressesToFetchForLiveData = $stationRtuAddressMap->values()->unique()->toArray();
        Log::info('RTU Addresses to fetch: ' . implode(', ', $rtuAddressesToFetchForLiveData));

        $liveRtuData = RtuData::whereIn('address', $rtuAddressesToFetchForLiveData)
                              ->get()
                              ->keyBy('address'); // المفتاح هنا هو الـ address من rtu_data

        // 6. دمج جميع البيانات في تنسيق واحد للواجهة الأمامية
        $formattedLiveData = $allStations->map(function ($station) use ($liveRtuData, $stationRtuAddressMap) {
            // استخدام الـ address الصحيح من الخريطة لجلب بيانات RTU الحية
            $actualRtuAddress = $stationRtuAddressMap->get($station->station_id);
            $rtu = $liveRtuData->get($actualRtuAddress);
            
            $totalValue = 0.0;
            $statusCode = 0;
            $statusDescription = 'No Data';
            $connectionStatus = 'Off (No Data)';
            $percentageDay = 0.00;
            $rtuDataId = null;

            if ($rtu) {
                $rtuDataId = $rtu->id;
                $isRecent = ($rtu->lastmodified && Carbon::parse($rtu->lastmodified)->gt(Carbon::now()->subMinutes(5)));

                $statusCode = (int)$rtu->status_rtu;
                $totalValue = (float)$rtu->status_Count;
                $percentageDay = (float)$rtu->percentage_day ?? 0.00; 

                if ($isRecent) {
                    $connectionStatus = 'On';
                } else {
                    $connectionStatus = 'Off (No Data)';
                }

                switch ($statusCode) {
                    case 1: $statusDescription = 'Normal'; break;
                    case 2: $statusDescription = 'Failed'; break;
                    case 3: $statusDescription = 'Marginal'; break;
                    case 4: $statusDescription = 'Alarm'; break;
                    default: $statusDescription = 'Unknown'; break;
                }
            }

            $statusColor = 'gray';
            switch ($statusDescription) {
                case 'Normal': $statusColor = 'green'; break;
                case 'Failed': $statusColor = 'red'; break;
                case 'Marginal': $statusColor = 'orange'; break;
                case 'Alarm': $statusColor = 'blue'; break;
                case 'Off (No Data)': $statusColor = 'red'; break;
                default: $statusColor = 'gray'; break;
            }

            $connectionStatusColor = 'gray';
            switch ($connectionStatus) {
                case 'On': $connectionStatusColor = 'green'; break;
                case 'Off': $connectionStatusColor = 'red'; break;
                case 'Off (No Data)': $connectionStatusColor = 'red'; break;
                default: $connectionStatusColor = 'gray'; break;
            }

            return [
                'station_id' => $station->station_id,
                'rtu_data_id' => $rtuDataId,
                'arabic_name' => $station->arabic_name,
                'english_name' => $station->name,
                'rtu_status_text' => str_replace([' ', '(', ')'], '', $statusDescription),
                'rtu_status_color' => $statusColor,
                'connection_status_text' => str_replace([' ', '(', ')'], '', $connectionStatus),
                'connection_status_color' => $connectionStatusColor,
                'percentage_day' => $percentageDay,
            ];
        })->values();

        Log::info('Formatted Live Data count before overall stats calculation: ' . $formattedLiveData->count());

        // حساب الإحصائيات العامة (تعتمد على البيانات الحية فقط)
        $totalStationsCount = $formattedLiveData->count();
        $connectedStationsCount = $formattedLiveData->where('connection_status_text', 'On')->count();
        $disconnectedStationsCount = $totalStationsCount - $connectedStationsCount;

        $statusDistribution = [
            'Normal' => 0,
            'Failed' => 0,
            'Marginal' => 0,
            'Alarm' => 0,
            'Unknown' => 0,
            'OffNoData' => 0,
        ];
        foreach ($formattedLiveData as $item) {
            if (isset($statusDistribution[$item['rtu_status_text']])) {
                $statusDistribution[$item['rtu_status_text']]++;
            } else {
                $statusDistribution['Unknown']++;
            }
        }
        
        $validPercentageDayValues = $formattedLiveData->where('percentage_day', '>', 0)->pluck('percentage_day');
        $averageDailyOperationPercentage = $validPercentageDayValues->count() > 0 ? round($validPercentageDayValues->avg(), 2) : 0.00;

        $topPerformingStations = $formattedLiveData->sortByDesc('percentage_day')
                                                   ->take(5)
                                                   ->values()
                                                   ->map(function($item) {
                                                       return [
                                                           'station_id' => $item['station_id'],
                                                           'arabic_name' => $item['arabic_name'],
                                                           'english_name' => $item['english_name'],
                                                           'daily_operation_percentage' => $item['percentage_day']
                                                       ];
                                                   });

        $bottomPerformingStations = $formattedLiveData->sortBy('percentage_day')
                                                      ->take(5)
                                                      ->values()
                                                      ->map(function($item) {
                                                          return [
                                                              'station_id' => $item['station_id'],
                                                              'arabic_name' => $item['arabic_name'],
                                                              'english_name' => $item['english_name'],
                                                              'daily_operation_percentage' => $item['percentage_day']
                                                          ];
                                                      });


        return response()->json([
            'stations_data' => $formattedLiveData,
            'overall_stats' => [
                'total_stations_count' => $totalStationsCount,
                'connected_stations_count' => $connectedStationsCount,
                'disconnected_stations_count' => $disconnectedStationsCount,
                'status_distribution' => $statusDistribution,
                'average_daily_operation_percentage' => $averageDailyOperationPercentage,
                'top_performing_stations' => $topPerformingStations,
                'bottom_performing_stations' => $bottomPerformingStations,
            ]
        ]);
    }
}
