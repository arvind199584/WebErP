<?php
namespace App\Modules\Workshop\Machine\TurfManagement\Reports\Services;

require_once __DIR__ . '/../Models/ReportModel.php';

use App\Modules\Workshop\Machine\TurfManagement\Reports\Models\ReportModel;
use DateTime;
use DateInterval;
use DatePeriod;
use Exception;

class ReportService {
    protected $reportModel;

    public function __construct() {
        $this->reportModel = new ReportModel();
    }

    public function getDashboardData(int $officeId, string $from, string $to, string $fuelTypeFilter = 'all'): array {
        $trendRaw = $this->reportModel->getFuelConsumptionTrend($officeId, $from, $to);
        $fuelBreakdownRaw = $this->reportModel->getMachineFuelBreakdown($officeId, $from, $to);
        $hoursBreakdownRaw = $this->reportModel->getMachineHoursBreakdown($officeId, $from, $to);
        $tableData = $this->reportModel->getSummaryTableData($officeId, $from, $to);

        if ($fuelTypeFilter !== 'all') {
            $crossTabRaw = $this->reportModel->getDailyFuelConsumptionReportFiltered($officeId, $from, $to, $fuelTypeFilter);
        } else {
            $crossTabRaw = $this->reportModel->getDailyFuelConsumptionReport($officeId, $from, $to);
        }

        $formattedData = [
            'fuel_trend' => $this->formatTrendData($trendRaw),
            'fuel_breakdown' => $this->formatPieData($fuelBreakdownRaw, 'machine_name', 'total_fuel'),
            'hours_breakdown' => $this->formatPieData($hoursBreakdownRaw, 'machine_name', 'hours_run'),
            'summary_table' => $tableData,
            'crosstab_report' => $this->formatCrossTabData($crossTabRaw, $from, $to)
        ];

        return $formattedData;
    }

    public function getMonthwiseMachineStats(int $officeId, int $machineId): array {
        $machineInfo = $this->reportModel->getMachineInfo($machineId);
        if (!$machineInfo) {
            throw new Exception("Machine not found.");
        }

        $stats = $this->reportModel->getMonthwiseMachineStats($officeId, $machineId);

        return [
            'machine' => $machineInfo,
            'stats' => $stats
        ];
    }

    public function getMachineLogData(int $officeId, int $machineId, string $from, string $to, string $displayMode = 'auto'): array {
        $rawHistory = $this->reportModel->getMachineLogHistory($officeId, $machineId, $from, $to);

        $machineInfo = $this->reportModel->getMachineInfo($machineId);
        $tracksHours = $machineInfo && $machineInfo['runduration'] == 1;
        $runsDaily = $machineInfo && $machineInfo['daily_run'] == 1;

        if (empty($rawHistory) && !$runsDaily && $displayMode !== 'all') {
            return [];
        }

        $shouldShowAllDays = ($displayMode === 'all') || ($displayMode === 'auto' && $tracksHours && $runsDaily);

        if (!$shouldShowAllDays) {
            return $rawHistory;
        }

        $dates = [];
        $start = new DateTime($from);
        $end = new DateTime($to);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $dates[] = $dt->format('Y-m-d');
        }

        $historyByDate = [];
        foreach ($rawHistory as $row) {
            $historyByDate[$row['log_date']] = $row;
        }

        $fullTimeline = [];
        $lastKnownHours = null;

