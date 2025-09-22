<?php
// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
require 'config.php';
session_start();

$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
    exit();
}

$pdo = getDBConnection();

function getHistoricalPeriod() {
    $today = new DateTime();
    $day = (int)$today->format('j');
    $month = (int)$today->format('n');
    $year = (int)$today->format('Y');

    if ($day >= 22) {
        $current_start_month = $month;
        $current_start_year = $year;
        $current_end_month = $month + 1;
        $current_end_year = $year;
        if ($current_end_month > 12) {
            $current_end_month = 1;
            $current_end_year++;
        }
        $forecast_start = new DateTime(sprintf('%04d-%02d-22', $current_start_year, $current_start_month));
        $forecast_end = new DateTime(sprintf('%04d-%02d-21', $current_end_year, $current_end_month));
    } else {
        $current_end_month = $month;
        $current_end_year = $year;
        $current_start_month = $month - 1;
        $current_start_year = $year;
        if ($current_start_month < 1) {
            $current_start_month = 12;
            $current_start_year--;
        }
        $forecast_start = new DateTime(sprintf('%04d-%02d-22', $current_start_year, $current_start_month));
        $forecast_end = new DateTime(sprintf('%04d-%02d-21', $current_end_year, $current_end_month));
    }

    $is_current_complete = $today > $forecast_end;
    if ($is_current_complete) {
        $last_complete_end = clone $forecast_end;
    } else {
        $last_complete_end = clone $forecast_start;
        $last_complete_end->sub(new DateInterval('P1D'));
    }

    $last_period_end_month = (int)$last_complete_end->format('n');
    $last_period_end_year = (int)$last_complete_end->format('Y');
    $last_period_start_month = $last_period_end_month - 1;
    $last_period_start_year = $last_period_end_year;
    if ($last_period_start_month < 1) {
        $last_period_start_month = 12;
        $last_period_start_year--;
    }
    $last_period_start = new DateTime(sprintf('%04d-%02d-22', $last_period_start_year, $last_period_start_month));

    $prev_period_end = clone $last_period_start;
    $prev_period_end->sub(new DateInterval('P1D'));

    $prev_period_end_month = (int)$prev_period_end->format('n');
    $prev_period_end_year = (int)$prev_period_end->format('Y');
    $prev_period_start_month = $prev_period_end_month - 1;
    $prev_period_start_year = $prev_period_end_year;
    if ($prev_period_start_month < 1) {
        $prev_period_start_month = 12;
        $prev_period_start_year--;
    }
    $prev_period_start = new DateTime(sprintf('%04d-%02d-22', $prev_period_start_year, $prev_period_start_month));

    $prev_prev_end = clone $prev_period_start;
    $prev_prev_end->sub(new DateInterval('P1D'));

    $prev_prev_end_month = (int)$prev_prev_end->format('n');
    $prev_prev_end_year = (int)$prev_prev_end->format('Y');
    $prev_prev_start_month = $prev_prev_end_month - 1;
    $prev_prev_start_year = $prev_prev_end_year;
    if ($prev_prev_start_month < 1) {
        $prev_prev_start_month = 12;
        $prev_prev_start_year--;
    }
    $prev_prev_start = new DateTime(sprintf('%04d-%02d-22', $prev_prev_start_year, $prev_prev_start_month));

    $super_prev_end = clone $prev_prev_start;
    $super_prev_end->sub(new DateInterval('P1D'));

    $super_prev_end_month = (int)$super_prev_end->format('n');
    $super_prev_end_year = (int)$super_prev_end->format('Y');
    $super_prev_start_month = $super_prev_end_month - 1;
    $super_prev_start_year = $super_prev_end_year;
    if ($super_prev_start_month < 1) {
        $super_prev_start_month = 12;
        $super_prev_start_year--;
    }
    $super_prev_start = new DateTime(sprintf('%04d-%02d-22', $super_prev_start_year, $super_prev_start_month));

    return [
        'historical_start' => $prev_period_start->format('Y-m-d'),
        'historical_end' => $last_complete_end->format('Y-m-d'),
        'last_period_start' => $last_period_start->format('Y-m-d'),
        'prev_period_start' => $prev_period_start->format('Y-m-d'),
        'prev_period_end' => $prev_period_end->format('Y-m-d'),
        'trend_previous_start' => $super_prev_start->format('Y-m-d'),
        'trend_previous_end' => $prev_prev_end->format('Y-m-d'),
        'forecast_start' => $forecast_start->format('Y-m-d'),
        'forecast_end' => $forecast_end->format('Y-m-d')
    ];
}

// Function to calculate daily attendance rate for a class or student
function calculateAttendanceRate($pdo, $class_id, $start_date, $end_date, $lrn = null) {
    $total_days = 0;
    $present_late_days = 0;

    $query = "
        SELECT attendance_date, lrn, attendance_status
        FROM attendance_tracking
        WHERE class_id = :class_id
        AND attendance_date BETWEEN :start_date AND :end_date
        AND logged_by = 'Teacher'
        AND attendance_status IN ('Present', 'Absent', 'Late')
    ";
    if ($lrn) {
        $query .= " AND lrn = :lrn";
    }

    $stmt = $pdo->prepare($query);
    $params = [
        ':class_id' => $class_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ];
    if ($lrn) {
        $params[':lrn'] = $lrn;
    }
    try {
        $stmt->execute($params);
        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in calculateAttendanceRate: " . $e->getMessage());
        $attendance_records = [];
    }

    $daily_records = [];
    foreach ($attendance_records as $record) {
        $date = $record['attendance_date'];
        if (!isset($daily_records[$date])) {
            $daily_records[$date] = [];
        }
        $daily_records[$date][] = $record;
    }

    foreach ($daily_records as $date => $records) {
        $total_students = count($records);
        $present_late = 0;

        foreach ($records as $record) {
            if ($record['attendance_status'] === 'Present' || $record['attendance_status'] === 'Late') {
                $present_late++;
            }
        }

        if ($total_students > 0) {
            $total_days++;
            if (!$lrn) {
                $present_late_days += ($present_late / $total_students);
            } else {
                foreach ($records as $record) {
                    if ($record['lrn'] === $lrn && in_array($record['attendance_status'], ['Present', 'Late'])) {
                        $present_late_days++;
                        break;
                    }
                }
            }
        }
    }

    $rate = $total_days > 0 ? ($present_late_days / $total_days) * 100 : 0;
    return [
        'rate' => number_format($rate, 2),
        'total_days' => $total_days,
        'present_late_days' => $present_late_days
    ];
}

