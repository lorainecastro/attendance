<?php
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
if (!$pdo instanceof PDO) {
    http_response_code(500);
    die("Failed to establish database connection. Please check your configuration.");
}

$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    error_log("Teacher ID not found in session.");
    destroySession();
    header("Location: index.php");
    exit();
}

// Function to calculate attendance percentages for today
function calculateAttendancePercentages($teacher_id) {
    $pdo = getDBConnection();
    try {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT 
                c.class_id,
                COUNT(*) as total_records,
                SUM(CASE WHEN at.attendance_status = 'Present' THEN 1 ELSE 0 END) as present_records
            FROM classes c
            LEFT JOIN class_students cs ON c.class_id = cs.class_id AND cs.is_enrolled = 1
            LEFT JOIN attendance_tracking at ON cs.class_id = at.class_id AND cs.lrn = at.lrn
            WHERE c.teacher_id = ? 
                AND at.attendance_date = ? 
                AND c.isArchived = 0
            GROUP BY c.class_id
        ");
        $stmt->execute([$teacher_id, $today]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attendanceData = [];
        $totalPresent = 0;
        $totalRecords = 0;

        foreach ($results as $row) {
            $class_id = $row['class_id'];
            $total = $row['total_records'];
            $present = $row['present_records'];
            $percentage = $total > 0 ? ($present / $total) * 100 : 0;
            $attendanceData[$class_id] = $percentage;
            $totalPresent += $present;
            $totalRecords += $total;
            error_log("Class ID: $class_id, Present: $present, Total: $total, Percentage: $percentage%");
        }

        $overallAverage = $totalRecords > 0 ? ($totalPresent / $totalRecords) * 100 : 0;
        error_log("Total Present: $totalPresent, Total Records: $totalRecords, Overall Average: $overallAverage%");

        return [
            'success' => true,
            'class_percentages' => $attendanceData,
            'overall_average' => $overallAverage
        ];
    } catch (PDOException $e) {
        error_log("Calculate attendance percentages error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to calculate attendance percentages: ' . $e->getMessage()];
    }
}

// Function to calculate daily attendance rate for a class or student
function calculateAttendanceRate($pdo, $class_id, $lrn = null) {
    $today = date('Y-m-d');
    $total_days = 0;
    $present_late_days = 0;

    $query = "
        SELECT attendance_date, lrn, attendance_status
        FROM attendance_tracking
        WHERE class_id = :class_id
        AND attendance_date = :today
        AND logged_by IN ('Teacher', 'Device Camera', 'Scanner Device')
        AND attendance_status IN ('Present', 'Absent', 'Late')
    ";
    if ($lrn) {
        $query .= " AND lrn = :lrn";
    }

    $stmt = $pdo->prepare($query);
    $params = [
        ':class_id' => $class_id,
        ':today' => $today
    ];
    if ($lrn) {
        $params[':lrn'] = $lrn;
    }

    try {
        $stmt->execute($params);
        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in calculateAttendanceRate: " . $e->getMessage());
        return [
            'rate' => '0.00',
            'total_days' => 0,
            'present_late_days' => 0
        ];
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

// Set earliest date to today
$earliest_date = date('Y-m-d');

// Fetch attendance data for JavaScript processing
$stmt = $pdo->prepare("
    SELECT a.class_id, a.attendance_date, a.lrn, a.attendance_status, a.time_checked, a.is_qr_scanned,
           sch.start_time, sch.grace_period_minutes, sch.end_time,
           s.first_name, s.last_name
    FROM attendance_tracking a 
    JOIN classes c ON a.class_id = c.class_id 
    LEFT JOIN schedules sch ON c.class_id = sch.class_id AND DATE_FORMAT(a.attendance_date, '%W') = LOWER(sch.day)
    LEFT JOIN students s ON a.lrn = s.lrn
    WHERE c.teacher_id = ? AND a.attendance_date = ? 
        AND a.logged_by IN ('Teacher', 'Device Camera', 'Scanner Device') 
        AND a.attendance_status IN ('Present', 'Absent', 'Late')
        AND c.isArchived = 0
    ORDER BY a.attendance_date DESC, a.class_id, a.lrn
");
$stmt->execute([$teacher_id, $earliest_date]);
$attendance_raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getDashboardStats($pdo, $teacher_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_classes 
            FROM classes 
            WHERE teacher_id = ? AND status = 'active' AND isArchived = 0
        ");
        $stmt->execute([$teacher_id]);
        $totalClassesResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalClasses = $totalClassesResult ? (int)$totalClassesResult['total_classes'] : 0;

        $stmt = $pdo->prepare("
            SELECT SUM(student_count) as total_students
            FROM (
                SELECT 
                    c.class_id,
                    (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.class_id AND cs.is_enrolled = 1) as student_count
                FROM classes c
                WHERE c.teacher_id = ? AND c.status = 'active' AND c.isArchived = 0
            ) as subquery
        ");
        $stmt->execute([$teacher_id]);
        $totalStudentsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalStudents = $totalStudentsResult ? (int)$totalStudentsResult['total_students'] : 0;

        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN at.attendance_status = 'Present' THEN 1 END) as present,
                COUNT(CASE WHEN at.attendance_status = 'Absent' THEN 1 END) as absent,
                COUNT(CASE WHEN at.attendance_status = 'Late' THEN 1 END) as late,
                COUNT(*) as total
            FROM attendance_tracking at
            INNER JOIN classes c ON at.class_id = c.class_id
            WHERE c.teacher_id = ? AND at.attendance_date = ? 
            AND c.status = 'active' 
            AND c.isArchived = 0
            AND at.logged_by IN ('Teacher', 'Device Camera', 'Scanner Device') 
            AND at.attendance_status IN ('Present', 'Absent', 'Late')
        ");
        $stmt->execute([$teacher_id, $today]);
        $monthAttendance = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];

        $attended = (int)$monthAttendance['present'] + (int)$monthAttendance['late'];
        $total = (int)$monthAttendance['total'];
        $monthAttendanceRate = $total > 0 ? round(($attended / $total) * 100, 2) : 0;

        return [
            'totalClasses' => $totalClasses,
            'totalStudents' => $totalStudents,
            'monthAttendanceRate' => $monthAttendanceRate,
            'monthAbsent' => (int)$monthAttendance['absent'],
            'monthPresent' => (int)$monthAttendance['present'],
            'monthLate' => (int)$monthAttendance['late']
        ];
    } catch (PDOException $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        return [
            'totalClasses' => 0,
            'totalStudents' => 0,
            'monthAttendanceRate' => 0,
            'monthAbsent' => 0,
            'monthPresent' => 0,
            'monthLate' => 0
        ];
    }
}

function getClassesData($pdo, $teacher_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.class_id, 
                c.section_name, 
                sub.subject_name, 
                c.room, 
                COUNT(DISTINCT cs.lrn) as students_count,
                c.status
            FROM classes c
            INNER JOIN subjects sub ON c.subject_id = sub.subject_id
            LEFT JOIN class_students cs ON c.class_id = cs.class_id AND cs.is_enrolled = 1
            WHERE c.teacher_id = ? AND c.status = 'active' AND c.isArchived = 0
            GROUP BY c.class_id
        ");
        $stmt->execute([$teacher_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $today = date('Y-m-d');
        
        foreach ($classes as &$class) {
            $rate_data = calculateAttendanceRate($pdo, $class['class_id']);
            $class['attendance_percentage'] = $rate_data['rate'];
        }
        
        return $classes;
    } catch (PDOException $e) {
        error_log("Error getting classes data: " . $e->getMessage());
        return [];
    }
}

function getTodaySchedule($pdo, $teacher_id) {
    try {
        $today = strtolower(date('l'));
        $stmt = $pdo->prepare("
            SELECT 
                c.class_id, 
                c.section_name, 
                sub.subject_name, 
                c.room, 
                s.start_time, 
                s.end_time, 
                COUNT(cs.lrn) as students_count, 
                c.status,
                c.grade_level
            FROM classes c
            INNER JOIN schedules s ON c.class_id = s.class_id
            INNER JOIN subjects sub ON c.subject_id = sub.subject_id
            LEFT JOIN class_students cs ON c.class_id = cs.class_id AND cs.is_enrolled = 1
            WHERE c.teacher_id = ? 
                AND s.day = ?
                AND c.status = 'active'
                AND c.isArchived = 0
            GROUP BY c.class_id, s.start_time, s.end_time
            ORDER BY s.start_time ASC
        ");
        $stmt->execute([$teacher_id, $today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting today's schedule: " . $e->getMessage());
        return [];
    }
}

function getTodayAttendanceData($pdo, $teacher_id) {
    try {
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT 
                c.section_name, 
                COUNT(CASE WHEN at.attendance_status = 'Present' THEN 1 END) as present,
                COUNT(CASE WHEN at.attendance_status = 'Absent' THEN 1 END) as absent,
                COUNT(CASE WHEN at.attendance_status = 'Late' THEN 1 END) as late,
                COUNT(*) as total
            FROM classes c
            LEFT JOIN attendance_tracking at ON c.class_id = at.class_id
                AND at.attendance_date = ?
                AND at.logged_by IN ('Teacher', 'Device Camera', 'Scanner Device') 
                AND at.attendance_status IN ('Present', 'Absent', 'Late')
            WHERE c.teacher_id = ? AND c.status = 'active' AND c.isArchived = 0
            GROUP BY c.class_id
            HAVING total > 0
        ");
        $stmt->execute([$today, $teacher_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting today's attendance data: " . $e->getMessage());
        return [];
    }
}

// Process attendance data for JavaScript
$attendanceData = [];
foreach ($attendance_raw_data as $record) {
    $date = $record['attendance_date'];
    $classId = $record['class_id'];
    $lrn = $record['lrn'];
    
    if (!isset($attendanceData[$date])) {
        $attendanceData[$date] = [];
    }
    if (!isset($attendanceData[$date][$classId])) {
        $attendanceData[$date][$classId] = [];
    }
    
    $attendanceData[$date][$classId][$lrn] = [
        'status' => $record['attendance_status'],
        'time_checked' => $record['time_checked'],
        'is_qr_scanned' => $record['is_qr_scanned'],
        'student_name' => trim($record['first_name'] . ' ' . $record['last_name'])
    ];
}

// Get all dashboard data
$dashboardStats = getDashboardStats($pdo, $teacher_id);
$classesData = getClassesData($pdo, $teacher_id);
$todaySchedule = getTodaySchedule($pdo, $teacher_id);
$monthlyAttendanceData = getTodayAttendanceData($pdo, $teacher_id);

// Format date for chart titles
$todayFormatted = date('M d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-hover: #2563eb;
            --primary-blue-light: #dbeafe;
            --success-green: #22c55e;
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
            --primary-gradient: linear-gradient(135deg, #3b82f6, #3b82f6);
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
            margin-bottom: 20px;
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--whitefont-color);
        }

        .bg-purple { background: var(--primary-gradient); }
        .bg-pink { background: var(--secondary-gradient); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-green { background: linear-gradient(135deg, #10b981, #34d399); }
        .bg-red { background: linear-gradient(135deg, #ef4444, #f87171); }

        .card-title {
            font-size: 14px;
            color: var(--grayfont-color);
            margin-bottom: 5px;
        }

        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--blackfont-color);
        }

        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
        }

        .schedule-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-table th,
        .schedule-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .schedule-table th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: 14px;
            background: var(--inputfield-color);
        }

        .schedule-table td {
            font-size: 14px;
            color: var(--blackfont-color);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: var(--font-size-sm);
            font-weight: 500;
        }

        .status-badge.active {
            background-color: var(--status-present-bg);
            color: var(--success-color);
        }

        .status-badge.inactive {
            background-color: var(--status-absent-bg);
            color: var(--danger-color);
        }

        .btn-info {
            background: var(--primary-blue);
            color: var(--whitefont-color);
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition-normal);
        }

        .btn-info:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        .no-schedule {
            text-align: center;
            padding: 20px;
            color: var(--grayfont-color);
            font-size: var(--font-size-lg);
        }

        .quick-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
        }

        .action-btn {
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
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .action-btn:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        #classDetailsModal .status-present {
            background-color: var(--status-present-bg);
            color: var(--success-color);
        }

        #classDetailsModal .status-absent {
            background-color: var(--status-absent-bg);
            color: var(--danger-color);
        }

        #classDetailsModal .status-late {
            background-color: var(--status-late-bg);
            color: var(--warning-color);
        }

        #classDetailsModal .status-none {
            background-color: var(--status-none-bg);
            color: var(--grayfont-color);
        }

        #modalAttendanceTable:empty::after {
            content: 'No attendance records available';
            display: block;
            text-align: center;
            padding: var(--spacing-md);
            color: var(--grayfont-color);
            font-size: var(--font-size-base);
        }

        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: var(--spacing-sm);
            }
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            .schedule-table th,
            .schedule-table td {
                padding: 10px;
            }
            .card-value {
                font-size: 20px;
            }
            .table-responsive {
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            #classDetailsModal {
                padding: var(--spacing-sm);
            }
            #classDetailsModal > div {
                width: 95%;
                padding: var(--spacing-md);
            }
            #modalTitle {
                font-size: var(--font-size-lg);
            }
        }
    </style>
