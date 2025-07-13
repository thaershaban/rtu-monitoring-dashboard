<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RtuData; // افترض أن لديك نموذج (Model) باسم RtuData لجدول rtu-data
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
    /**
     * Handles the API request to get RTU live data and overall statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Select only the necessary columns, explicitly excluding connection status fields
            $rtuDataEntries = RtuData::select([
                'id',
                'name',
                'Arabic_Names',
                'status_rtu',
                'percentage_day',
                'lastmodified' // Keep lastmodified if it's used for any other logic (e.g., determining 'OffNoData' status)
            ])->get();

            // If no data is found, return empty data and stats
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
                
                // Update stats based on processed data
                $stats['total_percentage'] += $processedData['station']['percentage_day'];
                if ($processedData['station']['percentage_day'] > 0) {
                    $stats['stations_with_data']++;
                }
                // Increment status counts
                $stats['status_counts'][$processedData['station']['rtu_status_text']] = 
                    ($stats['status_counts'][$processedData['station']['rtu_status_text']] ?? 0) + 1;
            }

            // Sort stations data by rtu_data_id (which is 'id' from DB) for consistent ordering
            usort($stationsData, fn($a, $b) => $a['rtu_data_id'] <=> $b['rtu_data_id']);

            // Compile and return overall statistics
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

    /**
     * Exports RTU data to an Excel (XLSX) file.
     * This method is called by the frontend's exportToExcel button.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToExcel()
    {
        return $this->exportRtuDataExcel();
    }

    /**
     * Exports RTU data to a CSV file.
     * Note: The frontend currently calls exportToExcel(), so this method might not be directly used unless you change the frontend.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportRtuData()
    {
        try {
            $data = $this->prepareExportData();
            $filename = 'rtu_status_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(
                function () use ($data) {
                    $output = fopen('php://output', 'w');
                    // CSV Headers - 'Connection Status' column is already excluded here
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

    /**
     * Exports RTU data to an Excel (XLSX) file.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportRtuDataExcel()
    {
        try {
            $data = $this->prepareExportData();
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('RTU Status');

            // Set headers - 'Connection Status' column is already excluded here
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

                // Apply status color to the RTU STATUS column (D)
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

    /**
     * Initializes an empty statistics array for overall_stats.
     *
     * @return array
     */
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

    /**
     * Initializes the statistics tracking variables.
     * 'connected' is removed as it's no longer tracked directly.
     *
     * @return array
     */
    private function initializeStats()
    {
        return [
            'total_percentage' => 0,
            'stations_with_data' => 0,
            'status_counts' => [
                'Normal' => 0, 'Failed' => 0, 'Marginal' => 0,
                'Alarm' => 0, 'OffNoData' => 0, 'Unknown' => 0
            ]
        ];
    }

    /**
     * Processes a single RTU data entry from the database.
     * Removes 'is_connected' calculation and related fields from the station array.
     *
     * @param \App\Models\RtuData $rtu
     * @return array
     */
    private function processRtuData($rtu)
    {
        // Determine RTU status (text and color) based on status_rtu code
        $status = $this->determineRtuStatus($rtu->status_rtu ?? 0);
        
        // Removed checkConnectionStatus as it's no longer used for 'Connection Status' column
        // Removed 'is_connected' from the returned array

        return [
            'station' => [
                'station_id' => $rtu->id ?? 0,
                'rtu_data_id' => $rtu->id ?? 0, // Assuming rtu_data_id is the same as id
                'arabic_name' => $rtu->Arabic_Names ?? 'N/A',
                'english_name' => $rtu->name ?? 'N/A',
                'rtu_status_text' => $status['text'],
                'rtu_status_color' => $status['color'],
                'percentage_day' => (float)($rtu->percentage_day ?? 0)
            ],
        ];
    }

    /**
     * Determines the RTU status text and color based on the status code.
     *
     * @param int $statusCode
     * @return array
     */
    private function determineRtuStatus($statusCode)
    {
        // Added 'OffNoData' status mapping
        return match ((int)$statusCode) {
            1 => ['text' => 'Normal', 'color' => 'green'],
            2 => ['text' => 'Failed', 'color' => 'red'],
            3 => ['text' => 'Marginal', 'color' => 'orange'],
            4 => ['text' => 'Alarm', 'color' => 'blue'],
            5 => ['text' => 'OffNoData', 'color' => 'red'], // Assuming status code 5 for OffNoData
            default => ['text' => 'Unknown', 'color' => 'gray']
        };
    }

    /**
     * Compiles the overall statistics from processed station data.
     * Connected/Disconnected counts are now derived from status_distribution.
     *
     * @param array $stationsData
     * @param array $stats
     * @return array
     */
    private function compileStats($stationsData, $stats)
    {
        $totalStations = count($stationsData);
        
        // Calculate connected stations based on 'Normal' status from status_distribution
        // You might need to adjust this logic if 'Connected' means something else (e.g., Normal + Marginal)
        $connectedCount = ($stats['status_counts']['Normal'] ?? 0);
        // Add other statuses if they are considered 'connected' (e.g., Marginal, Alarm)
        // $connectedCount += ($stats['status_counts']['Marginal'] ?? 0);
        // $connectedCount += ($stats['status_counts']['Alarm'] ?? 0);


        $disconnectedCount = $totalStations - $connectedCount; // All others are disconnected

        $avgPercentage = $stats['stations_with_data'] > 0 
            ? $stats['total_percentage'] / $stats['stations_with_data'] 
            : 0;

        return [
            'total_stations_count' => $totalStations,
            'connected_stations_count' => $connectedCount,
            'disconnected_stations_count' => $disconnectedCount,
            'status_distribution' => $stats['status_counts'],
            'average_daily_operation_percentage' => round($avgPercentage, 2),
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

    /**
     * Prepares data for CSV/Excel export.
     * Ensures 'Connection Status' related fields are not included.
     *
     * @return array
     */
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

    /**
     * Returns the hexadecimal color code for a given RTU status text for Excel export.
     *
     * @param string $statusText
     * @return string
     */
    private function getStatusColor($statusText)
    {
        return match ($statusText) {
            'Normal' => 'C6EFCE', // Light Green
            'Failed' => 'FFC7CE', // Light Red
            'Marginal' => 'FFEB9C', // Light Amber
            'Alarm' => 'ADD8E6',   // Light Blue
            'OffNoData' => 'FFC7CE', // Light Red (same as Failed for visibility)
            default => 'DCDCDC'    // Light Gray (Unknown)
        };
    }
}
