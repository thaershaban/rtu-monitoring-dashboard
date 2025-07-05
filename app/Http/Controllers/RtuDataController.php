<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RtuData;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // تأكد من وجود هذا الاستيراد
use Symfony\Component\HttpFoundation\StreamedResponse; // تأكد من وجود هذا الاستيراد

class RtuDataController extends Controller
{
    public function index()
    {
        $rtuDataEntries = RtuData::all();

        $stationsData = [];
        $connectedStationsCount = 0;
        $disconnectedStationsCount = 0;
        $totalDailyOperationPercentage = 0;
        $stationsWithOperationDataCount = 0;
        $statusDistribution = [
            'Normal' => 0,
            'Failed' => 0,
            'Marginal' => 0,
            'Alarm' => 0,
            'OffNoData' => 0,
            'Unknown' => 0,
        ];

        foreach ($rtuDataEntries as $rtu) {
            $rtuId = $rtu->id;
            $arabicName = $rtu->Arabic_Names;
            $englishName = $rtu->name;

            $rtuStatusText = 'Unknown';
            $rtuStatusColor = 'gray';
            $connectionStatusText = 'OffNoData';
            $connectionStatusColor = 'red';
            // تأكد من أن percentage_day يتم تحويله إلى float بشكل صحيح
            $percentageDay = (float)$rtu->percentage_day ?? 0.00;

            // تحديد ما إذا كانت البيانات حديثة (آخر 5 دقائق) لتحديد حالة الاتصال
            $isRecent = ($rtu->lastmodified && Carbon::parse($rtu->lastmodified)->gt(Carbon::now()->subMinutes(5)));

            // تحديد حالة RTU بناءً على status_rtu
            switch ((int)$rtu->status_rtu) {
                case 1: $rtuStatusText = 'Normal'; break;
                case 2: $rtuStatusText = 'Failed'; break;
                case 3: $rtuStatusText = 'Marginal'; break;
                case 4: $rtuStatusText = 'Alarm'; break;
                default: $rtuStatusText = 'Unknown'; break;
            }

            // تحديد لون حالة RTU
            switch ($rtuStatusText) {
                case 'Normal': $rtuStatusColor = 'green'; break;
                case 'Failed': $rtuStatusColor = 'red'; break;
                case 'Marginal': $rtuStatusColor = 'orange'; break;
                case 'Alarm': $rtuStatusColor = 'blue'; break;
                default: $rtuStatusColor = 'gray'; break;
            }

            // تحديد حالة الاتصال بناءً على الحداثة
            if ($isRecent) {
                $connectionStatusText = 'On';
                $connectionStatusColor = 'green';
            } else {
                $connectionStatusText = 'OffNoData';
                $connectionStatusColor = 'red';
            }

            // تحديث إحصائيات الاتصال
            if ($connectionStatusText === 'On') {
                $connectedStationsCount++;
            } else {
                $disconnectedStationsCount++;
            }

            // تحديث إحصائيات نسبة التشغيل اليومي
            $totalDailyOperationPercentage += $percentageDay;
            $stationsWithOperationDataCount++;

            // تحديث توزيع الحالات
            if (array_key_exists($rtuStatusText, $statusDistribution)) {
                $statusDistribution[$rtuStatusText]++;
            } else {
                $statusDistribution['Unknown']++;
            }
            
            // إضافة البيانات المنسقة للمحطة إلى مصفوفة stationsData
            $stationsData[] = [
                'station_id' => $rtuId, // استخدام id كمعرف للمحطة
                'rtu_data_id' => $rtuId, // هذا هو رقم RTU الذي سيظهر في الواجهة
                'arabic_name' => $arabicName,
                'english_name' => $englishName,
                'rtu_status_text' => $rtuStatusText,
                'rtu_status_color' => $rtuStatusColor,
                'connection_status_text' => $connectionStatusText,
                'connection_status_color' => $connectionStatusColor,
                'percentage_day' => $percentageDay, // قيمة نسبة التشغيل اليومي
            ];
        }

        // فرز stationsData حسب rtu_data_id (رقم RTU)
        usort($stationsData, function($a, $b) {
            return $a['rtu_data_id'] <=> $b['rtu_data_id'];
        });

        // حساب الإحصائيات العامة
        $totalStationsCount = count($rtuDataEntries); // عدد السجلات في rtu_data هو إجمالي المحطات
        $averageDailyOperationPercentage = $stationsWithOperationDataCount > 0
            ? $totalDailyOperationPercentage / $stationsWithOperationDataCount
            : 0;

        // المحطات الأعلى أداءً (بناءً على percentage_day تنازلياً)
        $topPerformingStations = collect($stationsData)
            ->sortByDesc('percentage_day') // فرز تنازلياً
            ->take(5) // أخذ أول 5
            ->values() // إعادة فهرسة المفاتيح
            ->toArray();

        // المحطات الأدنى أداءً (بناءً على percentage_day تصاعدياً)
        $bottomPerformingStations = collect($stationsData)
            ->sortBy('percentage_day') // فرز تصاعدياً
            ->take(5) // أخذ أول 5
            ->values() // إعادة فهرسة المفاتيح
            ->toArray();

        // تجميع جميع الإحصائيات في مصفوفة واحدة
        $overallStats = [
            'total_stations_count' => $totalStationsCount,
            'connected_stations_count' => $connectedStationsCount,
            'disconnected_stations_count' => $disconnectedStationsCount,
            'status_distribution' => $statusDistribution,
            'average_daily_operation_percentage' => $averageDailyOperationPercentage,
            'top_performing_stations' => $topPerformingStations,
            'bottom_performing_stations' => $bottomPerformingStations,
        ];

        // *** أسطر التسجيل لتتبع البيانات المرسلة إلى الواجهة الأمامية ***
        Log::info('Overall Stats being sent:', $overallStats);
        Log::info('Top Performing Stations (Backend):', $topPerformingStations);
        Log::info('Bottom Performing Stations (Backend):', $bottomPerformingStations);
        // ************************************************************

        // إرجاع البيانات كاستجابة JSON
        return response()->json([
            'stations_data' => $stationsData,
            'overall_stats' => $overallStats,
        ]);
    }