</head>

<body>
    <h1>Teacher Dashboard</h1>

    <div class="quick-actions">
        <a href="attendance.php" class="action-btn">
            <i class="fas fa-check-circle"></i> Mark Attendance
        </a>
        <a href="manage-classes.php" class="action-btn">
            <i class="fas fa-book"></i> View Class Details
        </a>
        <a href="reports.php" class="action-btn">
            <i class="fas fa-file-alt"></i> Generate Report
        </a>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Classes</div>
                    <div class="card-value"><?php echo htmlspecialchars($dashboardStats['totalClasses']); ?></div>
                </div>
                <div class="card-icon bg-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z" />
                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Students</div>
                    <div class="card-value"><?php echo htmlspecialchars($dashboardStats['totalStudents']); ?></div>
                </div>
                <div class="card-icon bg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z" />
                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Attendance Rate (<?php echo $todayFormatted; ?>)</div>
                    <div class="card-value"><?php echo htmlspecialchars($dashboardStats['monthAttendanceRate']); ?>%</div>
                </div>
                <div class="card-icon bg-green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Absent (<?php echo $todayFormatted; ?>)</div>
                    <div class="card-value"><?php echo htmlspecialchars($dashboardStats['monthAbsent']); ?></div>
                </div>
                <div class="card-icon bg-red">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Average Attendance Rate by Class (<?php echo $todayFormatted; ?>)</div>
            </div>
            <div>
                <canvas id="attendance-chart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Attendance Status (<?php echo $todayFormatted; ?>)</div>
            </div>
            <div>
                <canvas id="status-chart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <div class="schedule-card">
        <div class="chart-header">
            <div class="chart-title">Today's Schedule</div>
        </div>
        <div class="table-responsive">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Grade Level</th>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Total Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($todaySchedule)): ?>
                        <tr>
                            <td colspan="6" class="no-schedule">No classes scheduled today</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($todaySchedule as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['grade_level'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($class['section_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($class['subject_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    echo isset($class['start_time'], $class['end_time']) 
                                        ? date('g:i A', strtotime($class['start_time'])) . ' - ' . date('g:i A', strtotime($class['end_time'])) 
                                        : 'N/A'; 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($class['room'] ? $class['room'] : 'No room specified'); ?></td>
                                <td><?php echo htmlspecialchars($class['students_count'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="classDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border-radius: var(--radius-lg); padding: var(--spacing-lg); width: 90%; max-width: 800px; max-height: 80vh; overflow-y: auto; box-shadow: var(--shadow-lg); border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
                <h2 id="modalTitle" style="font-size: var(--font-size-xl); color: var(--blackfont-color);">Class Details</h2>
                <button onclick="closeModal()" style="background: none; border: none; font-size: var(--font-size-lg); cursor: pointer; color: var(--grayfont-color);">&times;</button>
            </div>
            <div id="modalContent">
                <div id="modalClassInfo" style="margin-bottom: var(--spacing-md);"></div>
                <div id="modalAttendance" style="margin-bottom: var(--spacing-md);">
                    <h3 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-sm);">Attendance Records</h3>
                    <div class="table-responsive">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                    <th>Time Checked</th>
                                    <th>QR Scanned</th>
                                </tr>
                            </thead>
                            <tbody id="modalAttendanceTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <button onclick="closeModal()" class="btn-info">Close</button>
            </div>
        </div>
    </div>

    <script>
        const attendanceData = <?php echo json_encode($attendanceData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const classesData = <?php echo json_encode($classesData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const monthlyAttendanceData = <?php echo json_encode($monthlyAttendanceData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const dashboardStats = <?php echo json_encode($dashboardStats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const earliestDate = '<?php echo $earliest_date; ?>';
        const today = '<?php echo date('Y-m-d'); ?>';

        function calcAttendanceRate(class_id, lrn) {
            let totalMarkedDays = 0;
            let presentOrLateDays = 0;

            console.log(`Calculating attendance for LRN: ${lrn}, Class: ${class_id}`);
            console.log(`Date: ${today}`);

            if (attendanceData[today]?.[class_id]) {
                const classData = attendanceData[today][class_id];
                let hasMarkedDay = false;
                for (const studentLrn in classData) {
                    const dayData = classData[studentLrn];
                    if (dayData && dayData.status && dayData.status !== '') {
                        hasMarkedDay = true;
                        break;
                    }
                }

                if (hasMarkedDay) {
                    const studentDayData = classData[lrn];
                    if (studentDayData && studentDayData.status && studentDayData.status !== '') {
                        totalMarkedDays++;
                        if (studentDayData.status === 'Present' || studentDayData.status === 'Late') {
                            presentOrLateDays++;
                        }
                        console.log(`${today}: ${studentDayData.status}`);
                    }
                }
            }

            console.log(`Total marked days: ${totalMarkedDays}, Present/Late days: ${presentOrLateDays}`);
            const rate = totalMarkedDays > 0 ? (presentOrLateDays / totalMarkedDays * 100).toFixed(2) : '0.00';
            console.log(`Attendance rate: ${rate}%`);
            return rate + '%';
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                if (!attendanceData || !classesData || !monthlyAttendanceData || !dashboardStats) {
                    console.error('Invalid or missing dashboard data');
                    alert('Failed to load dashboard data. Please try again later.');
                    return;
                }
                
                console.log('Dashboard data loaded successfully');
                console.log('Attendance data:', attendanceData);
                console.log('Classes data:', classesData);
                
                initializeCharts();

                if (classesData.length > 0 && Object.keys(attendanceData).length > 0) {
                    console.log('Testing attendance calculation...');
                    const firstClass = classesData[0];
                    console.log('First class:', firstClass);
                }
                
            } catch (error) {
                console.error('Error initializing dashboard:', error);
                alert('An error occurred while loading the dashboard. Please try again later.');
            }
        });

        function viewClass(classId) {
            try {
                const classData = classesData.find(c => c.class_id == classId);
                if (!classData) {
                    alert('Class not found.');
                    return;
                }

                document.getElementById('modalTitle').textContent = `Class: ${classData.section_name || 'Unknown'}`;
                const classInfo = `
                    <p><strong>Subject:</strong> ${classData.subject_name || 'N/A'}</p>
                    <p><strong>Room:</strong> ${classData.room ? classData.room : 'No room specified'}</p>
                    <p><strong>Total Students:</strong> ${classData.students_count || 0}</p>
                    <p><strong>Attendance Rate (<?php echo $todayFormatted; ?>):</strong> ${classData.attendance_percentage || 0}%</p>
                    <p><strong>Status:</strong> <span class="status-badge ${classData.status || 'inactive'}">${classData.status ? classData.status.charAt(0).toUpperCase() + classData.status.slice(1) : 'Inactive'}</span></p>
                `;
                document.getElementById('modalClassInfo').innerHTML = classInfo;

                const attendanceTable = document.getElementById('modalAttendanceTable');
                attendanceTable.innerHTML = '';

                let attendanceFound = false;
                for (const date in attendanceData) {
                    if (attendanceData[date][classId]) {
                        for (const lrn in attendanceData[date][classId]) {
                            const record = attendanceData[date][classId][lrn];
                            const statusClass = `status-${(record.status || 'None').toLowerCase()}`;
                            const row = `
                                <tr>
                                    <td>${date}</td>
                                    <td>${record.student_name || 'Unknown'}</td>
                                    <td><span class="status-badge ${statusClass}">${record.status || 'None'}</span></td>
                                    <td>${record.time_checked || 'N/A'}</td>
                                    <td>${record.is_qr_scanned ? 'Yes' : 'No'}</td>
                                </tr>
                            `;
                            attendanceTable.innerHTML += row;
                            attendanceFound = true;
                        }
                    }
                }

                if (!attendanceFound) {
                    attendanceTable.innerHTML = '';
                }

                document.getElementById('classDetailsModal').style.display = 'flex';
            } catch (error) {
                console.error('Error displaying class details:', error);
                alert('An error occurred while loading class details.');
            }
        }

        function closeModal() {
            document.getElementById('classDetailsModal').style.display = 'none';
        }

        function initializeCharts() {
            const primaryBlue = getComputedStyle(document.documentElement).getPropertyValue('--primary-blue').trim();
            const successGreen = getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim();
            const dangerRed = getComputedStyle(document.documentElement).getPropertyValue('--danger-color').trim();
            const warningYellow = getComputedStyle(document.documentElement).getPropertyValue('--warning-color').trim();

            const attendanceChartData = {
                labels: classesData.length ? classesData.map(c => c.section_name || 'Unknown') : ['No Data'],
                values: classesData.length ? classesData.map(c => parseFloat(c.attendance_percentage) || 0) : [0]
            };

            const statusData = {
                labels: ['Present', 'Absent', 'Late'],
                values: [
                    monthlyAttendanceData.length ? monthlyAttendanceData.reduce((sum, c) => sum + (parseInt(c.present) || 0), 0) : 0,
                    monthlyAttendanceData.length ? monthlyAttendanceData.reduce((sum, c) => sum + (parseInt(c.absent) || 0), 0) : 0,
                    monthlyAttendanceData.length ? monthlyAttendanceData.reduce((sum, c) => sum + (parseInt(c.late) || 0), 0) : 0
                ]
            };

            console.log('Chart data prepared:', { attendanceChartData, statusData });

            const attendanceChartCtx = document.getElementById('attendance-chart');
            if (!attendanceChartCtx) {
                console.error('Attendance chart canvas not found');
                return;
            }
            
            const attendanceChart = new Chart(attendanceChartCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: attendanceChartData.labels,
                    datasets: [{
                        label: 'Attendance Rate (%)',
                        data: attendanceChartData.values,
                        backgroundColor: primaryBlue,
                        borderColor: primaryBlue,
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + '% attendance';
                                }
                            }
                        }
                    }
                }
            });

            const statusChartCtx = document.getElementById('status-chart');
            if (!statusChartCtx) {
                console.error('Status chart canvas not found');
                return;
            }
            
            const statusChart = new Chart(statusChartCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: statusData.labels,
                    datasets: [{
                        data: statusData.values,
                        backgroundColor: [successGreen, dangerRed, warningYellow],
                        borderWidth: 0,
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' students';
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
            
            console.log('Charts initialized successfully');
        }
    </script>
</body>
</html>