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
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RtuDataController extends Controller
{
    public function index()
    {
        try {
            $rtuDataEntries = RtuData::select([
                'id',
                'name',
                'Arabic_Names',
                'status_rtu',
                'percentage_day',
                'lastmodified'
            ])->get();

            if ($rtuDataEntries->isEmpty()) {
                return response()->json([
                    'stations_data' => [],
                    'overall_stats' => $this->emptyStats()
                ]);
            }

            $stationsData = [];
            $stats = $this->initializeStats();

            foreach ($rtuDataEntries as $rtu) {
                $processedData = $this->processRtuData($rtu);
                $stationsData[] = $processedData['station'];
                
                // Update stats
                $stats['connected'] += $processedData['is_connected'] ? 1 : 0;
                $stats['total_percentage'] += $processedData['station']['percentage_day'];
                if ($processedData['station']['percentage_day'] > 0) {
                    $stats['stations_with_data']++;
                }
                $stats['status_counts'][$processedData['station']['rtu_status_text']]++;
            }

            usort($stationsData, fn($a, $b) => $a['rtu_data_id'] <=> $b['rtu_data_id']);

            return response()->json([
                'stations_data' => $stationsData,
                'overall_stats' => $this->compileStats($stationsData, $stats)
            ]);

        } catch (\Exception $e) {
            Log::error("RTU Data API Error: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load RTU data',
                'details' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function exportToExcel()
    {
        return $this->exportRtuDataExcel();
    }

    public function exportRtuData()
    {
        try {
            $data = $this->prepareExportData();
            $filename = 'rtu_status_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(
                function () use ($data) {
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['RTU NUMBER', 'STATION NAME (English)', 'STATION NAME (Arabic)', 'RTU STATUS', 'PERCENTAGE DAY']);
                    foreach ($data as $item) {
                        fputcsv($output, [
                            $item['rtu_data_id'],
                            $item['english_name'],
                            $item['arabic_name'],
                            $item['rtu_status_text'],
                            round($item['percentage_day'], 1) . '%'
                        ]);
                    }
                    fclose($output);
                },
                $filename,
                ['Content-Type' => 'text/csv']
            );

        } catch (\Exception $e) {
            Log::error("CSV Export Error: " . $e->getMessage());
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    public function exportRtuDataExcel()
    {
        try {
            $data = $this->prepareExportData();
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('RTU Status');

            // Set headers
            $sheet->fromArray(['RTU NUMBER', 'STATION NAME (English)', 'STATION NAME (Arabic)', 'RTU STATUS', 'PERCENTAGE DAY'], null, 'A1');

            // Header style
            $sheet->getStyle('A1:E1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // Add data
            $row = 2;
            foreach ($data as $item) {
                $sheet->fromArray([
                    $item['rtu_data_id'],
                    $item['english_name'],
                    $item['arabic_name'],
                    $item['rtu_status_text'],
                    round($item['percentage_day'], 1) . '%'
                ], null, "A{$row}");

                // Apply status color
                $sheet->getStyle("D{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $this->getStatusColor($item['rtu_status_text'])]
                    ]
                ]);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'rtu_status_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';

            return response()->streamDownload(
                function () use ($spreadsheet) {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                },
                $filename,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );

        } catch (\Exception $e) {
            Log::error("Excel Export Error: " . $e->getMessage());
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    // Helper methods
    private function emptyStats()
    {
        return [
            'total_stations_count' => 0,
            'connected_stations_count' => 0,
            'disconnected_stations_count' => 0,
            'status_distribution' => [
                'Normal' => 0, 'Failed' => 0, 'Marginal' => 0,
                'Alarm' => 0, 'OffNoData' => 0, 'Unknown' => 0
            ],
            'average_daily_operation_percentage' => 0,
            'top_performing_stations' => [],
            'bottom_performing_stations' => []
        ];
    }

    private function initializeStats()
    {
        return [
            'connected' => 0,
            'total_percentage' => 0,
            'stations_with_data' => 0,
            'status_counts' => [
                'Normal' => 0, 'Failed' => 0, 'Marginal' => 0,
                'Alarm' => 0, 'OffNoData' => 0, 'Unknown' => 0
            ]
        ];
    }

    private function processRtuData($rtu)
    {
        $status = $this->determineRtuStatus($rtu->status_rtu ?? 0);
        $isConnected = $this->checkConnectionStatus($rtu->lastmodified ?? null);

        return [
            'station' => [
                'station_id' => $rtu->id ?? 0,
                'rtu_data_id' => $rtu->id ?? 0,
                'arabic_name' => $rtu->Arabic_Names ?? 'N/A',
                'english_name' => $rtu->name ?? 'N/A',
                'rtu_status_text' => $status['text'],
                'rtu_status_color' => $status['color'],
                'percentage_day' => (float)($rtu->percentage_day ?? 0)
            ],
            'is_connected' => $isConnected
        ];
    }

    private function determineRtuStatus($statusCode)
    {
        return match ((int)$statusCode) {
            1 => ['text' => 'Normal', 'color' => 'green'],
            2 => ['text' => 'Failed', 'color' => 'red'],
            3 => ['text' => 'Marginal', 'color' => 'orange'],
            4 => ['text' => 'Alarm', 'color' => 'blue'],
            default => ['text' => 'Unknown', 'color' => 'gray']
        };
    }

    private function checkConnectionStatus($lastModified)
    {
        return $lastModified && Carbon::parse($lastModified)->gt(Carbon::now()->subMinutes(5));
    }

    private function compileStats($stationsData, $stats)
    {
        $avgPercentage = $stats['stations_with_data'] > 0 
            ? $stats['total_percentage'] / $stats['stations_with_data'] 
            : 0;

        return [
            'total_stations_count' => count($stationsData),
            'connected_stations_count' => $stats['connected'],
            'disconnected_stations_count' => count($stationsData) - $stats['connected'],
            'status_distribution' => $stats['status_counts'],
            'average_daily_operation_percentage' => $avgPercentage,
            'top_performing_stations' => collect($stationsData)
                ->sortByDesc('percentage_day')
                ->take(5)
                ->values()
                ->toArray(),
            'bottom_performing_stations' => collect($stationsData)
                ->sortBy('percentage_day')
                ->take(5)
                ->values()
                ->toArray()
        ];
    }

    private function prepareExportData()
    {
        return RtuData::select(['id', 'name', 'Arabic_Names', 'status_rtu', 'percentage_day'])
            ->get()
            ->map(function ($rtu) {
                $status = $this->determineRtuStatus($rtu->status_rtu ?? 0);
                return [
                    'rtu_data_id' => $rtu->id ?? 0,
                    'english_name' => $rtu->name ?? 'N/A',
                    'arabic_name' => $rtu->Arabic_Names ?? 'N/A',
                    'rtu_status_text' => $status['text'],
                    'percentage_day' => (float)($rtu->percentage_day ?? 0)
                ];
            })
            ->toArray();
    }

    private function getStatusColor($statusText)
    {
        return match ($statusText) {
            'Normal' => 'C6EFCE',
            'Failed' => 'FFC7CE',
            'Marginal' => 'FFEB9C',
            'Alarm' => 'ADD8E6',
            default => 'DCDCDC'
        };
    }
}