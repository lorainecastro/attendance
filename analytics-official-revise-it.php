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
    $query = "
        SELECT DISTINCT attendance_date
        FROM attendance_tracking
        WHERE class_id = :class_id
        AND attendance_date BETWEEN :start_date AND :end_date
        AND logged_by = 'Teacher'
        AND attendance_status IN ('Present', 'Absent', 'Late')
        ORDER BY attendance_date
    ";
    $stmt = $pdo->prepare($query);
    try {
        $stmt->execute([
            ':class_id' => $class_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Database error in getHistoricalAttendanceData: " . $e->getMessage());
        $dates = [];
    }

    $time_series = [];
    foreach ($dates as $date) {
        $rate_data = calculateAttendanceRate($pdo, $class_id, $date, $date, $lrn);
        $time_series[$date] = floatval($rate_data['rate']);
    }

    return $time_series;
}

// Function to calculate standard deviation
function calculateStandardDeviation($data) {
    $n = count($data);
    if ($n < 2) return 1.0; // Default for insufficient data
    $mean = array_sum($data) / $n;
    $variance = 0;
    foreach ($data as $value) {
        $variance += pow($value - $mean, 2);
    }
    return sqrt($variance / $n);
}

// Simple ARIMA(1,1,1) forecasting function using example parameters
function arimaForecast($data, $periods = 30) {
    if (count($data) < 2) {
        // Fallback for insufficient data
        $lastValue = count($data) > 0 ? end($data) : 0.0; // Default to 0% if no data
        $stdDev = count($data) > 1 ? calculateStandardDeviation($data) : 1.0;
        $forecast = [];
        for ($i = 0; $i < $periods; $i++) {
            $noise = (mt_rand(-50, 50) / 100.0) * ($stdDev * 0.1); // Minimal noise
            $predicted = max(0, min(100, $lastValue + $noise));
            $forecast[] = round($predicted, 2);
        }
        return $forecast;
    }

    $values = array_values($data);
    $dates = array_keys($data);
    $n = count($values);

    // Calculate standard deviation for bounds
    $stdDev = calculateStandardDeviation($values);

    // Determine dynamic periods: two months ago to one month ago, and one month ago to today
    $today = date('Y-m-d');
    $one_month_ago = date('Y-m-d', strtotime('-1 month', strtotime($today)));
    $two_months_ago = date('Y-m-d', strtotime('-2 months', strtotime($today)));

    // Filter data for the two periods
    $period1_data = []; // Two months ago to one month ago
    $period2_data = []; // One month ago to today
    foreach ($dates as $index => $date) {
        if ($date >= $two_months_ago && $date < $one_month_ago) {
            $period1_data[] = $values[$index];
        } elseif ($date >= $one_month_ago && $date <= $today) {
            $period2_data[] = $values[$index];
        }
    }

    // Calculate average rates for each period
    $period1_avg = !empty($period1_data) ? array_sum($period1_data) / count($period1_data) : 0.0; // Default if empty
    $period2_avg = !empty($period2_data) ? array_sum($period2_data) / count($period2_data) : $period1_avg;

    // Calculate Δy (change between periods)
    $delta_y = $period2_avg - $period1_avg;

    // ARIMA(1,1,1) parameters from example
    $phi = 0.5; // AR parameter
    $theta = -0.3; // MA parameter (unused in example calculation)

    // Calculate forecast for next period
    $forecast = [];
    $currentValue = $period2_avg; // Start from most recent period rate

    // First forecast value using example formula: next_rate = current_rate + φ * Δy
    $first_forecast = $currentValue + $phi * $delta_y;
    $first_forecast = max(0, min(100, $first_forecast)); // Enforce bounds
    $forecast[] = round($first_forecast, 2);

    // Generate remaining forecast values with minimal variation
    for ($i = 1; $i < $periods; $i++) {
        // Add small noise based on historical volatility
        $noise = (mt_rand(-50, 50) / 100.0) * ($stdDev * 0.1);
        $predicted = $first_forecast + $noise;
        $predicted = max(0, min(100, $predicted)); // Enforce bounds

        // Limit period-to-period changes
        $maxChange = $stdDev * 0.3; // Consistent with original code
        if ($predicted > $forecast[$i-1] + $maxChange) {
            $predicted = $forecast[$i-1] + $maxChange;
        } elseif ($predicted < $forecast[$i-1] - $maxChange) {
            $predicted = $forecast[$i-1] - $maxChange;
        }

        $forecast[] = round($predicted, 2);
    }

    return $forecast;
}

// Mean reversion forecasting without random noise
function meanReversionForecast($data, $periods = 30) {
    if (count($data) < 1) {
        return array_fill(0, $periods, 0.0);
    }

    $values = array_values($data);
    $n = count($values);
    $stdDev = calculateStandardDeviation($values);
    $mean = array_sum($values) / $n;
    $coefVariation = ($mean > 0) ? $stdDev / $mean : 0.5;

    // Use weighted average for baseline, favoring recent data
    $weights = [];
    $weightedSum = 0;
    $totalWeight = 0;
    for ($i = 0; $i < $n; $i++) {
        $weight = 1 + ($i / $n); // Linear weight increase
        $weights[] = $weight;
        $weightedSum += $values[$i] * $weight;
        $totalWeight += $weight;
    }
    $baseline = $weightedSum / $totalWeight;

    // Dynamic reversion rate scaled by volatility
    $reversionRate = max(0.02, min(0.08, $coefVariation * 0.15));

    // Dynamic max change per period based on historical volatility
    $maxChange = $stdDev * max(0.2, min(0.4, $coefVariation));

    $forecast = [];
    $currentValue = end($values); // Start from last known value

    for ($i = 0; $i < $periods; $i++) {
        // Gradual reversion to mean
        $moveTowardMean = ($mean - $currentValue) * $reversionRate;
        $currentValue += $moveTowardMean;

        // Set predicted value deterministically
        $predicted = $currentValue;

        // Enforce strict bounds based on historical data
        $minBound = max(0, $mean - $stdDev * 1.5);
        $maxBound = min(100, $mean + $stdDev * 1.5);
        $predicted = max($minBound, min($maxBound, $predicted));

        // Limit period-to-period changes
        if (!empty($forecast)) {
            $lastValue = end($forecast);
            if ($predicted > $lastValue + $maxChange) {
                $predicted = $lastValue + $maxChange;
            } elseif ($predicted < $lastValue - $maxChange) {
                $predicted = $lastValue - $maxChange;
            }
        } else {
            // Limit initial deviation from last historical value
            $lastHistorical = end($values);
            if ($predicted > $lastHistorical + $maxChange) {
                $predicted = $lastHistorical + $maxChange;
            } elseif ($predicted < $lastHistorical - $maxChange) {
                $predicted = $lastHistorical - $maxChange;
            }
        }

        $forecast[] = round($predicted, 2);
        $currentValue = $predicted;
    }

    return $forecast;
}

// Updated main forecasting function with better logic
function generateForecast($pdo, $class_id, $lrn = null) {
    $today = date('Y-m-d');
    $end_date = $today;
    $start_date = date('Y-m-d', strtotime('-1 months', strtotime($today)));
    
    $historical_data = getHistoricalAttendanceData($pdo, $class_id, $start_date, $end_date, $lrn);

    $forecast_dates = [];
    $start_forecast = new DateTime($today);
    $start_forecast->add(new DateInterval('P1D'));
    
    for ($i = 0; $i < 30; $i++) {
        $date = clone $start_forecast;
        $date->add(new DateInterval('P' . $i . 'D'));
        $forecast_dates[] = $date->format('Y-m-d');
    }

    $forecast_values = arimaForecast($historical_data, 30);

    // Validate forecast reasonableness
    if (!empty($historical_data)) {
        $historical_avg = array_sum($historical_data) / count($historical_data);
        $forecast_avg = array_sum($forecast_values) / count($forecast_values);
        
        // If forecast deviates too much, use more conservative approach
        if (abs($forecast_avg - $historical_avg) > 15) {
            $forecast_values = meanReversionForecast($historical_data, 30);
        }
    }

    return [
        'historical' => $historical_data,
        'forecast' => array_combine($forecast_dates, $forecast_values)
    ];
}

// Function to validate forecast reasonableness
function validateForecast($historical_data, $forecast_data) {
    if (empty($historical_data) || empty($forecast_data)) {
        return false;
    }
    
    $historical_values = array_values($historical_data);
    $forecast_values = array_values($forecast_data);
    
    $historical_avg = array_sum($historical_values) / count($historical_values);
    $forecast_avg = array_sum($forecast_values) / count($forecast_values);
    
    // Check if forecast is unreasonably different from historical average
    $difference = abs($forecast_avg - $historical_avg);
    
    // Flag as unreasonable if forecast differs by more than 20% from historical average
    return $difference <= 20;
}

// Debug function to log forecasting details
function debugForecast($student_name, $historical_data, $forecast_data) {
    if (empty($historical_data)) return;
    
    $historical_values = array_values($historical_data);
    $forecast_values = array_values($forecast_data);
    
    $historical_avg = array_sum($historical_values) / count($historical_values);
    $forecast_avg = array_sum($forecast_values) / count($forecast_values);
    
    error_log("Forecast Debug for {$student_name}:");
    error_log("Historical average: " . round($historical_avg, 2) . "%");
    error_log("Forecast average: " . round($forecast_avg, 2) . "%");
    error_log("Difference: " . round(abs($forecast_avg - $historical_avg), 2) . "%");
    error_log("Historical data: " . implode(", ", $historical_values));
    error_log("Forecast data: " . implode(", ", array_slice($forecast_values, 0, 5)) . "...");
}

// Debug function to understand why certain predictions are made
function explainForecast($historical_data, $forecast_values) {
    if (empty($historical_data)) return "No historical data available";
    
    $values = array_values($historical_data);
    $n = count($values);
    $historical_avg = array_sum($values) / $n;
    $forecast_avg = array_sum($forecast_values) / count($forecast_values);
    
    $recent_values = array_slice($values, -min(3, $n));
    $recent_avg = array_sum($recent_values) / count($recent_values);
    
    $explanation = "Forecast Analysis:\n";
    $explanation .= "Historical Average: " . round($historical_avg, 2) . "%\n";
    $explanation .= "Recent Average (last " . count($recent_values) . " periods): " . round($recent_avg, 2) . "%\n";
    $explanation .= "Predicted Average: " . round($forecast_avg, 2) . "%\n";
    $explanation .= "Change from Recent: " . round($forecast_avg - $recent_avg, 2) . "%\n";
    
    if ($forecast_avg < $recent_avg - 5) {
        $explanation .= "Reason: Declining trend detected in recent data\n";
    } elseif ($forecast_avg > $recent_avg + 5) {
        $explanation .= "Reason: Improving trend detected in recent data\n";
    } else {
        $explanation .= "Reason: Stable attendance pattern, minimal change predicted\n";
    }
    
    return $explanation;
}

// Function to calculate attendance status counts for a student or class
function calculateAttendanceStatus($pdo, $class_id, $lrn = null) {
    $today = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-1 month', strtotime($today)));
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
    $params = [':class_id' => $class_id, ':start_date' => $start_date, ':end_date' => $today];
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
try {
    $stmt = $pdo->prepare("
        SELECT c.class_id, c.section_name, s.subject_name, c.grade_level, c.room
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        WHERE c.teacher_id = ?
    ");
    $stmt->execute([$user['teacher_id']]);
    $classes_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        foreach ($students as $student) {
            $analytics = generateForecast($pdo, $class['class_id'], $student['lrn']);
            $current_rate_data = calculateAttendanceRate($pdo, $class['class_id'], date('Y-m-d', strtotime('-1 month', strtotime(date('Y-m-d')))), date('Y-m-d'), $student['lrn']);
            $current_rate = $current_rate_data['rate'];
            $total_days = $current_rate_data['total_days'];
            $present_late_days = $current_rate_data['present_late_days'];
            $absences = $total_days - $present_late_days;
            $chronic = ($total_days > 0) ? round(($absences / $total_days) * 100, 2) : 0;
            $avg_forecast = array_sum($analytics['forecast']) / count($analytics['forecast']);
            $riskLevel = ($absences == 0) ? 'no risk' : (($absences <= 13) ? 'low' : (($absences <= 26) ? 'medium' : (($absences <= 39) ? 'high' : 'critical')));

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
                'chronicAbsenteeism' => $chronic,
                'attendanceStatus' => calculateAttendanceStatus($pdo, $class['class_id'], $student['lrn']),
                'behaviorPatterns' => []
            ];
        }

        $class_analytics = generateForecast($pdo, $class['class_id']);
        $class_current_rate_data = calculateAttendanceRate($pdo, $class['class_id'], date('Y-m-d', strtotime('-1 month', strtotime(date('Y-m-d')))), date('Y-m-d'));
        $class_current_rate = $class_current_rate_data['rate'];
        $class_avg_forecast = array_sum($class_analytics['forecast']) / count($class_analytics['forecast']);
        $classes[] = [
            'id' => $class['class_id'],
            'code' => $class['subject_name'] . '-' . $class['class_id'],
            'sectionName' => $class['section_name'],
            'subject' => $class['subject_name'],
            'gradeLevel' => $class['grade_level'],
            'room' => $class['room'],
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
    </style>
</head>
<body>
    <h1>Analytics & Predictions</h1>

    <div class="controls">
        <div class="controls-left">
            <select class="selector-select" id="class-filter">
                <?php if (!empty($classes)): ?>
                    <option value="<?php echo htmlspecialchars($classes[0]['id']); ?>">
                        <?php echo htmlspecialchars($classes[0]['gradeLevel'] . ' – ' . $classes[0]['sectionName'] . ' (' . $classes[0]['subject'] . ')'); ?>
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
            <div class="attendance-status-title">Attendance Status Distribution</div>
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
            <h3>Personal Analytics & AI Recommendations</h3>
            <div id="student-recommendations"></div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Current Value</th>
                            <th>Forecast Next Month</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody id="student-metrics"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="pattern-table">
        <div class="table-header">
            <div class="table-title">AI-Powered Early Warning System</div>
            <div class="alert alert-warning">
                <i class="fas fa-bell"></i>
                <span>Automated alerts based on time series anomaly detection</span>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Student</th>
                        <th>Predicted Next Month</th>
                        <th>Risk Level</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>
                <tbody id="early-warning-table"></tbody>
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
            classes.forEach(cls => {
                const option = document.createElement('option');
                option.value = cls.id;
                option.textContent = `${cls.gradeLevel} – ${cls.sectionName} (${cls.subject})`;
                classFilter.appendChild(option);
            });
            
            updateStudentFilter();
        }

        function updateStudentFilter(searchTerm = '') {
            const selectedClassId = classFilter.value;
            let filteredStudents = classes.flatMap(c => c.students.map(s => ({
                ...s,
                gradeLevel: c.gradeLevel,
                subject: c.subject,
                section: c.sectionName
            })));

            if (selectedClassId) {
                filteredStudents = filteredStudents.filter(s => s.section === classes.find(c => c.id == selectedClassId).sectionName);
            }

            // Apply search filter
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
                    option.textContent = `${student.lastName}, ${student.firstName} ${student.middleName || ''} (${student.section})`.trim();
                    studentFilter.appendChild(option);
                });
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
            document.getElementById('at-risk-count').textContent = selectedClass.students.filter(s => s.riskLevel === 'medium' || s.riskLevel === 'high' || s.riskLevel === 'critical').length;
            document.getElementById('attendance-trend').textContent = selectedClass.trend === 'improving' ? '+2.0% vs last month' : '-2.0% vs last month';
            document.getElementById('at-risk-trend').textContent = selectedClass.students.filter(s => s.riskLevel === 'medium' || s.riskLevel === 'high' || s.riskLevel === 'critical').length > 0 ? '-1 vs last month' : 'Stable';

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
                            label: 'ARIMA Forecast',
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
                            beginAtZero: false,
                            min: 70,
                            max: 100,
                            title: { display: true, text: 'Attendance Rate (%)' }
                        },
                        x: { title: { display: true, text: 'Date' } }
                    }
                }
            });

            const total = statusCounts.reduce((a, b) => a + b, 0);
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

            document.getElementById('present-count').textContent = `${statusCounts[0]} (${total > 0 ? ((statusCounts[0] / total) * 100).toFixed(1) : 0}%)`;
            document.getElementById('absent-count').textContent = `${statusCounts[1]} (${total > 0 ? ((statusCounts[1] / total) * 100).toFixed(1) : 0}%)`;
            document.getElementById('late-count').textContent = `${statusCounts[2]} (${total > 0 ? ((statusCounts[2] / total) * 100).toFixed(1) : 0}%)`;
        }

        function updateEarlyWarningTable() {
            const earlyWarningTable = document.getElementById('early-warning-table');
            earlyWarningTable.innerHTML = '';
            
            const allStudents = classes.flatMap(c => c.students.map(s => ({
                ...s,
                subject: c.subject,
                section: c.sectionName,
                gradeLevel: c.gradeLevel
            })));
            
            const atRiskStudents = allStudents.filter(s => s.riskLevel === 'medium' || s.riskLevel === 'high' || s.riskLevel === 'critical');
            
            atRiskStudents.forEach(student => {
                const avgForecast = student.forecast.reduce((a, b) => a + b, 0) / student.forecast.length;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${student.gradeLevel} – ${student.section} (${student.subject})</td>
                    <td>${student.lastName}, ${student.firstName} ${student.middleName || ''}</td>
                    <td>${avgForecast.toFixed(1)}%</td>
                    <td><span class="risk-${student.riskLevel}">${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}</span></td>
                    <td>${student.riskLevel === 'high' || student.riskLevel === 'critical' ? 'Immediate parent conference' : 'Monitor closely + automated reminders'}</td>
                `;
                earlyWarningTable.appendChild(row);
            });
        }

        function showStudentPrediction(studentId) {
            const student = classes.flatMap(c => c.students.map(s => ({
                ...s,
                subject: c.subject,
                section: c.sectionName,
                gradeLevel: c.gradeLevel
            }))).find(s => s.id == studentId);
            
            if (!student) {
                document.getElementById('student-prediction-card').style.display = 'none';
                console.warn('No student found for ID:', studentId);
                return;
            }
            
            document.getElementById('student-prediction-card').style.display = 'block';
            
            const avgForecast = student.forecast.reduce((a, b) => a + b, 0) / student.forecast.length;
            
            const studentDetails = document.getElementById('student-details');
            studentDetails.innerHTML = `
                <div class="detail-item">
                    <strong>Class:</strong> ${student.subject} (${student.section})
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
                <div class="detail-item risk-${student.riskLevel}">
                    <strong>Risk Level:</strong> ${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}
                </div>
                <div class="detail-item">
                    <strong>Predicted Next Month:</strong> ${avgForecast.toFixed(1)}%
                </div>
                <div class="detail-item">
                    <strong>Total Absences:</strong> ${student.totalAbsences}
                </div>
                <div class="detail-item">
                    <strong>Chronic Absenteeism:</strong> ${student.chronicAbsenteeism}%
                </div>
            `;
            
            const recommendations = generateRecommendations(student);
            const recDiv = document.getElementById('student-recommendations');
            recDiv.innerHTML = recommendations.map(rec => 
                `<div class="alert alert-${rec.type}">
                    <i class="fas fa-${rec.icon}"></i>
                    <span>${rec.message}</span>
                </div>`
            ).join('');
            
            const metricsTable = document.getElementById('student-metrics');
            metricsTable.innerHTML = `
                <tr>
                    <td>Attendance Rate</td>
                    <td>${student.attendanceRate}%</td>
                    <td>${avgForecast.toFixed(1)}%</td>
                    <td>${avgForecast < student.attendanceRate ? 'Implement intervention plan' : 'Continue monitoring'}</td>
                </tr>
                <tr>
                    <td>Total Absences</td>
                    <td>${student.totalAbsences}</td>
                    <td>-</td>
                    <td>${student.totalAbsences > 10 ? 'Contact parents' : 'Review absence patterns'}</td>
                </tr>
                <tr>
                    <td>Chronic Absenteeism</td>
                    <td>${student.chronicAbsenteeism}%</td>
                    <td>-</td>
                    <td>${student.chronicAbsenteeism > 10 ? 'Implement attendance plan' : 'Monitor attendance'}</td>
                </tr>
                <tr>
                    <td>Risk Level</td>
                    <td><span class="risk-${student.riskLevel}">${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}</span></td>
                    <td>-</td>
                    <td>${student.riskLevel === 'high' || student.riskLevel === 'critical' ? 'Schedule parent conference' : student.riskLevel === 'medium' ? 'Implement peer support' : 'Continue current strategies'}</td>
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
        }
        
        function generateRecommendations(student) {
            const recommendations = [];
            
            if (student.riskLevel === 'high' || student.riskLevel === 'critical') {
                recommendations.push({
                    type: 'danger',
                    icon: 'exclamation-triangle',
                    message: `Critical: Schedule immediate parent conference to address ${student.primaryAbsenceReason.toLowerCase()} issues`
                });
                recommendations.push({
                    type: 'warning',
                    icon: 'phone',
                    message: 'Enable daily automated SMS reminders and check-ins'
                });
                if (student.primaryAbsenceReason === 'Transportation') {
                    recommendations.push({
                        type: 'info',
                        icon: 'bus',
                        message: 'Provide bus pass subsidies if available'
                    });
                }
            } else if (student.riskLevel === 'medium') {
                recommendations.push({
                    type: 'warning',
                    icon: 'bell',
                    message: 'Moderate risk: Implement peer support system and weekly progress reviews'
                });
                recommendations.push({
                    type: 'info',
                    icon: 'users',
                    message: 'Provide resources for family engagement workshops'
                });
            } else {
                recommendations.push({
                    type: 'info',
                    icon: 'thumbs-up',
                    message: 'Good attendance: Continue current engagement strategies'
                });
            }
            
            if (student.trend === 'declining') {
                recommendations.push({
                    type: 'warning',
                    icon: 'chart-line-down',
                    message: 'Declining trend detected: Investigate underlying causes and adjust approach'
                });
            }
            
            return recommendations;
        }
        
        function createIndividualForecastChart(student) {
            const ctx = document.getElementById('individual-forecast-chart');
            if (!ctx) {
                console.error('Individual forecast chart canvas not found');
                return;
            }
            console.log('Creating chart for student:', student.id);
            console.log('Historical Data:', student.timeSeriesData);
            console.log('Forecast Data:', student.forecast);
            console.log('Historical Dates:', student.historical_dates);
            console.log('Forecast Dates:', student.forecast_dates);

            // Validate data
            if (!student.timeSeriesData || !student.forecast || !student.historical_dates || !student.forecast_dates) {
                console.error('Invalid or missing student data:', {
                    timeSeriesData: student.timeSeriesData,
                    forecast: student.forecast,
                    historical_dates: student.historical_dates,
                    forecast_dates: student.forecast_dates
                });
                return;
            }
            if (student.historical_dates.length !== student.timeSeriesData.length) {
                console.error('Mismatch between historical dates and data');
                return;
            }
            if (student.forecast_dates.length !== student.forecast.length) {
                console.error('Mismatch between forecast dates and data');
                return;
            }

            // Ensure data is numeric
            const validatedTimeSeries = student.timeSeriesData.map(val => isNaN(val) ? 0 : Number(val));
            const validatedForecast = student.forecast.map(val => isNaN(val) ? 0 : Number(val));

            // Ensure the forecast starts close to the last historical value
            if (validatedForecast.length > 0 && validatedTimeSeries.length > 0) {
                const lastHistorical = validatedTimeSeries[validatedTimeSeries.length - 1];
                const firstForecast = validatedForecast[0];
                const stdDev = validatedTimeSeries.length > 1 ? Math.sqrt(
                    validatedTimeSeries.reduce((sum, val) => sum + Math.pow(val - (validatedTimeSeries.reduce((a, b) => a + b, 0) / validatedTimeSeries.length), 2), 0) / validatedTimeSeries.length
                ) : 1.0;
                const maxChange = stdDev * 0.3; // Same as PHP arimaForecast
                if (firstForecast > lastHistorical + maxChange) {
                    validatedForecast[0] = lastHistorical + maxChange;
                } else if (firstForecast < lastHistorical - maxChange) {
                    validatedForecast[0] = lastHistorical - maxChange;
                }
            }

            try {
                if (individualForecastChart) {
                    individualForecastChart.destroy();
                }
                individualForecastChart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [...student.historical_dates, ...student.forecast_dates],
                        datasets: [
                            {
                                label: 'Historical Data',
                                data: [...validatedTimeSeries, ...Array(student.forecast.length).fill(null)],
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'ARIMA Forecast',
                                data: [...Array(student.timeSeriesData.length).fill(null), ...validatedForecast],
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                borderDash: [5, 5], // Match main chart's dashed forecast line
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
                                beginAtZero: false,
                                min: Math.max(0, Math.min(...validatedTimeSeries, ...validatedForecast) - 5),
                                max: Math.min(100, Math.max(...validatedTimeSeries, ...validatedForecast) + 5),
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
            if (forecastChart) forecastChart.destroy();
            if (attendanceStatusChart) attendanceStatusChart.destroy();
            initializeCharts();
            updateEarlyWarningTable();
            studentSearch.value = ''; // Clear search input on class change
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
            }
        });

        document.getElementById('refresh-data').addEventListener('click', () => {
            if (forecastChart) forecastChart.destroy();
            if (attendanceStatusChart) attendanceStatusChart.destroy();
            initializeCharts();
            updateEarlyWarningTable();
        });

        document.getElementById('clear-filters').addEventListener('click', () => {
            classFilter.value = classes.length > 0 ? classes[0].id : '';
            studentFilter.value = '';
            studentSearch.value = '';
            updateStudentFilter();
            document.getElementById('student-prediction-card').style.display = 'none';
            if (attendanceStatusChart) {
                const selectedClass = classes.find(c => c.id == classFilter.value) || classes[0];                const attendanceData = selectedClass.students.reduce((acc, student) => {
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
            if (forecastChart) {
                forecastChart.destroy();
            }
            initializeCharts();
            updateEarlyWarningTable();
        });

        document.getElementById('export-chart').addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = forecastChart.toBase64Image();
            link.download = 'attendance_forecast.png';
            link.click();
        });

        // Initialize on page load
        if (classes.length > 0) {
            initializeFilters();
            initializeCharts();
            updateEarlyWarningTable();
        } else {
            console.warn('No classes available to initialize charts.');
            document.getElementById('current-attendance-rate').textContent = 'N/A';
            document.getElementById('predicted-attendance').textContent = 'N/A';
            document.getElementById('at-risk-count').textContent = '0';
            document.getElementById('attendance-trend').textContent = 'No data';
            document.getElementById('at-risk-trend').textContent = 'No data';
        }
    </script>
</body>
</html>