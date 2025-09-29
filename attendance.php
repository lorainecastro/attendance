<?php
ob_start();
// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
require 'config.php';
session_start();

require 'PHPMailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Validate session
$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['class_id']) && isset($input['date']) && isset($input['attendance'])) {
        $class_id = $input['class_id'];
        $date = $input['date']; // YYYY-MM-DD
        $attendance = $input['attendance'];
        $pdo = getDBConnection();
        
        // Get schedule for the class to determine grace period and check if schedule exists
        $schedule_stmt = $pdo->prepare("
            SELECT s.day, s.start_time, s.grace_period_minutes, s.end_time 
            FROM schedules s 
            JOIN classes c ON s.class_id = c.class_id 
            WHERE s.class_id = ? AND DATE_FORMAT(?, '%W') = s.day AND c.isArchived = 0
        ");
        $schedule_stmt->execute([$class_id, $date]);
        $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Prevent submission if no schedule or if date is in the past (but allow updates for today only in backend for consistency)
        $today = date('Y-m-d');
        if (!$schedule || $date < $today) {
            echo json_encode(['success' => false, 'error' => 'Cannot mark attendance: No schedule or past date.']);
            exit();
        }
        
        $grace_period_end = null;
        if ($schedule) {
            $start_time = new DateTime($date . ' ' . $schedule['start_time']);
            $grace_period_end = clone $start_time;
            $grace_period_end->add(new DateInterval('PT' . $schedule['grace_period_minutes'] . 'M'));
        }
        
        foreach ($attendance as $lrn => $att) {
            $status = $att['status'] ?: null;
            $is_qr_scanned = isset($att['is_qr_scanned']) ? $att['is_qr_scanned'] : 0;
            $scan_time = $att['timeChecked'] ? new DateTime($att['timeChecked']) : new DateTime();
            
            // Apply grace period logic for QR scans only
            if ($is_qr_scanned && $status === 'Present' && $grace_period_end) {
                if ($scan_time > $grace_period_end) {
                    $status = 'Late'; // Mark as Late if scanned after grace period
                }
            }
            
            // Parse timeChecked in Asia/Manila timezone
            $time_checked = $att['timeChecked'] ? date('Y-m-d H:i:s', strtotime($att['timeChecked'] . ' Asia/Manila')) : date('Y-m-d H:i:s');
            
            // Check if exists
            $stmt = $pdo->prepare("SELECT attendance_id, is_qr_scanned FROM attendance_tracking WHERE class_id = ? AND lrn = ? AND attendance_date = ?");
            $stmt->execute([$class_id, $lrn, $date]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Skip update if record was QR-scanned and status is already set
                if ($existing['is_qr_scanned'] && $status && $status !== 'Select Status') {
                    continue;
                }
                // Update
                $stmt = $pdo->prepare("UPDATE attendance_tracking SET attendance_status = ?, time_checked = ?, is_qr_scanned = ?, logged_by = ? WHERE class_id = ? AND lrn = ? AND attendance_date = ?");
                $logged_by = $is_qr_scanned ? ($att['logged_by'] ?? 'Scanner Device') : 'Teacher';
                $stmt->execute([$status, $time_checked, $is_qr_scanned, $logged_by, $class_id, $lrn, $date]);
            } else if ($status && $status !== 'Select Status') {
                // Insert
                $logged_by = $is_qr_scanned ? ($att['logged_by'] ?? 'Scanner Device') : 'Teacher';
                $stmt = $pdo->prepare("INSERT INTO attendance_tracking (class_id, lrn, attendance_date, attendance_status, time_checked, is_qr_scanned, logged_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$class_id, $lrn, $date, $status, $time_checked, $is_qr_scanned, $logged_by]);
            }
            
            // Send email if QR-scanned and Present (only if within grace period)
            if ($is_qr_scanned && $status === 'Present') {
                $stmt = $pdo->prepare("SELECT parent_email, full_name FROM students WHERE lrn = ?");
                $stmt->execute([$lrn]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($student && $student['parent_email']) {
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'student.attendance.monitoring.sys@gmail.com';
                        $mail->Password = 'cajlpvkqvphqchro';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->setFrom('student.attendance.monitoring.sys@gmail.com', 'SAMS');
                        $mail->addAddress($student['parent_email']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Attendance Notification';
                        $mail->Body = "
                            <h3>Attendance Notification</h3>
                            <p>Dear Parent/Guardian,</p>
                            <p>Your child, {$student['full_name']}, has been marked {$status} for the class on {$date} at {$time_checked}.</p>
                            <p>Thank you,<br>SAMS Team</p>
                        ";
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Email error for LRN $lrn: " . $mail->ErrorInfo);
                    }
                }
            }
        }
        echo json_encode(['success' => true]);
        exit();
    }
    echo json_encode(['success' => false]);
    exit();
}

$pdo = getDBConnection();

// Fetch classes for the teacher with schedule information
$stmt = $pdo->prepare("
    SELECT c.class_id, c.section_name, s.subject_name, c.grade_level,
           sch.day, sch.start_time, sch.grace_period_minutes, sch.end_time
    FROM classes c 
    JOIN subjects s ON c.subject_id = s.subject_id 
    LEFT JOIN schedules sch ON c.class_id = sch.class_id
    WHERE c.teacher_id = ? AND c.isArchived = 0
    ORDER BY c.grade_level, c.section_name, s.subject_name
");
$stmt->execute([$user['teacher_id']]);
$classes_fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students by class (include parent_email)
$students_by_class = [];
foreach ($classes_fetch as $class) {
    $class_id = $class['class_id'];
    $stmt = $pdo->prepare("
        SELECT s.lrn, s.full_name, s.photo, s.parent_email 
        FROM students s 
        JOIN class_students cs ON s.lrn = cs.lrn 
        WHERE cs.class_id = ?
    ");
    $stmt->execute([$class_id]);
    $students_by_class[$class_id] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch the earliest attendance date for the teacher
$stmt = $pdo->prepare("
    SELECT MIN(attendance_date) AS earliest_date 
    FROM attendance_tracking a 
    JOIN classes c ON a.class_id = c.class_id 
    WHERE c.teacher_id = ? AND c.isArchived = 0
");
$stmt->execute([$user['teacher_id']]);
$earliest_date_result = $stmt->fetch(PDO::FETCH_ASSOC);
$earliest_date = $earliest_date_result['earliest_date'] ?? date('Y-m-d');

// Fetch attendance from the earliest date to today
$stmt = $pdo->prepare("
    SELECT a.class_id, a.attendance_date, a.lrn, a.attendance_status, a.time_checked, a.is_qr_scanned,
           sch.start_time, sch.grace_period_minutes, sch.end_time
    FROM attendance_tracking a 
    JOIN classes c ON a.class_id = c.class_id 
    LEFT JOIN schedules sch ON c.class_id = sch.class_id AND DATE_FORMAT(a.attendance_date, '%W') = sch.day
    WHERE c.teacher_id = ? AND a.attendance_date >= ? AND c.isArchived = 0
    ORDER BY a.attendance_date DESC
");
$stmt->execute([$user['teacher_id'], $earliest_date]);
$attendance_arr = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = $row['attendance_date']; // YYYY-MM-DD
    $class_id = $row['class_id'];
    $lrn = $row['lrn'];
    
    if (!isset($attendance_arr[$date])) $attendance_arr[$date] = [];
    if (!isset($attendance_arr[$date][$class_id])) $attendance_arr[$date][$class_id] = [];
    
    // Calculate grace period end time for display
    $grace_period_end = null;
    if ($row['start_time'] && $row['grace_period_minutes']) {
        $start_time = new DateTime($date . ' ' . $row['start_time']);
        $grace_period_end = clone $start_time;
        $grace_period_end->add(new DateInterval('PT' . $row['grace_period_minutes'] . 'M'));
    }
    
    // Format time_checked in en-US with Asia/Manila timezone
    $time_checked = $row['time_checked'] ? (new DateTime($row['time_checked'], new DateTimeZone('Asia/Manila')))
        ->format('M d Y h:i:s A') : '';
    
    $attendance_arr[$date][$class_id][$lrn] = [
        'status' => $row['attendance_status'] ?: '',
        'timeChecked' => $time_checked,
        'is_qr_scanned' => $row['is_qr_scanned'] ? true : false,
        'grace_period_end' => $grace_period_end ? $grace_period_end->format('h:i:s A') : null,
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'grace_period_minutes' => $row['grace_period_minutes']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --status-present-bg: #e6ffed;
            --status-absent-bg: #ffe6e6;
            --status-late-bg: #fff8e6;
            --status-none-bg: #f8fafc;
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

        .attendance-grid, .stats-grid, .controls {
            flex-grow: 1; 
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

        .stats-grid { 
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

        .bg-purple { 
            background: var(--primary-gradient); 
        }

        .bg-pink { 
            background: var(--secondary-gradient); 
        }

        .bg-blue { 
            background: linear-gradient(135deg, #3b82f6, #60a5fa); 
        }

        .bg-green { 
            background: linear-gradient(135deg, #10b981, #34d399); 
        }

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

        .search-container {
            position: relative;
            min-width: 200px;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: var(--spacing-xs) var(--spacing-md) var(--spacing-xs) 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .search-icon {
            position: absolute;
            left: var(--spacing-sm);
            top: 55%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
            font-size: 0.875rem;
        }

        .selector-input,
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

        .selector-input:focus,
        .selector-select:focus {
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

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--medium-gray);
            color: var(--whitefont-color);
        }

        .btn-secondary:hover:not(:disabled) {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .attendance-grid {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .action-buttons-container {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-bottom: 15px;
            align-items: center;
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
            flex: 1;
        }

        .bulk-action-btn {
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

        .bulk-action-btn:hover:not(:disabled) {
            background: var(--inputfieldhover-color);
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 1000px;
        }

        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid var(--border-color); 
            white-space: nowrap;
        }

        th { 
            font-weight: 600; 
            color: var(--grayfont-color); 
            font-size: 14px; 
            background: var(--inputfield-color); 
        }

        th:nth-child(1), td:nth-child(1) { width: 5%; min-width: 50px; } /* Checkbox */
        th:nth-child(2), td:nth-child(2) { width: 10%; min-width: 80px; } /* Photo */
        th:nth-child(3), td:nth-child(3) { width: 15%; min-width: 120px; } /* LRN */
        th:nth-child(4), td:nth-child(4) { width: 25%; min-width: 200px; white-space: normal; } /* Student Name */
        th:nth-child(5), td:nth-child(5) { width: 20%; min-width: 150px; } /* Status */
        th:nth-child(6), td:nth-child(6) { width: 20%; min-width: 150px; white-space: normal; } /* Time Checked */
        th:nth-child(7), td:nth-child(7) { width: 15%; min-width: 120px; } /* Attendance Rate */

        tbody tr { 
            transition: var(--transition-normal); 
        }

        tbody tr:hover { 
            background-color: var(--inputfieldhover-color); 
        }

        #select-all {
            width: 10px;
            /* Fallback width */
            height: 10px;
            /* Fallback height */
            transform: scale(1.5);
            /* Scales the checkbox to 1.5x its default size */
            vertical-align: middle;
            /* Aligns with surrounding content */
        }

        input[type="checkbox"] {
            width: 10px;
            /* Fallback width */
            height: 10px;
            /* Fallback height */
            transform: scale(1.5);
            /* Scales the checkbox to 1.5x its default size */
            vertical-align: middle;
            /* Aligns with surrounding content */
        }

        .student-photo { 
            width: 45px; 
            height: 45px; 
            border-radius: 50%; 
            object-fit: cover; 
        }

        .status-select { 
            padding: 8px 12px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            font-size: 14px; 
            transition: var(--transition-normal); 
            width: 100%; 
        }

        .status-select:focus:not(:disabled) { 
            outline: none; 
            border-color: var(--primary-blue); 
            background: var(--inputfieldhover-color); 
        }

        .status-select:disabled { 
            background: var(--light-gray); 
            cursor: not-allowed; 
            color: var(--grayfont-color);
        }

        .status-select option[value="Present"] { 
            background-color: var(--status-present-bg); 
        }

        .status-select option[value="Absent"] { 
            background-color: var(--status-absent-bg); 
        }

        .status-select option[value="Late"] { 
            background-color: var(--status-late-bg); 
        }

        .status-select option[value=""] { 
            background-color: var(--status-none-bg); 
        }

        .status-select.present { 
            background-color: var(--status-present-bg); 
        }

        .status-select.absent { 
            background-color: var(--status-absent-bg); 
        }

        .status-select.late { 
            background-color: var(--status-late-bg); 
        }

        .status-select.none { 
            background-color: var(--status-none-bg); 
        }

        .attendance-rate { 
            color: var(--success-green); 
            font-weight: 600; 
        }

        .action-buttons { 
            display: flex; 
            justify-content: flex-end; 
            gap: 10px; 
            margin-top: 20px; 
        }

        .save-btn, .submit-btn { 
            padding: 8px 16px; 
            border: none; 
            border-radius: 8px; 
            font-size: 14px; 
            cursor: pointer; 
            transition: var(--transition-normal); 
        }

        .save-btn { 
            background: var(--inputfield-color); 
            color: var(--blackfont-color); 
        }

        .save-btn:hover:not(:disabled) { 
            background: var(--inputfieldhover-color); 
        }

        .submit-btn { 
            background: var(--primary-blue); 
            color: var(--whitefont-color); 
        }

        .submit-btn:hover:not(:disabled) { 
            background: var(--primary-blue-hover); 
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #qr-video { 
            width: 100%; 
            max-width: 300px; 
            border-radius: 8px; 
            border: 2px solid var(--primary-blue-light);
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }

        #qr-canvas { 
            display: none; 
        }

        .qr-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            border-radius: 8px;
            overflow: hidden;
        }

        .qr-overlay::before,
        .qr-overlay::after,
        .qr-overlay > .corner::before,
        .qr-overlay > .corner::after {
            content: '';
            position: absolute;
            background: transparent;
            border: 3px solid var(--primary-blue);
        }

        .qr-overlay::before {
            top: 10px;
            left: 10px;
            width: 30px;
            height: 30px;
            border-right: none;
            border-bottom: none;
        }

        .qr-overlay::after {
            bottom: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border-left: none;
            border-top: none;
        }

        .qr-overlay > .corner:nth-child(1)::before {
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border-left: none;
            border-bottom: none;
        }

        .qr-overlay > .corner:nth-child(2)::before {
            bottom: 10px;
            left: 10px;
            width: 30px;
            height: 30px;
            border-right: none;
            border-top: none;
        }

        .qr-scan-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-blue);
            animation: scan-line 2s linear infinite;
        }

        @keyframes scan-line {
            0% { transform: translateY(0); }
            50% { transform: translateY(calc(100% - 2px)); }
            100% { transform: translateY(0); }
        }

        .qr-scanner-title {
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--blackfont-color);
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-xs);
        }

        .qr-scanner-title i {
            color: var(--primary-blue);
        }

        .notification { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            padding: 10px 20px; 
            border-radius: 8px; 
            color: var(--whitefont-color); 
            z-index: 1000; 
            transition: opacity var(--transition-normal); 
        }

        .notification.success { 
            background: var(--success-green); 
        }

        .notification.error { 
            background: var(--danger-red); 
        }

        .notification.warning { 
            background: var(--warning-yellow); 
            color: var(--dark-gray);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--inputfield-color);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .pagination button:hover:not(:disabled) {
            background: var(--inputfieldhover-color);
        }

        .pagination button.disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .pagination button.active {
            background: var(--primary-blue);
            color: var(--whitefont-color);
            border-color: var(--primary-blue);
        }

        .no-students-message,
        .no-editing-message {
            text-align: center;
            padding: 20px;
            color: var(--grayfont-color);
            font-size: var(--font-size-lg);
        }

        .no-editing-message {
            background: var(--status-none-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
        }

        @media (max-width: 1024px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons-container {
                flex-direction: column;
                align-items: stretch;
            }

            .bulk-actions {
                flex-direction: column;
            }

            .stats-grid { 
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            }
        }

        @media (max-width: 768px) {
            body { 
                padding: var(--spacing-sm); 
            }

            .controls-left { 
                flex-direction: column; 
                gap: var(--spacing-xs); 
            }

            .search-container { 
                min-width: auto; 
                width: 100%; 
            }

            .selector-input, 
            .selector-select { 
                width: 100%; 
                min-width: auto; 
            }

            .btn { 
                width: 100%; 
                justify-content: center; 
            }

            .bulk-action-btn { 
                width: 100%; 
            }

            .qr-scanner-container {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .stats-grid { 
                grid-template-columns: 1fr; 
            }

            .card-value { 
                font-size: 20px; 
            }

            th, td { 
                padding: 10px; 
            }
        }

        .grace-period-info {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: var(--radius-md);
            padding: 12px;
            margin-bottom: 15px;
            font-size: 0.875rem;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .grace-period-info .icon {
            color: #f59e0b;
            font-size: 1rem;
        }
        
        .grace-period-info.expired {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-color: #ef4444;
            color: #dc2626;
        }
        
        .grace-period-info.expired .icon {
            color: #ef4444;
        }
        
        .grace-time {
            font-weight: 600;
            color: #92400e;
        }
        
        .grace-time.expired {
            color: #dc2626;
        }
        
        .grace-countdown {
            font-weight: 600;
            margin-left: 4px;
        }
        
        .grace-countdown.active {
            color: #059669;
        }
        
        .grace-countdown.expired {
            color: #dc2626;
        }
        
        .schedule-info {
            background: var(--inputfield-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-size: 0.75rem;
            color: var(--grayfont-color);
            margin-bottom: 10px;
        }
        
        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .status-indicator.present {
            background: var(--status-present-bg);
            color: #166534;
        }
        
        .status-indicator.late {
            background: var(--status-late-bg);
            color: #92400e;
        }
        
        .status-indicator.qr-locked {
            background: #fee2e2;
            color: #dc2626;
            font-size: 0.7rem;
        }

        .action-buttons-container .btn:disabled,
        .bulk-actions select:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>

    <style>
    /* Enhanced QR Scanner Container */
        .qr-scanner-container { 
            margin-bottom: 15px; 
            text-align: center; 
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 
                0 20px 40px rgba(59, 130, 246, 0.15),
                0 8px 24px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border: 3px solid rgba(59, 130, 246, 0.2);
            position: relative;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .qr-scanner-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4, #10b981, #f59e0b, #ef4444);
            background-size: 200% 100%;
            border-radius: 24px 24px 0 0;
            animation: gradient-flow 3s ease-in-out infinite;
        }

        @keyframes gradient-flow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .qr-scanner-container.active {
            border: 3px solid #3b82f6;
            background: linear-gradient(135deg, #f0f7ff, #ffffff);
            box-shadow: 
                0 25px 50px rgba(59, 130, 246, 0.3),
                0 12px 30px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 1),
                0 0 0 6px rgba(59, 130, 246, 0.1);
            transform: translateY(-4px) scale(1.02);
        }

        .qr-scanner-container.detecting {
            border: 3px solid #10b981;
            background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
            box-shadow: 
                0 25px 50px rgba(16, 185, 129, 0.4),
                0 12px 30px rgba(0, 0, 0, 0.15),
                0 0 0 6px rgba(16, 185, 129, 0.2);
            animation: detection-pulse 1.2s ease-in-out;
        }

        @keyframes detection-pulse {
            0% { transform: translateY(-4px) scale(1.02); }
            50% { transform: translateY(-4px) scale(1.05); }
            100% { transform: translateY(-4px) scale(1.02); }
        }

        .qr-scanner-container.detecting::before {
            background: linear-gradient(90deg, #10b981, #059669, #047857);
            animation: success-shimmer 1.2s ease-in-out;
        }

        @keyframes success-shimmer {
            0% { opacity: 1; background-position: 0% 50%; }
            50% { opacity: 0.8; background-position: 100% 50%; }
            100% { opacity: 1; background-position: 0% 50%; }
        }

        /* Square Video Container */
        .video-container {
            position: relative;
            width: 300px;
            height: 300px; /* Made square */
            margin: 0 auto 24px auto;
            border-radius: 17px;
            overflow: hidden;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.2),
                0 5px 15px rgba(0, 0, 0, 0.1),
                inset 0 0 0 3px rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #qr-video { 
            width: 100%; 
            height: 100%;
            object-fit: cover; /* Ensures video fills square container properly */
            border-radius: 17px;
            transition: all 0.4s ease;
        }

        /* Enhanced Overlay with Better Square Corners */
        .qr-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            border-radius: 17px;
            overflow: hidden;
        }

        /* Redesigned Corner Indicators for Square Format */
        .corner {
            position: absolute;
            width: 40px;
            height: 40px;
            z-index: 10;
        }

        .corner::before,
        .corner::after {
            content: '';
            position: absolute;
            background: linear-gradient(45deg, #3b82f6, #06b6d4);
            border-radius: 3px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.6);
        }

        .corner.top-left {
            top: 20px;
            left: 20px;
        }

        .corner.top-left::before {
            width: 30px;
            height: 4px;
            top: 0;
            left: 0;
        }

        .corner.top-left::after {
            width: 4px;
            height: 30px;
            top: 0;
            left: 0;
        }

        .corner.top-right {
            top: 20px;
            right: 20px;
        }

        .corner.top-right::before {
            width: 30px;
            height: 4px;
            top: 0;
            right: 0;
        }

        .corner.top-right::after {
            width: 4px;
            height: 30px;
            top: 0;
            right: 0;
        }

        .corner.bottom-left {
            bottom: 20px;
            left: 20px;
        }

        .corner.bottom-left::before {
            width: 30px;
            height: 4px;
            bottom: 0;
            left: 0;
        }

        .corner.bottom-left::after {
            width: 4px;
            height: 30px;
            bottom: 0;
            left: 0;
        }

        .corner.bottom-right {
            bottom: 20px;
            right: 20px;
        }

        .corner.bottom-right::before {
            width: 30px;
            height: 4px;
            bottom: 0;
            right: 0;
        }

        .corner.bottom-right::after {
            width: 4px;
            height: 30px;
            bottom: 0;
            right: 0;
        }

        .qr-scanner-container.detecting .corner::before,
        .qr-scanner-container.detecting .corner::after {
            background: linear-gradient(45deg, #10b981, #059669);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.8);
            animation: corner-glow 1s ease-in-out;
        }

        @keyframes corner-glow {
            0% { 
                box-shadow: 0 0 20px rgba(16, 185, 129, 0.8);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 30px rgba(16, 185, 129, 1);
                transform: scale(1.1);
            }
            100% { 
                box-shadow: 0 0 20px rgba(16, 185, 129, 0.8);
                transform: scale(1);
            }
        }

        /* Enhanced Scan Line for Square Format */
        .qr-scan-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(59, 130, 246, 0.3) 20%, 
                #3b82f6 50%, 
                rgba(59, 130, 246, 0.3) 80%, 
                transparent 100%
            );
            animation: scan-line-square 2.5s linear infinite;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.8);
            border-radius: 2px;
        }

        .qr-scanner-container.detecting .qr-scan-line {
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(16, 185, 129, 0.3) 20%, 
                #10b981 50%, 
                rgba(16, 185, 129, 0.3) 80%, 
                transparent 100%
            );
            box-shadow: 0 0 20px rgba(16, 185, 129, 1);
        }

        @keyframes scan-line-square {
            0% { transform: translateY(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(314px); opacity: 0; } /* Adjusted for square 320px container */
        }

        /* Center Crosshair for Square Format */
        .crosshair {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            border: 3px solid rgba(59, 130, 246, 0.7);
            border-radius: 12px;
            animation: crosshair-pulse-square 2.5s ease-in-out infinite;
        }

        .crosshair::before,
        .crosshair::after {
            content: '';
            position: absolute;
            background: rgba(59, 130, 246, 0.5);
        }

        .crosshair::before {
            width: 20px;
            height: 2px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .crosshair::after {
            width: 2px;
            height: 20px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .qr-scanner-container.detecting .crosshair {
            border-color: rgba(16, 185, 129, 0.9);
            animation: crosshair-success-square 0.8s ease-in-out;
        }

        .qr-scanner-container.detecting .crosshair::before,
        .qr-scanner-container.detecting .crosshair::after {
            background: rgba(16, 185, 129, 0.8);
        }

        @keyframes crosshair-pulse-square {
            0%, 100% { 
                opacity: 0.7; 
                transform: translate(-50%, -50%) scale(1); 
            }
            50% { 
                opacity: 1; 
                transform: translate(-50%, -50%) scale(1.15); 
            }
        }

        @keyframes crosshair-success-square {
            0% { 
                opacity: 0.9; 
                transform: translate(-50%, -50%) scale(1); 
            }
            50% { 
                opacity: 1; 
                transform: translate(-50%, -50%) scale(1.4); 
            }
            100% { 
                opacity: 0.9; 
                transform: translate(-50%, -50%) scale(1); 
            }
        }

        /* Enhanced Status Indicator */
        .scanner-status {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(59, 130, 246, 0.95);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            backdrop-filter: blur(12px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            letter-spacing: 0.5px;
        }

        .qr-scanner-container.detecting .scanner-status {
            background: rgba(16, 185, 129, 0.95);
            animation: status-celebration 1s ease-in-out;
        }

        /* QR Scanner Title Enhancement */
        .qr-scanner-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--blackfont-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.5px;
        }

        .qr-scanner-title i {
            color: var(--primary-blue);
            font-size: 1.25rem;
            animation: icon-pulse 2s ease-in-out infinite;
        }

        @keyframes icon-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Responsive Design for Square Format */
        @media (max-width: 768px) {
            .qr-scanner-container {
                max-width: 100%;
                padding: 24px 16px;
                margin: 0 auto 15px auto;
            }
            
            .video-container {
                width: 280px;
                height: 280px;
            }
            
            .corner {
                width: 35px;
                height: 35px;
            }
            
            .corner::before {
                width: 25px;
                height: 3px;
            }
            
            .corner::after {
                width: 3px;
                height: 25px;
            }
            
            .crosshair {
                width: 50px;
                height: 50px;
            }
            
            @keyframes scan-line-square {
                0% { transform: translateY(0); opacity: 0; }
                10% { opacity: 1; }
                90% { opacity: 1; }
                100% { transform: translateY(274px); opacity: 0; } /* Adjusted for 280px container */
            }
        }

        @media (max-width: 480px) {
            .video-container {
                width: 240px;
                height: 240px;
            }
            
            @keyframes scan-line-square {
                0% { transform: translateY(0); opacity: 0; }
                10% { opacity: 1; }
                90% { opacity: 1; }
                100% { transform: translateY(234px); opacity: 0; } /* Adjusted for 240px container */
            }
        }
    </style>
</head>
<body>
    <h1>Attendance Marking</h1>

    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Students</div>
                    <div class="card-value" id="total-students">0</div>
                </div>
                <div class="card-icon bg-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Present Count</div>
                    <div class="card-value" id="present-count">0</div>
                </div>
                <div class="card-icon bg-green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Absent Count</div>
                    <div class="card-value" id="absent-count">0</div>
                </div>
                <div class="card-icon bg-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm0 1h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Attendance Percentage</div>
                    <div class="card-value" id="attendance-percentage">0%</div>
                </div>
                <div class="card-icon bg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="controls">
        <div class="controls-left">
            <div class="search-container">
                <input type="text" class="form-input search-input" id="searchInput" placeholder="Search by LRN or Name">
                <i class="fas fa-search search-icon"></i>
            </div>
            <input type="date" class="selector-input" id="date-selector" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
            <select class="selector-select" id="gradeLevelSelector">
            </select>
            <select class="selector-select" id="sectionSelector">
            </select>
            <select class="selector-select" id="classSelector">
            </select>
            <select class="selector-select" id="statusSelector">
                <option value="">All Status</option>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="Late">Late</option>
            </select>
            <button class="btn btn-secondary" onclick="clearFilters()">
                <i class="fas fa-times"></i> Clear Filters
            </button>
        </div>
    </div>

    <div class="attendance-grid">
        <div id="schedule-info" class="schedule-info" style="display: none;">
            <i class="fas fa-clock"></i> Class schedule: <span id="schedule-details"></span>
        </div>
        
        <div id="grace-period-info" class="grace-period-info" style="display: none;">
            <i class="fas fa-info-circle icon"></i>
            <span id="grace-period-text">Grace period ends at </span>
            <span id="grace-period-time" class="grace-time"></span>
            <span id="grace-countdown" class="grace-countdown"></span>
        </div>

        <div class="qr-scanner-container" id="qr-scanner" style="display: none;">
            <div class="qr-scanner-title">
                <i class="fas fa-qrcode"></i> QR Code Scanner
            </div>
            <div class="video-container">
                <video id="qr-video"></video>
                <div class="qr-overlay">
                    <div class="corner top-left"></div>
                    <div class="corner top-right"></div>
                    <div class="corner bottom-left"></div>
                    <div class="corner bottom-right"></div>
                    <div class="qr-scan-line"></div>
                    <div class="crosshair"></div>
                    <div class="scanner-status">Scanning...</div>
                </div>
            </div>
            <canvas id="qr-canvas"></canvas>
            <button class="btn btn-secondary" onclick="stopQRScanner()">Stop Scanner</button>
        </div>
        
        <div class="action-buttons-container" id="action-buttons-container">
            <div class="bulk-actions">
                <select class="bulk-action-btn" id="bulk-action-select">
                    <option value="">Select Bulk Action</option>
                    <option value="Present">Mark Selected as Present</option>
                    <option value="Absent">Mark Selected as Absent</option>
                    <option value="Late">Mark Selected as Late</option>
                </select>
                <button class="btn btn-primary" onclick="applyBulkAction()">Apply</button>
            </div>
            <button class="btn btn-primary" onclick="markAllPresent()">
                <i class="fas fa-check-circle"></i> Mark All Present
            </button>
            <button class="btn btn-primary" id="qr-scan-btn" onclick="toggleQRScanner()">
                <i class="fas fa-qrcode"></i> Scan QR Code
            </button>
        </div>
        
        <div class="table-responsive">
            <table id="attendance-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
                        <th>Photo</th>
                        <th>LRN</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Time Checked</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <div class="action-buttons">
        <button class="btn btn-primary submit-btn" id="submit-btn" onclick="submitAttendance()">Submit Attendance</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <script>
        const classes = <?php echo json_encode($classes_fetch); ?>;
        const students_by_class = <?php echo json_encode($students_by_class); ?>;
        const attendanceData = <?php echo json_encode($attendance_arr); ?> || {};
        const earliestDate = '<?php echo $earliest_date; ?>';
        let today = document.getElementById('date-selector').value;
        let currentToday = '<?php echo date('Y-m-d'); ?>';
        let videoStream = null;
        let scannedStudents = new Set();
        let selectedStudents = new Set();
        let current_class_id = null;
        let currentPage = 1;
        const rowsPerPage = 5;
        let isProcessingScan = false;
        let scannerInputBuffer = '';
        let isScannerActive = false;
        let isCameraActive = false;
        let currentSchedule = null;
        let gracePeriodInterval = null;
        let hasSchedule = false;
        let isEditableDate = false;

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function updateScheduleInfo() {
            const scheduleInfo = document.getElementById('schedule-info');
            const gracePeriodInfo = document.getElementById('grace-period-info');
            const scheduleDetails = document.getElementById('schedule-details');
            const gracePeriodText = document.getElementById('grace-period-text');
            const gracePeriodTime = document.getElementById('grace-period-time');
            const graceCountdown = document.getElementById('grace-countdown');

            if (!current_class_id || !currentSchedule) {
                scheduleInfo.style.display = 'none';
                gracePeriodInfo.style.display = 'none';
                if (gracePeriodInterval) {
                    clearInterval(gracePeriodInterval);
                    gracePeriodInterval = null;
                }
                hasSchedule = false;
                return;
            }

            const selectedDate = new Date(today);
            const dayOfWeek = selectedDate.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
            
            const classSchedule = classes.find(c => c.class_id == current_class_id && c.day === dayOfWeek);
            if (classSchedule && classSchedule.start_time && classSchedule.day === dayOfWeek) {
                scheduleInfo.style.display = 'block';
                const startTime = new Date(`${today}T${classSchedule.start_time}`).toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: true 
                });
                const endTime = new Date(`${today}T${classSchedule.end_time}`).toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: true 
                });
                scheduleDetails.textContent = `${classSchedule.day.charAt(0).toUpperCase() + classSchedule.day.slice(1)} ${startTime} - ${endTime}`;
                
                hasSchedule = true;
                
                if (classSchedule.grace_period_minutes > 0) {
                    gracePeriodInfo.style.display = 'block';
                    gracePeriodInfo.classList.remove('expired');
                    
                    const startTime = new Date(`${today}T${classSchedule.start_time}`);
                    const graceEndTime = new Date(startTime.getTime() + (classSchedule.grace_period_minutes * 60 * 1000));
                    
                    const updateGracePeriod = () => {
                        const now = new Date();
                        const timeRemaining = graceEndTime - now;
                        
                        const graceTime = graceEndTime.toLocaleTimeString('en-US', { 
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: true 
                        });
                        
                        gracePeriodTime.textContent = graceTime;
                        gracePeriodTime.classList.remove('expired');
                        
                        if (timeRemaining <= 0) {
                            gracePeriodInfo.classList.add('expired');
                            graceCountdown.textContent = '(Grace period Ended)';
                            graceCountdown.className = 'grace-countdown expired';
                            if (gracePeriodInterval) {
                                clearInterval(gracePeriodInterval);
                                gracePeriodInterval = null;
                            }
                        } else {
                            const minutes = Math.floor(timeRemaining / 60000);
                            const seconds = Math.floor((timeRemaining % 60000) / 1000);
                            
                            graceCountdown.textContent = `(${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')} min remaining)`;
                            graceCountdown.className = 'grace-countdown active';
                        }
                    };
                    
                    updateGracePeriod();
                    if (gracePeriodInterval) {
                        clearInterval(gracePeriodInterval);
                    }
                    gracePeriodInterval = setInterval(updateGracePeriod, 1000);
                } else {
                    gracePeriodInfo.style.display = 'none';
                    if (gracePeriodInterval) {
                        clearInterval(gracePeriodInterval);
                        gracePeriodInterval = null;
                    }
                }
            } else {
                scheduleInfo.style.display = 'none';
                gracePeriodInfo.style.display = 'none';
                if (gracePeriodInterval) {
                    clearInterval(gracePeriodInterval);
                    gracePeriodInterval = null;
                }
                hasSchedule = false;
            }
        }

        function updateEditingPermissions() {
            isEditableDate = today === currentToday;
            const actionContainer = document.getElementById('action-buttons-container');
            const submitBtn = document.getElementById('submit-btn');
            const bulkSelect = document.getElementById('bulk-action-select');
            
            if (!hasSchedule) {
                actionContainer.style.opacity = '0.6';
                actionContainer.style.pointerEvents = 'none';
                submitBtn.disabled = true;
                bulkSelect.disabled = true;
                showNotification('No schedule for this class on the selected date. Attendance marking is disabled.', 'warning');
                
                if (!isEditableDate) {
                    setTimeout(() => {
                        showNotification('Attendance for past dates cannot be modified.', 'warning');
                    }, 3000);
                }
            } else if (!isEditableDate) {
                actionContainer.style.opacity = '0.6';
                actionContainer.style.pointerEvents = 'none';
                submitBtn.disabled = true;
                bulkSelect.disabled = true;
                showNotification('Attendance for past dates cannot be modified.', 'warning');
            } else {
                actionContainer.style.opacity = '1';
                actionContainer.style.pointerEvents = 'auto';
                submitBtn.disabled = false;
                bulkSelect.disabled = false;
            }
        }

        function populateGradeLevels() {
            const gradeLevelSelector = document.getElementById('gradeLevelSelector');
            gradeLevelSelector.innerHTML = '';
            const gradeLevels = [...new Set(classes.map(c => c.grade_level))].sort();
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelSelector.appendChild(option);
            });
            if (gradeLevels.length > 0) {
                gradeLevelSelector.value = gradeLevels[0];
                populateSections(gradeLevels[0]);
            }
        }

        function populateSections(gradeLevel) {
            const sectionSelector = document.getElementById('sectionSelector');
            sectionSelector.innerHTML = '';
            const sections = [...new Set(classes.filter(c => c.grade_level === gradeLevel).map(c => c.section_name))].sort();
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelector.appendChild(option);
            });
            if (sections.length > 0) {
                sectionSelector.value = sections[0];
                populateSubjects(gradeLevel, sections[0]);
            }
        }

        function populateSubjects(gradeLevel, section) {
            const classSelector = document.getElementById('classSelector');
            classSelector.innerHTML = '';
            const subjects = [...new Set(classes.filter(c => c.grade_level === gradeLevel && c.section_name === section).map(c => c.subject_name))].sort();
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                classSelector.appendChild(option);
            });
            if (subjects.length > 0) {
                classSelector.value = subjects[0];
            }
        }

        function updateStats(filteredStudents) {
            const total = filteredStudents.length;
            let presentCount = 0;
            
            filteredStudents.forEach(student => {
                const att = attendanceData[today]?.[current_class_id]?.[student.lrn];
                if (att && (att.status === 'Present' || att.status === 'Late')) {
                    presentCount++;
                }
            });
            
            const absent = filteredStudents.filter(s => 
                attendanceData[today]?.[current_class_id]?.[s.lrn]?.status === 'Absent'
            ).length;
            const percentage = total ? ((presentCount / total) * 100).toFixed(1) : 0;

            document.getElementById('total-students').textContent = total;
            document.getElementById('present-count').textContent = presentCount;
            document.getElementById('absent-count').textContent = absent;
            document.getElementById('attendance-percentage').textContent = `${percentage}%`;
        }

        function formatDateTime(date) {
            const options = {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            return date.toLocaleString('en-US', options).replace(',', '');
        }

        function getAllFilteredStudents() {
            const gradeLevelFilter = gradeLevelSelector.value;
            const sectionFilter = sectionSelector.value;
            const subjectFilter = classSelector.value;

            if (!gradeLevelFilter || !sectionFilter || !subjectFilter || !current_class_id) {
                return [];
            }

            const current_students = students_by_class[current_class_id] || [];
            const statusFilter = statusSelector.value;
            const searchQuery = searchInput.value.toLowerCase();
            
            return current_students.filter(s => {
                const att = attendanceData[today]?.[current_class_id]?.[s.lrn] || {status: ''};
                const matchesStatus = statusFilter ? att.status === statusFilter : true;
                const matchesSearch = searchQuery ? 
                    s.lrn.toString().includes(searchQuery) || 
                    s.full_name.toLowerCase().includes(searchQuery) : true;
                return matchesStatus && matchesSearch;
            }).sort((a, b) => a.full_name.localeCompare(b.full_name));
        }

        function updateSelectAllState() {
            const allFilteredStudents = getAllFilteredStudents();
            const editableStudents = allFilteredStudents.filter(student => 
                !attendanceData[today][current_class_id][student.lrn].is_qr_scanned && hasSchedule && isEditableDate
            );
            const allSelected = editableStudents.length > 0 && 
                editableStudents.every(student => selectedStudents.has(student.lrn.toString()));
            
            const selectAllCheckbox = document.getElementById('select-all');
            selectAllCheckbox.checked = allSelected;
            
            const someSelected = editableStudents.some(student => selectedStudents.has(student.lrn.toString()));
            selectAllCheckbox.indeterminate = someSelected && !allSelected;
        }

        function calcAttendanceRate(class_id, lrn) {
            // Initialize counters: total days with teacher-marked attendance and present/late days
            let total = 0;
            let pl = 0;

            // Define the date range: from 1 calendar month ago to the selected date (inclusive)
            const endDate = new Date(`${today}T00:00:00`); // Selected date in local time at midnight
            const startDate = new Date(endDate);           // Copy of end date
            startDate.setMonth(startDate.getMonth() - 1);  // Subtract 1 calendar month
            startDate.setDate(startDate.getDate() + 1);    // Adjust to include the day after one month ago

            const earliestSystemDate = new Date(earliestDate); // Earliest date from system
            const effectiveStartDate = startDate > earliestSystemDate ? startDate : earliestSystemDate;

            const startStr = effectiveStartDate.toISOString().split('T')[0]; // Format: YYYY-MM-DD
            const endStr = today; // Use provided 'today' string

            // Iterate through all dates in attendanceData
            for (const date in attendanceData) {
                // Filter dates within the range (inclusive)
                if (date >= startStr && date <= endStr) {
                    const classData = attendanceData[date]?.[class_id];
                    if (!classData || Object.keys(classData).length === 0) continue; // Skip if no data

                    // Check if there's at least one teacher-marked status on this day
                    let hasTeacherMarkedDay = false;
                    for (const studentLrn in classData) {
                        const dayData = classData[studentLrn];
                        if (dayData && dayData.status && dayData.status !== '') {
                            hasTeacherMarkedDay = true;
                            break;
                        }
                    }
                    if (!hasTeacherMarkedDay) continue; // Skip unmarked days

                    // Check this specific student's record
                    const studentDayData = classData[lrn];
                    if (studentDayData && studentDayData.status && studentDayData.status !== '') {
                        total++; // Count this teacher-marked day with a valid student status
                        if (studentDayData.status === 'Present' || studentDayData.status === 'Late') {
                            pl++; // Count if Present or Late
                        }
                    }
                }
            }

            // Debug Logs — helpful for testing
            console.log(`For ${today} (LRN: ${lrn}): Range ${startStr} to ${endStr}`);
            console.log(`Total marked days: ${total}, Present/Late: ${pl}`);

            // Optional: list marked dates and student status
            const markedDates = [];
            for (const date in attendanceData) {
                if (date >= startStr && date <= endStr) {
                    const classData = attendanceData[date]?.[class_id];
                    if (classData && Object.keys(classData).length > 0) {
                        let hasTeacherMarked = false;
                        for (const sLrn in classData) {
                            if (classData[sLrn]?.status && classData[sLrn].status !== '') {
                                hasTeacherMarked = true;
                                break;
                            }
                        }
                        if (hasTeacherMarked) {
                            const studentStatus = classData[lrn]?.status || 'NO_STATUS';
                            markedDates.push(`${date}: ${studentStatus}`);
                        }
                    }
                }
            }
            console.log('Marked dates in range:', markedDates);

            // Calculate and return the percentage (Present or Late days / Total days) with two decimal places
            return total > 0 ? (pl / total * 100).toFixed(2) + '%' : '0.00%';
        }

        function isWithinGracePeriod(currentTime, startTime, graceMinutes) {
            if (!startTime || !graceMinutes) return true;
            
            const classStart = new Date(`${today}T${startTime}`);
            const graceEnd = new Date(classStart.getTime() + (graceMinutes * 60 * 1000));
            
            return currentTime <= graceEnd;
        }

        function renderTable(isPagination = false) {
            const bulkActionSelect = document.getElementById('bulk-action-select');
            const selectedBulkAction = bulkActionSelect.value;

            if (!isPagination) currentPage = 1;
            const tableBody = document.querySelector('#attendance-table tbody');
            tableBody.innerHTML = '';
            const gradeLevelFilter = gradeLevelSelector.value;
            const sectionFilter = sectionSelector.value;
            const subjectFilter = classSelector.value;

            if (!gradeLevelFilter || !sectionFilter || !subjectFilter) {
                updateStats([]);
                current_class_id = null;
                selectedStudents.clear();
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                updateScheduleInfo();
                updateEditingPermissions();
                return;
            }

            const matchingClasses = classes.filter(c => 
                c.grade_level === gradeLevelFilter &&
                c.section_name === sectionFilter &&
                c.subject_name === subjectFilter
            );

            if (matchingClasses.length === 0) {
                updateStats([]);
                current_class_id = null;
                selectedStudents.clear();
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                updateScheduleInfo();
                updateEditingPermissions();
                return;
            }

            const currentClass = matchingClasses[0];
            current_class_id = currentClass.class_id;
            const dayOfWeek = new Date(today).toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
            currentSchedule = classes.find(c => c.class_id === current_class_id && c.day === dayOfWeek) || null;
            const current_students = students_by_class[current_class_id] || [];

            if (current_students.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-students-message">No students for this class</td></tr>';
                updateStats([]);
                document.getElementById('pagination').innerHTML = '';
                selectedStudents.clear();
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                updateScheduleInfo();
                updateEditingPermissions();
                return;
            }

            if (!attendanceData[today]) {
                attendanceData[today] = {};
            }
            if (!attendanceData[today][current_class_id]) {
                attendanceData[today][current_class_id] = {};
            }
            
            current_students.forEach(student => {
                if (!attendanceData[today][current_class_id][student.lrn]) {
                    attendanceData[today][current_class_id][student.lrn] = {
                        status: '',
                        timeChecked: '',
                        is_qr_scanned: false,
                        isNew: true,
                        start_time: currentSchedule ? currentSchedule.start_time : null,
                        end_time: currentSchedule ? currentSchedule.end_time : null,
                        grace_period_minutes: currentSchedule ? currentSchedule.grace_period_minutes : null
                    };
                }
            });

            updateScheduleInfo();
            updateEditingPermissions();

            const filteredStudents = getAllFilteredStudents();

            if (filteredStudents.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-students-message">No students match the current filters</td></tr>';
                updateStats([]);
                document.getElementById('pagination').innerHTML = '';
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                return;
            }

            if (!hasSchedule || !isEditableDate) {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-editing-message">You cannot mark attendance for past dates. Attendance marking is disabled.</td></tr>';
                updateStats(filteredStudents);
                document.getElementById('pagination').innerHTML = '';
                selectedStudents.clear();
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                return;
            }

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedStudents = filteredStudents.slice(start, end);

            paginatedStudents.forEach(student => {
                let att = attendanceData[today]?.[current_class_id]?.[student.lrn] || {
                    status: '',
                    timeChecked: '',
                    is_qr_scanned: false,
                    start_time: currentSchedule ? currentSchedule.start_time : null,
                    end_time: currentSchedule ? currentSchedule.end_time : null,
                    grace_period_minutes: currentSchedule ? currentSchedule.grace_period_minutes : null
                };
                
                const isQRScanned = att.is_qr_scanned;
                const isEditable = !isQRScanned && hasSchedule && isEditableDate;
                const statusClass = att.status ? att.status.toLowerCase() : 'none';
                const isChecked = selectedStudents.has(student.lrn.toString()) && isEditable ? 'checked' : '';
                const rate = calcAttendanceRate(current_class_id, student.lrn);
                
                let statusIndicator = '';
                if (isQRScanned && att.status === 'Late') {
                    // statusIndicator = '<span class="status-indicator qr-locked">QR</span>';
                }
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="select-student" data-id="${student.lrn}" ${isChecked} ${!isEditable ? 'disabled' : ''}></td>
                    <td><img src="uploads/${student.photo || 'no-icon.png'}" class="student-photo" alt="${student.full_name}" onerror="this.src='uploads/no-icon.png'"></td>
                    <td>${student.lrn}</td>
                    <td>${student.full_name}</td>
                    <td>
                        <select class="status-select ${statusClass}" data-id="${student.lrn}" ${!isEditable ? 'disabled' : ''}>
                            <option value="" ${att.status === '' ? 'selected' : ''}>Select Status</option>
                            <option value="Present" ${att.status === 'Present' ? 'selected' : ''}>Present</option>
                            <option value="Absent" ${att.status === 'Absent' ? 'selected' : ''}>Absent</option>
                            <option value="Late" ${att.status === 'Late' ? 'selected' : ''}>Late</option>
                        </select>
                        ${statusIndicator}
                    </td>
                    <td>${att.timeChecked || '-'}</td>
                `;
                tableBody.appendChild(row);
            });

            bulkActionSelect.value = selectedBulkAction;
            updateSelectAllState();

            document.querySelectorAll('.select-student').forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    const studentId = checkbox.dataset.id.toString();
                    if (checkbox.checked) {
                        selectedStudents.add(studentId);
                    } else {
                        selectedStudents.delete(studentId);
                    }
                    updateSelectAllState();
                });
            });

            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', () => {
                    if (select.disabled) return;
                    const studentId = select.dataset.id.toString();
                    const newStatus = select.value;
                    
                    attendanceData[today][current_class_id][studentId] = {
                        ...attendanceData[today][current_class_id][studentId],
                        status: newStatus,
                        is_qr_scanned: false,
                        timeChecked: newStatus ? formatDateTime(new Date()) : ''
                    };
                    
                    select.classList.remove('present', 'absent', 'late', 'none');
                    select.classList.add(newStatus ? newStatus.toLowerCase() : 'none');
                    
                    updateStats(filteredStudents);
                    renderTable(true);
                });
            });

            updateStats(filteredStudents);

            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            const pageCount = Math.ceil(filteredStudents.length / rowsPerPage);

            if (pageCount <= 1) return;

            const prevButton = document.createElement('button');
            prevButton.textContent = 'Previous';
            prevButton.classList.add('pagination-btn');
            if (currentPage <= 1) prevButton.classList.add('disabled');
            prevButton.onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable(true);
                }
            };
            pagination.appendChild(prevButton);

            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(pageCount, startPage + maxVisiblePages - 1);
            
            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                const firstPageBtn = document.createElement('button');
                firstPageBtn.textContent = '1';
                firstPageBtn.classList.add('pagination-btn');
                firstPageBtn.onclick = () => {
                    currentPage = 1;
                    renderTable(true);
                };
                pagination.appendChild(firstPageBtn);
                
                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.textContent = '...';
                    ellipsis.classList.add('pagination-ellipsis');
                    pagination.appendChild(ellipsis);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i;
                pageButton.classList.add('pagination-btn');
                if (i === currentPage) pageButton.classList.add('active');
                pageButton.onclick = () => {
                    currentPage = i;
                    renderTable(true);
                };
                pagination.appendChild(pageButton);
            }

            if (endPage < pageCount) {
                if (endPage < pageCount - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.textContent = '...';
                    ellipsis.classList.add('pagination-ellipsis');
                    pagination.appendChild(ellipsis);
                }
                
                const lastPageBtn = document.createElement('button');
                lastPageBtn.textContent = pageCount;
                lastPageBtn.classList.add('pagination-btn');
                lastPageBtn.onclick = () => {
                    currentPage = pageCount;
                    renderTable(true);
                };
                pagination.appendChild(lastPageBtn);
            }

            const nextButton = document.createElement('button');
            nextButton.textContent = 'Next';
            nextButton.classList.add('pagination-btn');
            if (currentPage >= pageCount) nextButton.classList.add('disabled');
            nextButton.onclick = () => {
                if (currentPage < pageCount) {
                    currentPage++;
                    renderTable(true);
                }
            };
            pagination.appendChild(nextButton);
        }

        function toggleSelectAll() {
            const allFilteredStudents = getAllFilteredStudents().filter(student => 
                !attendanceData[today][current_class_id][student.lrn].is_qr_scanned && hasSchedule && isEditableDate
            );
            const selectAllCheckbox = document.getElementById('select-all');
            
            if (selectAllCheckbox.checked) {
                allFilteredStudents.forEach(student => {
                    selectedStudents.add(student.lrn.toString());
                });
            } else {
                allFilteredStudents.forEach(student => {
                    selectedStudents.delete(student.lrn.toString());
                });
            }
            
            document.querySelectorAll('.select-student').forEach(checkbox => {
                const studentId = checkbox.dataset.id.toString();
                if (!attendanceData[today][current_class_id][studentId].is_qr_scanned && hasSchedule && isEditableDate) {
                    checkbox.checked = selectedStudents.has(studentId);
                }
            });
            
            selectAllCheckbox.indeterminate = false;
        }

        function markAllPresent() {
            if (!current_class_id || !hasSchedule || !isEditableDate) {
                showNotification('Cannot mark attendance: No active schedule or selected date is in the past.', 'error');
                return;
            }
            const filteredStudents = getAllFilteredStudents().filter(student => 
                !attendanceData[today][current_class_id][student.lrn].is_qr_scanned
            );

            filteredStudents.forEach(student => {
                attendanceData[today][current_class_id][student.lrn] = {
                    ...attendanceData[today][current_class_id][student.lrn],
                    status: 'Present',
                    timeChecked: formatDateTime(new Date()),
                    is_qr_scanned: false
                };
            });
            renderTable(true);
        }

        function applyBulkAction() {
            if (!current_class_id || !hasSchedule || !isEditableDate) {
                showNotification('Cannot apply bulk action: No active schedule or selected date is in the past.', 'error');
                return;
            }
            const action = document.getElementById('bulk-action-select').value;
            if (!action) {
                showNotification('Please select a bulk action.', 'error');
                return;
            }
            const selected = Array.from(selectedStudents).filter(lrn => 
                (students_by_class[current_class_id] || []).some(s => 
                    s.lrn.toString() === lrn && 
                    !attendanceData[today][current_class_id][lrn].is_qr_scanned
                )
            );
            if (selected.length === 0) {
                showNotification('Please select at least one editable student.', 'error');
                return;
            }
            selected.forEach(studentId => {
                attendanceData[today][current_class_id][studentId] = {
                    ...attendanceData[today][current_class_id][studentId],
                    status: action,
                    timeChecked: formatDateTime(new Date()),
                    is_qr_scanned: false
                };
            });
            renderTable(true);
        }

        function submitAttendance() {
            if (!current_class_id || !hasSchedule || !isEditableDate) {
                showNotification('Cannot submit attendance: No active schedule or selected date is in the past.', 'error');
                return;
            }
            const data = attendanceData[today][current_class_id];
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({class_id: current_class_id, date: today, attendance: data})
            }).then(res => res.json()).then(result => {
                if (result.success) {
                    showNotification('Attendance submitted successfully.', 'success');
                    location.reload();
                } else {
                    showNotification(result.error || 'Failed to submit attendance.', 'error');
                }
            }).catch(err => {
                showNotification('Error: ' + err.message, 'error');
            });
        }

        function processQRScan(qrData, source) {
            if (!current_class_id || isProcessingScan || !hasSchedule || !isEditableDate) {
                showNotification('Cannot process QR scan: No active schedule, selected date is in the past, or scanner inactive.', 'error');
                return;
            }
            isProcessingScan = true; // Set debounce flag

            // Debug: Log the raw QR code data
            console.log('Raw QR Data:', qrData);

            // Extract LRN using regex (assuming LRN is a number)
            const lrnMatch = qrData.match(/^(\d+),/);
            const lrn = lrnMatch ? lrnMatch[1].trim() : qrData.trim(); // Fallback to full data if no comma

            console.log('Extracted LRN:', lrn); // Debug: Check extracted LRN

            // Validate LRN (e.g., ensure it's numeric and has expected length)
            if (!/^\d+$/.test(lrn)) {
                showNotification('Invalid LRN format.', 'error');
                setTimeout(() => { isProcessingScan = false; }, 1000); // Reset after 1 second
                return;
            }

            const student = (students_by_class[current_class_id] || []).find(s => s.lrn.toString() === lrn);
            console.log('Found Student:', student); // Debug: Check if student is found

            if (student) {
                if (scannedStudents.has(lrn) || attendanceData[today][current_class_id][lrn]?.is_qr_scanned) {
                    showNotification(`Student ${student.full_name} already scanned today.`, 'error');
                    setTimeout(() => { isProcessingScan = false; }, 1000); // Reset after 1 second
                } else {
                    const now = new Date();
                    let status = 'Present';
                    
                    if (currentSchedule && currentSchedule.start_time && currentSchedule.grace_period_minutes) {
                        const classStart = new Date(`${today}T${currentSchedule.start_time}`);
                        const graceEnd = new Date(classStart.getTime() + (currentSchedule.grace_period_minutes * 60 * 1000));
                        
                        if (now > graceEnd) {
                            status = 'Late';
                            showNotification(`Student ${student.full_name} marked as Late (after grace period).`, 'warning');
                        } else {
                            showNotification(`Student ${student.full_name} marked as Present (within grace period).`, 'success');
                        }
                    } else {
                        showNotification(`Student ${student.full_name} marked as Present.`, 'success');
                    }
                    
                    attendanceData[today][current_class_id][lrn] = {
                        status: status,
                        timeChecked: formatDateTime(now),
                        is_qr_scanned: true,
                        logged_by: source === 'scanner' ? 'Scanner Device' : 'Device Camera',
                        start_time: currentSchedule?.start_time,
                        end_time: currentSchedule?.end_time,
                        grace_period_minutes: currentSchedule?.grace_period_minutes
                    };
                    
                    scannedStudents.add(lrn);
                    showNotification(`Student ${student.full_name} marked as Present. Email sent to parent.`, 'success');

                    // Submit to database immediately
                    const data = {};
                    data[lrn] = attendanceData[today][current_class_id][lrn];
                    fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({class_id: current_class_id, date: today, attendance: data})
                    }).then(res => res.json()).then(result => {
                        if (!result.success) {
                            showNotification('Failed to save QR attendance.', 'error');
                        }
                        setTimeout(() => { isProcessingScan = false; }, 1000); // Reset after 1 second
                    }).catch(err => {
                        showNotification('Error: ' + err.message, 'error');
                        setTimeout(() => { isProcessingScan = false; }, 1000); // Reset after 1 second
                    });
                    renderTable(true);
                }
            } else {
                showNotification('Invalid LRN for this class.', 'error');
                setTimeout(() => { isProcessingScan = false; }, 1000); // Reset after 1 second
            }
        }

        function toggleQRScanner() {
            if (!hasSchedule || !isEditableDate) {
                showNotification('QR scanning is only available for scheduled classes on the current date.', 'error');
                return;
            }
            const qrScanner = document.getElementById('qr-scanner');
            const scanButton = document.getElementById('qr-scan-btn');
            const video = document.getElementById('qr-video');
            const canvasElement = document.getElementById('qr-canvas');
            const canvas = canvasElement.getContext('2d');

            if (isCameraActive || isScannerActive) {
                // Stop both camera and scanner
                stopQRScanner();
                scanButton.innerHTML = '<i class="fas fa-qrcode"></i> Scan QR Code';
                qrScanner.classList.remove('active');
            } else {
                if (!current_class_id) {
                    showNotification('Please select a class before scanning.', 'error');
                    return;
                }
                if (today !== currentToday) {
                    showNotification('QR scanning is only available for the current date.', 'error');
                    return;
                }
                // Start both camera and scanner
                const selectedDate = new Date(today);
                const dayOfWeek = selectedDate.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
                const classSchedule = classes.find(c => c.class_id == current_class_id && c.day === dayOfWeek);
                
                if (!classSchedule) {
                    showNotification('No schedule found for this class today. Cannot enable QR scanning.', 'error');
                    return;
                }
                
                scannedStudents.clear();
                isProcessingScan = false; // Reset debounce flag
                scannerInputBuffer = ''; // Clear scanner input buffer

                // Start camera
                qrScanner.style.display = 'block';
                qrScanner.classList.add('active');
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(stream => {
                        videoStream = stream;
                        video.srcObject = stream;
                        video.play();
                        isCameraActive = true;
                        scanButton.innerHTML = '<i class="fas fa-stop"></i> Stop Scanning';
                        requestAnimationFrame(tick);
                    })
                    .catch(err => {
                        showNotification('Error accessing camera: ' + err.message, 'error');
                        qrScanner.style.display = 'none';
                        // Still enable scanner even if camera fails
                        isScannerActive = true;
                        scanButton.innerHTML = '<i class="fas fa-stop"></i> Stop Scanning';
                        showNotification('Scan a QR code.', 'success');
                    });

                // Start scanner
                isScannerActive = true;
                if (!isCameraActive) {
                    showNotification('Scan a QR code.', 'success');
                }

                function triggerDetectionAnimation() {
                    const container = document.getElementById('qr-scanner');
                    const status = document.querySelector('.scanner-status');
                    
                    container.classList.add('detecting');
                    status.textContent = 'QR Code Detected!';
                    
                    setTimeout(() => {
                        container.classList.remove('detecting');
                        status.textContent = 'Scanning...';
                    }, 1500);
                }

                function tick() {
                    if (video.readyState === video.HAVE_ENOUGH_DATA && !isProcessingScan && isCameraActive) {
                        canvasElement.height = video.videoHeight;
                        canvasElement.width = video.videoWidth;
                        canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
                        const imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: 'dontInvert',
                        });

                        if (code) {
                            triggerDetectionAnimation(); // Add this line
                            processQRScan(code.data, 'camera');
                        }
                    }
                    if (qrScanner.style.display !== 'none' && isCameraActive) {
                        requestAnimationFrame(tick);
                    }
                }
            }
        }
        
        function stopQRScanner() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
            document.getElementById('qr-scanner').style.display = 'none';
            document.getElementById('qr-scanner').classList.remove('active');
            isScannerActive = false;
            isCameraActive = false;
            isProcessingScan = false; // Reset debounce flag
            scannerInputBuffer = ''; // Clear scanner input buffer
            if (gracePeriodInterval) {
                clearInterval(gracePeriodInterval);
                gracePeriodInterval = null;
            }
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('date-selector').value = '<?php echo date('Y-m-d'); ?>';
            selectedStudents.clear();
            document.getElementById('select-all').checked = false;
            document.getElementById('select-all').indeterminate = false;
            populateGradeLevels();
            today = '<?php echo date('Y-m-d'); ?>';
            stopQRScanner(); // Stop any active scanner
            renderTable();
        }

        // Handle USB scanner input (simulating keyboard input)
        document.addEventListener('keydown', (event) => {
            if (!isScannerActive) return;

            // Prevent default behavior for input fields to avoid interference
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT') {
                event.preventDefault();
                return;
            }

            // Handle scanner input
            if (event.key === 'Enter') {
                if (scannerInputBuffer) {
                    // Clean the buffer (remove extra spaces, newlines, etc.)
                    const cleanedBuffer = scannerInputBuffer.trim();
                    console.log('Scanner Buffer:', cleanedBuffer); // Debug: Log buffer
                    processQRScan(cleanedBuffer, 'scanner');
                    scannerInputBuffer = ''; // Clear buffer after processing
                }
            } else if (event.key.length === 1) { // Only accumulate printable characters
                // Add character to buffer (assuming scanner sends QR code data as text)
                scannerInputBuffer += event.key;
            }
        });

        const tableBody = document.querySelector('#attendance-table tbody');
        const dateSelector = document.getElementById('date-selector');
        const gradeLevelSelector = document.getElementById('gradeLevelSelector');
        const classSelector = document.getElementById('classSelector');
        const sectionSelector = document.getElementById('sectionSelector');
        const statusSelector = document.getElementById('statusSelector');
        const selectAllCheckbox = document.getElementById('select-all');
        const searchInput = document.getElementById('searchInput');

        gradeLevelSelector.addEventListener('change', () => {
            const gradeLevel = gradeLevelSelector.value;
            populateSections(gradeLevel);
            selectedStudents.clear();
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            stopQRScanner(); // Stop scanner when changing filters
            renderTable();
        });

        sectionSelector.addEventListener('change', () => {
            const gradeLevel = gradeLevelSelector.value;
            const section = sectionSelector.value;
            populateSubjects(gradeLevel, section);
            selectedStudents.clear();
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            stopQRScanner(); // Stop scanner when changing filters
            renderTable();
        });

        classSelector.addEventListener('change', () => {
            selectedStudents.clear();
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            stopQRScanner(); // Stop scanner when changing filters
            renderTable();
        });

        statusSelector.addEventListener('change', () => {
            updateSelectAllState();
            renderTable(true);
        });

        searchInput.addEventListener('input', () => {
            updateSelectAllState();
            renderTable();
        });

        dateSelector.addEventListener('change', () => {
            today = dateSelector.value;
            selectedStudents.clear();
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            stopQRScanner(); // Stop scanner when changing date
            renderTable();
        });

        selectAllCheckbox.addEventListener('change', toggleSelectAll);

        document.addEventListener('DOMContentLoaded', () => {
            populateGradeLevels();
            renderTable();
        });
    </script>
</body>
</html>