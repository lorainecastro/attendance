<?php
ob_start();
require 'config.php';
session_start();

// Validate session
$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
    exit();
}

// Function to check for duplicate class
function isDuplicateClass($pdo, $section_name, $subject_id, $teacher_id, $grade_level, $class_id = null)
{
    $query = "SELECT COUNT(*) FROM classes WHERE section_name = ? AND subject_id = ? AND teacher_id = ? AND grade_level = ?";
    $params = [$section_name, $subject_id, $teacher_id, $grade_level];

    if ($class_id) {
        $query .= " AND class_id != ?";
        $params[] = $class_id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

// Function to add a new class// Function to add or update a class
function addClass($classData, $scheduleData, $class_id = null)
{
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Insert or update subject
        $stmt = $pdo->prepare("
            INSERT INTO subjects (subject_code, subject_name)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE 
                subject_code = VALUES(subject_code),
                subject_name = VALUES(subject_name),
                subject_id = LAST_INSERT_ID(subject_id)
        ");
        $stmt->execute([$classData['code'], $classData['subject']]);
        $subject_id = $pdo->lastInsertId();

        // Check for duplicate class
        if (isDuplicateClass($pdo, $classData['sectionName'], $subject_id, $_SESSION['teacher_id'], $classData['gradeLevel'], $class_id)) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'This class already exists for this teacher, section, and grade level.'];
        }

        if ($class_id) {
            // Update existing class
            $stmt = $pdo->prepare("
                UPDATE classes 
                SET section_name = ?, subject_id = ?, grade_level = ?, room = ?, status = ?
                WHERE class_id = ? AND teacher_id = ?
            ");
            $stmt->execute([
                $classData['sectionName'],
                $subject_id,
                $classData['gradeLevel'],
                $classData['room'],
                $classData['status'],
                $class_id,
                $_SESSION['teacher_id']
            ]);

            // Delete existing schedules
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE class_id = ?");
            $stmt->execute([$class_id]);
        } else {
            // Insert new class
            $stmt = $pdo->prepare("
                INSERT INTO classes (section_name, subject_id, teacher_id, grade_level, room, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $classData['sectionName'],
                $subject_id,
                $_SESSION['teacher_id'],
                $classData['gradeLevel'],
                $classData['room'],
                $classData['status']
            ]);
            $class_id = $pdo->lastInsertId();
        }

        // Insert schedules
        foreach ($scheduleData as $day => $times) {
            $stmt = $pdo->prepare("
                INSERT INTO schedules (class_id, day, start_time, end_time)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $class_id,
                $day,
                $times['start'],
                $times['end']
            ]);
        }

        $pdo->commit();
        return ['success' => true, 'class_id' => $class_id];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log(($class_id ? "Update" : "Add") . " class error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to ' . ($class_id ? 'update' : 'add') . ' class: ' . $e->getMessage()];
    }
}
// Function to delete a class
function deleteClass($class_id)
{
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Delete related schedules
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE class_id = ?");
        $stmt->execute([$class_id]);

        // Delete related class_students entries
        $stmt = $pdo->prepare("DELETE FROM class_students WHERE class_id = ?");
        $stmt->execute([$class_id]);

        // Delete the class
        $stmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $_SESSION['teacher_id']]);

        $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delete class error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete class: ' . $e->getMessage()];
    }
}

