<?php
ob_start();
require 'config.php';
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

session_start();

// Detect AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Validate session only for non-AJAX requests
$user = $isAjax ? ['teacher_id' => $_SESSION['teacher_id'] ?? null] : validateSession();
if (!$isAjax && !$user) {
    destroySession();
    header("Location: index.php");
    exit();
}

// Function to check subject by code
function checkSubjectByCode($subject_code)
{
    $pdo = getDBConnection();
    try {
        $stmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE subject_code = ?");
        $stmt->execute([$subject_code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['success' => true, 'exists' => !!$result, 'subject_name' => $result ? $result['subject_name'] : null];
    } catch (PDOException $e) {
        error_log("Check subject error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to check subject: ' . $e->getMessage()];
    }
}

// Function to calculate attendance percentages for the past two months
function calculateAttendancePercentages($teacher_id) {
    $pdo = getDBConnection();
    try {
        $twoMonthsAgo = date('Y-m-d', strtotime('-2 months'));
        $stmt = $pdo->prepare("
            SELECT 
                c.class_id,
                COUNT(*) as total_records,
                SUM(CASE WHEN at.attendance_status = 'Present' THEN 1 ELSE 0 END) as present_records
            FROM classes c
            LEFT JOIN class_students cs ON c.class_id = cs.class_id AND cs.is_enrolled = 1
            LEFT JOIN attendance_tracking at ON cs.class_id = at.class_id AND cs.lrn = at.lrn
            WHERE c.teacher_id = ? 
            AND at.attendance_date >= ? 
            AND at.attendance_date <= CURDATE()
            GROUP BY c.class_id
        ");
        $stmt->execute([$teacher_id, $twoMonthsAgo]);
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

// Updated isDuplicateClass function to handle the new logic
function isDuplicateClass($pdo, $section_name, $subject_code, $teacher_id, $grade_level, $class_id = null)
{
    // For lower grades, check section + teacher + grade combination
    $lowerGrades = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
    $isLowerGrade = in_array($grade_level, $lowerGrades);
    
    if ($isLowerGrade && ($subject_code === 'No Subject Code' || empty($subject_code))) {
        // For lower grades without subject, just check section + teacher + grade
        $query = "SELECT COUNT(*) FROM classes c WHERE c.section_name = ? AND c.teacher_id = ? AND c.grade_level = ?";
        $params = [$section_name, $teacher_id, $grade_level];
    } else {
        // For classes with subjects, check section + teacher + grade + subject combination
        $query = "SELECT COUNT(*) FROM classes c 
                  JOIN subjects s ON c.subject_id = s.subject_id 
                  WHERE c.section_name = ? AND c.teacher_id = ? AND c.grade_level = ? AND s.subject_code = ?";
        $params = [$section_name, $teacher_id, $grade_level, $subject_code];
    }

    if ($class_id) {
        $query .= " AND c.class_id != ?";
        $params[] = $class_id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

// Updated addClass function with proper subject handling
function addClass($classData, $scheduleData, $class_id = null)
{
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        $lowerGrades = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        $isLowerGrade = in_array($classData['gradeLevel'], $lowerGrades);

        // Ensure subject_id = 0 exists in subjects table
        $stmt = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_id = 0");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $pdo->exec("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_id, subject_code, subject_name) VALUES (0, 'No Subject Code', 'No Subject')");
            $stmt->execute();
            $pdo->exec("SET SESSION sql_mode = ''");
        }

        if (empty($classData['subject']) && !$isLowerGrade) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Subject is required for Grade 7 and above.'];
        }

        $subject_code = empty($classData['code']) ? 'No Subject Code' : $classData['code'];

        // Check for duplicate class - but exclude the current class being edited
        if (isDuplicateClass($pdo, $classData['sectionName'], $subject_code, $_SESSION['teacher_id'], $classData['gradeLevel'], $class_id)) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'A class with this section name, subject code/name, and grade level already exists for this teacher.'];
        }

        if (empty($classData['subject'])) {
            // For lower grades with no subject
            $subject_id = 0;
        } else {
            // Handle subject for both lower and higher grades when subject is provided
            // Allow same subject_code for multiple subject_names - always create new entry for different combinations
            
            if ($class_id) {
                // When editing, check if we need to update the existing subject or create new one
                $stmt = $pdo->prepare("
                    SELECT s.subject_id, s.subject_code, s.subject_name,
                           (SELECT COUNT(*) FROM classes WHERE subject_id = s.subject_id) as usage_count
                    FROM classes c 
                    JOIN subjects s ON c.subject_id = s.subject_id 
                    WHERE c.class_id = ?
                ");
                $stmt->execute([$class_id]);
                $currentSubject = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($currentSubject && $currentSubject['usage_count'] == 1) {
                    // This subject is only used by this class, safe to update it
                    if ($currentSubject['subject_code'] !== $subject_code || $currentSubject['subject_name'] !== $classData['subject']) {
                        $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ? WHERE subject_id = ?");
                        $stmt->execute([$subject_code, $classData['subject'], $currentSubject['subject_id']]);
                    }
                    $subject_id = $currentSubject['subject_id'];
                } else {
                    // Subject is used by multiple classes, need to find existing or create new
                    $stmt = $pdo->prepare("
                        SELECT subject_id FROM subjects 
                        WHERE subject_code = ? AND subject_name = ? AND subject_id != 0
                    ");
                    $stmt->execute([$subject_code, $classData['subject']]);
                    $existingSubject = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingSubject) {
                        $subject_id = $existingSubject['subject_id'];
                    } else {
                        // Create new subject entry
                        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)");
                        $stmt->execute([$subject_code, $classData['subject']]);
                        $subject_id = $pdo->lastInsertId();
                    }
                }
            } else {
                // When adding new class, always allow creating new subject entries
                // Check if exact combination exists, if not create new
                $stmt = $pdo->prepare("
                    SELECT subject_id FROM subjects 
                    WHERE subject_code = ? AND subject_name = ? AND subject_id != 0
                ");
                $stmt->execute([$subject_code, $classData['subject']]);
                $existingSubject = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingSubject) {
                    // Exact match found, use existing subject_id
                    $subject_id = $existingSubject['subject_id'];
                } else {
                    // No exact match found, create new subject (allows same code with different name)
                    $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)");
                    $stmt->execute([$subject_code, $classData['subject']]);
                    $subject_id = $pdo->lastInsertId();
                }
            }
        }

        if ($class_id) {
            // Update existing class
            $stmt = $pdo->prepare("
                UPDATE classes 
                SET section_name = ?, subject_id = ?, grade_level = ?, room = ?
                WHERE class_id = ? AND teacher_id = ?
            ");
            $stmt->execute([
                $classData['sectionName'],
                $subject_id,
                $classData['gradeLevel'],
                $classData['room'],
                $class_id,
                $_SESSION['teacher_id']
            ]);

            // Delete existing schedules for this class
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE class_id = ?");
            $stmt->execute([$class_id]);
        } else {
            // Insert new class
            $stmt = $pdo->prepare("
                INSERT INTO classes (section_name, subject_id, teacher_id, grade_level, room)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $classData['sectionName'],
                $subject_id,
                $_SESSION['teacher_id'],
                $classData['gradeLevel'],
                $classData['room']
            ]);
            $class_id = $pdo->lastInsertId();
        }

        // Insert new schedules
        foreach ($scheduleData as $day => $times) {
            if (!empty($times['start']) && !empty($times['end'])) {
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
function fetchClassesForTeacher()
{
    $pdo = getDBConnection();
    try {
        // Fetch class details
        $stmt = $pdo->prepare("
            SELECT c.class_id, c.section_name, c.grade_level, c.room, 
                   COALESCE(s.subject_code, '') as subject_code, 
                   COALESCE(s.subject_name, '') as subject_name,
                   (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.class_id AND cs.is_enrolled = 1) as student_count
            FROM classes c
            LEFT JOIN subjects s ON c.subject_id = s.subject_id
            WHERE c.teacher_id = ?
        ");
        $stmt->execute([$_SESSION['teacher_id']]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate attendance percentages
        $attendanceResult = calculateAttendancePercentages($_SESSION['teacher_id']);
        if (!$attendanceResult['success']) {
            return ['success' => false, 'error' => $attendanceResult['error']];
        }

        $classPercentages = $attendanceResult['class_percentages'];
        $overallAverage = $attendanceResult['overall_average'];

        foreach ($classes as &$class) {
            $stmt = $pdo->prepare("
                SELECT day, start_time, end_time
                FROM schedules
                WHERE class_id = ?
            ");
            $stmt->execute([$class['class_id']]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $class['schedule'] = [];
            foreach ($schedules as $schedule) {
                if (!empty($schedule['day'])) {
                    $class['schedule'][$schedule['day']] = [
                        'start' => $schedule['start_time'],
                        'end' => $schedule['end_time']
                    ];
                }
            }
            // Assign calculated attendance percentage
            $class['calculated_attendance_percentage'] = isset($classPercentages[$class['class_id']]) ? 
                round($classPercentages[$class['class_id']], 1) : 0;
        }

        error_log("Overall Average Attendance in fetchClassesForTeacher: " . round($overallAverage, 1)); // Debug log

        return [
            'success' => true,
            'data' => $classes,
            'overall_average_attendance' => round($overallAverage, 1)
        ];
    } catch (PDOException $e) {
        error_log("Fetch classes error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to fetch classes: ' . $e->getMessage()];
    }
}

// Function to fetch students for a class
function fetchStudentsForClass($class_id)
{
    $pdo = getDBConnection();
    try {
        $teacher_id = $_SESSION['teacher_id'] ?? null;
        if (!$teacher_id) {
            return ['success' => false, 'error' => 'Teacher session not found'];
        }

        $stmt = $pdo->prepare("
            SELECT s.lrn, s.first_name, s.middle_name, s.last_name, s.email, s.gender, s.dob, s.grade_level, 
                   s.address, s.parent_name, s.parent_email, s.emergency_contact, s.photo, s.qr_code, s.date_added
            FROM class_students cs
            JOIN students s ON cs.lrn = s.lrn
            WHERE cs.class_id = ? AND cs.is_enrolled = 1
        ");
        $stmt->execute([$class_id]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (PDOException $e) {
        error_log("Fetch students error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to fetch students: ' . $e->getMessage()];
    }
}

// Function to delete a student from a class
function deleteStudentFromClass($class_id, $lrn)
{
    $pdo = getDBConnection();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM class_students WHERE class_id = ? AND lrn = ?");
        $stmt->execute([$class_id, $lrn]);
        $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delete student error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete student: ' . $e->getMessage()];
    }
}

// Function to import students from Excel
function importStudents($class_id, $filePath)
{
    $pdo = getDBConnection();
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $pdo->beginTransaction();
        $header = array_shift($rows);

        $qrs_to_generate = [];
        foreach ($rows as $index => $row) {
            if (count($row) >= 12) {
                $lrn = $row[0] ?? null;
                if ($lrn && (!isset($row[13]) || empty(trim($row[13])))) {
                    $qrs_to_generate[] = [
                        'lrn' => $lrn,
                        'content' => "$lrn, {$row[1]}, {$row[2]}" . (isset($row[3]) && !empty($row[3]) ? " {$row[3]}" : '')
                    ];
                }
            }
        }

        $qr_files = [];
        if (!empty($qrs_to_generate)) {
            foreach ($qrs_to_generate as $item) {
                $qrCode = new QrCode($item['content']);
                $writer = new PngWriter();
                $result = $writer->write($qrCode);
                $dir = 'qrcodes';
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                $filename = $item['lrn'] . '.png';
                $savePath = $dir . '/' . $filename;
                $result->saveToFile($savePath);
                $qr_files[$item['lrn']] = $filename;
            }
        }

        foreach ($rows as $row) {
            if (count($row) >= 12) {
                $lrn = $row[0] ?? null;
                if (!$lrn) continue;

                $qr_code = isset($qr_files[$lrn]) ? $qr_files[$lrn] : (isset($row[13]) && !empty(trim($row[13])) ? trim($row[13]) : null);

                $stmt = $pdo->prepare("
                    INSERT INTO students (lrn, last_name, first_name, middle_name, email, gender, dob, grade_level, address, parent_name, parent_email, emergency_contact, photo, qr_code, date_added)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                    ON DUPLICATE KEY UPDATE 
                        first_name = VALUES(first_name),
                        last_name = VALUES(last_name),
                        middle_name = VALUES(middle_name),
                        email = VALUES(email),
                        gender = VALUES(gender),
                        dob = VALUES(dob),
                        grade_level = VALUES(grade_level),
                        address = VALUES(address),
                        parent_name = VALUES(parent_name),
                        parent_email = VALUES(parent_email),
                        emergency_contact = VALUES(emergency_contact),
                        photo = VALUES(photo),
                        qr_code = VALUES(qr_code)
                ");
                $stmt->execute([
                    $lrn,
                    $row[1] ?? null,
                    $row[2] ?? null,
                    $row[3] ?? null,
                    $row[4] ?? null,
                    $row[5] ?? null,
                    $row[6] ?? null,
                    $row[7] ?? null,
                    $row[8] ?? null,
                    $row[9] ?? null,
                    $row[10] ?? null,
                    $row[11] ?? null,
                    $row[12] ?? null,
                    $qr_code
                ]);

                $stmt = $pdo->prepare("
                    INSERT INTO class_students (class_id, lrn, is_enrolled)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE is_enrolled = 1
                ");
                $stmt->execute([$class_id, $lrn]);
            }
        }

        $pdo->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Import students error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to import students: ' . $e->getMessage()];
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        error_log("Spreadsheet error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Invalid Excel file: ' . $e->getMessage()];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    ob_clean();

    if ($_POST['action'] === 'checkSubject') {
        $subject_code = $_POST['subjectCode'] ?? '';
        if (!$subject_code) {
            echo json_encode(['success' => false, 'error' => 'Missing subject code']);
            exit;
        }
        echo json_encode(checkSubjectByCode($subject_code));
        exit;
    } elseif ($_POST['action'] === 'addClass') {
        $classData = [
            'code' => $_POST['classCode'] ?? '',
            'sectionName' => $_POST['sectionName'] ?? '',
            'subject' => $_POST['subject'] ?? '',
            'gradeLevel' => $_POST['gradeLevel'] ?? '',
            'room' => $_POST['room'] ?? ''
        ];

        $scheduleData = json_decode($_POST['schedule'] ?? '{}', true);
        $classId = $_POST['classId'] ?? null;

        $lowerGrades = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        $isLowerGrade = in_array($classData['gradeLevel'], $lowerGrades);

        if (empty($classData['sectionName']) || empty($classData['gradeLevel'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        if (!$isLowerGrade && empty($classData['subject'])) {
            echo json_encode(['success' => false, 'error' => 'Subject is required for Grade 7 and above']);
            exit;
        }

        if (!$isLowerGrade && !empty($classData['code']) && empty($classData['subject'])) {
            echo json_encode(['success' => false, 'error' => 'Subject name is required if subject code is provided']);
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
    } elseif ($_POST['action'] === 'fetchStudents') {
        $class_id = $_POST['classId'] ?? null;
        if (!$class_id) {
            echo json_encode(['success' => false, 'error' => 'Missing class ID']);
            exit;
        }
        echo json_encode(fetchStudentsForClass($class_id));
        exit;
    } elseif ($_POST['action'] === 'deleteStudent') {
        $class_id = $_POST['classId'] ?? null;
        $lrn = $_POST['lrn'] ?? null;
        if (!$class_id || !$lrn) {
            echo json_encode(['success' => false, 'error' => 'Missing class ID or LRN']);
            exit;
        }
        echo json_encode(deleteStudentFromClass($class_id, $lrn));
        exit;
    } elseif ($_POST['action'] === 'importStudents') {
        $class_id = $_POST['classId'] ?? null;
        if (!$class_id || !isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'Missing class ID or file']);
            exit;
        }

        $file = $_FILES['file'];
        $filePath = 'uploads/' . uniqid() . '_' . $file['name'];
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $result = importStudents($class_id, $filePath);
            unlink($filePath);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
        }
        exit;
    } elseif ($_POST['action'] === 'generateQRCodes') {
        $qrs_json = $_POST['qrs'] ?? '[]';
        $qrs_to_generate = json_decode($qrs_json, true);

        if (empty($qrs_to_generate)) {
            echo json_encode(['success' => false, 'error' => 'No QR codes to generate']);
            exit;
        }

        try {
            $qr_files = [];
            foreach ($qrs_to_generate as $item) {
                $qrCode = new QrCode($item['content']);
                $writer = new PngWriter();
                $result = $writer->write($qrCode);
                $dir = 'qrcodes';
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                $filename = $item['lrn'] . '.png';
                $savePath = $dir . '/' . $filename;
                $result->saveToFile($savePath);
                $qr_files[$item['lrn']] = $filename;
            }

            echo json_encode(['success' => true, 'qr_files' => $qr_files]);
        } catch (Exception $e) {
            error_log("QR Code generation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to generate QR codes: ' . $e->getMessage()]);
        }
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetchClasses') {
    header('Content-Type: application/json');
    ob_clean();
    echo json_encode(fetchClassesForTeacher());
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetchStudents') {
    header('Content-Type: application/json');
    ob_clean();
    $class_id = $_GET['classId'] ?? null;
    if (!$class_id) {
        echo json_encode(['success' => false, 'error' => 'Missing class ID']);
        exit;
    }
    echo json_encode(fetchStudentsForClass($class_id));
    exit;
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }

        html {
            height: 100%;
            /* Ensure html takes full viewport height */
            margin: 0;
        }

        body {
            background-color: var(--card-bg);
            color: var(--blackfont-color);
            height: 100%;
            /* margin: 0; */
            padding: 20px;
            /* Padding applies within the height */
            display: flex;
            /* Use flex to manage content height */
            flex-direction: column;
            /* Stack content vertically */
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
            /* background: var(--light-gray); */
            /* opacity: 0.7; */
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
            gap: 15px;
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
            border-radius: var(--radius-xl);
            max-width: 1100px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .modal-header {
            padding: var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            border-top-left-radius: var(--radius-xl);
            border-top-right-radius: var(--radius-xl);
        }

        .modal-title {
            margin: 0;
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--whitefont-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--whitefont-color);
            padding: var(--spacing-sm);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--whitefont-color);
        }

        .modal form {
            padding: var(--spacing-xl);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
        }

        .schedule-inputs {
            display: grid;
            gap: var(--spacing-md);
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
            padding-top: var(--spacing-xl);
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

        .student-table-container {
            margin-top: var(--spacing-lg);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .student-table th,
        .student-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .student-table th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
        }

        .student-table tr:hover {
            background: var(--inputfieldhover-color);
        }

        .import-section {
            margin-top: var(--spacing-lg);
            padding: var(--spacing-lg);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .import-section .btn {
            margin-right: var(--spacing-sm);
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
            .modal,
            .student-table-container,
            .import-section {
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

    <style>
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

        .controls-right {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
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

        .search-icon {
            position: absolute;
            left: var(--spacing-sm);
            top: 55%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
            font-size: 0.875rem;
        }

        .filter-select {
            min-width: 140px;
            padding: var(--spacing-xs) var(--spacing-sm);
        }

        .btn {
            padding: var(--spacing-xs) var(--spacing-md);
            font-size: var(--font-size-sm);
        }

        .view-toggle {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--inputfield-color);
            display: flex;
        }

        .view-btn {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.875rem;
        }

        @media (max-width: 1024px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .controls-right {
                justify-content: flex-start;
                margin-top: var(--spacing-sm);
            }
        }

        @media (max-width: 768px) {
            .controls-left {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .controls-right {
                flex-direction: column;
                gap: var(--spacing-xs);
            }

            .search-container {
                min-width: auto;
                width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .view-toggle {
                width: 100%;
                justify-content: space-between;
            }
        }

        .preview-table-container {
            margin-top: var(--spacing-lg);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .preview-table-container h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
            margin-bottom: var(--spacing-md);
        }
    </style>

    <!-- CSS for Add New Class Modal -->
    <style>
        /* Class Modal Specific Styles */
        .class-modal {
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

        .class-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .class-modal-content {
            background: var(--card-bg);
            margin: 0 auto;
            padding: 0;
            border-radius: var(--radius-xl);
            max-width: 1000px;
            /* Landscape layout */
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--border-color);
            position: relative;
        }

        .class-modal-header {
            padding: var(--spacing-xl) var(--spacing-2xl);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            border-top-left-radius: var(--radius-xl);
            border-top-right-radius: var(--radius-xl);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .class-modal-title {
            margin: 0;
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--whitefont-color);
            letter-spacing: 0.02em;
        }

        .class-close-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--whitefont-color);
            padding: var(--spacing-sm);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }

        .class-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .class-modal-form {
            padding: var(--spacing-2xl);
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* Two-column layout */
            gap: var(--spacing-xl);
            background: linear-gradient(180deg, #f9fafb, #ffffff);
        }

        .class-form-group {
            margin-bottom: var(--spacing-lg);
        }

        .class-form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
            letter-spacing: 0.01em;
        }

        .class-form-input,
        .class-form-select {
            padding: var(--spacing-md) var(--spacing-lg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .class-form-input:focus,
        .class-form-select:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .class-form-input:disabled,
        .class-form-select:disabled {
            background: var(--light-gray);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .class-schedule-inputs {
            display: grid;
            gap: var(--spacing-md);
            background: var(--white);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .class-schedule-day-input {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
            background: var(--inputfield-color);
        }

        .class-schedule-day-input:hover {
            background: var(--inputfieldhover-color);
            box-shadow: var(--shadow-sm);
        }

        .class-schedule-day-input input[type="checkbox"] {
            margin-right: var(--spacing-sm);
            accent-color: var(--primary-blue);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .class-schedule-day-input label {
            min-width: 90px;
            margin: 0;
            font-weight: 500;
            color: var(--blackfont-color);
            font-size: var(--font-size-sm);
        }

        .class-schedule-day-input input[type="time"] {
            padding: var(--spacing-xs) var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--white);
            transition: var(--transition-normal);
            width: 100px;
        }

        .class-schedule-day-input input[type="time"]:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px var(--primary-blue-light);
        }

        .class-schedule-day-input input[type="time"]:disabled {
            background: var(--light-gray);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .class-schedule-day-input span {
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            margin: 0 var(--spacing-xs);
        }

        .class-form-actions {
            grid-column: 1 / -1;
            /* Span across both columns */
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        .class-modal .btn {
            padding: var(--spacing-md) var(--spacing-xl);
            font-size: var(--font-size-base);
            font-weight: 600;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .class-modal .btn-primary {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            box-shadow: var(--shadow-sm);
        }

        .class-modal .btn-primary:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .class-modal .btn-secondary {
            background: var(--medium-gray);
            color: var(--whitefont-color);
            box-shadow: var(--shadow-sm);
        }

        .class-modal .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

        @media (max-width: 768px) {
            .class-modal-content {
                width: 98%;
                max-height: 95vh;
            }

            .class-modal-form {
                grid-template-columns: 1fr;
                /* Stack columns on smaller screens */
                padding: var(--spacing-lg);
            }

            .class-schedule-inputs {
                padding: var(--spacing-sm);
            }

            .class-schedule-day-input {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
            }

            .class-schedule-day-input input[type="time"] {
                width: 100%;
            }

            .class-form-actions {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .class-form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <style>
        /* Adjusted styles for Class Details (View Modal) and Student List */
        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--inputfield-color);
            border-radius: var(--radius-sm);
            transition: var(--transition-normal);
        }

        .detail-row:hover {
            background: var(--inputfieldhover-color);
            box-shadow: var(--shadow-sm);
        }

        .detail-row strong {
            flex: 0 0 150px;
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-sm);
        }

        .detail-row span,
        .detail-row div {
            flex: 1;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
        }

        .schedule-details {
            padding-left: var(--spacing-md);
            color: var(--grayfont-color);
        }

        .schedule-details .schedule-item {
            margin-bottom: var(--spacing-xs);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .schedule-details .schedule-item::before {
            content: "•";
            color: var(--primary-blue);
            margin-right: var(--spacing-xs);
        }

        /* Adjusted Student List (Student Modal) */
        .student-table-container {
            margin-top: var(--spacing-xl);
            padding: var(--spacing-xl);
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            width: 100%;
            max-width: 1200px;
            /* Increased width for better readability */
            margin-left: auto;
            margin-right: auto;
        }

        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .student-table th,
        .student-table td {
            padding: var(--spacing-lg) var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: var(--font-size-sm);
        }

        .student-table th {
            font-weight: 600;
            color: var(--grayfont-color);
            background: var(--inputfield-color);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .student-table td {
            color: var(--blackfont-color);
        }

        .student-table tr:hover {
            background: var(--inputfieldhover-color);
            transition: var(--transition-fast);
        }

        .student-table img {
            border-radius: var(--radius-sm);
            object-fit: cover;
        }

        /* Responsive adjustments for student table */
        @media (max-width: 1024px) {
            .student-table-container {
                max-width: 100%;
                padding: var(--spacing-md);
            }

            .student-table th,
            .student-table td {
                padding: var(--spacing-md) var(--spacing-sm);
            }

            .student-table th:nth-child(n+8),
            .student-table td:nth-child(n+8) {
                display: none;
            }
        }

        @media (max-width: 768px) {

            .student-table th:nth-child(n+6),
            .student-table td:nth-child(n+6) {
                display: none;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
                padding: var(--spacing-sm);
            }

            .detail-row strong {
                flex: none;
                width: 100%;
            }
        }

        @media (max-width: 576px) {

            .student-table th:nth-child(n+4),
            .student-table td:nth-child(n+4) {
                display: none;
            }

            .student-table-container {
                padding: var(--spacing-sm);
            }

            .student-table th,
            .student-table td {
                padding: var(--spacing-sm) var(--spacing-xs);
                font-size: 0.75rem;
            }

            .detail-row {
                padding: var(--spacing-xs);
            }
        }
    </style>

    <style>
        .modal-body {
            padding: 1.5rem 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .import-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .import-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .file-input {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            max-width: 250px;
            flex: 1;
        }

        .import-note {
            display: block;
            color: var(--grayfont-color);
            font-size: 0.85rem;
            line-height: 1.2;
            margin-top: 0.5rem;
        }

        .preview-table-container {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .preview-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
            margin-bottom: 1rem;
        }

        .table-wrapper {
            overflow-x: auto;
            max-width: 100%;
        }

        .preview-table,
        .student-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1200px;
            /* Increased to maximize column widths and force scroll if needed */
            table-layout: auto;
            /* Allow columns to adjust based on content */
        }

        .preview-table th,
        .student-table th,
        .preview-table td,
        .student-table td {
            padding: 1rem 1.5rem;
            /* Increased padding for better readability */
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
            overflow: hidden;
            /* text-overflow: ellipsis; */
            max-width: 200px;
            /* Maximize column width while preventing overflow */
        }

        .preview-table th,
        .student-table th {
            font-weight: 600;
            color: var(--grayfont-color);
            background: var(--inputfield-color);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .student-table td {
            color: var(--blackfont-color);
        }

        .preview-table tr:hover,
        .student-table tr:hover {
            background: var(--inputfieldhover-color);
            transition: var(--transition-fast);
        }

        .student-table img {
            border-radius: var(--radius-sm);
            object-fit: cover;
            max-width: 60px;
            max-height: 60px;
        }

        .form-actions {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 1024px) {
            .modal-body {
                padding: 1rem;
            }

            .import-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .file-input {
                max-width: 100%;
            }

            .preview-table th:nth-child(n+8),
            .preview-table td:nth-child(n+8),
            .student-table th:nth-child(n+8),
            .student-table td:nth-child(n+8) {
                display: none;
            }

            .preview-table th,
            .preview-table td,
            .student-table th,
            .student-table td {
                padding: 0.75rem 1rem;
                max-width: 120px;
            }
        }

        @media (max-width: 768px) {

            .preview-table th:nth-child(n+6),
            .preview-table td:nth-child(n+6),
            .student-table th:nth-child(n+6),
            .student-table td:nth-child(n+6) {
                display: none;
            }

            .modal-content {
                width: 98%;
                max-height: 95vh;
            }

            .import-section {
                padding: 0.75rem;
            }

            .preview-table th,
            .preview-table td,
            .student-table th,
            .student-table td {
                padding: 0.5rem 0.75rem;
                max-width: 100px;
                font-size: 0.875rem;
            }

            .form-actions {
                padding: 1rem;
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        @media (max-width: 576px) {

            .preview-table th:nth-child(n+4),
            .preview-table td:nth-child(n+4),
            .student-table th:nth-child(n+4),
            .student-table td:nth-child(n+4) {
                display: none;
            }

            .modal-body {
                padding: 0.75rem;
            }

            .preview-table-container,
            .student-table-container {
                padding: 0.75rem;
            }

            .preview-table th,
            .preview-table td,
            .student-table th,
            .student-table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
                max-width: 80px;
            }

            .form-actions {
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .import-controls {
                gap: 0.5rem;
            }

            .import-note {
                font-size: 0.65rem;
            }
        }
    </style>

    <style>
        /* Class Details (View Modal) Styles */
        #viewModal .modal-content {
            max-width: 900px;
            /* Slightly narrower for better focus */
            width: 90%;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        #viewModal .modal-header {
            padding: var(--spacing-xl) var(--spacing-2xl);
            background: var(--primary-gradient);
            border-top-left-radius: var(--radius-xl);
            border-top-right-radius: var(--radius-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        #viewModal .modal-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--whitefont-color);
            letter-spacing: 0.02em;
        }

        #viewModal .close-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--whitefont-color);
            padding: var(--spacing-sm);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }

        #viewModal .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        #viewModal .modal-body {
            padding: var(--spacing-2xl);
            max-height: 70vh;
            overflow-y: auto;
            background: linear-gradient(180deg, #f9fafb, #ffffff);
        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--inputfield-color);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .detail-row:hover {
            background: var(--inputfieldhover-color);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .detail-row strong {
            flex: 0 0 180px;
            /* Wider label for better readability */
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
            letter-spacing: 0.01em;
        }

        .detail-row span,
        .detail-row div {
            flex: 1;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            line-height: 1.5;
        }

        .schedule-details {
            padding-left: var(--spacing-lg);
            color: var(--grayfont-color);
            width: 100%;
        }

        .schedule-details .schedule-item {
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-size-sm);
            line-height: 1.4;
        }

        .schedule-details .schedule-item::before {
            content: "•";
            color: var(--primary-blue);
            font-size: 1.2rem;
            margin-right: var(--spacing-sm);
        }

        .detail-row .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: var(--font-size-sm);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-row .status-badge.active {
            background-color: rgba(34, 197, 94, 0.15);
            color: var(--success-green);
        }

        .detail-row .status-badge.inactive {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger-red);
        }

        #viewModal .form-actions {
            padding: var(--spacing-xl) var(--spacing-2xl);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            background: var(--card-bg);
        }

        #viewModal .btn {
            padding: var(--spacing-md) var(--spacing-xl);
            font-size: var(--font-size-base);
            font-weight: 600;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }

        #viewModal .btn-secondary {
            background: var(--medium-gray);
            color: var(--whitefont-color);
            box-shadow: var(--shadow-sm);
        }

        #viewModal .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        #viewContent {
            padding: 30px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            #viewModal .modal-content {
                width: 98%;
                max-height: 95vh;
            }

            #viewModal .modal-header {
                padding: var(--spacing-lg) var(--spacing-xl);
            }

            #viewModal .modal-body {
                padding: var(--spacing-lg);
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-sm);
                padding: var(--spacing-md);
                margin-bottom: var(--spacing-md);
            }

            .detail-row strong {
                flex: none;
                width: 100%;
                font-size: var(--font-size-sm);
            }

            .detail-row span,
            .detail-row div {
                font-size: 0.875rem;
            }

            .schedule-details {
                padding-left: var(--spacing-sm);
            }

            .schedule-details .schedule-item {
                font-size: 0.875rem;
                margin-bottom: var(--spacing-xs);
            }

            #viewModal .form-actions {
                padding: var(--spacing-md) var(--spacing-lg);
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            #viewModal .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            #viewModal .modal-header {
                padding: var(--spacing-md);
            }

            #viewModal .modal-body {
                padding: var(--spacing-md);
            }

            .detail-row {
                padding: var(--spacing-sm);
                margin-bottom: var(--spacing-sm);
            }

            .detail-row strong {
                font-size: 0.875rem;
            }

            .detail-row span,
            .detail-row div {
                font-size: 0.75rem;
            }

            .schedule-details .schedule-item {
                font-size: 0.75rem;
            }

            #viewModal .form-actions {
                padding: var(--spacing-sm);
            }
        }

        @media (max-width: 1024px) {
    .preview-table th:nth-child(n+9),
    .preview-table td:nth-child(n+9),
    .student-table th:nth-child(n+9),
    .student-table td:nth-child(n+9) {
        display: none;
    }

    .preview-table th,
    .preview-table td,
    .student-table th,
    .student-table td {
        padding: 0.75rem 1rem;
        max-width: 120px;
    }
}

@media (max-width: 768px) {
    .preview-table th:nth-child(n+7),
    .preview-table td:nth-child(n+7),
    .student-table th:nth-child(n+7),
    .student-table td:nth-child(n+7) {
        display: none;
    }

    .modal-content {
        width: 98%;
        max-height: 95vh;
    }

    .import-section {
        padding: 0.75rem;
    }

    .preview-table th,
    .preview-table td,
    .student-table th,
    .student-table td {
        padding: 0.5rem 0.75rem;
        max-width: 100px;
        font-size: 0.875rem;
    }

    .form-actions {
        padding: 1rem;
        flex-direction: column;
        gap: 0.75rem;
    }
}

@media (max-width: 576px) {
    .preview-table th:nth-child(n+5),
    .preview-table td:nth-child(n+5),
    .student-table th:nth-child(n+5),
    .student-table td:nth-child(n+5) {
        display: none;
    }

    .modal-body {
        padding: 0.75rem;
    }

    .preview-table-container,
    .student-table-container {
        padding: 0.75rem;
    }

    .preview-table th,
    .preview-table td,
    .student-table th,
    .student-table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.75rem;
        max-width: 80px;
    }

    .form-actions {
        padding: 1rem;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .import-controls {
        gap: 0.5rem;
    }

    .import-note {
        font-size: 0.65rem;
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
                    <div class="card-title">Average Attendance Percentage</div>
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
                </select>
                <select class="form-select filter-select" id="sectionFilter">
                    <option value="">All Sections</option>
                </select>
                <select class="form-select filter-select" id="subjectFilter">
                    <option value="">All Subjects</option>
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

        <div id="gridView" class="class-grid"></div>

        <div id="tableView" class="table-container hidden">
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th>Subject & Section</th>
                        <th>Grade Level</th>
                        <th>Schedule</th>
                        <th>Students</th>
                        <th>Attendance %</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Add New Class Modal -->
    <div id="classModal" class="class-modal">
        <div class="class-modal-content">
            <div class="class-modal-header">
                <h2 class="class-modal-title" id="modalTitle">Add New Class</h2>
                <button class="class-close-btn" onclick="closeModal()">×</button>
            </div>
            <form id="classForm" class="class-modal-form">
                <div class="class-form-column">
                    <div class="class-form-group">
                        <label class="class-form-label" for="gradeLevel">Grade Level</label>
                        <select class="class-form-select" id="gradeLevel" required>
                            <option value="">Select Grade</option>
                            <option value="Kindergarten">Kindergarten</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                            <option value="Grade 11">Grade 11</option>
                            <option value="Grade 12">Grade 12</option>
                            <option value="College 1st Year">College 1st Year</option>
                            <option value="College 2nd Year">College 2nd Year</option>
                            <option value="College 3rd Year">College 3rd Year</option>
                            <option value="College 4th Year">College 4th Year</option>
                            <option value="College 5th Year">College 5th Year</option>
                        </select>
                    </div>
                    <div class="class-form-group">
                        <label class="class-form-label" for="sectionName">Section Name</label>
                        <input type="text" class="class-form-input" id="sectionName" required placeholder="e.g., Section A, Diamond, Einstein">
                    </div>
                    <div class="class-form-group subject-field" id="classCodeGroup">
                        <label class="class-form-label" for="classCode">Subject Code (Optional)</label>
                        <input type="text" class="class-form-input" id="classCode" placeholder="e.g., MATH-101-A">
                    </div>
                    <div class="class-form-group subject-field" id="subjectGroup">
                        <label class="class-form-label" for="subject" id="subjectLabel">Subjects (Optional, Multiple Allowed)</label>
                        <input type="text" class="class-form-input" id="subject" placeholder="e.g., Mathematics, Science, English">
                    </div>
                    <div class="class-form-group">
                        <label class="class-form-label" for="room">Room (Optional)</label>
                        <input type="text" class="class-form-input" id="room" placeholder="e.g., Room 201, Lab 1">
                    </div>
                </div>
                <div class="class-form-column">
                    <div class="class-form-group">
                        <label class="class-form-label">Schedule (Optional)</label>
                        <div class="class-schedule-inputs">
                            <div class="class-schedule-day-input">
                                <input type="checkbox" id="monday" name="scheduleDays">
                                <label for="monday">Monday</label>
                                <input type="time" id="mondayStart" name="mondayStart" disabled>
                                <span>to</span>
                                <input type="time" id="mondayEnd" name="mondayEnd" disabled>
                            </div>
                            <div class="class-schedule-day-input">
                                <input type="checkbox" id="tuesday" name="scheduleDays">
                                <label for="tuesday">Tuesday</label>
                                <input type="time" id="tuesdayStart" name="tuesdayStart" disabled>
                                <span>to</span>
                                <input type="time" id="tuesdayEnd" name="tuesdayEnd" disabled>
                            </div>
                            <div class="class-schedule-day-input">
                                <input type="checkbox" id="wednesday" name="scheduleDays">
                                <label for="wednesday">Wednesday</label>
                                <input type="time" id="wednesdayStart" name="wednesdayStart" disabled>
                                <span>to</span>
                                <input type="time" id="wednesdayEnd" name="wednesdayEnd" disabled>
                            </div>
                            <div class="class-schedule-day-input">
                                <input type="checkbox" id="thursday" name="scheduleDays">
                                <label for="thursday">Thursday</label>
                                <input type="time" id="thursdayStart" name="thursdayStart" disabled>
                                <span>to</span>
                                <input type="time" id="thursdayEnd" name="thursdayEnd" disabled>
                            </div>
                            <div class="class-schedule-day-input">
                                <input type="checkbox" id="friday" name="scheduleDays">
                                <label for="friday">Friday</label>
                                <input type="time" id="fridayStart" name="fridayStart" disabled>
                                <span>to</span>
                                <input type="time" id="fridayEnd" name="fridayEnd" disabled>
                            </div>
                            <div class="class-schedule-day-input">
                                <input type="checkbox" id="saturday" name="scheduleDays">
                                <label for="saturday">Saturday</label>
                                <input type="time" id="saturdayStart" name="saturdayStart" disabled>
                                <span>to</span>
                                <input type="time" id="saturdayEnd" name="saturdayEnd" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="class-form-actions">
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
            <div id="viewContent" class="p-6"></div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Student List</h2>
                <button class="close-btn" onclick="closeStudentModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="import-section">
                    <div class="import-controls">
                        <input type="file" id="importFile" accept=".xlsx, .xls" class="file-input">
                        <button class="btn btn-success" onclick="importStudents()">Import Excel</button>
                    </div>
                    <small class="import-note">Expected columns: LRN, Last Name, First Name, Middle Name, Email, Gender, DOB, Grade Level, Address, Parent Name, Parent Email, Emergency Contact, Photo, QR Code</small>
                </div>
                <div class="preview-table-container" id="previewTableContainer" style="display: none;">
                    <h3 class="preview-title">Preview</h3>
                    <div class="table-wrapper">
                        <table class="preview-table" id="previewTable">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="student-table-container">
                    <div class="table-wrapper">
                        <table class="student-table" id="studentTable">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
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
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeStudentModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        let classes = [];
        let overallAverageAttendance = 0;  // Store overall average globally to avoid reset
        let currentView = 'grid';
        let editingClassId = null;

        document.addEventListener('DOMContentLoaded', function() {
            fetchClasses();
            setupEventListeners();
            clearScheduleInputs();
            toggleSubjectFields();
        });

        function fetchClasses() {
            fetch('manage-classes.php?action=fetchClasses')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                    return response.json();
                })
                .then(result => {
                    console.log('Fetch Classes Response:', result); // Debug the response
                    if (result.success) {
                        classes = result.data || [];
                        overallAverageAttendance = result.overall_average_attendance || 0;  // Store globally
                        updateStats();  // Update stats with stored value
                        renderClasses();  // Render views without updating stats
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
            const totalStudents = classes.reduce((sum, c) => sum + (parseInt(c.student_count) || 0), 0);
            const averageAttendance = parseFloat(overallAverageAttendance) || 0;

            console.log('Updating Stats:', { totalClasses, totalStudents, averageAttendance }); // Debug

            document.getElementById('total-classes').textContent = totalClasses;
            document.getElementById('total-students').textContent = totalStudents;
            document.getElementById('average-attendance').textContent = averageAttendance.toFixed(1) + '%';
        }

        function setupEventListeners() {
            const searchInput = document.getElementById('searchInput');
            const gradeFilter = document.getElementById('gradeFilter');
            const subjectFilter = document.getElementById('subjectFilter');
            const sectionFilter = document.getElementById('sectionFilter');
            const classForm = document.getElementById('classForm');
            const gradeLevelSelect = document.getElementById('gradeLevel');

            if (searchInput) searchInput.addEventListener('input', handleSearch);
            if (gradeFilter) gradeFilter.addEventListener('change', handleFilter);
            if (subjectFilter) subjectFilter.addEventListener('change', handleFilter);
            if (sectionFilter) sectionFilter.addEventListener('change', handleFilter);
            if (classForm) classForm.addEventListener('submit', handleFormSubmit);
            if (gradeLevelSelect) gradeLevelSelect.addEventListener('change', toggleSubjectFields);

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

        function toggleSubjectFields() {
            const gradeLevel = document.getElementById('gradeLevel').value;
            const classCodeGroup = document.getElementById('classCodeGroup');
            const subjectGroup = document.getElementById('subjectGroup');
            const classCodeInput = document.getElementById('classCode');
            const subjectInput = document.getElementById('subject');
            const subjectLabel = document.getElementById('subjectLabel');

            const lowerGrades = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
            const isLowerGrade = lowerGrades.includes(gradeLevel);

            if (classCodeGroup && subjectGroup && classCodeInput && subjectInput && subjectLabel) {
                classCodeGroup.style.display = 'block';
                subjectGroup.style.display = 'block';
                classCodeInput.removeAttribute('required');

                if (isLowerGrade) {
                    subjectLabel.textContent = 'Subjects (Optional, Multiple Allowed)';
                    subjectInput.placeholder = 'e.g., Mathematics, Science, English';
                    subjectInput.removeAttribute('required');
                } else {
                    subjectLabel.textContent = 'Subject (One Subject Only)';
                    subjectInput.placeholder = 'e.g., Mathematics';
                    subjectInput.setAttribute('required', 'required');
                }
            }
        }

        function renderClasses() {
            // Removed erroneous updateStats call - stats are overall and set in fetchClasses
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
                const attendancePercentage = parseFloat(classItem.calculated_attendance_percentage) || 0;
                const studentCount = parseInt(classItem.student_count) || 0;

                const card = document.createElement('div');
                card.className = 'class-card';
                card.innerHTML = `
                    <div class="class-header">
                        <h3>${sanitizeHTML(classItem.grade_level)} - ${sanitizeHTML(classItem.section_name)}</h3>
                    </div>
                    <div class="class-info">
                        <h4>${sanitizeHTML(classItem.subject_name)}</h4>
                        ${classItem.subject_name ? `<p><i class="fas fa-book"></i> ${sanitizeHTML(classItem.subject_code)}</p>` : ''}
                        <p><i class="fas fa-map-marker-alt"></i> ${sanitizeHTML(classItem.room || 'No room specified')}</p>
                        <p><i class="fas fa-users"></i> ${studentCount} students</p>
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
                        <button class="btn btn-sm btn-success" onclick="openStudentModal(${classItem.class_id})">
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
                tbody.innerHTML = '<tr><td colspan="7" class="no-classes">No classes found</td></tr>';
                return;
            }

            filteredClasses.forEach(classItem => {
                const scheduleText = formatScheduleShort(classItem.schedule);
                const attendancePercentage = parseFloat(classItem.calculated_attendance_percentage) || 0;
                const studentCount = parseInt(classItem.student_count) || 0;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="row-checkbox" data-class-id="${classItem.class_id}"></td>
                    <td>
                        <strong>${sanitizeHTML(classItem.subject_name || classItem.section_name)}</strong><br>
                        <small>${sanitizeHTML(classItem.section_name)}</small>
                    </td>
                    <td>${sanitizeHTML(classItem.grade_level)}</td>
                    <td>${sanitizeHTML(scheduleText)}</td>
                    <td>${studentCount}</td>
                    <td>${attendancePercentage.toFixed(1)}%</td>
                    <td class="actions">
                        <button class="btn btn-sm btn-info" onclick="viewClass(${classItem.class_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="editClass(${classItem.class_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-success" onclick="openStudentModal(${classItem.class_id})">
                            <i class="fas fa-users"></i>
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
            const subjectFilter = document.getElementById('subjectFilter');
            const sectionFilter = document.getElementById('sectionFilter');

            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const gradeFilterValue = gradeFilter ? gradeFilter.value : '';
            const subjectFilterValue = subjectFilter ? subjectFilter.value : '';
            const sectionFilterValue = sectionFilter ? sectionFilter.value : '';

            return classes.filter(classItem => {
                const matchesSearch = searchTerm === '' ||
                    (classItem.subject_code && classItem.subject_code.toLowerCase().includes(searchTerm)) ||
                    (classItem.section_name && classItem.section_name.toLowerCase().includes(searchTerm)) ||
                    (classItem.subject_name && classItem.subject_name.toLowerCase().includes(searchTerm));

                const matchesGrade = gradeFilterValue === '' || classItem.grade_level === gradeFilterValue;
                const matchesSubject = subjectFilterValue === '' || classItem.subject_name === subjectFilterValue;
                const matchesSection = sectionFilterValue === '' || classItem.section_name === sectionFilterValue;

                return matchesSearch && matchesGrade && matchesSubject && matchesSection;
            });
        }

        function populateFilters() {
            const subjectFilter = document.getElementById('subjectFilter');
            const sectionFilter = document.getElementById('sectionFilter');
            const gradeFilter = document.getElementById('gradeFilter');
            if (!subjectFilter || !sectionFilter || !gradeFilter) return;

            const subjects = [...new Set(classes.map(c => c.subject_name).filter(s => s))];
            const sections = [...new Set(classes.map(c => c.section_name).filter(s => s))];
            const gradeLevels = [...new Set(classes.map(c => c.grade_level).filter(g => g))];

            subjectFilter.innerHTML = '<option value="">All Subjects</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectFilter.appendChild(option);
            });

            sectionFilter.innerHTML = '<option value="">All Sections</option>';
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionFilter.appendChild(option);
            });

            gradeFilter.innerHTML = '<option value="">All Grade Levels</option>';
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeFilter.appendChild(option);
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
            const subjectInput = document.getElementById('subject');
            const classCodeInput = document.getElementById('classCode');

            if (modalTitle) modalTitle.textContent = 'Add New Class';
            if (classForm) classForm.reset();
            if (subjectInput) subjectInput.value = '';
            if (classCodeInput) classCodeInput.value = '';
            clearScheduleInputs();
            toggleSubjectFields();
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
                classCode: classItem.subject_code || '',
                sectionName: classItem.section_name,
                subject: classItem.subject_name || '',
                gradeLevel: classItem.grade_level,
                room: classItem.room || ''
            };

            Object.entries(fields).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) element.value = value;
            });

            toggleSubjectFields();

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
            const attendancePercentage = parseFloat(classItem.calculated_attendance_percentage) || 0;
            const studentCount = parseInt(classItem.student_count) || 0;

            const content = document.getElementById('viewContent');
            if (content) {
                content.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            ${classItem.subject_code ? `<div class="detail-row"><strong>Subject Code:</strong> ${sanitizeHTML(classItem.subject_code)}</div>` : ''}
                            <div class="detail-row">
                                <strong>Section Name:</strong> ${sanitizeHTML(classItem.section_name)}
                            </div>
                            ${classItem.subject_name ? `<div class="detail-row"><strong>Subject:</strong> ${sanitizeHTML(classItem.subject_name)}</div>` : ''}
                            <div class="detail-row">
                                <strong>Grade Level:</strong> ${sanitizeHTML(classItem.grade_level)}
                            </div>
                        </div>
                        <div>
                            <div class="detail-row">
                                <strong>Room:</strong> ${sanitizeHTML(classItem.room || 'No room specified')}
                            </div>
                            <div class="detail-row">
                                <strong>Students:</strong> ${studentCount}
                            </div>
                            <div class="detail-row">
                                <strong>Attendance Percentage:</strong> ${attendancePercentage.toFixed(1)}%
                            </div>
                        </div>
                        <div class="col-span-2">
                            <div class="detail-row">
                                <strong>Schedule:</strong>
                                <div class="schedule-details">
                                    ${scheduleText}
                                </div>
                            </div>
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=deleteClass&classId=${classId}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        classes = classes.filter(c => c.class_id !== classId);
                        overallAverageAttendance = classes.length > 0 ? overallAverageAttendance : 0;  // Re-fetch if needed, but for simplicity, keep as-is (or refetch)
                        updateStats();  // Update stats after delete
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
                classId: editingClassId || ''
            };

            fetch('manage-classes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=addClass&${new URLSearchParams(classData)}&schedule=${encodeURIComponent(JSON.stringify(schedule))}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
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

                if (checkbox && startInput && endInput) {
                    checkbox.checked = false;
                    startInput.disabled = true;
                    endInput.disabled = true;
                    startInput.value = '';
                    endInput.value = '';
                }
            });
        }

        function formatSchedule(schedule) {
            if (!schedule || Object.keys(schedule).length === 0) {
                return '<div class="no-schedule">No schedule set</div>';
            }

            return Object.entries(schedule).map(([day, times]) => {
                return `<div class="schedule-item">${day.charAt(0).toUpperCase() + day.slice(1)}: ${times.start} - ${times.end}</div>`;
            }).join('');
        }

        function formatScheduleShort(schedule) {
            if (!schedule || Object.keys(schedule).length === 0) {
                return 'No schedule set';
            }

            return Object.entries(schedule).map(([day, times]) => {
                return `${day.charAt(0).toUpperCase() + day.slice(1)}: ${times.start} - ${times.end}`;
            }).join(', ');
        }

        function sanitizeHTML(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }

        function openStudentModal(classId) {
            const studentModal = document.getElementById('studentModal');
            if (studentModal) {
                studentModal.dataset.classId = classId;
                studentModal.classList.add('show');
            }
            fetchStudents(classId);
        }

        function closeStudentModal() {
            const studentModal = document.getElementById('studentModal');
            const previewTableContainer = document.getElementById('previewTableContainer');
            const importFile = document.getElementById('importFile');
            if (studentModal) studentModal.classList.remove('show');
            if (previewTableContainer) previewTableContainer.style.display = 'none';
            if (importFile) importFile.value = '';
            document.querySelector('#previewTable tbody').innerHTML = '';
        }

        function fetchStudents(classId) {
            fetch(`manage-classes.php?action=fetchStudents&classId=${classId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        renderStudentTable(result.data, classId);
                    } else {
                        console.error('Error fetching students:', result.error);
                        alert('Failed to fetch students: ' + result.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching students:', error);
                    alert('Error fetching students: ' + error.message);
                });
        }

        function excelDateToYYYYMMDD(excelDate) {
            if (!excelDate || isNaN(excelDate)) return 'N/A';
            const baseDate = new Date(1899, 11, 30);
            const date = new Date(baseDate.getTime() + excelDate * 86400000);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function renderStudentTable(students, classId) {
            const tbody = document.querySelector('#studentTable tbody');
            if (!tbody) return;

            tbody.innerHTML = '';

            if (!students || students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="15" class="no-classes">No students enrolled</td></tr>';
                return;
            }

            students.forEach(student => {
                const photoSrc = student.photo ? `uploads/${student.photo}` : '';
                const qrSrc = student.qr_code ? `qrcodes/${student.qr_code}` : '';

                let qrDisplay = qrSrc ? 
                    `<img src="${qrSrc}" alt="QR Code" style="max-width: 50px; max-height: 50px;"><br><small>${student.qr_code}</small>` : 
                    'QR Code To Be Provided';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${sanitizeHTML(student.lrn || 'N/A')}</td>
                    <td>${sanitizeHTML(student.first_name || 'N/A')}</td>
                    <td>${sanitizeHTML(student.middle_name || 'N/A')}</td>
                    <td>${sanitizeHTML(student.last_name || 'N/A')}</td>
                    <td>${sanitizeHTML(student.email || 'N/A')}</td>
                    <td>${sanitizeHTML(student.gender || 'N/A')}</td>
                    <td>${sanitizeHTML(student.dob || 'N/A')}</td>
                    <td>${sanitizeHTML(student.grade_level || 'N/A')}</td>
                    <td>${sanitizeHTML(student.address || 'N/A')}</td>
                    <td>${sanitizeHTML(student.parent_name || 'N/A')}</td>
                    <td>${sanitizeHTML(student.parent_email || 'N/A')}</td>
                    <td>${sanitizeHTML(student.emergency_contact || 'N/A')}</td>
                    <td>${photoSrc ? `<img src="${photoSrc}" alt="Student Photo" style="max-width: 45px; max-height: 45px; border-radius:50%;">` : 'Photo To Be Provided'}</td>
                    <td>${qrDisplay}</td>
                    <td>${sanitizeHTML(student.date_added || 'N/A')}</td>
                    <td class="actions">
                        <button class="btn btn-sm btn-danger" onclick="deleteStudent(${classId}, '${student.lrn}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function deleteStudent(classId, lrn) {
            if (!confirm('Are you sure you want to delete this student from the class?')) return;

            fetch('manage-classes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=deleteStudent&classId=${classId}&lrn=${lrn}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        fetchStudents(classId);
                        alert('Student removed successfully!');
                    } else {
                        alert('Failed to delete student: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting student:', error);
                    alert('Error deleting student: ' + error.message);
                });
        }

        let previewData = [];
        let excelHeader = [];

        document.getElementById('importFile').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) {
                const previewTableContainer = document.getElementById('previewTableContainer');
                if (previewTableContainer) previewTableContainer.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheet];
                    const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, raw: false });

                    const previewTableContainer = document.getElementById('previewTableContainer');

                    if (!previewTableContainer) {
                        console.error('Preview table elements not found');
                        alert('Error: Preview table is not available.');
                        return;
                    }

                    if (rows.length <= 1) {
                        previewTableContainer.style.display = 'none';
                        alert('The selected Excel file is empty or contains only headers.');
                        return;
                    }

                    excelHeader = rows[0];
                    previewData = rows.slice(1).filter(row => row.length >= 11);
                    processQRCodeFilenames();
                    generateAndDisplayQRCodes();
                    renderPreviewTable();
                    previewTableContainer.style.display = 'block';
                } catch (error) {
                    console.error('Error reading Excel file:', error);
                    alert('Error reading Excel file: ' + error.message);
                    const previewTableContainer = document.getElementById('previewTableContainer');
                    if (previewTableContainer) previewTableContainer.style.display = 'none';
                }
            };
            reader.readAsArrayBuffer(file);
        });

        function renderPreviewTable() {
            const tbody = document.querySelector('#previewTable tbody');
            if (!tbody) return;

            tbody.innerHTML = '';

            previewData.forEach((row, index) => {
                const photoValue = row[12] || '';
                let photoDisplay = photoValue && (photoValue.includes('.jpg') || photoValue.includes('.jpeg') || photoValue.includes('.png') || photoValue.includes('.gif')) ?
                    `<img src="uploads/${photoValue}" alt="Student Photo" style="max-width: 45px; max-height: 45px; border-radius: 50%;" onerror="this.style.display='none'; this.nextSibling.style.display='inline';"><span style="display:none;">${sanitizeHTML(photoValue)}</span>` :
                    photoValue ? sanitizeHTML(photoValue) : 'Photo To Be Provided';

                let qrDisplay = row[13] && row[13].toString().trim() !== '' ?
                    `<img src="qrcodes/${row[13]}" alt="QR Code" style="max-width: 50px; max-height: 50px;" onerror="this.style.display='none'; this.nextSibling.style.display='inline';"><span style="display:none;">QR: ${row[13]}</span>` :
                    'To be generated';

                const tr = document.createElement('tr');
                tr.dataset.index = index;
                tr.innerHTML = `
                    <td>${sanitizeHTML(row[0] || '')}</td>
                    <td>${sanitizeHTML(row[1] || '')}</td>
                    <td>${sanitizeHTML(row[2] || '')}</td>
                    <td>${sanitizeHTML(row[3] || '')}</td>
                    <td>${sanitizeHTML(row[4] || '')}</td>
                    <td>${sanitizeHTML(row[5] || '')}</td>
                    <td>${sanitizeHTML(row[6] || excelDateToYYYYMMDD(row[6]))}</td>
                    <td>${sanitizeHTML(row[7] || '')}</td>
                    <td>${sanitizeHTML(row[8] || '')}</td>
                    <td>${sanitizeHTML(row[9] || '')}</td>
                    <td>${sanitizeHTML(row[10] || '')}</td>
                    <td>${sanitizeHTML(row[11] || '')}</td>
                    <td>${photoDisplay}</td>
                    <td>${qrDisplay}</td>
                    <td class="actions">
                        <button class="btn btn-sm btn-danger" onclick="removePreviewRow(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function removePreviewRow(btn) {
            const tr = btn.closest('tr');
            const index = parseInt(tr.dataset.index);
            previewData.splice(index, 1);
            renderPreviewTable();
        }

        function importStudents() {
            if (previewData.length === 0) {
                alert('No data to import.');
                return;
            }

            const newRows = [excelHeader, ...previewData];
            const newWs = XLSX.utils.aoa_to_sheet(newRows);
            const newWb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(newWb, newWs, 'Sheet1');

            const excelBuffer = XLSX.write(newWb, { bookType: 'xlsx', type: 'array' });
            const blob = new Blob([excelBuffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

            const formData = new FormData();
            formData.append('action', 'importStudents');
            formData.append('classId', document.querySelector('#studentModal').dataset.classId || '0');
            formData.append('file', blob, 'students.xlsx');

            fetch('manage-classes.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        fetchStudents(document.querySelector('#studentModal').dataset.classId);
                        document.getElementById('previewTableContainer').style.display = 'none';
                        document.getElementById('importFile').value = '';
                        document.querySelector('#previewTable tbody').innerHTML = '';
                        previewData = [];
                        alert('Students imported successfully!');
                    } else {
                        alert('Failed to import students: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error importing students:', error);
                    alert('Error importing students: ' + error.message);
                });
        }

        function processQRCodeFilenames() {
            previewData.forEach((row, index) => {
                const lrn = row[0];
                if (!row[13] || row[13].toString().trim() === '') {
                    const qrFilename = `${lrn}.png`;
                    previewData[index][13] = qrFilename;
                }
            });
        }

        function generateAndDisplayQRCodes() {
            const qrsToGenerate = [];
            previewData.forEach((row, index) => {
                const lrn = row[0];
                const lastName = row[1] || '';
                const firstName = row[2] || '';
                const middleName = row[3] || '';

                if (!row[13] || row[13].toString().trim() === '' || row[13] === `${lrn}.png`) {
                    const qrContent = `${lrn}, ${lastName}, ${firstName}${middleName ? ' ' + middleName : ''}`;
                    qrsToGenerate.push({
                        lrn: lrn,
                        content: qrContent,
                        index: index
                    });
                }
            });

            if (qrsToGenerate.length > 0) {
                generateQRCodesOnServer(qrsToGenerate);
            } else {
                updateAllQRDisplays();
            }
        }

        function generateQRCodesOnServer(qrsToGenerate) {
            const formData = new FormData();
            formData.append('action', 'generateQRCodes');
            formData.append('qrs', JSON.stringify(qrsToGenerate));

            fetch('manage-classes.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.qr_files) {
                        Object.keys(data.qr_files).forEach(lrn => {
                            const index = previewData.findIndex(row => row[0] === lrn);
                            if (index !== -1) {
                                previewData[index][13] = data.qr_files[lrn];
                                updatePreviewQRCode(index, `qrcodes/${data.qr_files[lrn]}`, data.qr_files[lrn]);
                            }
                        });
                    }
                    updateAllQRDisplays();
                })
                .catch(error => {
                    console.error('Error generating QR codes:', error);
                    updateAllQRDisplays();
                });
        }

        function updateAllQRDisplays() {
            previewData.forEach((row, index) => {
                if (row[13]) {
                    const qrPath = `qrcodes/${row[13]}`;
                    updatePreviewQRCode(index, qrPath, row[13]);
                }
            });
        }

        function updatePreviewQRCode(index, qrPath, filename) {
            const tbody = document.querySelector('#previewTable tbody');
            if (!tbody) return;

            const row = tbody.querySelector(`tr[data-index="${index}"]`);
            if (!row) return;

            const qrCell = row.cells[13];
            if (qrCell) {
                qrCell.innerHTML = `<img src="${qrPath}" alt="QR Code" style="max-width: 50px; max-height: 50px;" onerror="this.style.display='none'; this.nextSibling.style.display='inline';"><span style="display:none;">QR: ${filename}</span>`;
            }
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
        }
    </script>
</body>
</html>