    /**
     * Export RTU data to CSV.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportRtuData()
    {
        try {
            $rtuDataEntries = RtuData::all();

            $csvData = [];
            $csvData[] = [
                'RTU NUMBER',
                'STATION NAME (English)',
                'STATION NAME (Arabic)',
                'RTU STATUS',
                'CONNECTION STATUS',
                'PERCENTAGE DAY'
            ];

            foreach ($rtuDataEntries as $rtu) {
                $rtuStatusText = 'Unknown';
                $connectionStatusText = 'OffNoData';
                $percentageDay = (float)$rtu->percentage_day ?? 0.00;

                $isRecent = ($rtu->lastmodified && Carbon::parse($rtu->lastmodified)->gt(Carbon::now()->subMinutes(5)));

                switch ((int)$rtu->status_rtu) {
                    case 1: $rtuStatusText = 'Normal'; break;
                    case 2: $rtuStatusText = 'Failed'; break;
                    case 3: $rtuStatusText = 'Marginal'; break;
                    case 4: $rtuStatusText = 'Alarm'; break;
                    default: $rtuStatusText = 'Unknown'; break;
                }

                if ($isRecent) {
                    $connectionStatusText = 'On';
                } else {
                    $connectionStatusText = 'OffNoData';
                }

                $csvData[] = [
                    $rtu->id, // استخدام id كـ RTU NUMBER
                    $rtu->name,
                    $rtu->Arabic_Names,
                    $rtuStatusText,
                    $connectionStatusText,
                    round($percentageDay, 1) . '%'
                ];
            }

            // فرز حسب رقم RTU (مع تخطي صف الرأس)
            usort($csvData, function($a, $b) {
                if (!is_numeric($a[0]) || !is_numeric($b[0])) return 0;
                return $a[0] <=> $b[0];
            });

            $filename = 'rtu_live_status_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

            $handle = fopen('php://temp', 'r+');
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            rewind($handle);
            $contents = stream_get_contents($handle);
            fclose($handle);

            return response($contents)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error("CSV Export failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return response()->json(['error' => 'Failed to export CSV data. An internal error occurred.'], 500);
        }
    }

    /**
     * Export RTU data to Excel (XLSX).
     *
     * @return \Illuminate\Http\Response
     */
    public function exportRtuDataExcel()
    {
        // زيادة حد الذاكرة ووقت التنفيذ لهذه الدالة فقط
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        try {
            $rtuDataEntries = RtuData::all();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('RTU Live Status');

            // تعيين الرؤوس
            $headers = [
                'RTU NUMBER',
                'STATION NAME (English)',
                'STATION NAME (Arabic)',
                'RTU STATUS',
                'CONNECTION STATUS',
                'PERCENTAGE DAY'
            ];
            $sheet->fromArray($headers, NULL, 'A1');

            // تنسيق الرؤوس
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD'], // أزرق داكن
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

            $currentRow = 1; // يبدأ من 1 لأن الصف الأول هو الرؤوس
            foreach ($rtuDataEntries as $rtu) {
                $currentRow++; // زيادة الصف لكل سجل
                $rtuStatusText = 'Unknown';
                $connectionStatusText = 'OffNoData';
                $percentageDay = (float)$rtu->percentage_day ?? 0.00;

                $isRecent = ($rtu->lastmodified && Carbon::parse($rtu->lastmodified)->gt(Carbon::now()->subMinutes(5)));

                switch ((int)$rtu->status_rtu) {
                    case 1: $rtuStatusText = 'Normal'; break;
                    case 2: $rtuStatusText = 'Failed'; break;
                    case 3: $rtuStatusText = 'Marginal'; break;
                    case 4: $rtuStatusText = 'Alarm'; break;
                    default: $rtuStatusText = 'Unknown'; break;
                }

                // تحديد لون حالة RTU (للتنسيق في Excel)
                $rtuStatusColorHex = 'DCDCDC'; // Light Gray default
                switch ($rtuStatusText) {
                    case 'Normal': $rtuStatusColorHex = 'C6EFCE'; break; // Light Green
                    case 'Failed': $rtuStatusColorHex = 'FFC7CE'; break; // Light Red
                    case 'Marginal': $rtuStatusColorHex = 'FFEB9C'; break; // Light Orange
                    case 'Alarm': $rtuStatusColorHex = 'ADD8E6'; break; // Light Blue
                }

                if ($isRecent) {
                    $connectionStatusText = 'On';
                } else {
                    $connectionStatusText = 'OffNoData';
                }

                // تحديد لون حالة الاتصال (للتنسيق في Excel)
                $connectionStatusColorHex = 'DCDCDC'; // Light Gray default
                switch ($connectionStatusText) {
                    case 'On': $connectionStatusColorHex = 'C6EFCE'; break; // Light Green
                    case 'OffNoData': $connectionStatusColorHex = 'FFC7CE'; break; // Light Red
                }

                $sheet->setCellValue('A' . $currentRow, $rtu->id);
                $sheet->setCellValue('B' . $currentRow, $rtu->name);
                $sheet->setCellValue('C' . $currentRow, $rtu->Arabic_Names);
                $sheet->setCellValue('D' . $currentRow, $rtuStatusText);
                $sheet->setCellValue('E' . $currentRow, $connectionStatusText);
                $sheet->setCellValue('F' . $currentRow, round($percentageDay, 1) . '%');

                // تطبيق الألوان على الخلايا
                $sheet->getStyle('D' . $currentRow)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $rtuStatusColorHex]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
                ]);
                $sheet->getStyle('E' . $currentRow)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . $connectionStatusColorHex]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
                ]);
                // تطبيق الحدود على باقي الخلايا
                $sheet->getStyle('A' . $currentRow . ':C' . $currentRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
                ]);
                $sheet->getStyle('F' . $currentRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
                ]);
            }

            // ضبط حجم الأعمدة تلقائياً
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // إعدادات الطباعة
            $sheet->getPageSetup()->setPrintArea('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow());
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(1);
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageMargins()->setTop(0.25);
            $sheet->getPageMargins()->setBottom(0.25);
            $sheet->getPageMargins()->setLeft(0.25);
            $sheet->getPageMargins()->setRight(0.25);
            $sheet->setShowGridlines(false);

            $filename = 'rtu_live_status_excel_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
            $writer = new Xlsx($spreadsheet);

            // مسح أي buffers إخراج سابقة
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Exception $e) {
            // تسجيل الخطأ بتفاصيل أكثر
            Log::error("Excel Export failed: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to export Excel data. An internal server error occurred. Please check server logs for details.'], 500);
        }
    }
}