// Function to fetch classes for display
// Function to fetch classes for display
function fetchClassesForTeacher()
{
    $pdo = getDBConnection();
    try {
        // Fetch class details without schedule
        $stmt = $pdo->prepare("
            SELECT c.class_id, c.section_name, c.grade_level, c.room, c.attendance_percentage, c.status,
                   s.subject_code, s.subject_name
            FROM classes c
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.teacher_id = ?
        ");
        $stmt->execute([$_SESSION['teacher_id']]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch schedules separately
        foreach ($classes as &$class) {
            $stmt = $pdo->prepare("
                SELECT day, start_time, end_time
                FROM schedules
                WHERE class_id = ?
            ");
            $stmt->execute([$class['class_id']]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Build schedule array
            $class['schedule'] = [];
            foreach ($schedules as $schedule) {
                if (!empty($schedule['day'])) {
                    $class['schedule'][$schedule['day']] = [
                        'start' => $schedule['start_time'],
                        'end' => $schedule['end_time']
                    ];
                }
            }
        }

        return ['success' => true, 'data' => $classes];
    } catch (PDOException $e) {
        error_log("Fetch classes error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to fetch classes: ' . $e->getMessage()];
    }
}
// Handle AJAX requests
// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    ob_clean();

    if ($_POST['action'] === 'addClass') {
        $classData = [
            'code' => $_POST['classCode'] ?? '',
            'sectionName' => $_POST['sectionName'] ?? '',
            'subject' => $_POST['subject'] ?? '',
            'gradeLevel' => $_POST['gradeLevel'] ?? '',
            'room' => $_POST['room'] ?? '',
            'status' => $_POST['status'] ?? ''
        ];

        $scheduleData = json_decode($_POST['schedule'] ?? '{}', true);
        $classId = $_POST['classId'] ?? null; // Get classId for editing

        // Validate inputs
        if (
            empty($classData['code']) || empty($classData['sectionName']) ||
            empty($classData['subject']) || empty($classData['gradeLevel']) ||
            empty($classData['status'])
        ) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        echo json_encode(addClass($classData, $scheduleData, $classId));
        exit;
    } elseif ($_POST['action'] === 'deleteClass') {
        $class_id = $_POST['classId'] ?? null;
        if (!$class_id) {
            echo json_encode(['success' => false, 'error' => 'Missing class ID']);
            exit;
        }
        echo json_encode(deleteClass($class_id));
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetchClasses') {
    header('Content-Type: application/json');
    ob_clean(); // Clear any output before JSON response
    echo json_encode(fetchClassesForTeacher());
    exit;
}

// For non-AJAX requests, render the HTML page
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
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
            --primary-gradient: linear-gradient(135deg, #3b82f6, #8b5cf6);
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

        html,
        body {
            height: 100%;
            margin: 0;
            /* Ensure no default margins interfere */
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
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-2xl);
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-lg);
            align-items: center;
            border: 1px solid var(--border-color);
        }

        .controls-left {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .controls-right {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }

        .search-container {
            position: relative;
            min-width: 280px;
        }

        .search-input {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md) var(--spacing-sm) 2.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
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
            left: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
            font-size: 1rem;
        }

        .form-input,
        .form-select {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .form-input:disabled,
        .form-select:disabled {
            background: var(--light-gray);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .filter-select {
            min-width: 140px;
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-lg);
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

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: var(--whitefont-color);
        }

        .btn-warning:hover {
            background: var(--warning-yellow);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #4ade80);
            color: var(--whitefont-color);
        }

        .btn-success:hover {
            background: var(--success-green);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: var(--whitefont-color);
        }

        .btn-danger:hover {
            background: var(--danger-red);
            transform: translateY(-2px);
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4, #22d3ee);
            color: var(--whitefont-color);
        }

        .btn-info:hover {
            background: var(--info-cyan);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.75rem;
        }

        .view-toggle {
            display: flex;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--inputfield-color);
        }

        .view-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: var(--transition-normal);
            color: var(--grayfont-color);
            font-size: 1rem;
        }

        .view-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .view-btn.active {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-lg);
        }

        .class-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            /* width: 395px; */
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .class-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .class-header h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.active {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success-green);
        }

        .status-badge.inactive {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-red);
        }

        .class-info {
            margin-bottom: var(--spacing-md);
        }

        .class-info h4 {
            font-size: var(--font-size-base);
            font-weight: 500;
            margin-bottom: var(--spacing-sm);
            color: var(--blackfont-color);
        }

        .class-info p {
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .class-info i {
            width: 16px;
            color: var(--primary-blue);
        }

        .class-schedule {
            margin-bottom: var(--spacing-md);
        }

        .class-schedule h5 {
            font-size: var(--font-size-base);
            font-weight: 500;
            margin-bottom: var(--spacing-sm);
            color: var(--blackfont-color);
        }

        .schedule-item {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
        }

        .no-schedule {
            color: var(--grayfont-color);
            font-style: italic;
        }

        .class-actions {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            overflow-x: auto;
            border: 1px solid var(--border-color);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th,
        .table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
        }

        .table tr:hover {
            background: var(--inputfieldhover-color);
        }

        .actions {
            display: flex;
            gap: var(--spacing-xs);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            margin: 0 auto;
            padding: 0;
            border-radius: var(--radius-lg);
            max-width: 640px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .modal-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--inputfield-color);
        }

        .modal-title {
            margin: 0;
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--grayfont-color);
            padding: var(--spacing-xs);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
        }

        .close-btn:hover {
            background: var(--inputfieldhover-color);
            color: var(--blackfont-color);
        }

        .modal form {
            padding: var(--spacing-lg);
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 500;
            color: var(--blackfont-color);
            font-size: var(--font-size-sm);
        }

        .schedule-inputs {
            display: grid;
            gap: var(--spacing-sm);
        }

        .schedule-day-input {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .schedule-day-input:hover {
            background: var(--inputfieldhover-color);
        }

        .schedule-day-input input[type="checkbox"] {
            margin-right: var(--spacing-sm);
            accent-color: var(--primary-blue);
        }

        .schedule-day-input label {
            min-width: 90px;
            margin: 0;
            font-weight: 500;
            color: var(--blackfont-color);
        }

        .schedule-day-input input[type="time"] {
            padding: var(--spacing-xs) var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--white);
            transition: var(--transition-normal);
        }

        .schedule-day-input input[type="time"]:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px var(--primary-blue-light);
        }

        .schedule-day-input input[type="time"]:disabled {
            background: var(--light-gray);
            opacity: 0.7;
        }

        .schedule-day-input span {
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        .hidden {
            display: none !important;
        }

        .no-classes {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--grayfont-color);
            font-style: italic;
            font-size: var(--font-size-base);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 1024px) {
            .controls {
                grid-template-columns: 1fr;
            }

            .controls-right {
                justify-content: flex-start;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            body {
                padding: var(--spacing-md);
            }

            .controls-left {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .controls-right {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .search-container {
                min-width: auto;
            }

            .class-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-md);
            }

            .class-card {
                padding: var(--spacing-md);
            }

            .class-actions {
                flex-direction: column;
            }

            .class-actions .btn {
                justify-content: center;
            }

            .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            .modal-header {
                padding: var(--spacing-md);
            }

            .modal form {
                padding: var(--spacing-md);
            }

            .schedule-day-input {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
            }

            .schedule-day-input input[type="time"] {
                width: 100%;
            }

            .form-actions {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: var(--spacing-sm);
            }

            h1 {
                font-size: var(--font-size-xl);
            }

            .class-card {
                padding: var(--spacing-sm);
            }

            .modal-content {
                width: 98%;
                max-height: 98vh;
            }

            .btn {
                padding: var(--spacing-md);
                justify-content: center;
            }

            .view-toggle {
                width: 100%;
            }

            .view-btn {
                flex: 1;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {

            .table th:nth-child(n+6),
            .table td:nth-child(n+6) {
                display: none;
            }
        }

        @media (max-width: 600px) {

            .table th:nth-child(n+4),
            .table td:nth-child(n+4) {
                display: none;
            }
        }

        @media print {

            .controls,
            .class-actions,
            .actions,
            .modal {
                display: none !important;
            }

            body {
                padding: 0;
            }

            .class-card {
                box-shadow: none;
                border: 1px solid var(--border-color);
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <h1>Class Management</h1>
    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Classes</div>
                    <div class="card-value" id="total-classes">0</div>
                </div>
                <div class="card-icon bg-purple">
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
                    <div class="card-title">Active Classes</div>
                    <div class="card-value" id="active-classes">0</div>
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
                    <div class="card-title">Total Students</div>
                    <div class="card-value" id="total-students">0</div>
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
                    <div class="card-title">Average Attendance</div>
                    <div class="card-value" id="average-attendance">0%</div>
                </div>
                <div class="card-icon bg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="controls">
            <div class="controls-left">
                <div class="search-container">
                    <input type="text" class="form-input search-input" placeholder="Search classes..." id="searchInput">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <select class="form-select filter-select" id="gradeFilter">
                    <option value="">All Grade Levels</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                </select>
                <select class="form-select filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select class="form-select filter-select" id="subjectFilter">
                    <option value="">All Subjects</option>
                </select>
                <select class="form-select filter-select" id="sectionFilter">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="controls-right">
                <div class="view-toggle">
                    <button class="view-btn active" onclick="switchView('grid')">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="view-btn" onclick="switchView('table')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add Class
                </button>
            </div>
        </div>

        <div id="gridView" class="class-grid">
        </div>

        <div id="tableView" class="table-container hidden">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>Subject Code & Section</th>
                        <th>Grade Level</th>
                        <th>Subject</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <div id="classModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New Class</h2>
                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form id="classForm">
                <div class="form-group">
                    <label class="form-label" for="classCode">Subject Code</label>
                    <input type="text" class="form-input" id="classCode" required placeholder="e.g., MATH-101-A">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sectionName">Section Name</label>
                    <input type="text" class="form-input" id="sectionName" required placeholder="e.g., Section A, Diamond, Einstein">
                </div>
                <div class="form-group">
                    <label class="form-label" for="subject">Subject</label>
                    <input type="text" class="form-input" id="subject" required placeholder="e.g., Mathematics, Science, English">
                </div>
                <div class="form-group">
                    <label class="form-label" for="gradeLevel">Grade Level</label>
                    <select class="form-select" id="gradeLevel" required>
                        <option value="">Select Grade</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="room">Room</label>
                    <input type="text" class="form-input" id="room" placeholder="e.g., Room 201, Lab 1">
                </div>
                <div class="form-group">
                    <label class="form-label">Schedule</label>
                    <div class="schedule-inputs">
                        <div class="schedule-day-input">
                            <input type="checkbox" id="monday" name="scheduleDays">
                            <label for="monday">Monday</label>
                            <input type="time" id="mondayStart" name="mondayStart" disabled>
                            <span>to</span>
                            <input type="time" id="mondayEnd" name="mondayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="tuesday" name="scheduleDays">
                            <label for="tuesday">Tuesday</label>
                            <input type="time" id="tuesdayStart" name="tuesdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="tuesdayEnd" name="tuesdayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="wednesday" name="scheduleDays">
                            <label for="wednesday">Wednesday</label>
                            <input type="time" id="wednesdayStart" name="wednesdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="wednesdayEnd" name="wednesdayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="thursday" name="scheduleDays">
                            <label for="thursday">Thursday</label>
                            <input type="time" id="thursdayStart" name="thursdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="thursdayEnd" name="thursdayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="friday" name="scheduleDays">
                            <label for="friday">Friday</label>
                            <input type="time" id="fridayStart" name="fridayStart" disabled>
                            <span>to</span>
                            <input type="time" id="fridayEnd" name="fridayEnd" disabled>
                        </div>
                        <div class="schedule-day-input">
                            <input type="checkbox" id="saturday" name="scheduleDays">
                            <label for="saturday">Saturday</label>
                            <input type="time" id="saturdayStart" name="saturdayStart" disabled>
                            <span>to</span>
                            <input type="time" id="saturdayEnd" name="saturdayEnd" disabled>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" required>
                        <option value="">Select Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Class</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Class Details</h2>
                <button class="close-btn" onclick="closeViewModal()">×</button>
            </div>
            <div id="viewContent">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        let classes = [];
        let currentView = 'grid';
        let editingClassId = null;

        document.addEventListener('DOMContentLoaded', function() {
            fetchClasses();
            setupEventListeners();
            clearScheduleInputs();
        });

        function fetchClasses() {
            fetch('manage-classes.php?action=fetchClasses')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        classes = result.data || [];
                        updateStats();
                        renderClasses();
                        populateFilters();
                    } else {
                        console.error('Error fetching classes:', result.error);
                        alert('Failed to fetch classes: ' + result.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching classes:', error);
                    alert('Error fetching classes: ' + error.message);
                });
        }

        function updateStats() {
            const totalClasses = classes.length;
            const activeClasses = classes.filter(c => c.status === 'active').length;

            document.getElementById('total-classes').textContent = totalClasses;
            document.getElementById('active-classes').textContent = activeClasses;
        }

        function setupEventListeners() {
            const searchInput = document.getElementById('searchInput');
            const gradeFilter = document.getElementById('gradeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const subjectFilter = document.getElementById('subjectFilter');
            const sectionFilter = document.getElementById('sectionFilter');
            const classForm = document.getElementById('classForm');

            if (searchInput) searchInput.addEventListener('input', handleSearch);
            if (gradeFilter) gradeFilter.addEventListener('change', handleFilter);
            if (statusFilter) statusFilter.addEventListener('change', handleFilter);
            if (subjectFilter) subjectFilter.addEventListener('change', handleFilter);
            if (sectionFilter) sectionFilter.addEventListener('change', handleFilter);
            if (classForm) classForm.addEventListener('submit', handleFormSubmit);

            const scheduleCheckboxes = document.querySelectorAll('input[name="scheduleDays"]');
            scheduleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', handleScheduleToggle);
            });

            window.addEventListener('click', function(event) {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (event.target === modal) {
                        modal.classList.remove('show');
                    }
                });
            });
        }

        function renderClasses() {
            updateStats();
            if (currentView === 'grid') {
                renderGridView();
            } else {
                renderTableView();
            }
        }

        function renderGridView() {
            const container = document.getElementById('gridView');
            if (!container) return;

            const filteredClasses = getFilteredClasses();
            container.innerHTML = '';

            if (filteredClasses.length === 0) {
                container.innerHTML = '<div class="no-classes">No classes found</div>';
                return;
            }

            filteredClasses.forEach(classItem => {
                const scheduleText = formatSchedule(classItem.schedule);
                const attendancePercentage = parseFloat(classItem.attendance_percentage) || 0;

                const card = document.createElement('div');
                card.className = 'class-card';
                card.innerHTML = `
                    <div class="class-header">
                        <h3>${sanitizeHTML(classItem.subject_code)}</h3>
                        <span class="status-badge ${sanitizeHTML(classItem.status)}">${sanitizeHTML(classItem.status)}</span>
                    </div>
                    <div class="class-info">
                        <h4>${sanitizeHTML(classItem.section_name)}</h4>
                        <p><i class="fas fa-book"></i> ${sanitizeHTML(classItem.subject_name)}</p>
                        <p><i class="fas fa-graduation-cap"></i> ${sanitizeHTML(classItem.grade_level)}</p>
                        <p><i class="fas fa-map-marker-alt"></i> ${sanitizeHTML(classItem.room || 'N/A')}</p>
                        <p><i class="fas fa-percentage"></i> ${attendancePercentage.toFixed(1)}% attendance</p>
                    </div>
                    <div class="class-schedule">
                        <h5>Schedule:</h5>
                        ${scheduleText}
                    </div>
                    <div class="class-actions">
                        <button class="btn btn-sm btn-info" onclick="viewClass(${classItem.class_id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="editClass(${classItem.class_id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                                                <button class="btn btn-sm btn-success" onclick="openStudentModal(${classItem.id})">
                            <i class="fas fa-users"></i> Students
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClass(${classItem.class_id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function renderTableView() {
            const tbody = document.querySelector('#tableView tbody');
            if (!tbody) return;

            const filteredClasses = getFilteredClasses();
            tbody.innerHTML = '';

            if (filteredClasses.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="no-classes">No classes found</td></tr>';
                return;
            }

            filteredClasses.forEach(classItem => {
                const scheduleText = formatScheduleShort(classItem.schedule);
                const attendancePercentage = parseFloat(classItem.attendance_percentage) || 0;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="row-checkbox" data-class-id="${classItem.class_id}"></td>
                    <td>
                        <strong>${sanitizeHTML(classItem.subject_code)}</strong><br>
                        <small>${sanitizeHTML(classItem.section_name)}</small>
                    </td>
                    <td>${sanitizeHTML(classItem.grade_level)}</td>
                    <td>${sanitizeHTML(classItem.subject_name)}</td>
                    <td>${sanitizeHTML(scheduleText)}</td>
                    <td>${sanitizeHTML(classItem.room || 'N/A')}</td>
                    <td>${attendancePercentage.toFixed(1)}%</td>
                    <td><span class="status-badge ${sanitizeHTML(classItem.status)}">${sanitizeHTML(classItem.status)}</span></td>
                    <td class="actions">
                        <button class="btn btn-sm btn-info" onclick="viewClass(${classItem.class_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="editClass(${classItem.class_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteClass(${classItem.class_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function getFilteredClasses() {
            const searchInput = document.getElementById('searchInput');
            const gradeFilter = document.getElementById('gradeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const subjectFilter = document.getElementById('subjectFilter');
            const sectionFilter = document.getElementById('sectionFilter');

            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const gradeFilterValue = gradeFilter ? gradeFilter.value : '';
            const statusFilterValue = statusFilter ? statusFilter.value : '';
            const subjectFilterValue = subjectFilter ? subjectFilter.value : '';
            const sectionFilterValue = sectionFilter ? sectionFilter.value : '';

            return classes.filter(classItem => {
                const matchesSearch = searchTerm === '' ||
                    (classItem.subject_code && classItem.subject_code.toLowerCase().includes(searchTerm)) ||
                    (classItem.section_name && classItem.section_name.toLowerCase().includes(searchTerm)) ||
                    (classItem.subject_name && classItem.subject_name.toLowerCase().includes(searchTerm));

                const matchesGrade = gradeFilterValue === '' || classItem.grade_level === gradeFilterValue;
                const matchesStatus = statusFilterValue === '' || classItem.status === statusFilterValue;
                const matchesSubject = subjectFilterValue === '' || classItem.subject_name === subjectFilterValue;
                const matchesSection = sectionFilterValue === '' || classItem.section_name === sectionFilterValue;

                return matchesSearch && matchesGrade && matchesStatus && matchesSubject && matchesSection;
            });
        }

        function populateFilters() {
            const subjectFilter = document.getElementById('subjectFilter');
            const sectionFilter = document.getElementById('sectionFilter');
            if (!subjectFilter || !sectionFilter) return;

            const subjects = [...new Set(classes.map(c => c.subject_name).filter(s => s))];
            const sections = [...new Set(classes.map(c => c.section_name).filter(s => s))];

            subjectFilter.innerHTML = '<option value="">All Subjects</option>';
            sectionFilter.innerHTML = '<option value="">All Sections</option>';

            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectFilter.appendChild(option);
            });

            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionFilter.appendChild(option);
            });
        }

        function handleSearch() {
            renderClasses();
        }

        function handleFilter() {
            renderClasses();
        }

        function switchView(view) {
            currentView = view;

            const viewButtons = document.querySelectorAll('.view-btn');
            viewButtons.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');

            const gridView = document.getElementById('gridView');
            const tableView = document.getElementById('tableView');

            if (gridView && tableView) {
                if (view === 'grid') {
                    gridView.classList.remove('hidden');
                    tableView.classList.add('hidden');
                } else {
                    gridView.classList.add('hidden');
                    tableView.classList.remove('hidden');
                }
            }

            renderClasses();
        }

        function openModal() {
            editingClassId = null;
            const modalTitle = document.getElementById('modalTitle');
            const classForm = document.getElementById('classForm');
            const classModal = document.getElementById('classModal');

            if (modalTitle) modalTitle.textContent = 'Add New Class';
            if (classForm) classForm.reset();
            clearScheduleInputs();
            if (classModal) classModal.classList.add('show');
        }

        function closeModal() {
            const classModal = document.getElementById('classModal');
            if (classModal) classModal.classList.remove('show');
            editingClassId = null;
        }

        function editClass(classId) {
            const classItem = classes.find(c => c.class_id === classId);
            if (!classItem) return;

            editingClassId = classId;
            const modalTitle = document.getElementById('modalTitle');
            if (modalTitle) modalTitle.textContent = 'Edit Class';

            const fields = {
                classCode: classItem.subject_code,
                sectionName: classItem.section_name,
                subject: classItem.subject_name,
                gradeLevel: classItem.grade_level,
                room: classItem.room || '',
                status: classItem.status
            };

            Object.entries(fields).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) element.value = value;
            });

            clearScheduleInputs();
            if (classItem.schedule) {
                Object.keys(classItem.schedule).forEach(day => {
                    const checkbox = document.getElementById(day);
                    const startInput = document.getElementById(day + 'Start');
                    const endInput = document.getElementById(day + 'End');

                    if (checkbox && startInput && endInput) {
                        checkbox.checked = true;
                        startInput.disabled = false;
                        endInput.disabled = false;
                        startInput.value = classItem.schedule[day].start || '';
                        endInput.value = classItem.schedule[day].end || '';
                    }
                });
            }

            const classModal = document.getElementById('classModal');
            if (classModal) classModal.classList.add('show');
        }

        function viewClass(classId) {
            const classItem = classes.find(c => c.class_id === classId);
            if (!classItem) return;

            const scheduleText = formatSchedule(classItem.schedule);
            const attendancePercentage = parseFloat(classItem.attendance_percentage) || 0;

            const content = document.getElementById('viewContent');
            if (content) {
                content.innerHTML = `
                    <div class="view-details">
                        <div class="detail-row">
                            <strong>Subject Code:</strong> ${sanitizeHTML(classItem.subject_code)}
                        </div>
                        <div class="detail-row">
                            <strong>Section Name:</strong> ${sanitizeHTML(classItem.section_name)}
                        </div>
                        <div class="detail-row">
                            <strong>Subject:</strong> ${sanitizeHTML(classItem.subject_name)}
                        </div>
                        <div class="detail-row">
                            <strong>Grade Level:</strong> ${sanitizeHTML(classItem.grade_level)}
                        </div>
                        <div class="detail-row">
                            <strong>Room:</strong> ${sanitizeHTML(classItem.room || 'N/A')}
                        </div>
                        <div class="detail-row">
                            <strong>Attendance Percentage:</strong> ${attendancePercentage.toFixed(1)}%
                        </div>
                        <div class="detail-row">
                            <strong>Schedule:</strong>
                            <div class="schedule-details">
                                ${scheduleText}
                            </div>
                        </div>
                        <div class="detail-row">
                            <strong>Status:</strong> <span class="status-badge ${sanitizeHTML(classItem.status)}">${sanitizeHTML(classItem.status)}</span>
                        </div>
                    </div>
                `;
            }

            const viewModal = document.getElementById('viewModal');
            if (viewModal) viewModal.classList.add('show');
        }

        function closeViewModal() {
            const viewModal = document.getElementById('viewModal');
            if (viewModal) viewModal.classList.remove('show');
        }

        function deleteClass(classId) {
            if (!confirm('Are you sure you want to delete this class?')) return;

            fetch('manage-classes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=deleteClass&classId=${classId}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        classes = classes.filter(c => c.class_id !== classId);
                        renderClasses();
                        populateFilters();
                        alert('Class deleted successfully!');
                    } else {
                        alert('Failed to delete class: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting class:', error);
                    alert('Error deleting class: ' + error.message);
                });
        }

        function handleFormSubmit(event) {
    event.preventDefault();

    const schedule = getScheduleFromForm();

    const classData = {
        classCode: document.getElementById('classCode')?.value || '',
        sectionName: document.getElementById('sectionName')?.value || '',
        subject: document.getElementById('subject')?.value || '',
        gradeLevel: document.getElementById('gradeLevel')?.value || '',
        room: document.getElementById('room')?.value || '',
        status: document.getElementById('status')?.value || '',
        classId: editingClassId || '' // Include classId for editing
    };

    fetch('manage-classes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `action=addClass&${new URLSearchParams(classData)}&schedule=${encodeURIComponent(JSON.stringify(schedule))}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                fetchClasses();
                closeModal();
                alert(editingClassId ? 'Class updated successfully!' : 'Class added successfully!');
            } else {
                alert('Error: ' + (data.error || 'Failed to ' + (editingClassId ? 'update' : 'add') + ' class'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error ' + (editingClassId ? 'updating' : 'adding') + ' class: ' + error.message);
        });
}
        function getScheduleFromForm() {
            const schedule = {};
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

            days.forEach(day => {
                const checkbox = document.getElementById(day);
                const startInput = document.getElementById(day + 'Start');
                const endInput = document.getElementById(day + 'End');

                if (checkbox && checkbox.checked && startInput && startInput.value && endInput && endInput.value) {
                    schedule[day] = {
                        start: startInput.value,
                        end: endInput.value
                    };
                }
            });

            return schedule;
        }

        function handleScheduleToggle(event) {
            const day = event.target.id;
            const startInput = document.getElementById(day + 'Start');
            const endInput = document.getElementById(day + 'End');

            if (startInput && endInput) {
                if (event.target.checked) {
                    startInput.disabled = false;
                    endInput.disabled = false;
                } else {
                    startInput.disabled = true;
                    endInput.disabled = true;
                    startInput.value = '';
                    endInput.value = '';
                }
            }
        }

        function clearScheduleInputs() {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            days.forEach(day => {
                const checkbox = document.getElementById(day);
                const startInput = document.getElementById(day + 'Start');
                const endInput = document.getElementById(day + 'End');

                if (checkbox) checkbox.checked = false;
                if (startInput) {
                    startInput.value = '';
                    startInput.disabled = true;
                }
                if (endInput) {
                    endInput.value = '';
                    endInput.disabled = true;
                }
            });
        }

        function formatSchedule(schedule) {
            if (!schedule || Object.keys(schedule).length === 0) {
                return '<span class="no-schedule">No schedule set</span>';
            }

            return Object.entries(schedule).map(([day, times]) => {
                const dayName = capitalizeFirst(day);
                return `<div class="schedule-item">${sanitizeHTML(dayName)}: ${formatTime(times.start)} - ${formatTime(times.end)}</div>`;
            }).join('');
        }

        function formatScheduleShort(schedule) {
            if (!schedule || Object.keys(schedule).length === 0) {
                return 'No schedule';
            }

            const days = Object.keys(schedule).map(day => capitalizeFirst(day).substring(0, 3));
            return sanitizeHTML(days.join(', '));
        }

        function formatTime(time) {
            if (!time) return '';
            const [hours, minutes] = time.split(':');
            const hourNum = parseInt(hours);
            const period = hourNum >= 12 ? 'PM' : 'AM';
            const displayHour = hourNum % 12 || 12;
            return `${displayHour}:${minutes} ${period}`;
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');

            if (selectAll) {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
            }
        }

        function capitalizeFirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function sanitizeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
</body>

</html>