        foreach ($dates as $date) {
            if (isset($historyByDate[$date])) {
                $row = $historyByDate[$date];

                if ($row['running_hours_to'] !== null && $row['running_hours_to'] !== '') {
                    $lastKnownHours = $row['running_hours_to'];
                }
                $row['is_virtual'] = false;
                $fullTimeline[] = $row;
            } else {
                if ($shouldShowAllDays) {
                    $virtualRow = [
                        'log_date' => $date,
                        'running_hours_start' => $tracksHours ? $lastKnownHours : null,
                        'running_hours_to' => $tracksHours ? $lastKnownHours : null,
                        'fuel_consumed_qty' => '-',
                        'operator' => '-',
                        'is_virtual' => true
                    ];
                    $fullTimeline[] = $virtualRow;
                }
            }
        }
        return $fullTimeline;
    }

    public function getMachineAuditData(int $officeId, int $machineId, string $from, string $to): array {
        $history = $this->reportModel->getMachineLogHistory($officeId, $machineId, $from, $to);
        $anomalies = [];

        $machineInfo = $this->reportModel->getMachineInfo($machineId);
        $runsDaily = $machineInfo && $machineInfo['daily_run'] == 1;

        $validHistory = $history;

        if (count($validHistory) < 3) return $anomalies;

        $lastValidValue = -1;

        foreach ($validHistory as $index => &$row) {
            $val = (float)($row['running_hours_to'] ?? 0);

            if ($val <= 0) {
                $row['is_anomaly'] = true;
                $row['type'] = 'Missing Data (Blank/Zero)';
                $row['running_hours_to'] = 0;
            } else {
                if ($val >= $lastValidValue) {
                    $row['is_anomaly'] = false;
                    $lastValidValue = $val;
                } else {
                    $row['is_anomaly'] = true;
                    $row['type'] = 'Impossible Drop';
                }
            }
        }
        unset($row);

        foreach ($validHistory as $index => $row) {
            if ($row['is_anomaly']) {
                $prevAnchor = null;
                for ($i = $index - 1; $i >= 0; $i--) {
                    if (!$validHistory[$i]['is_anomaly']) {
                        $prevAnchor = $validHistory[$i];
                        break;
                    }
                }

                $nextAnchor = null;
                for ($i = $index + 1; $i < count($validHistory); $i++) {
                    if (!$validHistory[$i]['is_anomaly']) {
                        $nextAnchor = $validHistory[$i];
                        break;
                    }
                }

                if (!$prevAnchor || !$nextAnchor) continue;

                $prevVal = (float)$prevAnchor['running_hours_to'];
                $nextVal = (float)$nextAnchor['running_hours_to'];

                $fuelConsumed = (float)($row['fuel_consumed_qty'] ?? 0);

                if (!$runsDaily && $fuelConsumed <= 0) {
                    $proposedFix = $prevVal;
                    $typeSuffix = ' (Idle/No Fuel)';
                } else {
                    $totalGap = $nextVal - $prevVal;
                    $randomWeight = mt_rand(40, 60) / 100;
                    $proposedFix = $prevVal + ($totalGap * $randomWeight);

                    $proposedFix = round($proposedFix * 2) / 2;

                    if ($proposedFix <= $prevVal) $proposedFix = $prevVal + 0.5;
                    if ($proposedFix >= $nextVal) $proposedFix = $nextVal - 0.5;

                    $typeSuffix = ($fuelConsumed <= 0) ? ' (Daily Run, No Fuel)' : ' (Fuel Consumed)';
                }

                $anomalies[] = [
                    'id' => $row['id'],
                    'log_date' => $row['log_date'],
                    'prev_val' => $prevVal,
                    'curr_val' => $row['running_hours_to'],
                    'next_val' => $nextVal,
                    'proposed_val' => $proposedFix,
                    'type' => $row['type'] . $typeSuffix
                ];
            }
        }

        return $anomalies;
    }

    public function calculateBackfillData(int $officeId, int $machineId, string $month): array {
        $machineInfo = $this->reportModel->getMachineInfo($machineId);
        if (!$machineInfo || !$machineInfo['runduration'] || !$machineInfo['daily_run']) {
            throw new Exception("This machine is not configured for daily auto-backfill.");
        }

        $hasRestDay = $machineInfo['has_rest_day'] ?? false;
        $restDayIndex = (int)($machineInfo['rest_day'] ?? -1);

        $startOfMonth = date('Y-m-01', strtotime($month));
        $endOfMonth = date('Y-m-t', strtotime($month));

        $fullHistory = $this->reportModel->getFullMachineLogHistory($officeId, $machineId);

        $anchors = [];
        foreach ($fullHistory as $row) {
            $fuel = (float)($row['fuel_consumed_qty'] ?? 0);
            $hours = (float)($row['running_hours'] ?? 0);
            if ($fuel > 0 && $hours > 0) {
                $anchors[] = [
                    'date' => $row['log_date'],
                    'hours' => $hours
                ];
            }
        }

        if (count($anchors) < 2) {
            throw new Exception("Not enough fuel anchors found to interpolate data.");
        }

        $dates = [];
        $start = new DateTime($startOfMonth);
        $end = new DateTime($endOfMonth);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $dates[] = $dt->format('Y-m-d');
        }

        $generatedData = [];
        $existingDates = array_column($fullHistory, 'log_date');

        foreach ($dates as $dateStr) {
            if (!in_array($dateStr, $existingDates)) {

                $dateObj = new DateTime($dateStr);
                $dayOfWeek = (int)$dateObj->format('w');

                if ($hasRestDay && $dayOfWeek === $restDayIndex) {
                    $carryValue = $this->findValueForDate($dateStr, $anchors, $generatedData);
                    if ($carryValue !== null) {
                        $generatedData[] = [
                            'log_date' => $dateStr,
                            'running_hours' => $carryValue,
                            'type' => 'Rest Day (Carry Over)'
                        ];
                    }
                    continue;
                }

                $prevAnchor = null;
                $nextAnchor = null;

                for ($i = count($anchors) - 1; $i >= 0; $i--) {
                    if ($anchors[$i]['date'] < $dateStr) {
                        $prevAnchor = $anchors[$i];
                        break;
                    }
                }

                foreach ($anchors as $anchor) {
                    if ($anchor['date'] > $dateStr) {
                        $nextAnchor = $anchor;
                        break;
                    }
                }

                if ($prevAnchor && $nextAnchor) {
                    $totalGapHours = $nextAnchor['hours'] - $prevAnchor['hours'];

                    $workingDaysInGap = $this->countWorkingDays($prevAnchor['date'], $nextAnchor['date'], $hasRestDay, $restDayIndex);

                    if ($workingDaysInGap > 0) {
                        $daysSincePrev = $this->countWorkingDays($prevAnchor['date'], $dateStr, $hasRestDay, $restDayIndex);

                        $proportion = $daysSincePrev / $workingDaysInGap;
                        $jitter = mt_rand(-5, 5) / 100;
                        $proportion += $jitter;

                        if ($proportion <= 0) $proportion = 0.05;
                        if ($proportion >= 1) $proportion = 0.95;

                        $calculatedHours = $prevAnchor['hours'] + ($totalGapHours * $proportion);
                        $calculatedHours = round($calculatedHours * 2) / 2;

                        $generatedData[] = [
                            'log_date' => $dateStr,
                            'running_hours' => $calculatedHours,
                            'type' => 'Interpolated Work Day'
                        ];
                    }
                }
            }
        }

        usort($generatedData, function($a, $b) {
            return strtotime($a['log_date']) - strtotime($b['log_date']);
        });

        return $generatedData;
    }

    private function findValueForDate(string $dateStr, array $anchors, array $generatedData): ?float {
        $bestVal = null;
        $bestDate = '1970-01-01';

        foreach ($anchors as $a) {
            if ($a['date'] < $dateStr && $a['date'] > $bestDate) {
                $bestDate = $a['date'];
                $bestVal = $a['hours'];
            }
        }
        foreach ($generatedData as $g) {
            if ($g['log_date'] < $dateStr && $g['log_date'] > $bestDate) {
                $bestDate = $g['log_date'];
                $bestVal = $g['running_hours'];
            }
        }

        return $bestVal;
    }

    private function countWorkingDays(string $start, string $end, bool $hasRestDay, int $restDayIndex): int {
        $count = 0;
        $current = new DateTime($start);
        $endDate = new DateTime($end);

        $current->modify('+1 day');
        while ($current < $endDate) {
            $dayOfWeek = (int)$current->format('w');
            if (!($hasRestDay && $dayOfWeek === $restDayIndex)) {
                $count++;
            }
            $current->modify('+1 day');
        }
        return $count;
    }


    private function formatCrossTabData(array $rawData, string $fromDate, string $toDate): array {
        $dates = [];
        $start = new DateTime($fromDate);
        $end = new DateTime($toDate);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $dates[] = $dt->format('Y-m-d');
        }

        $matrix = [];
        $fuelTotals = [];
        $dailyFuelTypeTotals = [
            'petrol' => array_fill_keys($dates, 0),
            'diesel' => array_fill_keys($dates, 0)
        ];

        foreach ($rawData as $row) {
            $machine = $row['machine_name'];
            $fuelType = $row['fuel_type'];
            $date = $row['log_date'];
            $qty = (float)$row['daily_fuel'];

            if (!isset($matrix[$machine])) {
                $matrix[$machine] = [
                    'fuel_type' => $fuelType,
                    'dates' => array_fill_keys($dates, 0)
                ];
            }

            if ($date && $qty > 0) {
                $matrix[$machine]['dates'][$date] = $qty;

                // Update grand totals
                if (!isset($fuelTotals[$fuelType])) {
                    $fuelTotals[$fuelType] = 0;
                }
                $fuelTotals[$fuelType] += $qty;

                // Update daily fuel type totals
                $normalizedFuelType = strtolower($fuelType);
                if (strpos($normalizedFuelType, 'petrol') !== false) {
                    $dailyFuelTypeTotals['petrol'][$date] += $qty;
                } elseif (strpos($normalizedFuelType, 'diesel') !== false) {
                    $dailyFuelTypeTotals['diesel'][$date] += $qty;
                }
            }
        }

        foreach ($matrix as $machine => &$data) {
            $rowTotal = array_sum($data['dates']);
            $data['total'] = $rowTotal;
        }

        return [
            'dates' => $dates,
            'rows' => $matrix,
            'grand_totals' => $fuelTotals,
            'daily_fuel_type_totals' => $dailyFuelTypeTotals
        ];
    }

    private function formatTrendData(array $rawData): array {
        $labels = [];
        $datasets = [];
        $groupedByFuel = [];

        foreach ($rawData as $row) {
            $date = $row['log_date'];
            $fuelType = $row['fuel_type'];
            $qty = (float)$row['total_qty'];

            if (!in_array($date, $labels)) {
                $labels[] = $date;
            }

            if (!isset($groupedByFuel[$fuelType])) {
                $groupedByFuel[$fuelType] = [];
            }
            $groupedByFuel[$fuelType][$date] = $qty;
        }

        sort($labels);

        $colors = ['#f6ad55', '#343a40', '#007bff', '#28a745'];
        $colorIndex = 0;

        foreach ($groupedByFuel as $fuelType => $dateData) {
            $dataPoints = [];
            foreach ($labels as $labelDate) {
                $dataPoints[] = $dateData[$labelDate] ?? 0;
            }

            $datasets[] = [
                'label' => $fuelType,
                'data' => $dataPoints,
                'borderColor' => $colors[$colorIndex % count($colors)],
                'backgroundColor' => $colors[$colorIndex % count($colors)],
                'tension' => 0.1,
                'fill' => false
            ];
            $colorIndex++;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    private function formatPieData(array $rawData, string $labelKey, string $dataKey): array {
        $labels = [];
        $data = [];

        foreach ($rawData as $row) {
            $labels[] = $row[$labelKey];
            $data[] = (float)$row[$dataKey];
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    public function executeAdHocQuery(string $sql, array $params = []): array {
        $trimmedSql = strtolower(trim($sql));
        if (strpos($trimmedSql, 'select') !== 0) {
            throw new Exception("Security Error: Only SELECT queries are allowed.");
        }

        // Prevent multiple queries / stacked queries
        if (strpos(rtrim($trimmedSql, ';'), ';') !== false) {
            throw new Exception("Security Error: Stacked queries (using semicolons) are not allowed.");
        }

        // Strict blacklist for SQL Injection / destructive keywords
        $forbidden = [
            'DROP', 'TRUNCATE', 'ALTER', 'GRANT', 'REVOKE', 'UPDATE', 'DELETE', 'INSERT', 
            'UNION', '--', '/*', '*/', 'EXEC', 'PG_SLEEP', 'COPY', 'VACUUM', 'ANALYZE',
            'INFORMATION_SCHEMA', 'PG_CATALOG', 'CURRENT_SETTING', 'PG_LS_DIR'
        ];
        foreach ($forbidden as $word) {
            if (preg_match("/\b" . preg_quote($word, '/') . "\b/", strtoupper($trimmedSql))) {
                throw new Exception("Security Error: Forbidden keyword/construct detected.");
            }
        }

        return $this->reportModel->executeAdHocQuery($sql, $params);
    }
}