// Function to fetch historical attendance data for ARIMA
function getHistoricalAttendanceData($pdo, $class_id, $start_date, $end_date, $lrn = null) {
    try {
        $query = "
            SELECT DISTINCT attendance_date
            FROM attendance_tracking
            WHERE class_id = :class_id
            AND attendance_date BETWEEN :start_date AND :end_date
            AND attendance_status IN ('Present', 'Absent', 'Late')
        ";
        $params = [
            ':class_id' => $class_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ];
        if ($lrn) {
            $query .= " AND lrn = :lrn";
            $params[':lrn'] = $lrn;
        }
        $query .= " ORDER BY attendance_date";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $time_series = [];
        $cumulative_present_late = 0;
        $cumulative_days = 0;

        foreach ($dates as $index => $date) {
            $rate_data = calculateAttendanceRate($pdo, $class_id, $date, $date, $lrn);
            if ($lrn) {
                $cumulative_days++;
                if ($rate_data['present_late_days'] > 0) {
                    $cumulative_present_late++;
                }
                $time_series[$date] = $cumulative_days > 0 ? ($cumulative_present_late / $cumulative_days) * 100 : 0;
            } else {
                $time_series[$date] = floatval($rate_data['rate']);
            }
        }

        return $time_series;
    } catch (PDOException $e) {
        error_log("Database error in getHistoricalAttendanceData: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("Unexpected error in getHistoricalAttendanceData: " . $e->getMessage());
        return [];
    }
}

// Simple ARIMA(1,1,0) forecasting function using example parameters
function arimaForecast($data, $periods = 30) {
    $period = getHistoricalPeriod();
    $yesterday = $period['historical_end'];
    $one_month_ago = $period['last_period_start'];
    $two_months_ago = $period['historical_start'];

    if (count($data) < 2) {
        $lastValue = count($data) > 0 ? end($data) : 0.0;
        return array_fill(0, $periods, round($lastValue, 2));
    }

    $values = array_values($data);
    $dates = array_keys($data);
    $n = count($values);

    // Filter data for the two periods
    $period1_data = []; // Two months ago to one month ago
    $period2_data = []; // One month ago to yesterday
    foreach ($dates as $index => $date) {
        if ($date >= $two_months_ago && $date < $one_month_ago) {
            $period1_data[] = $values[$index];
        } elseif ($date >= $one_month_ago && $date <= $yesterday) {
            $period2_data[] = $values[$index];
        }
    }

    // Calculate average rates for each period
    $period1_avg = !empty($period1_data) ? array_sum($period1_data) / count($period1_data) : 0.0;
    $period2_avg = !empty($period2_data) ? array_sum($period2_data) / count($period2_data) : $period1_avg;

    // Calculate differences for AR(1) coefficient
    $differences = [];
    for ($i = 1; $i < count($values); $i++) {
        $differences[] = $values[$i] - $values[$i - 1];
    }

    // Calculate phi_1 using least squares for AR(1) on differences
    $phi_1 = 0.5; // Default if insufficient data
    if (count($differences) >= 2) {
        $x = array_slice($differences, 0, -1); // ΔY_{t-1}
        $y = array_slice($differences, 1);     // ΔY_t
        $sum_xy = 0;
        $sum_xx = 0;
        for ($i = 0; $i < count($x); $i++) {
            $sum_xy += $x[$i] * $y[$i];
            $sum_xx += $x[$i] * $x[$i];
        }
        $phi_1 = $sum_xx > 0 ? $sum_xy / $sum_xx : 0.5;
        $phi_1 = max(-1, min(1, $phi_1)); // Bound for stability
    } else {
        // Fallback based on trend
        $monthly_trend = $period2_avg - $period1_avg;
        if ($monthly_trend > 0) {
            $phi_1 = 0.6;
        } elseif ($monthly_trend < 0) {
            $phi_1 = 0.4;
        } else {
            $phi_1 = 0.5;
        }
    }

    // Forecast generation
    $forecast = [];
    $currentValue = $period2_avg > 0 ? $period2_avg : ($period1_avg > 0 ? $period1_avg : 0.0);
    $last_delta = 0;

    for ($i = 0; $i < $periods; $i++) {
        $delta_y = $phi_1 * $last_delta;
        $predicted = $currentValue + $delta_y;
        $predicted = max(0, min(100, $predicted));

        $forecast[] = round($predicted, 2);

        // Update for next iteration
        $last_delta = $delta_y;
        $currentValue = $predicted;
    }

    return $forecast;
}

// Updated main forecasting function with better logic
function generateForecast($pdo, $class_id, $lrn = null) {
    $period = getHistoricalPeriod();
    $start_date = $period['historical_start'];
    $end_date = $period['historical_end'];
    
    $historical_data = getHistoricalAttendanceData($pdo, $class_id, $start_date, $end_date, $lrn);
    if (empty($historical_data)) {
        $historical_data = [$end_date => 0.0];
    }   

    $start_forecast = new DateTime($period['historical_end']);
    $start_forecast->add(new DateInterval('P1D'));
    $end_forecast = new DateTime($period['forecast_end']);
    $forecast_dates = [];
    $date = clone $start_forecast;
    while ($date <= $end_forecast) {
        $forecast_dates[] = $date->format('Y-m-d');
        $date->add(new DateInterval('P1D'));
    }
    $periods = count($forecast_dates);

    $forecast_values = arimaForecast($historical_data, $periods);

    return [
        'historical' => $historical_data,
        'forecast' => array_combine($forecast_dates, $forecast_values)
    ];
}

// Function to calculate attendance status counts for a student or class
function calculateAttendanceStatus($pdo, $class_id, $lrn = null) {
    $period = getHistoricalPeriod();
    $start_date = $period['historical_start'];
    $end_date = $period['historical_end'];
    $query = "
        SELECT attendance_status, COUNT(*) as count
        FROM attendance_tracking
        WHERE class_id = :class_id
        AND logged_by = 'Teacher'
        AND attendance_status IN ('Present', 'Absent', 'Late')
        AND attendance_date BETWEEN :start_date AND :end_date
    ";
    if ($lrn) {
        $query .= " AND lrn = :lrn";
    }
    $query .= " GROUP BY attendance_status";

    $stmt = $pdo->prepare($query);
    $params = [':class_id' => $class_id, ':start_date' => $start_date, ':end_date' => $end_date];
    if ($lrn) {
        $params[':lrn'] = $lrn;
    }
    try {
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in calculateAttendanceStatus: " . $e->getMessage());
        $results = [];
    }

    $status = ['present' => 0, 'absent' => 0, 'late' => 0];
    foreach ($results as $row) {
        $key = strtolower($row['attendance_status']);
        $status[$key] = $row['count'];
    }
    return $status;
}

// Fetch classes and students
$classes = [];
$period = getHistoricalPeriod();
try {
    $stmt = $pdo->prepare("
        SELECT c.class_id, c.section_name, s.subject_name, c.grade_level, c.room
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        WHERE c.teacher_id = ?
    ");
    $stmt->execute([$user['teacher_id']]);
    $classes_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Classes fetched: " . print_r($classes_db, true)); // Debug log

    foreach ($classes_db as $class) {
        $student_stmt = $pdo->prepare("
            SELECT s.lrn, s.last_name, s.first_name, s.middle_name, s.email
            FROM class_students cs
            JOIN students s ON cs.lrn = s.lrn
            WHERE cs.class_id = ?
        ");
        $student_stmt->execute([$class['class_id']]);
        $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

        $student_data = [];
        $isCollege = isset($class['grade_level']) && strpos($class['grade_level'], 'College') !== false;
        foreach ($students as $student) {
            $analytics = generateForecast($pdo, $class['class_id'], $student['lrn']);
            $current_start = $period['historical_start'];
            $current_end = $period['historical_end'];
            $current_rate_data = calculateAttendanceRate($pdo, $class['class_id'], $current_start, $current_end, $student['lrn']);
            $current_rate = $current_rate_data['rate'];
            $total_days = $current_rate_data['total_days'];
            $present_late_days = $current_rate_data['present_late_days'];
            $absences = $total_days - $present_late_days;
            $avg_forecast = array_sum($analytics['forecast']) / count($analytics['forecast']);
            $riskLevel = ($absences == 0) ? 'no risk' : ($isCollege ? ($absences >= 3 ? 'Running for Drop Out' : 'no risk') : (($absences <= 13) ? 'low' : (($absences <= 26) ? 'medium' : (($absences <= 39) ? 'high' : 'Running for Drop Out'))));

            $student_data[] = [
                'id' => $student['lrn'],
                'lastName' => $student['last_name'],
                'firstName' => $student['first_name'],
                'middleName' => $student['middle_name'],
                'email' => $student['email'],
                'lrn' => $student['lrn'],
                'attendanceRate' => $current_rate,
                'timeSeriesData' => array_values($analytics['historical']),
                'forecast' => array_values($analytics['forecast']),
                'historical_dates' => array_keys($analytics['historical']),
                'forecast_dates' => array_keys($analytics['forecast']),
                'trend' => ($avg_forecast >= floatval($current_rate)) ? 'improving' : 'declining',
                'riskLevel' => $riskLevel,
                'totalAbsences' => $absences,
                'primaryAbsenceReason' => 'Unknown',
                'attendanceStatus' => calculateAttendanceStatus($pdo, $class['class_id'], $student['lrn']),
                'behaviorPatterns' => []
            ];
        }

        $class_analytics = generateForecast($pdo, $class['class_id']);
        $current_start = $period['historical_start'];
        $current_end = $period['historical_end'];
        $class_current_rate_data = calculateAttendanceRate($pdo, $class['class_id'], $current_start, $current_end);
        $class_current_rate = $class_current_rate_data['rate'];
        $class_avg_forecast = array_sum($class_analytics['forecast']) / count($class_analytics['forecast']);
        $classes[] = [
            'id' => $class['class_id'],
            'code' => (isset($class['subject_name']) ? $class['subject_name'] : 'Unknown') . '-' . $class['class_id'],
            'section_name' => isset($class['section_name']) ? $class['section_name'] : 'Unknown',
            'subject_name' => isset($class['subject_name']) ? $class['subject_name'] : 'Unknown',
            'grade_level' => isset($class['grade_level']) ? $class['grade_level'] : '',
            'room' => isset($class['room']) ? $class['room'] : '',
            'attendancePercentage' => $class_current_rate,
            'historical_dates' => array_keys($class_analytics['historical']),
            'historical_values' => array_values($class_analytics['historical']),
            'forecast_dates' => array_keys($class_analytics['forecast']),
            'forecast_values' => array_values($class_analytics['forecast']),
            'schedule' => [],
            'status' => 'active',
            'trend' => ($class_avg_forecast >= floatval($class_current_rate)) ? 'improving' : 'declining',
            'seasonality' => 'no_significant_pattern',
            'forecastConfidence' => 90.0,
            'students' => $student_data
        ];

        $previous_start = $period['trend_previous_start'];
        $previous_end = $period['trend_previous_end'];

        $previous_rate_data = calculateAttendanceRate($pdo, $class['class_id'], $previous_start, $previous_end);
        $previous_rate = floatval($previous_rate_data['rate']);
        $current_rate = floatval($class_current_rate_data['rate']);
        $attendance_diff = $current_rate - $previous_rate;
        $attendance_trend_direction = $attendance_diff > 0 ? 'up' : ($attendance_diff < 0 ? 'down' : 'stable');
        if ($attendance_diff == 0) {
            $attendance_trend_text = 'Stable';
        } else {
            $sign = $attendance_diff > 0 ? '+' : '-';
            $value = abs($attendance_diff);
            $attendance_trend_text = sprintf('%s%.1f%% vs last month', $sign, $value);
        }

        $current_at_risk = 0;
        foreach ($student_data as $s) {
            $absence_threshold = $isCollege ? 3 : 13;
            if ($s['totalAbsences'] >= $absence_threshold) $current_at_risk++;
        }
        $previous_at_risk = 0;
        foreach ($students as $student) {
            $prev_rate_data = calculateAttendanceRate($pdo, $class['class_id'], $previous_start, $previous_end, $student['lrn']);
            $prev_absences = $prev_rate_data['total_days'] - $prev_rate_data['present_late_days'];
            $absence_threshold = $isCollege ? 3 : 13;
            if ($prev_absences >= $absence_threshold) $previous_at_risk++;
        }
        $at_risk_diff = $current_at_risk - $previous_at_risk;
        $at_risk_trend_direction = $at_risk_diff > 0 ? 'up' : ($at_risk_diff < 0 ? 'down' : 'stable');
        if ($at_risk_diff == 0) {
            $at_risk_trend_text = 'Stable';
        } else {
            $sign = $at_risk_diff > 0 ? '+' : '-';
            $value = abs($at_risk_diff);
            $at_risk_trend_text = sprintf('%s%d vs last month', $sign, $value);
        }

        // Add to class array
        $classes[count($classes) - 1]['attendance_trend_text'] = $attendance_trend_text;
        $classes[count($classes) - 1]['attendance_trend_direction'] = $attendance_trend_direction;
        $classes[count($classes) - 1]['at_risk_trend_text'] = $at_risk_trend_text;
        $classes[count($classes) - 1]['at_risk_trend_direction'] = $at_risk_trend_direction;
        $classes[count($classes) - 1]['at_risk_count'] = $current_at_risk;
    }
} catch (PDOException $e) {
    error_log("Database error in class fetch: " . $e->getMessage());
    $classes = [];
}

// Encode for JavaScript with error handling
$classes_json = json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($classes_json === false) {
    error_log("JSON encoding failed: " . json_last_error_msg());
    $classes_json = json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Predictions - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-hover: #2563eb;
            --primary-blue-light: #dbeafe;
            --success-green: #10b981;
            --warning-yellow: #f59e0b;
            --danger-red: #ef4444;
            --info-cyan: #06b6d4;
            --dark-gray: #1f2937;
            --medium-gray: #6b7280;
            --light-gray: #e5e7eb;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --blackfont-color: #1e293b;
            --whitefont-color: #ffffff;
            --grayfont-color: #64748b;
            --primary-gradient: linear-gradient(135deg, #3b82f6, #60a5fa);
            --secondary-gradient: linear-gradient(135deg, #ec4899, #f472b6);
            --inputfield-color: #f8fafc;
            --inputfieldhover-color: #f1f5f9;
            --font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.875rem;
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition-fast: 0.2s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
            --status-present-bg: rgba(16, 185, 129, 0.15);
            --status-absent-bg: rgba(239, 68, 68, 0.15);
            --status-late-bg: rgba(245, 158, 11, 0.15);
            --status-none-bg: #f8fafc;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --present-color: linear-gradient(135deg, #34d399, #10b981);
            --absent-color: linear-gradient(135deg, #f87171, #ef4444);
            --late-color: linear-gradient(135deg, #fbbf24, #f59e0b);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }

        body {
            background-color: var(--card-bg);
            color: var(--blackfont-color);
            padding: 20px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: var(--spacing-lg);
            color: var(--blackfont-color);
            position: relative;
            padding-bottom: 10px;
        }

        h1:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 4px;
            width: 80px;
            background: var(--primary-gradient);
            border-radius: var(--radius-sm);
        }

        h2 {
            font-size: var(--font-size-xl);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
        }

        h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
        }

        .controls {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            align-items: center;
            border: 1px solid var(--border-color);
        }

        .controls-left {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            flex: 1;
            align-items: center;
        }

        .selector-select {
            padding: var(--spacing-xs) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            min-width: 180px;
            height: 38px;
            box-sizing: border-box;
        }

        .selector-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .search-input {
            padding: var(--spacing-xs) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            min-width: 180px;
            height: 38px;
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .btn {
            padding: var(--spacing-xs) var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .btn-primary:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--medium-gray);
            color: var(--whitefont-color);
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xl);
            color: var(--whitefont-color);
        }

        .bg-purple { background: var(--primary-gradient); }
        .bg-pink { background: var(--secondary-gradient); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-green { background: linear-gradient(135deg, #10b981, #34d399); }
        .bg-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

        .card-title {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
        }

        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--blackfont-color);
        }

        .card-trend {
            font-size: var(--font-size-sm);
            margin-top: var(--spacing-xs);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .trend-up { color: var(--success-color); }
        .trend-down { color: var(--danger-color); }

        .chart-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .chart-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
        }

        .chart-filter {
            display: flex;
            gap: var(--spacing-sm);
        }

        .filter-btn {
            padding: var(--spacing-xs) var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            background: var(--inputfield-color);
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .filter-btn.active {
            background: var(--primary-blue);
            color: var(--whitefont-color);
        }

        .filter-btn:hover {
            background: var(--inputfieldhover-color);
        }

        canvas {
            max-height: 400px;
            width: 100%;
        }

        .attendance-status-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(8px);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-lg);
            margin-bottom: var(--spacing-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
            align-items: center;
            transition: var(--transition-normal);
        }

        .attendance-status-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        }

        .attendance-status-header {
            grid-column: span 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .attendance-status-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
        }

        .attendance-status-legend {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-size-base);
            color: var(--blackfont-color);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: var(--transition-fast);
            transform: translateX(0);
            opacity: 1;
        }

        .legend-item:hover {
            background: var(--inputfieldhover-color);
            transform: translateX(5px);
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .legend-item:nth-child(1) { animation: slideIn 0.3s ease-in-out; }
        .legend-item:nth-child(2) { animation: slideIn 0.4s ease-in-out; }
        .legend-item:nth-child(3) { animation: slideIn 0.5s ease-in-out; }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .legend-label {
            font-weight: 600;
            font-size: var(--font-size-base);
        }

        .legend-value {
            font-weight: 700;
            font-size: var(--font-size-lg);
            color: var(--primary-blue);
        }

        .pattern-table {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .table-header {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }

        .table-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: var(--spacing-sm) var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
        }

        tbody tr {
            transition: var(--transition-normal);
        }

        tbody tr:hover {
            background-color: var(--inputfieldhover-color);
        }

        .risk-high { color: var(--danger-color); font-weight: 600; }
        .risk-medium { color: var(--warning-color); font-weight: 600; }
        .risk-low { color: var(--success-color); font-weight: 600; }
        .risk-no { color: var(--success-green); font-weight: 600; }
        .risk-critical { color: var(--danger-red); font-weight: 600; }
        .risk-running-for-drop-out { color: var(--danger-color); font-weight: 600; }

        .prediction-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .prediction-header {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }

        .prediction-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .detail-item {
            font-size: var(--font-size-sm);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            background: var(--inputfield-color);
            border-left: 4px solid var(--primary-blue);
        }

        .detail-item strong {
            color: var(--grayfont-color);
            display: block;
            margin-bottom: var(--spacing-xs);
        }

        .detail-item.risk-high { border-left-color: var(--danger-color); }
        .detail-item.risk-medium { border-left-color: var(--warning-color); }
        .detail-item.risk-low { border-left-color: var(--success-color); }
        .detail-item.risk-no { border-left-color: var(--success-green); }
        .detail-item.risk-critical { border-left-color: var(--danger-red); }
        .detail-item.risk-running-for-drop-out { border-left-color: var(--danger-red); }

        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .alert-warning { background: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--warning-color); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger-color); }
        .alert-info { background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary-blue); }

        .forecast-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            .controls-left {
                flex-direction: column;
                gap: var(--spacing-xs);
            }
            .selector-select, .search-input {
                width: 100%;
                min-width: auto;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: var(--spacing-sm);
            }
            th, td {
                padding: var(--spacing-xs);
            }
            .card-value {
                font-size: var(--font-size-xl);
            }
            .table-responsive {
                overflow-x: auto;
            }
            .attendance-status-title {
                font-size: var(--font-size-lg);
            }
            .legend-value {
                font-size: var(--font-size-base);
            }
            .attendance-status-container {
                grid-template-columns: 1fr;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .attendance-status-legend {
                width: 100%;
                max-width: 350px;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .attendance-status-container {
                padding: var(--spacing-md);
            }
        }

        .student-name {
            font-weight: 700;
            font-size: var(--font-size-lg);
            color: var(--primary-blue);
        }
    </style>
</head>
<body>
    <h1>Analytics & Predictions</h1>

    <div class="controls">
        <div class="controls-left">
            <select class="selector-select" id="class-filter">
                <?php if (!empty($classes)): ?>
                    <option value="<?php echo htmlspecialchars($classes[0]['id']); ?>">
                        <?php echo htmlspecialchars((isset($classes[0]['grade_level']) ? $classes[0]['grade_level'] . ' – ' : '') . $classes[0]['section_name'] . ' (' . $classes[0]['subject_name'] . ')'); ?>
                    </option>
                <?php else: ?>
                    <option value="">No Classes Available</option>
                <?php endif; ?>
            </select>
            <input type="text" class="search-input" id="student-search" placeholder="Search by LRN or Name">
            <select class="selector-select" id="student-filter">
                <option value="">All Students</option>
            </select>
            <button class="btn btn-primary" id="refresh-data"><i class="fas fa-sync"></i> Update Forecast</button>
            <button class="btn btn-primary" id="export-chart"><i class="fas fa-download"></i> Export</button>
            <button class="btn btn-secondary" id="clear-filters"><i class="fas fa-times"></i> Clear</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Current Attendance Rate</div>
                    <div class="card-value" id="current-attendance-rate">0.00%</div>
                    <div class="card-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span id="attendance-trend">Calculating...</span>
                    </div>
                </div>
                <div class="card-icon bg-green">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Predicted Next Month</div>
                    <div class="card-value" id="predicted-attendance">0.00%</div>
                    <div class="card-trend">
                        <i class="fas fa-crystal-ball"></i>
                        <span>ARIMA Forecast</span>
                    </div>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fas fa-chart-area"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">At-Risk Students</div>
                    <div class="card-value" id="at-risk-count">0</div>
                    <div class="card-trend trend-down">
                        <i class="fas fa-arrow-down"></i>
                        <span id="at-risk-trend">Calculating...</span>
                    </div>
                </div>
                <div class="card-icon bg-pink">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="forecast-container">
        <div class="chart-header">
            <div class="chart-title">ARIMA Time Series Forecast</div>
            <div class="chart-filter">
                <button class="filter-btn active" data-period="monthly">Monthly</button>
            </div>
        </div>
        <canvas id="forecast-chart"></canvas>
    </div>

    <div class="attendance-status-container">
        <div class="attendance-status-header">
            <div class="attendance-status-title" id="attendance-status-title">Attendance Status Distribution</div>
        </div>
        <canvas id="attendance-status" style="max-width: 350px; margin: 0 auto;"></canvas>
        <div class="attendance-status-legend">
            <div class="legend-item">
                <span class="legend-color" style="background: var(--present-color);"></span>
                <span class="legend-label">Present</span>
                <span class="legend-value" id="present-count">0 (0.0%)</span>
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background: var(--absent-color);"></span>
                <span class="legend-label">Absent</span>
                <span class="legend-value" id="absent-count">0 (0.0%)</span>
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background: var(--late-color);"></span>
                <span class="legend-label">Late</span>
                <span class="legend-value" id="late-count">0 (0.0%)</span>
            </div>
        </div>
    </div>

    <div class="prediction-card" id="student-prediction-card" style="display: none;">
        <div class="prediction-header">
            <div class="table-title">Individual Student Time Series Analysis</div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>ARIMA model predictions based on historical attendance patterns</span>
            </div>
        </div>
        
        <div class="prediction-details" id="student-details"></div>
        
        <div class="chart-container">
            <h3>Individual Forecast Chart</h3>
            <canvas id="individual-forecast-chart"></canvas>
        </div>

        <div class="pattern-table">
            <h3>Personal Analytics</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Current Value</th>
                        </tr>
                    </thead>
                    <tbody id="student-metrics"></tbody>
                </table>
            </div>
            <div class="pattern-table">
                <h3>Forecast Metrics</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Forecast Next Month</th>
                            </tr>
                        </thead>
                        <tbody id="student-forecast-metrics"></tbody>
                    </table>
                </div>
            </div>
            <h3>AI Recommendation</h3>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle"></i>
                <span id="student-recommendation-text"></span>
            </div>
        </div>
    </div>

    <div class="pattern-table" id="at-risk-students">
        <div class="table-header">
            <div class="table-title">At-Risk Students</div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Name</th>
                        <th>Total Absences</th>
                    </tr>
                </thead>
                <tbody id="at-risk-table-body"></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script>
        // Parse JSON data
        let classes;
        try {
            classes = <?php echo $classes_json; ?>;
            console.log('Classes data loaded:', classes);
        } catch (e) {
            console.error('Error parsing classes JSON:', e);
            classes = [];
        }

        const classFilter = document.getElementById('class-filter');
        const studentFilter = document.getElementById('student-filter');
        const studentSearch = document.getElementById('student-search');
        const forecastChartCtx = document.getElementById('forecast-chart').getContext('2d');
        const attendanceStatusCtx = document.getElementById('attendance-status').getContext('2d');
        let forecastChart, attendanceStatusChart, individualForecastChart;

        function initializeFilters() {
            // Clear existing options
            classFilter.innerHTML = '';

            // Create a Set to track unique class IDs
            const uniqueClasses = new Set();
            
            // Add unique classes to the filter
            classes.forEach(cls => {
                if (!uniqueClasses.has(cls.id)) {
                    uniqueClasses.add(cls.id);
                    const option = document.createElement('option');
                    option.value = cls.id;
                    option.textContent = `${cls.grade_level ? cls.grade_level + ' – ' : ''}${cls.section_name} (${cls.subject_name})`;
                    classFilter.appendChild(option);
                }
            });
            
            updateStudentFilter();
            updateAtRiskTable();
        }

        function updateStudentFilter(searchTerm = '') {
            const selectedClassId = classFilter.value;
            let filteredStudents = classes.flatMap(c => c.students.map(s => ({
                ...s,
                grade_level: c.grade_level || '',
                subject_name: c.subject_name || 'Unknown',
                section_name: c.section_name || 'Unknown'
            })));

            if (selectedClassId) {
                filteredStudents = filteredStudents.filter(s => s.section_name === classes.find(c => c.id == selectedClassId).section_name);
            }

            if (searchTerm) {
                searchTerm = searchTerm.toLowerCase().trim();
                filteredStudents = filteredStudents.filter(s => 
                    s.lrn.toLowerCase().includes(searchTerm) ||
                    s.lastName.toLowerCase().includes(searchTerm) ||
                    s.firstName.toLowerCase().includes(searchTerm) ||
                    (s.middleName && s.middleName.toLowerCase().includes(searchTerm))
                );
            }

            filteredStudents.sort((a, b) => a.lastName.localeCompare(b.lastName));

            studentFilter.innerHTML = '';
            if (filteredStudents.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No Students';
                studentFilter.appendChild(option);
            } else {
                const allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = 'All Students';
                studentFilter.appendChild(allOption);
                filteredStudents.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = `${student.lastName}, ${student.firstName} ${student.middleName || ''} (${student.section_name})`.trim();
                    studentFilter.appendChild(option);
                });
            }
        }

        function updateAtRiskTable() {
            const selectedClassId = classFilter.value;
            const atRiskTableBody = document.getElementById('at-risk-table-body');
            atRiskTableBody.innerHTML = '';

            let hasAtRisk = false;
            classes.forEach(cls => {
                if (!selectedClassId || cls.id == selectedClassId) {
                    const isCollege = cls.grade_level && cls.grade_level.includes('College');
                    const absenceThreshold = isCollege ? 3 : 13;

                    cls.students.forEach(student => {
                        if (student.totalAbsences >= absenceThreshold) {
                            hasAtRisk = true;
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${cls.grade_level ? cls.grade_level + ' - ' : ''}${cls.section_name} (${cls.subject_name})</td>
                                <td>${student.lastName}, ${student.firstName} ${student.middleName || ''}</td>
                                <td class="risk-${student.riskLevel}">${student.totalAbsences} - ${isCollege ? (student.riskLevel === 'running for drop out' ? 'Running for Drop Out' : 'No Risk') : student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}</td>
                            `;
                            atRiskTableBody.appendChild(row);
                        }
                    });
                }
            });

            if (!hasAtRisk) {
                atRiskTableBody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No data available</td></tr>';
            }
        }

        function initializeCharts() {
            let selectedClass = classes.find(c => c.id == classFilter.value) || classes[0];
            if (!selectedClass) {
                console.error('No selected class found');
                return;
            }

            const statusCounts = selectedClass.students.reduce((acc, student) => {
                acc[0] += student.attendanceStatus.present;
                acc[1] += student.attendanceStatus.absent;
                acc[2] += student.attendanceStatus.late;
                return acc;
            }, [0, 0, 0]);

            document.getElementById('current-attendance-rate').textContent = `${selectedClass.attendancePercentage}%`;
            document.getElementById('predicted-attendance').textContent = `${parseFloat(selectedClass.forecast_values.reduce((a, b) => a + b, 0) / selectedClass.forecast_values.length).toFixed(2)}%`;
            document.getElementById('at-risk-count').textContent = selectedClass.at_risk_count;

            if (forecastChart) forecastChart.destroy();

            forecastChart = new Chart(forecastChartCtx, {
                type: 'line',
                data: {
                    labels: [...selectedClass.historical_dates, ...selectedClass.forecast_dates],
                    datasets: [
                        {
                            label: 'Historical Data',
                            data: [...selectedClass.historical_values, ...Array(selectedClass.forecast_values.length).fill(null)],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Forecasted Average',
                            data: [...Array(selectedClass.historical_values.length).fill(null), ...selectedClass.forecast_values],
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: { display: true, text: 'Attendance Rate (%)' }
                        },
                        x: { title: { display: true, text: 'Date' } }
                    }
                }
            });

            const total = statusCounts.reduce((a, b) => a + b, 0);
            if (attendanceStatusChart) attendanceStatusChart.destroy();
            attendanceStatusChart = new Chart(attendanceStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: ['#22c55e', '#ef4444', '#f59e0b'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#ffffff',
                            formatter: (value, context) => {
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${value}\n(${percentage}%)`;
                            },
                            font: {
                                weight: 'bold',
                                size: 12,
                                family: 'var(--font-family)'
                            },
                            textAlign: 'center',
                            padding: 4,
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                },
                plugins: [ChartDataLabels]
            });

            const attendanceTrendSpan = document.getElementById('attendance-trend');
            attendanceTrendSpan.textContent = selectedClass.attendance_trend_text;
            const attendanceTrendDiv = attendanceTrendSpan.parentElement;
            attendanceTrendDiv.classList.remove('trend-up', 'trend-down');
            const attendanceIcon = attendanceTrendDiv.querySelector('i');
            if (selectedClass.attendance_trend_direction !== 'stable') {
                attendanceTrendDiv.classList.add(`trend-${selectedClass.attendance_trend_direction}`);
                attendanceIcon.className = `fas fa-arrow-${selectedClass.attendance_trend_direction}`;
            } else {
                attendanceIcon.className = 'fas fa-minus';
            }

            const atRiskTrendSpan = document.getElementById('at-risk-trend');
            atRiskTrendSpan.textContent = selectedClass.at_risk_trend_text;
            const atRiskTrendDiv = atRiskTrendSpan.parentElement;
            atRiskTrendDiv.classList.remove('trend-up', 'trend-down');
            const atRiskIcon = atRiskTrendDiv.querySelector('i');
            if (selectedClass.at_risk_trend_direction === 'up') {
                atRiskTrendDiv.classList.add('trend-up');
                atRiskTrendDiv.style.color = 'var(--danger-color)';
                atRiskIcon.className = 'fas fa-arrow-up';
            } else if (selectedClass.at_risk_trend_direction === 'down') {
                atRiskTrendDiv.classList.add('trend-down');
                atRiskTrendDiv.style.color = 'var(--success-color)';
                atRiskIcon.className = 'fas fa-arrow-down';
            } else {
                atRiskTrendDiv.style.color = 'var(--grayfont-color)';
                atRiskIcon.className = 'fas fa-minus';
            }

            document.getElementById('present-count').textContent = `${statusCounts[0]} (${total > 0 ? ((statusCounts[0] / total) * 100).toFixed(1) : 0}%)`;
            document.getElementById('absent-count').textContent = `${statusCounts[1]} (${total > 0 ? ((statusCounts[1] / total) * 100).toFixed(1) : 0}%)`;
            document.getElementById('late-count').textContent = `${statusCounts[2]} (${total > 0 ? ((statusCounts[2] / total) * 100).toFixed(1) : 0}%)`;
        }
        
        function showStudentPrediction(studentId) {
            const student = classes.flatMap(c => c.students.map(s => ({
                ...s,
                subject_name: c.subject_name || 'Unknown',
                section_name: c.section_name || 'Unknown',
                grade_level: c.grade_level || ''
            }))).find(s => s.id == studentId);
            
            if (!student) {
                document.getElementById('student-prediction-card').style.display = 'none';
                console.warn('No student found for ID:', studentId);
                return;
            }
            
            document.getElementById('student-prediction-card').style.display = 'block';
            
            const avgForecast = student.forecast.reduce((a, b) => a + b, 0) / student.forecast.length;
            const isCollege = student.grade_level.includes('College');
            
            const studentDetails = document.getElementById('student-details');
            studentDetails.innerHTML = `
                <div class="detail-item">
                    <strong>Class:</strong> ${student.subject_name} (${student.section_name})
                </div>
                <div class="detail-item">
                    <strong>LRN:</strong> ${student.lrn || 'N/A'}
                </div>
                <div class="detail-item">
                    <strong>Student:</strong> ${student.lastName}, ${student.firstName} ${student.middleName || ''}
                </div>
                <div class="detail-item">
                    <strong>Current Attendance Rate:</strong> ${student.attendanceRate}%
                </div>
                <div class="detail-item">
                    <strong>Predicted Next Month:</strong> ${avgForecast.toFixed(1)}%
                </div>
                <div class="detail-item">
                    <strong>Total Absences:</strong> ${student.totalAbsences}${isCollege ? '' : ` - ${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}`}
                </div>
                ${isCollege ? '' : `
                <div class="detail-item risk-${student.riskLevel}">
                    <strong>Risk Level:</strong> ${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}
                </div>
                `}
            `;
            
            const recommendation = generateRecommendation(student);
            document.getElementById('student-recommendation-text').textContent = recommendation;
            
            const metricsTable = document.getElementById('student-metrics');
            metricsTable.innerHTML = `
                <tr>
                    <td>Attendance Rate</td>
                    <td>${student.attendanceRate}%</td>
                </tr>
                <tr>
                    <td>Total Absences</td>
                    <td${isCollege ? '' : ` class="risk-${student.riskLevel}"`}>${student.totalAbsences}${isCollege ? '' : ` - ${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}`}</td>
                </tr>
            `;
            
            const forecastMetricsTable = document.getElementById('student-forecast-metrics');
            forecastMetricsTable.innerHTML = `
                <tr>
                    <td>Attendance Rate</td>
                    <td>${avgForecast.toFixed(1)}%</td>
                </tr>
            `;
            
            createIndividualForecastChart(student);
            
            if (attendanceStatusChart) {
                const studentData = [
                    student.attendanceStatus.present,
                    student.attendanceStatus.absent,
                    student.attendanceStatus.late
                ];
                const studentTotal = studentData.reduce((a, b) => a + b, 0);
                attendanceStatusChart.data.datasets[0].data = studentData;
                attendanceStatusChart.update();
    
                document.getElementById('present-count').textContent = `${studentData[0]} (${studentTotal > 0 ? ((studentData[0] / studentTotal) * 100).toFixed(1) : 0}%)`;
                document.getElementById('absent-count').textContent = `${studentData[1]} (${studentTotal > 0 ? ((studentData[1] / studentTotal) * 100).toFixed(1) : 0}%)`;
                document.getElementById('late-count').textContent = `${studentData[2]} (${studentTotal > 0 ? ((studentData[2] / studentTotal) * 100).toFixed(1) : 0}%)`;
            }
    
            document.getElementById('attendance-status-title').innerHTML = `Attendance Status Distribution for <span class="student-name">${student.lastName}, ${student.firstName} ${student.middleName || ''}</span>`;
        }
    
        function generateRecommendation(student) {
            if (student.riskLevel === 'high' || student.riskLevel === 'Running for Drop Out') {
                return `Critical: Schedule immediate parent conference to address ${student.primaryAbsenceReason.toLowerCase()} issues`;
            } else if (student.riskLevel === 'medium') {
                return 'Moderate risk: Implement peer support system and weekly progress reviews';
            } else {
                return 'Good attendance: Continue current engagement strategies';
            }
        }
        
        function createIndividualForecastChart(student) {
            const ctx = document.getElementById('individual-forecast-chart');
            if (!ctx) return console.error('Individual forecast chart canvas not found');
            console.log('Creating chart for student:', student.id);
            console.log('Historical Data:', student.timeSeriesData);
            console.log('Forecast Data:', student.forecast);
            console.log('Historical Dates:', student.historical_dates);
            console.log('Forecast Dates:', student.forecast_dates);

            if (!student.timeSeriesData || !student.forecast || !student.historical_dates || !student.forecast_dates) {
                return console.error('Invalid or missing student data');
            }
            if (student.historical_dates.length !== student.timeSeriesData.length || student.forecast_dates.length !== student.forecast.length) {
                return console.error('Data length mismatch');
            }

            const validatedTimeSeries = student.timeSeriesData.map(val => isNaN(val) ? 0 : Number(val));
            const validatedForecast = student.forecast.map(val => isNaN(val) ? 0 : Number(val));

            try {
                if (individualForecastChart) individualForecastChart.destroy();
                individualForecastChart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [...student.historical_dates, ...student.forecast_dates],
                        datasets: [
                            {
                                label: 'Historical Data',
                                data: [...validatedTimeSeries, ...Array(validatedForecast.length).fill(null)],
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Forecasted Data',
                                data: [...Array(validatedTimeSeries.length).fill(null), ...validatedForecast],
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                borderDash: [5, 5],
                                fill: false,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: { label: context => `${context.dataset.label}: ${context.parsed.y.toFixed(1)}%` }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: { display: true, text: 'Attendance Rate (%)' }
                            },
                            x: { title: { display: true, text: 'Date' } }
                        }
                    }
                });
                console.log('Individual forecast chart created successfully');
            } catch (error) {
                console.error('Error creating individual forecast chart:', error);
            }
        }
        
        classFilter.addEventListener('change', () => {
            updateStudentFilter();
            updateAtRiskTable();
            if (forecastChart) forecastChart.destroy();
            if (attendanceStatusChart) attendanceStatusChart.destroy();
            initializeCharts();
            studentSearch.value = '';
            document.getElementById('attendance-status-title').textContent = 'Attendance Status Distribution';
        });

        studentSearch.addEventListener('input', (e) => {
            updateStudentFilter(e.target.value);
        });
        
        studentFilter.addEventListener('change', (e) => {
            if (e.target.value) {
                showStudentPrediction(e.target.value);
            } else {
                document.getElementById('student-prediction-card').style.display = 'none';
                if (attendanceStatusChart) {
                    const selectedClass = classes.find(c => c.id == classFilter.value) || classes[0];
                    const attendanceData = selectedClass.students.reduce((acc, student) => {
                        acc[0] += student.attendanceStatus.present;
                        acc[1] += student.attendanceStatus.absent;
                        acc[2] += student.attendanceStatus.late;
                        return acc;
                    }, [0, 0, 0]);
                    const total = attendanceData.reduce((a, b) => a + b, 0);
                    attendanceStatusChart.data.datasets[0].data = attendanceData;
                    attendanceStatusChart.update();
                    document.getElementById('present-count').textContent = `${attendanceData[0]} (${total > 0 ? ((attendanceData[0] / total) * 100).toFixed(1) : 0}%)`;
                    document.getElementById('absent-count').textContent = `${attendanceData[1]} (${total > 0 ? ((attendanceData[1] / total) * 100).toFixed(1) : 0}%)`;
                    document.getElementById('late-count').textContent = `${attendanceData[2]} (${total > 0 ? ((attendanceData[2] / total) * 100).toFixed(1) : 0}%)`;
                }
                document.getElementById('attendance-status-title').textContent = 'Attendance Status Distribution';
            }
        });

        document.getElementById('clear-filters').addEventListener('click', () => {
            classFilter.value = classes[0]?.id || '';
            studentFilter.value = '';
            studentSearch.value = '';
            updateStudentFilter();
            updateAtRiskTable();
            document.getElementById('student-prediction-card').style.display = 'none';
            if (individualForecastChart) individualForecastChart.destroy();
            if (forecastChart) forecastChart.destroy();
            if (attendanceStatusChart) attendanceStatusChart.destroy();
            initializeCharts();
            document.getElementById('attendance-status-title').textContent = 'Attendance Status Distribution';
        });

        document.getElementById('refresh-data').addEventListener('click', () => {
            window.location.reload();
        });

        document.getElementById('export-chart').addEventListener('click', () => {
            const canvas = document.getElementById('forecast-chart');
            if (!canvas) {
                console.error('No chart canvas found for export');
                return;
            }
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = 'attendance-forecast.png';
            link.click();
        });

        // Initialize on page load
        initializeFilters();
        initializeCharts();

        // Initialize the page
        if (classes.length > 0) {
            initializeFilters();
            initializeCharts();
        } else {
            console.warn('No classes available to initialize charts');
            document.getElementById('current-attendance-rate').textContent = 'N/A';
            document.getElementById('predicted-attendance').textContent = 'N/A';
            document.getElementById('at-risk-count').textContent = '0';
            document.getElementById('attendance-trend').textContent = 'No Data';
            document.getElementById('at-risk-trend').textContent = 'No Data';
            document.getElementById('attendance-status-title').textContent = 'No Attendance Data';
            document.getElementById('at-risk-table-body').innerHTML = '<tr><td colspan="3" style="text-align: center;">No data available</td></tr>';
        }
    </script>
</body>
</html>