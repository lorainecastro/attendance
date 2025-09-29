<?php
// archived_classes.php
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

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'unarchive_class') {
        $class_id = $_POST['class_id'] ?? 0;

        if ($class_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid class ID']);
            exit();
        }

        // Update isArchived to 0
        $stmt = $pdo->prepare("UPDATE classes SET isArchived = 0 WHERE class_id = :class_id AND teacher_id = :teacher_id");
        $success = $stmt->execute([
            'class_id' => $class_id,
            'teacher_id' => $user['teacher_id']
        ]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to unarchive class']);
        }
        exit();
    }

    if ($_POST['action'] === 'get_students') {
        $class_id = $_POST['class_id'] ?? 0;

        if ($class_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid class ID']);
            exit();
        }

        // Fetch students for the class
        $stmt = $pdo->prepare("
            SELECT s.lrn, s.full_name, s.email, s.gender, s.dob, 
                   s.grade_level, s.address, s.parent_name, s.parent_email, 
                   s.emergency_contact, s.photo, s.qr_code
            FROM students s
            JOIN class_students cs ON s.lrn = cs.lrn
            WHERE cs.class_id = :class_id
            ORDER BY s.full_name ASC
        ");
        $stmt->execute(['class_id' => $class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'students' => $students]);
        exit();
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit();
}

// Fetch total archived classes count
$archived_classes_stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = :teacher_id AND isArchived = 1");
$archived_classes_stmt->execute(['teacher_id' => $user['teacher_id']]);
$archived_classes = $archived_classes_stmt->fetchColumn();

// Fetch total archived students (count all enrollments in archived classes)
$total_archived_students_stmt = $pdo->prepare("SELECT COUNT(cs.lrn) FROM class_students cs JOIN classes c ON cs.class_id = c.class_id WHERE c.teacher_id = :teacher_id AND c.isArchived = 1");
$total_archived_students_stmt->execute(['teacher_id' => $user['teacher_id']]);
$total_archived_students = $total_archived_students_stmt->fetchColumn();

// Fetch archived classes details
$archived_classes_stmt = $pdo->prepare("
    SELECT c.*, sub.subject_code, sub.subject_name 
    FROM classes c 
    JOIN subjects sub ON c.subject_id = sub.subject_id 
    WHERE c.teacher_id = :teacher_id AND c.isArchived = 1
    ORDER BY c.grade_level, c.section_name
");
$archived_classes_stmt->execute(['teacher_id' => $user['teacher_id']]);
$archived_classes_db = $archived_classes_stmt->fetchAll(PDO::FETCH_ASSOC);

$archived_classes_php = [];
foreach ($archived_classes_db as $cls) {
    $students_stmt = $pdo->prepare("
        SELECT s.lrn AS id, s.full_name AS fullName 
        FROM students s 
        JOIN class_students cs ON s.lrn = cs.lrn 
        WHERE cs.class_id = :class_id 
        ORDER BY s.full_name ASC
    ");
    $students_stmt->execute(['class_id' => $cls['class_id']]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedules_stmt = $pdo->prepare("SELECT * FROM schedules WHERE class_id = :class_id");
    $schedules_stmt->execute(['class_id' => $cls['class_id']]);
    $schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $schedule_text = [];
    if (!empty($schedules)) {
        foreach ($schedules as $sch) {
            $day = ucfirst($sch['day']);
            $start_time = date('h:i A', strtotime($sch['start_time']));
            $end_time = date('h:i A', strtotime($sch['end_time']));
            $grace = $sch['grace_period'] ?? 15;
            $schedule_text[] = "{$day}: {$start_time} - {$end_time} (Grace: {$grace} min)";
        }
    }
    
    $late_to_absent = $schedules[0]['late_to_absent'] ?? 3;
    $grace_period = $schedules[0]['grace_period'] ?? 15;

    $archived_classes_php[] = [
        'id' => $cls['class_id'],
        'code' => $cls['subject_code'] ?? 'N/A',
        'sectionName' => $cls['section_name'],
        'subject' => $cls['subject_name'],
        'gradeLevel' => $cls['grade_level'],
        'room' => $cls['room'] ? htmlspecialchars($cls['room']) : 'No room specified',
        'totalStudents' => count($students),
        'scheduleText' => implode('<br>', $schedule_text),
        'late_to_absent' => $late_to_absent,
        'grace_period' => $grace_period
    ];
}

$unique_students_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT cs.lrn) 
    FROM class_students cs 
    JOIN classes c ON cs.class_id = c.class_id 
    WHERE c.teacher_id = :teacher_id AND c.isArchived = 1
");
$unique_students_stmt->execute(['teacher_id' => $user['teacher_id']]);
$total_unique_students = $unique_students_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Classes - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-hover: #2563eb;
            --primary-blue-light: #dbeafe;
            --success-green: #22c55e;
            --warning-yellow: #f59e0b;
            --danger-red: #ef4444;
            --danger-red-hover: #dc2626;
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
            --status-archived-bg: #ef4444;
            --status-archived-color: #f8fafc;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
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

        .bg-pink {
            background: var(--secondary-gradient);
        }
        .bg-purple { background: var(--primary-gradient); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-red { background: linear-gradient(135deg, #ef4444, #f87171); }
        .bg-green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .card-title {
            font-size: 14px;
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
        }

        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--blackfont-color);
        }

        .archived-classes-grid {
            display: grid;
            gap: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }

        .archived-class-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            border-left: 6px solid var(--primary-blue);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .archived-class-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: var(--card-bg);
            z-index: 0;
            animation: pulse 6s infinite;
        }

        .archived-class-card > * {
            position: relative;
            z-index: 1;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .class-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--blackfont-color);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .class-status {
            padding: 6px 14px;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: 600;
            background-color: var(--status-archived-bg);
            color: var(--status-archived-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .class-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
            background: rgba(255, 255, 255, 0.9);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.08);
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: var(--font-size-base);
            font-weight: 500;
            color: var(--blackfont-color);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .detail-value i {
            color: var(--primary-blue);
            font-size: var(--font-size-lg);
        }

        .schedule-section {
            grid-column: 1 / -1;
            margin-top: var(--spacing-md);
            background: rgba(255, 255, 255, 0.9);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.08);
        }

        .schedule-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .schedule-title i {
            color: var(--primary-blue);
        }

        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
            color: var(--medium-gray);
        }

        .class-buttons {
            display: flex;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
            justify-content: flex-end;
        }

        .btn {
            padding: var(--spacing-xs) var(--spacing-md);
            border: none;
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            text-decoration: none;
        }

        .btn-view { background: var(--info-cyan); color: var(--white); }
        .btn-view:hover { background: #0369a1; }
        .btn-students { background: var(--success-green); color: var(--white); }
        .btn-students:hover { background: #15803d; }
        .btn-unarchive { background: var(--warning-yellow); color: var(--white); }
        .btn-unarchive:hover { background: #d97706; }

        .no-classes {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--grayfont-color);
            font-style: italic;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            display: none;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            backdrop-filter: blur(3px);
        }

        .modal-content {
            background: var(--white);
            margin: 5% auto;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            position: relative;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: var(--spacing-sm);
        }

        .modal-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--blackfont-color);
        }

        .modal-close {
            padding: var(--spacing-xs) var(--spacing-md);
            border: none;
            border-radius: var(--radius-sm);
            background: var(--danger-red);
            color: var(--white);
            cursor: pointer;
            transition: var(--transition-normal);
            font-size: var(--font-size-sm);
            font-weight: 600;
        }

        .modal-close:hover {
            background: var(--danger-red-hover);
        }

        /* View Modal Specific Styles */
        .view-modal-content {
            width: 90%;
            max-width: 600px;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            padding: var(--spacing-xl);
        }

        .view-modal-body {
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
            line-height: 1.6;
        }

        .view-modal-body p {
            margin-bottom: var(--spacing-sm);
            padding: var(--spacing-sm);
            background: var(--inputfield-color);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-modal-body p strong {
            color: var(--primary-blue);
            font-weight: 600;
        }

        /* Students Modal Specific Styles */
        .students-modal-content {
            width: 95%;
            max-width: 1200px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }

        .students-table-container {
            overflow-y: auto;
            flex-grow: 1;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--font-size-sm);
            background: var(--white);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .students-table th {
            background: var(--primary-blue);
            color: var(--white);
            padding: var(--spacing-sm);
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .students-table th:nth-child(1) { width: 10%; } /* LRN */
        .students-table th:nth-child(2) { width: 15%; } /* Full Name */
        .students-table th:nth-child(3) { width: 15%; } /* Email */
        .students-table th:nth-child(4) { width: 8%; } /* Gender */
        .students-table th:nth-child(5) { width: 10%; } /* DOB */
        .students-table th:nth-child(6) { width: 8%; } /* Grade Level */
        .students-table th:nth-child(7) { width: 15%; } /* Address */
        .students-table th:nth-child(8) { width: 12%; } /* Parent Name */
        .students-table th:nth-child(9) { width: 15%; } /* Parent Email */
        .students-table th:nth-child(10) { width: 10%; } /* Emergency Contact */
        .students-table th:nth-child(11) { width: 6%; } /* Photo */
        .students-table th:nth-child(12) { width: 6%; } /* QR Code */

        .students-table td {
            padding: var(--spacing-sm);
            border-bottom: 1px solid var(--border-color);
            color: var(--blackfont-color);
        }

        .students-table tr:hover {
            background: var(--primary-blue-light);
        }

        .students-table img.photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid var(--border-color);
        }

        .students-table img.qr-code {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }

        .students-modal-close {
            position: sticky;
            top: var(--spacing-sm);
            align-self: flex-end;
            z-index: 20;
        }

        .no-students {
            text-align: center;
            padding: var(--spacing-md);
            color: var(--grayfont-color);
            font-style: italic;
        }

        @media (max-width: 768px) {
            body {
                padding: var(--spacing-sm);
            }
            .card-value {
                font-size: 20px;
            }
            .class-header {
                flex-direction: column;
                align-items: stretch;
            }
            .class-details {
                grid-template-columns: 1fr;
            }
            .class-buttons {
                flex-direction: column;
                width: 100%;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
            .view-modal-content {
                width: 95%;
                padding: var(--spacing-md);
            }
            .students-modal-content {
                width: 98%;
                padding: var(--spacing-md);
            }
            .students-table {
                font-size: 0.75rem;
            }
            .students-table img {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <h1>Archived Classes</h1>

    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Classes Archived</div>
                    <div class="card-value"><?php echo htmlspecialchars($archived_classes); ?></div>
                </div>
                <div class="card-icon bg-pink">
                    <i class="fas fa-archive"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Students Archived</div>
                    <div class="card-value"><?php echo htmlspecialchars($total_archived_students); ?></div>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Unique Students</div>
                    <div class="card-value"><?php echo htmlspecialchars($total_unique_students); ?></div>
                </div>
                <div class="card-icon bg-green">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="archived-classes-grid">
        <div class="no-classes">
            <i class="fas fa-archive" style="font-size: 3rem; color: var(--grayfont-color); margin-bottom: var(--spacing-md);"></i>
            <p>No archived classes found.</p>
        </div>
        <?php if (!empty($archived_classes_php)): ?>
            <?php foreach ($archived_classes_php as $cls): ?>
                <div class="archived-class-card">
                    <div class="class-header">
                        <div class="class-title">
                            <?php echo htmlspecialchars($cls['gradeLevel'] . ' - ' . $cls['sectionName']); ?>
                        </div>
                        <span class="class-status">Archived</span>
                    </div>

                    <div class="class-details">
                        <div class="detail-item">
                            <div class="detail-label">Subject</div>
                            <div class="detail-value"><i class="fas fa-book"></i> <?php echo htmlspecialchars($cls['subject']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Subject Code</div>
                            <div class="detail-value"><?php echo htmlspecialchars($cls['code']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Room</div>
                            <div class="detail-value"><?php echo htmlspecialchars($cls['room']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Students</div>
                            <div class="detail-value"><?php echo htmlspecialchars($cls['totalStudents']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Late to Absent</div>
                            <div class="detail-value"><?php echo htmlspecialchars($cls['late_to_absent']); ?> marks</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Grace Period</div>
                            <div class="detail-value"><?php echo htmlspecialchars($cls['grace_period']); ?> min</div>
                        </div>
                    </div>

                    <?php if (!empty($cls['scheduleText'])): ?>
                        <div class="schedule-section">
                            <div class="schedule-title">
                                <i class="fas fa-clock"></i> Schedule
                            </div>
                            <div class="schedule-list">
                                <?php echo $cls['scheduleText']; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="class-buttons">
                        <button class="btn btn-view" data-class-id="<?php echo $cls['id']; ?>"><i class="fas fa-eye"></i> View</button>
                        <button class="btn btn-students" data-class-id="<?php echo $cls['id']; ?>"><i class="fas fa-users"></i> Students</button>
                        <button class="btn btn-unarchive" data-class-id="<?php echo $cls['id']; ?>"><i class="fas fa-box-open"></i> Unarchive</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal for View -->
    <div id="viewModal" class="modal">
        <div class="modal-content view-modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Class Details</h2>
                <button class="modal-close">Close</button>
            </div>
            <div class="view-modal-body" id="modalContent"></div>
        </div>
    </div>

    <!-- Modal for Students -->
    <div id="studentsModal" class="modal">
        <div class="modal-content students-modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Students in Class</h2>
                <button class="modal-close students-modal-close">Close</button>
            </div>
            <div class="students-table-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>DOB</th>
                            <th>Grade Level</th>
                            <th>Address</th>
                            <th>Parent Name</th>
                            <th>Parent Email</th>
                            <th>Emergency Contact</th>
                            <th>Photo</th>
                            <th>QR Code</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Control visibility of no-classes div
        const noClassesDiv = document.querySelector('.no-classes');
        const hasClasses = <?php echo json_encode(!empty($archived_classes_php)); ?>;
        noClassesDiv.style.display = hasClasses ? 'none' : 'block';

        document.querySelectorAll('.btn-view').forEach(button => {
            button.addEventListener('click', () => {
                const classId = button.getAttribute('data-class-id');
                const classData = <?php echo json_encode($archived_classes_php); ?>.find(cls => cls.id == classId);
                if (classData) {
                    const content = `
                        <p><strong>Grade & Section:</strong> ${classData.gradeLevel} - ${classData.sectionName}</p>
                        <p><strong>Subject:</strong> ${classData.subject}</p>
                        <p><strong>Subject Code:</strong> ${classData.code}</p>
                        <p><strong>Room:</strong> ${classData.room}</p>
                        <p><strong>Total Students:</strong> ${classData.totalStudents}</p>
                        <p><strong>Late to Absent:</strong> ${classData.late_to_absent} marks</p>
                        <p><strong>Grace Period:</strong> ${classData.grace_period} min</p>
                        ${classData.scheduleText ? `<p><strong>Schedule:</strong><br>${classData.scheduleText}</p>` : ''}
                    `;
                    document.getElementById('modalContent').innerHTML = content;
                    document.getElementById('viewModal').style.display = 'block';
                }
            });
        });

        document.querySelectorAll('.btn-students').forEach(button => {
            button.addEventListener('click', () => {
                const classId = button.getAttribute('data-class-id');
                fetch('<?php echo basename(__FILE__); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_students&class_id=${encodeURIComponent(classId)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tableBody = document.getElementById('studentsTableBody');
                        tableBody.innerHTML = '';
                        if (data.students.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="12" class="no-students">No students found.</td></tr>';
                        } else {
                            data.students.forEach(student => {
                                const row = `
                                    <tr>
                                        <td>${student.lrn || 'N/A'}</td>
                                        <td>${student.full_name || 'N/A'}</td>
                                        <td>${student.email || 'N/A'}</td>
                                        <td>${student.gender || 'N/A'}</td>
                                        <td>${student.dob || 'N/A'}</td>
                                        <td>${student.grade_level || 'N/A'}</td>
                                        <td>${student.address || 'N/A'}</td>
                                        <td>${student.parent_name || 'N/A'}</td>
                                        <td>${student.parent_email || 'N/A'}</td>
                                        <td>${student.emergency_contact || 'N/A'}</td>
                                        <td><img src="${student.photo ? 'uploads/' + student.photo : 'uploads/no-icon.png'}" alt="Photo" class="photo" /></td>
                                        <td><img src="${student.qr_code ? 'qrcodes/' + student.qr_code : 'N/A'}" alt="QR Code" class="qr-code" /></td>
                                    </tr>
                                `;
                                tableBody.innerHTML += row;
                            });
                        }
                        document.getElementById('studentsModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load students.');
                });
            });
        });

        document.querySelectorAll('.btn-unarchive').forEach(button => {
            button.addEventListener('click', () => {
                const classId = button.getAttribute('data-class-id');
                if (confirm('Are you sure you want to unarchive this class?')) {
                    fetch('<?php echo basename(__FILE__); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=unarchive_class&class_id=${encodeURIComponent(classId)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the class card from the DOM
                            const classCard = button.closest('.archived-class-card');
                            classCard.remove();
                            // Update stats
                            const totalClassesCard = document.querySelector('.stats-grid .card .card-value');
                            const newCount = parseInt(totalClassesCard.textContent) - 1;
                            totalClassesCard.textContent = newCount;
                            // Show no-classes message if no classes remain
                            const remainingClasses = document.querySelectorAll('.archived-class-card').length;
                            if (remainingClasses === 0) {
                                document.querySelector('.no-classes').style.display = 'block';
                            }
                            alert('Class unarchived successfully!');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to unarchive class.');
                    });
                }
            });
        });

        // Close modals
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.modal').style.display = 'none';
            });
        });
    </script>
</body>
</html>