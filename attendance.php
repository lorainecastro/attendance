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
        foreach ($attendance as $lrn => $att) {
            $status = $att['status'] ?: null;
            $is_qr_scanned = isset($att['is_qr_scanned']) ? $att['is_qr_scanned'] : 0;
            // Parse timeChecked in Asia/Manila timezone
            $time_checked = $att['timeChecked'] ? date('Y-m-d H:i:s', strtotime($att['timeChecked'] . ' Asia/Manila')) : null;
            // Check if exists
            $stmt = $pdo->prepare("SELECT attendance_id, is_qr_scanned FROM attendance_tracking WHERE class_id = ? AND lrn = ? AND attendance_date = ?");
            $stmt->execute([$class_id, $lrn, $date]);
            $existing = $stmt->fetch();
            if ($existing) {
                // Skip update if record was QR-scanned
                if ($existing['is_qr_scanned']) {
                    continue;
                }
                // Update
                $stmt = $pdo->prepare("UPDATE attendance_tracking SET attendance_status = ?, time_checked = ?, is_qr_scanned = ?, logged_by = ? WHERE class_id = ? AND lrn = ? AND attendance_date = ?");
                $logged_by = $is_qr_scanned ? ($att['logged_by'] ?? 'Scanner Device') : 'Teacher';
                $stmt->execute([$status, $time_checked, $is_qr_scanned, $logged_by, $class_id, $lrn, $date]);
            } else if ($status) {
                // Insert
                $logged_by = $is_qr_scanned ? ($att['logged_by'] ?? 'Scanner Device') : 'Teacher';
                $stmt = $pdo->prepare("INSERT INTO attendance_tracking (class_id, lrn, attendance_date, attendance_status, time_checked, is_qr_scanned, logged_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$class_id, $lrn, $date, $status, $time_checked, $is_qr_scanned, $logged_by]);
            }
            // Send email if QR-scanned
            if ($is_qr_scanned && $status === 'Present') {
                $stmt = $pdo->prepare("SELECT parent_email, CONCAT(first_name, ' ', last_name) AS name FROM students WHERE lrn = ?");
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
                            <p>Your child, {$student['name']}, has been marked Present for the class on {$date} at {$time_checked}.</p>
                            <p>Thank you,<br>SAMS Team</p>
                        ";
                        $mail->send();
                    } catch (Exception $e) {
                        // Log error instead of echoing to avoid breaking JSON response
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

// Fetch classes for the teacher
$stmt = $pdo->prepare("
    SELECT c.class_id, c.section_name, s.subject_name, c.grade_level 
    FROM classes c 
    JOIN subjects s ON c.subject_id = s.subject_id 
    WHERE c.teacher_id = ?
");
$stmt->execute([$user['teacher_id']]);
$classes_fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students by class (include parent_email)
$students_by_class = [];
foreach ($classes_fetch as $class) {
    $class_id = $class['class_id'];
    $stmt = $pdo->prepare("
        SELECT s.lrn, CONCAT(s.last_name, ', ', s.first_name, ' ', s.middle_name) AS name, s.photo, s.parent_email 
        FROM students s 
        JOIN class_students cs ON s.lrn = cs.lrn 
        WHERE cs.class_id = ?
    ");
    $stmt->execute([$class_id]);
    $students_by_class[$class_id] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch existing attendance
$attendance_arr = [];
$stmt = $pdo->prepare("
    SELECT a.class_id, a.attendance_date, a.lrn, a.attendance_status, a.time_checked, a.is_qr_scanned 
    FROM attendance_tracking a 
    JOIN classes c ON a.class_id = c.class_id 
    WHERE c.teacher_id = ?
");
$stmt->execute([$user['teacher_id']]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = $row['attendance_date']; // YYYY-MM-DD
    $class_id = $row['class_id'];
    $lrn = $row['lrn'];
    if (!isset($attendance_arr[$date])) $attendance_arr[$date] = [];
    if (!isset($attendance_arr[$date][$class_id])) $attendance_arr[$date][$class_id] = [];
    // Format time_checked in en-US with Asia/Manila timezone
    $time_checked = $row['time_checked'] ? (new DateTime($row['time_checked'], new DateTimeZone('Asia/Manila')))
        ->format('M d Y h:i:s A') : '';
    $attendance_arr[$date][$class_id][$lrn] = [
        'status' => $row['attendance_status'] ?: '',
        'timeChecked' => $time_checked,
        'is_qr_scanned' => $row['is_qr_scanned'] ? true : false
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-family); }
        html, body {
            height: 100%;
            margin: 0;
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

        .bulk-action-btn:hover {
            background: var(--inputfieldhover-color);
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid var(--border-color); 
        }

        th { 
            font-weight: 600; 
            color: var(--grayfont-color); 
            font-size: 14px; 
            background: var(--inputfield-color); 
        }

        tbody tr { 
            transition: var(--transition-normal); 
        }

        tbody tr:hover { 
            background-color: var(--inputfieldhover-color); 
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

        .status-select:focus { 
            outline: none; 
            border-color: var(--primary-blue); 
            background: var(--inputfieldhover-color); 
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

        .status-select:disabled { 
            background: var(--light-gray); 
            cursor: not-allowed; 
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

        .save-btn:hover { 
            background: var(--inputfieldhover-color); 
        }

        .submit-btn { 
            background: var(--primary-blue); 
            color: var(--whitefont-color); 
        }

        .submit-btn:hover { 
            background: var(--primary-blue-hover); 
        }

        .qr-scanner-container { 
            margin-bottom: 15px; 
            text-align: center; 
        }

        #qr-video { 
            width: 100%; 
            max-width: 300px; 
            border-radius: 8px; 
        }

        #qr-canvas { 
            display: none; 
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

        .pagination button:hover {
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

        .no-students-message {
            text-align: center;
            padding: 20px;
            color: var(--grayfont-color);
            font-size: var(--font-size-lg);
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

            .table-responsive { 
                overflow-x: auto; 
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
    </style>
</head>
<body>
    <h1>Attendance Tracking</h1>

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
        <div class="qr-scanner-container" id="qr-scanner" style="display: none;">
            <video id="qr-video"></video>
            <canvas id="qr-canvas"></canvas>
            <button class="btn btn-secondary" onclick="stopQRScanner()">Stop Scanner</button>
        </div>
        <div class="action-buttons-container">
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
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <div class="action-buttons">
        <button class="btn btn-primary submit-btn" onclick="submitAttendance()">Submit Attendance</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <script>
        const classes = <?php echo json_encode($classes_fetch); ?>;
        const students_by_class = <?php echo json_encode($students_by_class); ?>;
        const attendanceData = <?php echo json_encode($attendance_arr); ?> || {};
        let today = document.getElementById('date-selector').value;
        let videoStream = null;
        let scannedStudents = new Set();
        let selectedStudents = new Set();
        let current_class_id = null;
        let currentPage = 1;
        const rowsPerPage = 5;
        let isProcessingScan = false; // Debounce flag
        let scannerInputBuffer = ''; // Buffer for USB scanner input
        let isScannerActive = false; // Flag to track if scanner is active
        let isCameraActive = false; // Flag to track if camera is active

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
            const present = filteredStudents.filter(s => 
                attendanceData[today]?.[current_class_id]?.[s.lrn]?.status === 'Present' || 
                attendanceData[today]?.[current_class_id]?.[s.lrn]?.status === 'Late'
            ).length;
            const absent = filteredStudents.filter(s => 
                attendanceData[today]?.[current_class_id]?.[s.lrn]?.status === 'Absent'
            ).length;
            const percentage = total ? ((present / total) * 100).toFixed(1) : 0;

            document.getElementById('total-students').textContent = total;
            document.getElementById('present-count').textContent = present;
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
                const att = attendanceData[today][current_class_id][s.lrn] || {status: ''};
                const matchesStatus = statusFilter ? att.status === statusFilter : true;
                const matchesSearch = searchQuery ? 
                    s.lrn.toString().includes(searchQuery) || 
                    s.name.toLowerCase().includes(searchQuery) : true;
                return matchesStatus && matchesSearch;
            }).sort((a, b) => a.name.localeCompare(b.name));
        }

        function updateSelectAllState() {
            const allFilteredStudents = getAllFilteredStudents();
            const allSelected = allFilteredStudents.length > 0 && 
                allFilteredStudents.every(student => selectedStudents.has(student.lrn.toString()));
            
            const selectAllCheckbox = document.getElementById('select-all');
            selectAllCheckbox.checked = allSelected;
            
            const someSelected = allFilteredStudents.some(student => selectedStudents.has(student.lrn.toString()));
            selectAllCheckbox.indeterminate = someSelected && !allSelected;
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
                return;
            }

            const matchingClasses = classes.filter(c => 
                c.grade_level === gradeLevelFilter &&
                c.section_name === sectionFilter &&
                c.subject_name === subjectFilter
            );

            if (matchingClasses.length !== 1) {
                updateStats([]);
                current_class_id = null;
                selectedStudents.clear();
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                return;
            }

            const currentClass = matchingClasses[0];
            current_class_id = currentClass.class_id;
            const current_students = students_by_class[current_class_id] || [];

            if (current_students.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-students-message">No students for this class</td></tr>';
                updateStats([]);
                document.getElementById('pagination').innerHTML = '';
                selectedStudents.clear();
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                return;
            }

            if (!attendanceData[today]) attendanceData[today] = {};
            if (!attendanceData[today][current_class_id]) attendanceData[today][current_class_id] = {};
            current_students.forEach(student => {
                if (!attendanceData[today][current_class_id][student.lrn]) {
                    attendanceData[today][current_class_id][student.lrn] = {
                        status: '',
                        timeChecked: '',
                        is_qr_scanned: false
                    };
                }
            });

            const filteredStudents = getAllFilteredStudents();

            if (filteredStudents.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-students-message">No students match the current filters</td></tr>';
                updateStats([]);
                document.getElementById('pagination').innerHTML = '';
                document.getElementById('select-all').checked = false;
                document.getElementById('select-all').indeterminate = false;
                return;
            }

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedStudents = filteredStudents.slice(start, end);

            paginatedStudents.forEach(student => {
                const att = attendanceData[today][current_class_id][student.lrn];
                const isQRScanned = att.is_qr_scanned;
                const isEditable = !isQRScanned;
                const statusClass = att.status ? att.status.toLowerCase() : 'none';
                const isChecked = selectedStudents.has(student.lrn.toString()) && isEditable ? 'checked' : '';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="select-student" data-id="${student.lrn}" ${isChecked} ${isQRScanned ? 'disabled' : ''}></td>
                    <td><img src="Uploads/${student.photo || 'no-icon.png'}" class="student-photo" alt="${student.name}"></td>
                    <td>${student.lrn}</td>
                    <td>${student.name}</td>
                    <td>
                        <select class="status-select ${statusClass}" data-id="${student.lrn}" ${isQRScanned ? 'disabled' : ''}>
                            <option value="" ${att.status === '' ? 'selected' : ''}>Select Status</option>
                            <option value="Present" ${att.status === 'Present' ? 'selected' : ''}>Present</option>
                            <option value="Absent" ${att.status === 'Absent' ? 'selected' : ''}>Absent</option>
                            <option value="Late" ${att.status === 'Late' ? 'selected' : ''}>Late</option>
                        </select>
                    </td>
                    <td>${att.timeChecked || '-'}</td>
                    <td class="attendance-rate">90%</td>
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
                    attendanceData[today][current_class_id][studentId].status = newStatus;
                    attendanceData[today][current_class_id][studentId].is_qr_scanned = false;
                    if (newStatus) {
                        attendanceData[today][current_class_id][studentId].timeChecked = formatDateTime(new Date());
                    } else {
                        attendanceData[today][current_class_id][studentId].timeChecked = '';
                    }
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
                !attendanceData[today][current_class_id][student.lrn].is_qr_scanned
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
                if (!attendanceData[today][current_class_id][studentId].is_qr_scanned) {
                    checkbox.checked = selectedStudents.has(studentId);
                }
            });
            
            selectAllCheckbox.indeterminate = false;
        }

        function markAllPresent() {
            if (!current_class_id) return;
            const filteredStudents = getAllFilteredStudents().filter(student => 
                !attendanceData[today][current_class_id][student.lrn].is_qr_scanned
            );

            filteredStudents.forEach(student => {
                attendanceData[today][current_class_id][student.lrn].status = 'Present';
                attendanceData[today][current_class_id][student.lrn].timeChecked = formatDateTime(new Date());
                attendanceData[today][current_class_id][student.lrn].is_qr_scanned = false;
            });
            renderTable(true);
        }

        function applyBulkAction() {
            if (!current_class_id) return;
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
                attendanceData[today][current_class_id][studentId].status = action;
                attendanceData[today][current_class_id][studentId].timeChecked = formatDateTime(new Date());
                attendanceData[today][current_class_id][studentId].is_qr_scanned = false;
            });
            renderTable(true);
        }

        function submitAttendance() {
            if (!current_class_id) return;
            const data = attendanceData[today][current_class_id];
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({class_id: current_class_id, date: today, attendance: data})
            }).then(res => res.json()).then(result => {
                if (result.success) {
                    showNotification('Attendance submitted successfully.', 'success');
                } else {
                    showNotification('Failed to submit attendance.', 'error');
                }
            }).catch(err => {
                showNotification('Error: ' + err.message, 'error');
            });
        }

        function processQRScan(qrData, source) {
            if (!current_class_id || isProcessingScan) return;
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
                    showNotification(`Student ${student.name} already scanned today.`, 'error');
                    setTimeout(() => { isProcessingScan = false; }, 1000); // Reset after 1 second
                } else {
                    attendanceData[today][current_class_id][lrn].status = 'Present';
                    attendanceData[today][current_class_id][lrn].timeChecked = formatDateTime(new Date());
                    attendanceData[today][current_class_id][lrn].is_qr_scanned = true;
                    attendanceData[today][current_class_id][lrn].logged_by = source === 'scanner' ? 'Scanner Device' : 'Device Camera';
                    scannedStudents.add(lrn);
                    showNotification(`Student ${student.name} marked as Present. Email sent to parent.`, 'success');

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
            const qrScanner = document.getElementById('qr-scanner');
            const scanButton = document.getElementById('qr-scan-btn');
            const video = document.getElementById('qr-video');
            const canvasElement = document.getElementById('qr-canvas');
            const canvas = canvasElement.getContext('2d');

            if (isCameraActive || isScannerActive) {
                // Stop both camera and scanner
                stopQRScanner();
                scanButton.innerHTML = '<i class="fas fa-qrcode"></i> Scan QR Code';
            } else {
                if (!current_class_id) {
                    showNotification('Please select a class before scanning.', 'error');
                    return;
                }
                // Start both camera and scanner
                scannedStudents.clear();
                isProcessingScan = false; // Reset debounce flag
                scannerInputBuffer = ''; // Clear scanner input buffer

                // Start camera
                qrScanner.style.display = 'block';
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
                        showNotification('Scanner device active. Scan a QR code.', 'success');
                    });

                // Start scanner
                isScannerActive = true;
                if (!isCameraActive) {
                    showNotification('Scanner device active. Scan a QR code.', 'success');
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
            isScannerActive = false;
            isCameraActive = false;
            isProcessingScan = false; // Reset debounce flag
            scannerInputBuffer = ''; // Clear scanner input buffer
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