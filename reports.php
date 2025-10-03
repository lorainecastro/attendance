<?php
// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
require 'config.php';
require 'vendor/autoload.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

session_start();

$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
    exit();
}

$pdo = getDBConnection();

// Fetch total students (count all enrollments, not distinct)
$total_students_stmt = $pdo->prepare("SELECT COUNT(cs.lrn) FROM class_students cs JOIN classes c ON cs.class_id = c.class_id WHERE c.teacher_id = :teacher_id");
$total_students_stmt->execute(['teacher_id' => $user['teacher_id']]);
$total_students = $total_students_stmt->fetchColumn();

// Fetch active classes count
$active_classes_stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = :teacher_id AND status = 'active'");
$active_classes_stmt->execute(['teacher_id' => $user['teacher_id']]);
$active_classes = $active_classes_stmt->fetchColumn();

// Fetch today's attendance stats
$today = date('Y-m-d');
$todayFormatted = date('M d');
$today_attendance_stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN at.attendance_status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN at.attendance_status = 'Absent' THEN 1 END) as absent,
        COUNT(CASE WHEN at.attendance_status = 'Late' THEN 1 END) as late,
        COUNT(*) as total
    FROM attendance_tracking at
    INNER JOIN classes c ON at.class_id = c.class_id
    WHERE c.teacher_id = :teacher_id AND at.attendance_date = :today 
    AND c.status = 'active' 
    AND at.logged_by IN ('Teacher', 'Device Camera', 'Scanner Device') 
    AND at.attendance_status IN ('Present', 'Absent', 'Late')
");
$today_attendance_stmt->execute(['teacher_id' => $user['teacher_id'], 'today' => $today]);
$today_attendance = $today_attendance_stmt->fetch(PDO::FETCH_ASSOC) ?: ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];

$attended = (int)$today_attendance['present'] + (int)$today_attendance['late'];
$total = (int)$today_attendance['total'];
$monthAttendanceRate = $total > 0 ? round(($attended / $total) * 100, 2) : 0;

// Fetch classes
$classes_stmt = $pdo->prepare("SELECT c.*, sub.subject_code, sub.subject_name FROM classes c JOIN subjects sub ON c.subject_id = sub.subject_id WHERE c.teacher_id = :teacher_id");
$classes_stmt->execute(['teacher_id' => $user['teacher_id']]);
$classes_db = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

$classes_php = [];
foreach ($classes_db as $cls) {
    $students_stmt = $pdo->prepare("SELECT s.lrn AS id, s.full_name AS fullName, s.email, s.gender FROM students s JOIN class_students cs ON s.lrn = cs.lrn WHERE cs.class_id = :class_id ORDER BY s.full_name ASC");
    $students_stmt->execute(['class_id' => $cls['class_id']]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedules_stmt = $pdo->prepare("SELECT * FROM schedules WHERE class_id = :class_id");
    $schedules_stmt->execute(['class_id' => $cls['class_id']]);
    $schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);
    $schedule = [];
    $late_to_absent = $schedules[0]['late_to_absent'] ?? 0;
    foreach ($schedules as $sch) {
        $schedule[$sch['day']] = ['start' => $sch['start_time'], 'end' => $sch['end_time']];
    }

    $classes_php[] = [
        'id' => $cls['class_id'],
        'code' => $cls['subject_code'],
        'sectionName' => $cls['section_name'],
        'subject' => $cls['subject_name'],
        'gradeLevel' => $cls['grade_level'],
        'room' => $cls['room'],
        'attendancePercentage' => $cls['attendance_percentage'],
        'schedule' => $schedule,
        'status' => $cls['status'],
        'late_to_absent' => $late_to_absent,
        'students' => array_map(function($student) {
            return [
                'id' => $student['id'],
                'fullName' => trim($student['fullName']),
                'gender' => $student['gender']
            ];
        }, $students)
    ];
}

// Fetch attendance data
$attendance_stmt = $pdo->prepare("SELECT at.*, at.lrn AS studentId, at.class_id AS classId, at.time_checked AS timeChecked, s.full_name AS fullName FROM attendance_tracking at JOIN classes c ON at.class_id = c.class_id JOIN students s ON at.lrn = s.lrn WHERE c.teacher_id = :teacher_id ORDER BY s.full_name ASC, at.time_checked DESC");
$attendance_stmt->execute(['teacher_id' => $user['teacher_id']]);
$attendance_db = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

$attendance_php = [];
foreach ($attendance_db as $att) {
    $time_checked = $att['timeChecked'] ? date('M d Y h:i:s A', strtotime($att['timeChecked'])) : '--';
    $attendance_php[] = [
        'studentId' => $att['studentId'],
        'classId' => $att['classId'],
        'date' => $att['attendance_date'],
        'status' => $att['attendance_status'],
        'timeChecked' => $time_checked,
        'fullName' => trim($att['fullName'])
    ];
}

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'exportReport') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $data = json_decode($_POST['data'], true);
    $headers = $data['headers'];
    $reportData = $data['data'];
    $reportType = $data['reportType'];
    $dateFrom = $data['dateFrom'];
    $dateTo = $data['dateTo'];
    $title = $data['title'];
    $format = $_POST['format'];
    $classId = $data['classId'] ?? null;
    $month = $data['month'] ?? null;

    if ($reportType === 'student') {
        $headers = ['Class', 'LRN', 'Name', 'Status', 'Time Checked'];
    } elseif ($reportType === 'class') {
        $headers = ['Class', 'Total Students', 'Present', 'Absent', 'Late', 'Average Attendance'];
    } elseif ($reportType === 'perfect') {
        $headers = ['Class', 'LRN', 'Name', 'Status', 'Attendance Issue Summary', 'Adjusted Attendance Record'];
    }

    try {
        if ($reportType === 'sf2') {
            if ($format !== 'excel') {
                throw new Exception('SF2 report is only available in Excel format.');
            }
            if (!$classId || !$month) {
                throw new Exception('Class and month are required for SF2 report.');
            }

            // Fetch class details
            $class_stmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = :class_id AND teacher_id = :teacher_id");
            $class_stmt->execute(['class_id' => $classId, 'teacher_id' => $user['teacher_id']]);
            $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$class) {
                throw new Exception('Class not found.');
            }

            $gradeLevel = $class['grade_level'];
            $sectionName = $class['section_name'];
            $schoolName = $user['institution'] ?: 'Add Institution here'; // Fallback to example

            // Month map
            $monthMap = [
                'JANUARY' => 1, 'FEBRUARY' => 2, 'MARCH' => 3, 'APRIL' => 4, 'MAY' => 5,
                'JUNE' => 6, 'JULY' => 7, 'AUGUST' => 8, 'SEPTEMBER' => 9, 'OCTOBER' => 10,
                'NOVEMBER' => 11, 'DECEMBER' => 12
            ];
            if (!isset($monthMap[$month])) {
                throw new Exception('Invalid month selected.');
            }
            $monthNum = $monthMap[$month];

            // Determine school year and month year
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('m');
            $schoolStartYear = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;
            $schoolYear = $schoolStartYear . ' - ' . ($schoolStartYear + 1);
            $year = ($monthNum >= 6) ? $schoolStartYear : $schoolStartYear + 1;

            // Get school days (Mon-Fri)
            $firstDay = new DateTime("$year-$monthNum-01");
            $lastDay = clone $firstDay;
            $lastDay->modify('last day of this month');
            $schoolDays = [];
            $current = clone $firstDay;
            while ($current <= $lastDay) {
                $dow = (int)$current->format('N'); // 1 (Mon) to 7 (Sun)
                if ($dow >= 1 && $dow <= 5) { // Mon-Fri
                    $schoolDays[] = clone $current;
                }
                $current->modify('+1 day');
            }
            $numDays = count($schoolDays);
            if ($numDays > 25) {
                $schoolDays = array_slice($schoolDays, 0, 25); // Truncate to max columns
                $numDays = 25;
            }

            // Define consecutive day columns starting from D to AB (25 columns)
            $dayColumns = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB'];

            // Define fixed day abbreviation sequence: M, T, W, TH, F repeated 5 times
            $fixedAbbrevs = ['M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F', 'M', 'T', 'W', 'TH', 'F'];
            $dayAssignments = [];
            $dayIndex = 0;

            // Determine the starting day of the week for the first school day
            $firstSchoolDay = reset($schoolDays);
            $firstDayOfWeek = (int)$firstSchoolDay->format('N'); // 1 (Mon) to 5 (Fri)
            $dayMap = [1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F'];
            $startAbbrev = $dayMap[$firstDayOfWeek]; // e.g., 'T' for Tuesday (July 1, 2025)

            // Find the index in fixedAbbrevs where we start
            $startIndex = array_search($startAbbrev, $fixedAbbrevs);
            if ($startIndex === false) {
                throw new Exception('Invalid starting day abbreviation.');
            }

            // Assign days, starting with the correct abbreviation
            for ($i = 0; $i < 25; $i++) {
                $abbrev = $fixedAbbrevs[$i];
                $col = $dayColumns[$i];
                if ($i < $startIndex) {
                    // Columns before the starting day are blank in row 5
                    $dayAssignments[] = [
                        'column' => $col,
                        'abbrev' => $abbrev,
                        'date' => null,
                        'dateNum' => ''
                    ];
                } else {
                    // Assign school days starting from the first school day
                    if ($dayIndex < count($schoolDays)) {
                        $dayAssignments[] = [
                            'column' => $col,
                            'abbrev' => $abbrev,
                            'date' => $schoolDays[$dayIndex],
                            'dateNum' => $schoolDays[$dayIndex]->format('j')
                        ];
                        $dayIndex++;
                    } else {
                        // No more school days, leave blank
                        $dayAssignments[] = [
                            'column' => $col,
                            'abbrev' => $abbrev,
                            'date' => null,
                            'dateNum' => ''
                        ];
                    }
                }
            }

            // Fetch students
            $males_stmt = $pdo->prepare("SELECT s.* FROM students s JOIN class_students cs ON s.lrn = cs.lrn WHERE cs.class_id = :class_id AND LOWER(s.gender) = 'male' ORDER BY s.full_name ASC");
            $males_stmt->execute(['class_id' => $classId]);
            $males = $males_stmt->fetchAll(PDO::FETCH_ASSOC);

            $females_stmt = $pdo->prepare("SELECT s.* FROM students s JOIN class_students cs ON s.lrn = cs.lrn WHERE cs.class_id = :class_id AND LOWER(s.gender) = 'female' ORDER BY s.full_name ASC");
            $females_stmt->execute(['class_id' => $classId]);
            $females = $females_stmt->fetchAll(PDO::FETCH_ASSOC);

            $maleCount = count($males);
            $femaleCount = count($females);
            $totalStudents = $maleCount + $femaleCount;

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // $sheet->setTitle('school_form_2_ver2014.2.1.1');
            $sheet->setTitle('School_Form_2');

            // Border style
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];

            $columnWidths = [
                'A' => 4.14, 'B' => 2.86, 'C' => 30.00, 'D' => 3.5, 'E' => 3.5,
                'F' => 3.5, 'G' => 3.5, 'H' => 3.5, 'I' => 3.5, 'J' => 3.5,
                'K' => 3.5, 'L' => 3.5, 'M' => 3.5, 'N' => 3.5, 'O' => 3.5,
                'P' => 3.5, 'Q' => 3.5, 'R' => 3.5, 'S' => 3.5, 'T' => 3.5,
                'U' => 3.5, 'V' => 3.5, 'W' => 3.5, 'X' => 3.5, 'Y' => 3.5,
                'Z' => 3.5, 'AA' => 3.5, 'AB' => 3.5, 'AC' => 5, 'AD' => 5,
                'AE' => 5, 'AF' => 5, 'AG' => 5, 'AH' => 5, 'AI' => 4.5,
                'AJ' => 4.5, 'AK' => 4.5, 'AL' => 4.5, 'AM' => 5, 'AN' => 5, 'AO' => 3.5,
                'AP' => 3.5, 'AQ' => 3.5, 'AR' => 3.5, 'AS' => 3.5, 'AT' => 3.5,
                'AU' => 3.5, 'AV' => 3.5, 'AW' => 7.14, 'AX' => 7.14, 'AY' => 7.14,
                'AZ' => 3.5
            ];
            foreach ($columnWidths as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            // Set row heights
            $rowHeights = [
                1 => 30,  // Title
                2 => 20,  // Subtitle
                3 => 20,  // School ID, Year, Month
                4 => 20,  // School Name, Grade, Section
                5 => 15,  // Headers
                6 => 15,  // dates
                7 => 15,  // Day abbreviations
            ];

            // Apply borders to row 6
            $sheet->getStyle('A6:AN6')->applyFromArray($borderStyle);

            // Set fixed texts and merge cells (rows 1–5)
            $sheet->setCellValue('A1', 'School Form 2 (SF2) Daily Attendance Report of Learners');
            $sheet->mergeCells('A1:AN1');
            $sheet->getStyle('A1')->getFont()->setName('SansSerif')->setSize(13)->setBold(true);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('A2', '(This replaces Form 1, Form 2 & STS Form 4 - Absenteeism and Dropout Profile)');
            $sheet->mergeCells('A2:AN2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('A3', 'School ID');
            $sheet->mergeCells('A3:C3');
            $sheet->setCellValue('I3', 'School Year');
            $sheet->mergeCells('I3:K3');
            $sheet->setCellValue('L3', $schoolYear);
            $sheet->mergeCells('L3:P3');
            $sheet->setCellValue('Q3', 'Report for the Month of');
            $sheet->mergeCells('Q3:W3');
            $sheet->setCellValue('X3', $month);
            $sheet->mergeCells('X3:AB3');
            $sheet->mergeCells('D3:H3'); // Merge F3 to J3
            $sheet->setCellValue('F3', ''); // Leave merged cell empty or set a placeholder if needed
            $sheet->getStyle('A3:AN3')->applyFromArray($borderStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('A4', 'Name of School');
            $sheet->mergeCells('A4:C4');
            $sheet->setCellValue('D4', $schoolName);
            $sheet->mergeCells('D4:P4');
            $sheet->setCellValue('Q4', 'Grade Level');
            $sheet->mergeCells('Q4:W4');
            $sheet->setCellValue('X4', $gradeLevel);
            $sheet->mergeCells('X4:AB4');
            $sheet->setCellValue('AC4', 'Section');
            $sheet->mergeCells('AC4:AF4');
            $sheet->setCellValue('AG4', $sectionName);
            $sheet->mergeCells('AG4:AN4');
            $sheet->getStyle('A4:AN4')->applyFromArray($borderStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Modified header row (row 5)
            $sheet->setCellValue('A5', 'No.');
            $sheet->mergeCells('A5:A7'); // Merge A5 through A7
            $sheet->setCellValue('B5', "NAME\n(Last Name, First Name, Middle Name)");
            $sheet->mergeCells('B5:C7'); // Merge B5:C5 through B7:C7
            $sheet->setCellValue('D5', '(1st row for date)');
            $sheet->mergeCells('D5:AB5'); // Merge D5 to AB5
            $sheet->setCellValue('AC5', "Total for the Month");
            $sheet->mergeCells('AC5:AF6');

            $sheet->getStyle('A5:AN5')->applyFromArray($borderStyle);
            $sheet->getStyle('A5:A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->applyFromArray($borderStyle);
            $sheet->getStyle('AC5:AF6')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B5:C7')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->applyFromArray($borderStyle);

            $sheet->mergeCells('AC7:AD7'); 
            $sheet->setCellValue('AC7', 'ABSENT');
            $sheet->getStyle('AC7:AD7')->applyFromArray($borderStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->mergeCells('AE7:AF7'); 
            $sheet->setCellValue('AE7', 'PRESENT');
            $sheet->getStyle('AE7:AF7')->applyFromArray($borderStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Merge AG5:AN7 for Remarks column
            $sheet->mergeCells('AG5:AN7');
            $sheet->setCellValue('AG5', 'REMARKS (If DROPPED OUT, state reason, please refer to legend number 2. If TRANSFERRED IN/OUT, write the name of School.)');
            $sheet->getStyle('AG5:AN7')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AG5:AN7')->applyFromArray($borderStyle);

            // Apply borders and center-align day and abbreviation cells
            foreach ($dayColumns as $col) {
                $sheet->getStyle($col . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '6')->applyFromArray($borderStyle);
                $sheet->getStyle($col . '7')->applyFromArray($borderStyle);
            }

            // Populate dates and day abbreviations
            foreach ($dayAssignments as $assignment) {
                $col = $assignment['column'];
                $sheet->setCellValue($col . '6', $assignment['dateNum']);
                $sheet->setCellValue($col . '7', $assignment['abbrev']);
                $sheet->getStyle($col . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . '6')->applyFromArray($borderStyle);
                $sheet->getStyle($col . '7')->applyFromArray($borderStyle);
            }

            // Student rows
            $maleStartRow = 8;
            $studentAttendance = []; // To track per student absences
            $dailyPresent = array_fill(0, $numDays, ['male' => 0, 'female' => 0, 'combined' => 0]);
            $consecutiveAbsentCount = 0;

            // Males
            for ($j = 0; $j < $maleCount; $j++) {
                $row = $maleStartRow + $j;
                $sheet->setCellValue('A' . $row, $j + 1); // Write number in column A only
                $sheet->setCellValue('B' . $row, $males[$j]['full_name']);
                $sheet->mergeCells('B' . $row . ':C' . $row);
                $lrn = $males[$j]['lrn'];
                $absentCount = 0;
                $statusArray = [];
                $dayIndex = 0;
                foreach ($dayAssignments as $assignment) {
                    $col = $assignment['column'];
                    if ($assignment['date']) { // Only process columns with valid dates
                        $date = $assignment['date']->format('Y-m-d');
                        $att_stmt = $pdo->prepare("SELECT attendance_status FROM attendance_tracking WHERE class_id = :class_id AND lrn = :lrn AND attendance_date = :date");
                        $att_stmt->execute(['class_id' => $classId, 'lrn' => $lrn, 'date' => $date]);
                        $status = $att_stmt->fetchColumn() ?: 'Absent';
                        $mark = ($status === 'Absent') ? 'x' : ($status === 'Late' ? 'L' : '');
                        $sheet->setCellValue($col . $row, $mark);
                        if ($status !== 'Absent') {
                            $dailyPresent[$dayIndex]['male']++;
                            $dailyPresent[$dayIndex]['combined']++;
                        }
                        if ($mark === 'x') {
                            $absentCount++;
                            $statusArray[] = 1;
                        } else {
                            $statusArray[] = 0;
                        }
                        $dayIndex++;
                    } else {
                        $sheet->setCellValue($col . $row, '');
                        $statusArray[] = 0; // No absence counted for blank dates
                    }
                    $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($col . $row)->applyFromArray($borderStyle);
                }
                $presentCount = $numDays - $absentCount;
                $sheet->setCellValue('AC' . $row, $absentCount);
                $sheet->setCellValue('AE' . $row, $presentCount);
                $sheet->getStyle('A' . $row . ':AN' . $row)->applyFromArray($borderStyle);
                $studentAttendance[] = $statusArray;

                // Check for 5 consecutive absents
                $streak = 0;
                foreach ($statusArray as $s) {
                    if ($s === 1) {
                        $streak++;
                        if ($streak >= 5) {
                            $consecutiveAbsentCount++;
                            break;
                        }
                    } else {
                        $streak = 0;
                    }
                }
            }
            $maleTotalRow = $maleStartRow + $maleCount;
            $sheet->setCellValue('B' . $maleTotalRow, '<=== MALE | TOTAL Per Day ===>');
            $sheet->mergeCells('B' . $maleTotalRow . ':C' . $maleTotalRow);
            $sheet->getRowDimension($maleTotalRow)->setRowHeight(20);
            $sheet->getStyle('A' . $maleTotalRow . ':AN' . $maleTotalRow)->applyFromArray($borderStyle);

            // Females
            $femaleStartRow = $maleTotalRow + 1;
            for ($j = 0; $j < $femaleCount; $j++) {
                $row = $femaleStartRow + $j;
                $sheet->setCellValue('A' . $row, $j + 1); // Write number in column A only
                $sheet->setCellValue('B' . $row, $females[$j]['full_name']);
                $sheet->mergeCells('B' . $row . ':C' . $row);
                $lrn = $females[$j]['lrn'];
                $absentCount = 0;
                $statusArray = [];
                $dayIndex = 0;
                foreach ($dayAssignments as $assignment) {
                    $col = $assignment['column'];
                    if ($assignment['date']) { // Only process columns with valid dates
                        $date = $assignment['date']->format('Y-m-d');
                        $att_stmt = $pdo->prepare("SELECT attendance_status FROM attendance_tracking WHERE class_id = :class_id AND lrn = :lrn AND attendance_date = :date");
                        $att_stmt->execute(['class_id' => $classId, 'lrn' => $lrn, 'date' => $date]);
                        $status = $att_stmt->fetchColumn() ?: 'Absent';
                        $mark = ($status === 'Absent') ? 'x' : ($status === 'Late' ? 'L' : '');
                        $sheet->setCellValue($col . $row, $mark);
                        if ($status !== 'Absent') {
                            $dailyPresent[$dayIndex]['female']++;
                            $dailyPresent[$dayIndex]['combined']++;
                        }
                        if ($mark === 'x') {
                            $absentCount++;
                            $statusArray[] = 1;
                        } else {
                            $statusArray[] = 0;
                        }
                        $dayIndex++;
                    } else {
                        $sheet->setCellValue($col . $row, '');
                        $statusArray[] = 0; // No absence counted for blank dates
                    }
                    $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($col . $row)->applyFromArray($borderStyle);
                }
                $presentCount = $numDays - $absentCount;
                $sheet->setCellValue('AC' . $row, $absentCount);
                $sheet->setCellValue('AE' . $row, $presentCount);
                $sheet->getStyle('A' . $row . ':AN' . $row)->applyFromArray($borderStyle);
                $studentAttendance[] = $statusArray;

                // Check for 5 consecutive absents
                $streak = 0;
                foreach ($statusArray as $s) {
                    if ($s === 1) {
                        $streak++;
                        if ($streak >= 5) {
                            $consecutiveAbsentCount++;
                            break;
                        }
                    } else {
                        $streak = 0;
                    }
                }
            }
            $femaleTotalRow = $femaleStartRow + $femaleCount;
            $sheet->setCellValue('B' . $femaleTotalRow, '<=== FEMALE | TOTAL Per Day ===>');
            $sheet->mergeCells('B' . $femaleTotalRow . ':C' . $femaleTotalRow);
            $sheet->getRowDimension($femaleTotalRow)->setRowHeight(20);
            $sheet->getStyle('A' . $femaleTotalRow . ':AN' . $femaleTotalRow)->applyFromArray($borderStyle);

            $combinedRow = $femaleTotalRow + 1;
            $sheet->setCellValue('B' . $combinedRow, 'Combined TOTAL Per Day');
            $sheet->mergeCells('B' . $combinedRow . ':C' . $combinedRow);
            $sheet->getRowDimension($combinedRow)->setRowHeight(20);
            $sheet->getStyle('A' . $combinedRow . ':AN' . $combinedRow)->applyFromArray($borderStyle);

            // 🔹 Merge AC:AD, AE:AF, and AG:AN for every row from maleStartRow (8) up to Combined TOTAL row
            for ($r = $maleStartRow; $r <= $combinedRow; $r++) {
                // Merge AC:AD
                $sheet->mergeCells('AC' . $r . ':AD' . $r);
                $sheet->getStyle('AC' . $r . ':AD' . $r)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('AC' . $r . ':AD' . $r)->applyFromArray($borderStyle);

                // Merge AE:AF
                $sheet->mergeCells('AE' . $r . ':AF' . $r);
                $sheet->getStyle('AE' . $r . ':AF' . $r)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('AE' . $r . ':AF' . $r)->applyFromArray($borderStyle);

                // Merge AG:AN (Remarks column)
                $sheet->mergeCells('AG' . $r . ':AN' . $r);
                $sheet->getStyle('AG' . $r . ':AN' . $r)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT) // Remarks usually left-aligned
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('AG' . $r . ':AN' . $r)->applyFromArray($borderStyle);
            }


            for ($i = 8; $i < $combinedRow; $i++) {
                $rowHeights[$i] = 20; // Height for student rows and total rows
            }
            $rowHeights[$combinedRow] = 20; // Combined TOTAL Per Day row
            foreach ($rowHeights as $row => $height) {
                $sheet->getRowDimension($row)->setRowHeight($height);
            }

            // Set daily totals
            $totalAttendance = 0;
            $dayIndex = 0;
            foreach ($dayAssignments as $assignment) {
                $col = $assignment['column'];
                if ($assignment['date']) {
                    $sheet->setCellValue($col . $maleTotalRow, $dailyPresent[$dayIndex]['male']);
                    $sheet->setCellValue($col . $femaleTotalRow, $dailyPresent[$dayIndex]['female']);
                    $sheet->setCellValue($col . $combinedRow, $dailyPresent[$dayIndex]['combined']);
                    $totalAttendance += $dailyPresent[$dayIndex]['combined'];
                    $dayIndex++;
                } else {
                    $sheet->setCellValue($col . $maleTotalRow, '');
                    $sheet->setCellValue($col . $femaleTotalRow, '');
                    $sheet->setCellValue($col . $combinedRow, '');
                }
                $sheet->getStyle($col . $maleTotalRow)->applyFromArray($borderStyle);
                $sheet->getStyle($col . $femaleTotalRow)->applyFromArray($borderStyle);
                $sheet->getStyle($col . $combinedRow)->applyFromArray($borderStyle);
            }

            // Calculate averages
            $averageDaily = $numDays > 0 ? $totalAttendance / $numDays : 0;
            $percentageAttendance = $totalStudents > 0 ? round(($averageDaily / $totalStudents) * 100, 2) : 0;

            // Guidelines and summary (shifted rows)
            $guidelinesStartRow = $combinedRow + 1;
            $offset = $guidelinesStartRow - 42;

            // Set guidelines texts with merges
            $newRow = 42 + $offset;
            $sheet->setCellValue('A' . $newRow, 'GUIDELINES:');
            $sheet->mergeCells('A' . $newRow . ':Q' . $newRow);
            $sheet->getStyle('A' . $newRow . ':Q' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A' . $newRow . ':Q' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            
            $sheet->setCellValue('S' . $newRow, '1. CODES FOR CHECKING ATTENDANCE');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('AC' . $newRow, "Month :\n" . $month);
            $sheet->mergeCells('AC' . $newRow . ':AE' . ($newRow + 1)); 
            $sheet->getStyle('AC' . $newRow . ':AE' . ($newRow + 1))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('AC' . $newRow . ':AE' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setBold(true);

            $sheet->setCellValue('AF' . $newRow, 'No. of Days of Classes:  ' .$numDays);
            $sheet->mergeCells('AF' . $newRow . ':AH' . ($newRow + 1)); 
            $sheet->getStyle('AF' . $newRow . ':AH' . ($newRow + 1))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('AF' . $newRow . ':AH' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setBold(true);

            // $sheet->setCellValue('AJ' . $newRow, $numDays);
            $sheet->setCellValue('AI' . $newRow, 'Summary');
            $sheet->mergeCells('AI' . $newRow . ':AN' . $newRow);
            $sheet->getStyle('AI' . $newRow . ':AN' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('AI' . $newRow . ':AN' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $newRow = 43 + $offset;
            $sheet->setCellValue('A' . $newRow, "1. The attendance shall be accomplished daily. Refer to the codes for checking learners' attendance.\n2. Dates shall be written in the columns after Learner's Name.\n3. To compute the following:");
            $sheet->mergeCells('A' . $newRow . ':Q' . ($newRow + 2)); 
            $sheet->getStyle('A' . $newRow . ':Q' . ($newRow + 4))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A' . $newRow . ':Q' . ($newRow + 4))->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('S' . $newRow, '(blank) - Present; (x)- Absent; Tardy (half shaded= Upper for Late Commer, Lower for Cutting Classes)');
            $sheet->mergeCells('S' . $newRow . ':AA' . ($newRow + 1));
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 1))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8);

            $sheet->setCellValue('AI' . $newRow, 'M');
            $sheet->mergeCells('AI' . $newRow . ':AJ' . $newRow);
            $sheet->getStyle('AI' . $newRow . ':AJ' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);

            $sheet->setCellValue('AK' . $newRow, 'F');
            $sheet->mergeCells('AK' . $newRow . ':AL' . $newRow);
            $sheet->getStyle('AK' . $newRow . ':AL' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);

            $sheet->setCellValue('AM' . $newRow, 'TOTAL');
            $sheet->mergeCells('AM' . $newRow . ':AN' . $newRow);
            $sheet->getStyle('AM' . $newRow . ':AN' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);

            $newRow = 44 + $offset;
            $sheet->setCellValue('AC' . $newRow, '* Enrolment as of (1st Friday of June)');
            $sheet->mergeCells('AC' . $newRow . ':AH' . $newRow);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getFont()->setName('SansSerif')->setSize(8);
            $sheet->getRowDimension($newRow)->setRowHeight(20);
            
            $sheet->setCellValue('AI' . $newRow, $maleCount);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . $newRow);
            $sheet->getStyle('AI' . $newRow . ':AJ' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . $newRow)->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, $femaleCount);
            $sheet->mergeCells('AK' . $newRow . ':AL' . $newRow);
            $sheet->getStyle('AK' . $newRow . ':AL' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . $newRow)->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AM' . $newRow, $totalStudents);
            $sheet->mergeCells('AM' . $newRow . ':AN' . $newRow);
            $sheet->getStyle('AM' . $newRow . ':AN' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . $newRow)->getFont()->setName('SansSerif')->setSize(9);
            // $sheet->getStyle('A' . $newRow . ':AS' . $newRow)->applyFromArray($borderStyle);
            // $sheet->getRowDimension($newRow)->setRowHeight(15);

            $newRow = 45 + $offset;
            $sheet->setCellValue('S' . $newRow, '2. REASONS/CAUSES FOR NLS');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

            // Create RichText object
            $richText = new RichText();
            $part1 = $richText->createTextRun('Late enrolment ');
            $part1->getFont()->setName('SansSerif')->setSize(8)->setItalic(true); // normal italic

            $part2 = $richText->createTextRun('during the month');
            $part2->getFont()->setName('SansSerif')->setSize(8)->setItalic(true)->setBold(true); // bold italic

            // Apply to cell
            $sheet->setCellValue('AC' . $newRow, $richText);
            $sheet->mergeCells('AC' . $newRow . ':AH' . $newRow);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setItalic(true);

            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);


            $sheet->setCellValue('AM' . $newRow, 0);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $newRow = 46 + $offset;
            $sheet->setCellValue('B' . $newRow, 'a. Percentage of Enrolment =');
            $sheet->mergeCells('B' . $newRow . ':C' . ($newRow + 1));
            $sheet->getStyle('B' . $newRow . ':C' . ($newRow + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B' . $newRow . ':C' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('D' . $newRow, 'Registered Learners as of end of the month');
            $sheet->mergeCells('D' . $newRow . ':O' . $newRow);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            
            $sheet->setCellValue('P' . $newRow, 'x 100');
            $sheet->mergeCells('P' . $newRow . ':Q' . ($newRow + 1));
            $sheet->getStyle('P' . $newRow . ':Q' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('P' . $newRow . ':Q' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            
            $sheet->setCellValue('S' . $newRow, 'a. Domestic-Related Factors');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('AC' . $newRow, '(beyond cut-off)');
            $sheet->mergeCells('AC' . $newRow . ':AH' . $newRow);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setItalic(true);

            $newRow = 47 + $offset;
            $sheet->setCellValue('D' . $newRow, 'Enrolment as of 1st Friday of the school year');
            $sheet->mergeCells('D' . $newRow . ':O' . $newRow);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            
            $sheet->setCellValue('S' . $newRow, "a.1. Had to take care of siblings\na.2. Early marriage/pregnancy\na.3. Parents' attitude toward schooling\na.4. Family problems");
            $sheet->mergeCells('S' . $newRow . ':AA' . ($newRow + 3)); 
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 3))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 3))->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('AC' . $newRow, 'Registered Learners as of');
            $sheet->mergeCells('AC' . $newRow . ':AH' . $newRow);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setItalic(true);

            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AM' . $newRow, 0);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            
            $newRow = 48 + $offset;
            $sheet->setCellValue('B' . $newRow, 'b. Average Daily Attendance =');
            $sheet->mergeCells('B' . $newRow . ':C' . ($newRow + 1));
            $sheet->getStyle('B' . $newRow . ':C' . ($newRow + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B' . $newRow . ':C' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('D' . $newRow, 'Total Daily Attendance');
            $sheet->mergeCells('D' . $newRow . ':O' . $newRow);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('AC' . $newRow, 'end of month');
            $sheet->mergeCells('AC' . $newRow . ':AH' . $newRow);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setItalic(true)->setBold(true);

            $newRow = 49 + $offset;
            $sheet->setCellValue('D' . $newRow, 'Number of School Days in reporting month');
            $sheet->mergeCells('D' . $newRow . ':O' . $newRow);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getFont()->setName('SansSerif')->setSize(8);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            
            $sheet->setCellValue('AC' . $newRow, 'Percentage of Enrolment as of');
            $sheet->mergeCells('AC' . $newRow . ':AH' . $newRow);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setItalic(true);

            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AM' . $newRow, 0);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            
            
            $newRow = 50 + $offset;
            $sheet->setCellValue('B' . $newRow, 'c. Percentage of Attendance for the month =');
            $sheet->mergeCells('B' . $newRow . ':C' . ($newRow + 1));
            $sheet->getStyle('B' . $newRow . ':C' . ($newRow + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B' . $newRow . ':C' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('D' . $newRow, 'Average daily attendance');
            $sheet->mergeCells('D' . $newRow . ':O' . $newRow);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('P' . $newRow, 'x 100');
            $sheet->mergeCells('P' . $newRow . ':Q' . ($newRow + 1));
            $sheet->getStyle('P' . $newRow . ':Q' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('P' . $newRow . ':Q' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            
            $sheet->setCellValue('AC' . $newRow, 'end of month');
            $sheet->mergeCells('AC' . $newRow . ':AH' . $newRow);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setItalic(true)->setBold(true);

            $newRow = 51 + $offset;
            $sheet->setCellValue('D' . $newRow, 'Registered Learners as of end of the month');
            $sheet->mergeCells('D' . $newRow . ':O' . $newRow);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getFont()->setName('SansSerif')->setSize(8);
            $sheet->getStyle('D' . $newRow . ':O' . $newRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

            $sheet->setCellValue('S' . $newRow, 'b. Individual-Related Factors');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('AC' . $newRow, 'Average Daily Attendance');
            $sheet->mergeCells('AC' . $newRow . ':AH' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setItalic(true);

            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AM' . $newRow, 0);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            
            $newRow = 52 + $offset;
            $sheet->setCellValue('S' . $newRow, "b.1. Illness\nb.2. Overage\nb.3. Death\nb.4. Drug Abuse\nb.5. Poor academic performance\nb.6. Lack of interest/Distractions\nb.7. Hunger/Malnutrition");
            $sheet->mergeCells('S' . $newRow . ':AA' . ($newRow + 5)); 
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 5))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 5))->getFont()->setName('SansSerif')->setSize(8);

            $newRow = 53 + $offset;
            $sheet->setCellValue('A' . $newRow, "4. Every end of the month, the class adviser will submit this form to the office of the principal for recording of summary table into School Form 4. Once signed by the principal, this form should be returned to the adviser.\n5. The adviser will provide neccessary interventions including but not limited to home visitation to learner/s who were absent for 5 consecutive days and/or those at risk of dropping out.\n6.  Attendance performance of learners will be reflected in Form 137 and Form 138 every grading period.");
            $sheet->mergeCells('A' . $newRow . ':Q' . ($newRow + 4)); 
            $sheet->getStyle('A' . $newRow . ':Q' . ($newRow + 4))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A' . $newRow . ':Q' . ($newRow + 4))->getFont()->setName('SansSerif')->setSize(8);
            
            $sheet->setCellValue('AC' . $newRow, 'Percentage of Attendance for the month');
            $sheet->mergeCells('AC' . $newRow . ':AH' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setItalic(true);
            
            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AM' . $newRow, $percentageAttendance);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $newRow = 54 + $offset;

            $newRow = 55 + $offset;
            $sheet->setCellValue('AC' . $newRow, 'Number of students absent for 5 consecutive days');
            $sheet->mergeCells('AC' . $newRow . ':AH' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setItalic(true);
            
            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AM' . $newRow, $consecutiveAbsentCount);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $newRow = 56 + $offset;

            $newRow = 57 + $offset;
            $sheet->setCellValue('AC' . $newRow, 'Dropped out');
            $sheet->mergeCells('AC' . $newRow . ':AH' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            
            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);


            $sheet->setCellValue('AM' . $newRow, 0);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            

            $newRow = 58 + $offset;
            $sheet->setCellValue('B' . $newRow, '*Beginning of School Year cut-off report is every 1st Friday of the School Year');
            $sheet->mergeCells('B' . $newRow . ':Q' . ($newRow + 1));
            $sheet->getStyle('B' . $newRow . ':Q' . ($newRow + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B' . $newRow . ':Q' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8);

            $sheet->setCellValue('S' . $newRow, 'c. School-Related Factors');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow); 
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

            // $sheet->setCellValue('AE' . $newRow, 'NLS');
            // $sheet->mergeCells('AE' . $newRow . ':AF' . $newRow);
            // $sheet->setCellValue('AG' . $newRow, 0);
            // $sheet->mergeCells('AG' . $newRow . ':AS' . $newRow);

            $newRow = 59 + $offset;
            $sheet->setCellValue('S' . $newRow, "c.1. Teacher Factor\nc.2. Physical condition of classroom\nc.3. Peer influence");
            $sheet->mergeCells('S' . $newRow . ':AA' . ($newRow + 2)); 
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 2))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 2))->getFont()->setName('SansSerif')->setSize(8);

            $sheet->setCellValue('AC' . $newRow, 'Transferred out');
            $sheet->mergeCells('AC' . $newRow . ':AH' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            
            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);


            $sheet->setCellValue('AM' . $newRow, 0);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            
            
            $newRow = 60 + $offset;

            $newRow = 61 + $offset;
            $sheet->setCellValue('AC' . $newRow, 'Transferred in');
            $sheet->mergeCells('AC' . $newRow . ':AH' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AH' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            
            $sheet->setCellValue('AI' . $newRow, 0);
            $sheet->mergeCells('AI' . $newRow . ':AJ' . ($newRow + 1));
            $sheet->getStyle('AI' . $newRow . ':AJ' .($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AI' . $newRow . ':AJ' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);

            $sheet->setCellValue('AK' . $newRow, 0);
            $sheet->mergeCells('AK' . $newRow . ':AL' . ($newRow + 1));
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AK' . $newRow . ':AL' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);


            $sheet->setCellValue('AM' . $newRow, 0);
            $sheet->mergeCells('AM' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AM' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            
            $newRow = 62 + $offset;
            $sheet->setCellValue('S' . $newRow, 'd. Geographic/Environmental');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow); 
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

            $newRow = 63 + $offset;
            $sheet->setCellValue('S' . $newRow, "d.1. Distance between home and school\nd.2. Armed conflict (incl. Tribal wars & clanfeuds)\nd.3. Calamities/Disasters");
            $sheet->mergeCells('S' . $newRow . ':AA' . ($newRow + 3)); 
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 3))->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('S' . $newRow . ':AA' . ($newRow + 3))->getFont()->setName('SansSerif')->setSize(8);

            $newRow = 64 + $offset;
            $sheet->setCellValue('AC' . $newRow, 'I certify that this is a true and correct report.');
            $sheet->mergeCells('AC' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(11)->setItalic(true);
            

            $newRow = 65 + $offset;
            // $sheet->setCellValue('X' . $newRow, 'd. Geographic/Environmental');
            // $sheet->mergeCells('X' . $newRow . ':AD' . $newRow);
            // $sheet->setCellValue('AE' . $newRow, 'I certify that this is a true and correct report.');
            // $sheet->mergeCells('AE' . $newRow . ':AS' . $newRow);
            // $sheet->setCellValue('X' . ($newRow + 1), 'd.1. Distance between home and school');
            // $sheet->mergeCells('X' . ($newRow + 1) . ':AD' . ($newRow + 1));
            // $sheet->setCellValue('X' . ($newRow + 2), 'd.2. Armed conflict (incl. Tribal wars & clanfeuds)');
            // $sheet->mergeCells('X' . ($newRow + 2) . ':AD' . ($newRow + 2));
            // $sheet->setCellValue('X' . ($newRow + 3), 'd.3. Calamities/Disasters');
            // $sheet->mergeCells('X' . ($newRow + 3) . ':AD' . ($newRow + 3));
            // for ($i = 0; $i <= 3; $i++) {
            //     $sheet->getStyle('A' . ($newRow + $i) . ':AS' . ($newRow + $i))->applyFromArray($borderStyle);
            //     $sheet->getRowDimension($newRow + $i)->setRowHeight(15);
            // }

            $newRow = 67 + $offset;
            $sheet->setCellValue('S' . $newRow, 'e. Financial-Related');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow); 
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

            $newRow = 68 + $offset;
            $sheet->setCellValue('S' . $newRow, 'e.1. Child labor, work');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow); 
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8);


            $newRow = 69 + $offset;
            $sheet->setCellValue('S' . $newRow, 'f. Others (Specify)');
            $sheet->mergeCells('S' . $newRow . ':AA' . ($newRow + 1)); 
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(8)->setBold(true);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP);

            $sheet->setCellValue('AC' . $newRow, '(Signature of Adviser over Printed Name)');
            $sheet->mergeCells('AC' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('AC' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            $sheet->getStyle('AC' . $newRow . ':AN' . $newRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            // for ($i = 0; $i <= 1; $i++) {
            //     $sheet->getStyle('A' . ($newRow + $i) . ':AS' . ($newRow + $i))->applyFromArray($borderStyle);
            //     $sheet->getRowDimension($newRow + $i)->setRowHeight(15);
            // }

            $newRow = 71 + $offset;
            $sheet->setCellValue('AC' . $newRow, 'Attested by:');
            $sheet->mergeCells('AC' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('AC' . $newRow . ':AN' .($newRow + 1))->getFont()->setName('SansSerif')->setSize(11)->setItalic(true);
            

            $newRow = 74 + $offset;

            $newRow = 75 + $offset;
            $sheet->setCellValue('S' . $newRow, 'Generated thru ****');
            $sheet->mergeCells('S' . $newRow . ':AA' . $newRow); 
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getFont()->setName('SansSerif')->setSize(9);
            $sheet->getStyle('S' . $newRow . ':AA' . $newRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
 
            $sheet->setCellValue('AC' . $newRow, '(Signature of School Head over Printed Name)');
            $sheet->mergeCells('AC' . $newRow . ':AN' . ($newRow + 1));
            $sheet->getStyle('AC' . $newRow . ':AN' . ($newRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('AC' . $newRow . ':AN' . ($newRow + 1))->getFont()->setName('SansSerif')->setSize(9);
            $sheet->getStyle('AC' . $newRow . ':AN' . $newRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
 
            // Save
            $filename = "sf2-{$month}-" . date('Y-m-d_H-i-s') . '.xlsx';
            $exportDir = 'exports';
            if (!file_exists($exportDir)) {
                mkdir($exportDir, 0777, true);
                chmod($exportDir, 0777);
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save("$exportDir/$filename");
            chmod("$exportDir/$filename", 0644);
            echo json_encode(['success' => true, 'filename' => $filename]);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } else if ($format === 'pdf') {
            class MYPDF extends TCPDF {
                public function Header() {
                    global $title, $dateFrom, $dateTo;
                    $this->SetFont('helvetica', 'B', 16);
                    $this->Cell(0, 10, $title, 0, 1, 'L');
                    $this->SetFont('helvetica', '', 10);
                    $this->Cell(0, 10, "Date Range: $dateFrom to $dateTo", 0, 1, 'L');
                    $this->Ln(5);
                }
            }

            $pdf = new MYPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Student Attendance System');
            $pdf->SetTitle($title);
            $pdf->SetMargins(10, 30, 10);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 10);
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->AddPage();

            $tbl = '<table border="1" cellpadding="4" cellspacing="0">';
            $tbl .= '<tr style="background-color:#2563eb;color:#ffffff;">';
            foreach ($headers as $header) {
                $tbl .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $tbl .= '</tr>';

            foreach ($reportData as $row) {
                $tbl .= '<tr>';
                foreach ($headers as $header) {
                    $value = htmlspecialchars($row[$header] ?? '');
                    $bgColor = '#ffffff';
                    $textColor = '#000000';
                    if ($header === 'Status' && $reportType === 'student') {
                        if ($value === 'Present') {
                            $bgColor = '#dcfce7';
                            $textColor = '#166534';
                        } elseif ($value === 'Late') {
                            $bgColor = '#fef3c7';
                            $textColor = '#92400e';
                        } elseif ($value === 'Absent') {
                            $bgColor = '#fecaca';
                            $textColor = '#991b1b';
                        }
                    } elseif ($header === 'Status' && $reportType === 'perfect') {
                        if ($value === 'Recognized') {
                            $bgColor = '#dcfce7';
                            $textColor = '#166534';
                        } else {
                            $bgColor = '#fecaca';
                            $textColor = '#991b1b';
                        }
                    }
                    $tbl .= "<td style=\"background-color:$bgColor;color:$textColor;\">$value</td>";
                }
                $tbl .= '</tr>';
            }
            $tbl .= '</table>';

            $pdf->writeHTML($tbl, true, false, true, false, '');
            $filename = "{$reportType}-report-" . date('Y-m-d_H-i-s') . '.pdf';
            $exportDir = 'exports';
            if (!file_exists($exportDir)) {
                mkdir($exportDir, 0777, true);
                chmod($exportDir, 0777);
            }
            $pdf->Output(__DIR__ . "/$exportDir/$filename", 'F');
            chmod(__DIR__ . "/$exportDir/$filename", 0644);
            // $pdf->Output("$exportDir/$filename", 'F');
            // chmod("$exportDir/$filename", 0644);
            echo json_encode(['success' => true, 'filename' => $filename]);
       } elseif ($format === 'excel') {
            try {
                error_log("Processing Excel export for reportType: $reportType");
                error_log("POST data: " . $_POST['data']);
                error_log("Headers: " . json_encode($headers));
                error_log("Data rows: " . count($reportData));

                // Validate headers and data
                if (empty($headers) || empty($reportData)) {
                    throw new Exception("Empty headers or data received for Excel export.");
                }

                // Define expected headers based on report type
                $expectedHeaders = [
                    'student' => ['Class', 'LRN', 'Name', 'Status', 'Time Checked'],
                    'class' => ['Class', 'Total Students', 'Present', 'Absent', 'Late', 'Average Attendance'],
                    'perfect' => ['Class', 'LRN', 'Name', 'Status', 'Attendance Issue Summary', 'Adjusted Attendance Record']
                ];

                // Use predefined headers to avoid mismatches
                $headers = $expectedHeaders[$reportType] ?? $headers;
                error_log("Using headers: " . json_encode($headers));

                // Sanitize headers for internal use
                $sanitizedHeaders = array_map(function($header) {
                    return preg_replace('/[^a-zA-Z0-9_]/', '_', trim($header));
                }, $headers);
                error_log("Sanitized headers: " . json_encode($sanitizedHeaders));

                // Validate data keys against headers
                foreach ($reportData as $index => $row) {
                    foreach ($headers as $header) {
                        if (!array_key_exists($header, $row)) {
                            error_log("Missing key '$header' in row $index: " . json_encode($row));
                            $reportData[$index][$header] = ''; // Fill missing keys with empty string
                        }
                    }
                }

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle($title);

                // Write title and date range
                $sheet->setCellValue('A1', $title);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->setCellValue('A2', "Date Range: $dateFrom to $dateTo");
                $sheet->getStyle('A2')->getFont()->setSize(10);

                // Write headers
                $sheet->fromArray($headers, null, 'A4');
                error_log("Headers written to A4");

                // Write data rows
                $row = 5;
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];
                foreach ($reportData as $dataRow) {
                    $col = 'A';
                    foreach ($headers as $index => $header) {
                        $value = isset($dataRow[$header]) ? $dataRow[$header] : '';
                        error_log("Processing row $row, header '$header', value: '$value'");

                        if ($header === 'LRN') {
                            $sheet->setCellValueExplicit($col . $row, (string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('0');
                        } else {
                            $sheet->setCellValue($col . $row, $value);
                        }

                        // Apply status styling
                        if ($header === 'Status' && $reportType === 'student') {
                            if ($value === 'Present') {
                                $sheet->getStyle($col . $row)->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'DCFCE7']],
                                    'font' => ['color' => ['argb' => '166534']]
                                ]);
                            } elseif ($value === 'Late') {
                                $sheet->getStyle($col . $row)->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FEF3C7']],
                                    'font' => ['color' => ['argb' => '92400E']]
                                ]);
                            } elseif ($value === 'Absent') {
                                $sheet->getStyle($col . $row)->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FECACA']],
                                    'font' => ['color' => ['argb' => '991B1B']]
                                ]);
                            }
                        } elseif ($header === 'Status' && $reportType === 'perfect') {
                            if ($value === 'Recognized') {
                                $sheet->getStyle($col . $row)->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'DCFCE7']],
                                    'font' => ['color' => ['argb' => '166534']]
                                ]);
                            } else {
                                $sheet->getStyle($col . $row)->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FECACA']],
                                    'font' => ['color' => ['argb' => '991B1B']]
                                ]);
                            }
                        }

                        $sheet->getStyle($col . $row)->applyFromArray($borderStyle);
                        $col++;
                    }
                    $row++;
                }
                error_log("Data rows written up to row $row");

                // Apply header styling
                $headerStyle = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '2563EB']],
                    'font' => ['color' => ['argb' => 'FFFFFF'], 'bold' => true],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];
                $sheet->getStyle('A4:' . chr(65 + count($headers) - 1) . '4')->applyFromArray($headerStyle);

                // Auto-size columns
                foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Save the file
                $filename = "{$reportType}-report-" . date('Y-m-d_H-i-s') . '.xlsx';
                $exportDir = 'exports';
                if (!file_exists($exportDir)) {
                    if (!mkdir($exportDir, 0777, true)) {
                        throw new Exception("Failed to create directory: $exportDir");
                    }
                    chmod($exportDir, 0777);
                }
                $fullPath = "$exportDir/$filename";
                $writer = new Xlsx($spreadsheet);
                $writer->save($fullPath);
                if (!file_exists($fullPath)) {
                    throw new Exception("Failed to save Excel file: $fullPath");
                }
                chmod($fullPath, 0644);
                error_log("Excel file saved: $fullPath");

                echo json_encode(['success' => true, 'filename' => $filename]);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (Exception $e) {
                error_log("Excel export error: " . $e->getMessage() . " in " . $e->getFile() . " at line " . $e->getLine());
                echo json_encode(['success' => false, 'message' => 'Failed to generate Excel report: ' . $e->getMessage()]);
            }
        }
    } catch (Exception $e) {
        error_log("Report export error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to generate report: ' . $e->getMessage()]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Student Attendance System</title>
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

        .bg-purple { background: var(--primary-gradient); }
        .bg-pink { background: var(--secondary-gradient); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-green { background: linear-gradient(135deg, #10b981, #34d399); }
        .bg-red { background: linear-gradient(135deg, #ef4444, #f87171); }
        .bg-yellow { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

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

        .attendance-grid {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .table-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
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
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
        }

        tbody tr {
            transition: var(--transition-normal);
        }

        tbody tr:hover {
            background-color: var(--inputfieldhover-color);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: var(--font-size-sm);
            font-weight: 500;
        }

        .status-present {
            background-color: var(--status-present-bg);
            color: var(--success-color);
        }

        .status-absent {
            background-color: var(--status-absent-bg);
            color: var(--danger-color);
        }

        .status-late {
            background-color: var(--status-late-bg);
            color: var(--warning-color);
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
            .selector-input, .selector-select {
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <h1>Reports</h1>

    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Classes</div>
                    <div class="card-value"><?php echo htmlspecialchars($active_classes); ?></div>
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
                    <div class="card-value"><?php echo htmlspecialchars($total_students); ?></div>
                </div>
                <div class="card-icon bg-blue">
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
                    <div class="card-title">Late (<?php echo $todayFormatted; ?>)</div>
                    <div class="card-value"><?php echo htmlspecialchars($today_attendance['late']); ?></div>
                </div>
                <div class="card-icon bg-yellow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Absent (<?php echo $todayFormatted; ?>)</div>
                    <div class="card-value"><?php echo htmlspecialchars($today_attendance['absent']); ?></div>
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

    <div class="controls">
        <div class="controls-left">
            <select class="selector-select" id="class-filter">
                <?php if (!empty($classes_php)) : ?>
                    <option value="<?php echo $classes_php[0]['id']; ?>">
                        <?php echo "{$classes_php[0]['gradeLevel']} - {$classes_php[0]['sectionName']} ({$classes_php[0]['subject']})"; ?>
                    </option>
                    <?php foreach ($classes_php as $cls) : ?>
                        <?php if ($cls['id'] !== $classes_php[0]['id']) : ?>
                            <option value="<?php echo $cls['id']; ?>">
                                <?php echo "{$cls['gradeLevel']} - {$cls['sectionName']} ({$cls['subject']})"; ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <option value="">No Classes Available</option>
                <?php endif; ?>
            </select>
            <input type="text" class="selector-input" id="student-search" placeholder="Search student...">
            <select class="selector-select" id="student-filter">
                <option value="">All Students</option>
            </select>
            <input type="date" class="selector-input" id="date-from">
            <input type="date" class="selector-input" id="date-to">
            <select class="selector-select" id="report-type">
                <option value="">Select Report Type</option>
                <option value="student">Student Attendance History</option>
                <option value="class">Attendance per Class</option>
                <option value="perfect">Perfect Attendance Recognition</option>
                <option value="sf2">SF2 – DepEd Attendance Report</option>
            </select>
            <select class="selector-select" id="month-select">
                <option value="">Select Month</option>
                <option value="JANUARY">JANUARY</option>
                <option value="FEBRUARY">FEBRUARY</option>
                <option value="MARCH">MARCH</option>
                <option value="APRIL">APRIL</option>
                <option value="MAY">MAY</option>
                <option value="JUNE">JUNE</option>
                <option value="JULY">JULY</option>
                <option value="AUGUST">AUGUST</option>
                <option value="SEPTEMBER">SEPTEMBER</option>
                <option value="OCTOBER">OCTOBER</option>
                <option value="NOVEMBER">NOVEMBER</option>
                <option value="DECEMBER">DECEMBER</option>
            </select>
            <select class="selector-select" id="export-format">
                <option value="">Select Export Format</option>
                <option value="excel">Excel</option>
                <option value="pdf">PDF</option>
            </select>
            <button class="btn btn-primary" id="generate-report">
                <i class="fas fa-file-alt"></i> Generate Report
            </button>
        </div>
    </div>

    <div class="attendance-grid" id="report-results">
        <div class="table-header">
            <div class="table-title" id="report-title">Attendance Report</div>
            <button class="btn btn-primary" id="export-report">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>
        <div class="table-responsive">
            <table id="report-table">
                <thead id="report-thead">
                    <tr>
                        <th>Class</th>
                        <th>LRN</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Time Checked</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        const classes = <?php echo json_encode($classes_php); ?>;
        const attendanceData = <?php echo json_encode($attendance_php); ?>;

        const classFilter = document.getElementById('class-filter');
        classFilter.addEventListener('change', populateStudents);
        document.getElementById('student-search').addEventListener('input', filterStudents);
        document.getElementById('generate-report').addEventListener('click', generateReport);
        document.getElementById('export-report').addEventListener('click', exportReport);

        function populateStudents() {
            const classId = classFilter.value;
            const studentFilter = document.getElementById('student-filter');
            studentFilter.innerHTML = '<option value="">All Students</option>';
            if (!classId) return;
            const cls = classes.find(c => c.id == classId);
            cls.students.sort((a, b) => a.fullName.localeCompare(b.fullName)).forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.fullName;
                studentFilter.appendChild(option);
            });
            filterStudents();
        }

        function filterStudents() {
            const search = document.getElementById('student-search').value.toLowerCase();
            const studentFilter = document.getElementById('student-filter');
            const options = studentFilter.querySelectorAll('option:not([value=""])');
            options.forEach(opt => {
                const text = opt.textContent.toLowerCase();
                const stuId = opt.value;
                if (text.includes(search) || stuId.includes(search)) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        }

        function generateReport() {
            const reportType = document.getElementById('report-type').value;
            const classId = document.getElementById('class-filter').value;
            const studentId = document.getElementById('student-filter').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const month = document.getElementById('month-select').value;

            if (!reportType) {
                alert('Please select a report type');
                return;
            }

            const reportResults = document.getElementById('report-results');
            const reportTitle = document.getElementById('report-title');
            const reportThead = document.getElementById('report-thead');
            const reportTbody = document.getElementById('report-tbody');

            reportTbody.innerHTML = '';
            let title = reportType === 'student' ? 'Student Attendance History' :
                        reportType === 'class' ? 'Attendance per Class' : 
                        reportType === 'perfect' ? 'Perfect Attendance Recognition' :
                        'SF2 (DEPED)';
            reportTitle.textContent = title;

            if (reportType === 'sf2') {
                if (!month) {
                    alert('Please select a month for SF2 report');
                    return;
                }
                reportThead.innerHTML = '<tr><th>SF2 Report</th></tr>';
                reportTbody.innerHTML = '<tr><td>Please click Export Report to download the SF2 Excel file.</td></tr>';
            } else if (reportType === 'class') {
                reportThead.innerHTML = `
                    <tr>
                        <th>Class</th>
                        <th>Total Students</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Average Attendance</th>
                    </tr>
                `;

                let filteredClasses = classId ? classes.filter(cls => cls.id == classId) : classes;

                filteredClasses.forEach(cls => {
                    let filteredData = attendanceData.filter(record => record.classId == cls.id);
                    if (dateFrom) filteredData = filteredData.filter(record => record.date >= dateFrom);
                    if (dateTo) filteredData = filteredData.filter(record => record.date <= dateTo);

                    const totalStudents = cls.students.length;
                    const presentCount = filteredData.filter(record => record.status === 'Present').length;
                    const absentCount = filteredData.filter(record => record.status === 'Absent').length;
                    const lateCount = filteredData.filter(record => record.status === 'Late').length;
                    const totalRecords = presentCount + absentCount + lateCount;
                    const attendanceRate = totalRecords ? (presentCount / totalRecords * 100).toFixed(2) : 0;
                    const formattedClass = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${formattedClass}</td>
                        <td>${totalStudents}</td>
                        <td>${presentCount}</td>
                        <td>${absentCount}</td>
                        <td>${lateCount}</td>
                        <td>${attendanceRate}%</td>
                    `;
                    reportTbody.appendChild(row);
                });
            } else if (reportType === 'perfect') {
                reportThead.innerHTML = `
                    <tr>
                        <th>Class</th>
                        <th>LRN</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Attendance Issue Summary</th>
                        <th>Adjusted Attendance Record</th>
                    </tr>
                `;

                let filteredClasses = classId ? classes.filter(cls => cls.id == classId) : classes;
                filteredClasses.forEach(cls => {
                    let students = studentId ? cls.students.filter(s => s.id == studentId) : cls.students;
                    students.sort((a, b) => a.fullName.localeCompare(b.fullName)).forEach(student => {
                        let filteredData = attendanceData.filter(record => record.classId == cls.id && record.studentId == student.id);
                        if (dateFrom) filteredData = filteredData.filter(record => record.date >= dateFrom);
                        if (dateTo) filteredData = filteredData.filter(record => record.date <= dateTo);

                        const presentCount = filteredData.filter(record => record.status === 'Present').length;
                        const absentCount = filteredData.filter(record => record.status === 'Absent').length;
                        const lateCount = filteredData.filter(record => record.status === 'Late').length;
                        const status = (absentCount === 0 && lateCount === 0 && presentCount > 0) ? 'Recognized' : 'Not Recognized';
                        let reason = '';
                        if (status === 'Not Recognized') {
                            let reasons = [];
                            if (lateCount > 0) reasons.push(`${lateCount} Late`);
                            if (absentCount > 0) reasons.push(`${absentCount} Absent`);
                            reason = reasons.join(' and ');
                        }
                        let adjustedStr = '';
                        if (lateCount > 0 || absentCount > 0) {
                            const lateToAbsent = cls.late_to_absent || 0;
                            if (lateCount === 0) {
                                adjustedStr = `${absentCount} Absent`;
                            } else {
                                let additionalAbsents = lateToAbsent > 0 ? Math.floor(lateCount / lateToAbsent) : 0;
                                let remainingLates = lateToAbsent > 0 ? lateCount % lateToAbsent : lateCount;
                                let adjustedAbsents = absentCount + additionalAbsents;
                                let parts = [];
                                if (remainingLates > 0) parts.push(`${remainingLates} Late`);
                                if (adjustedAbsents > 0) parts.push(`${adjustedAbsents} Absent`);
                                adjustedStr = parts.join(' and ');
                            }
                        }

                        const formattedClass = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;
                        const statusClass = status === 'Recognized' ? 'status-present' : 'status-absent';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${formattedClass}</td>
                            <td>${student.id}</td>
                            <td>${student.fullName}</td>
                            <td><span class="status-badge ${statusClass}">${status}</span></td>
                            <td>${reason}</td>
                            <td>${adjustedStr}</td>
                        `;
                        reportTbody.appendChild(row);
                    });
                });
            } else {
                reportThead.innerHTML = `
                    <tr>
                        <th>Class</th>
                        <th>LRN</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Time Checked</th>
                    </tr>
                `;

                let filteredData = attendanceData;
                if (classId) filteredData = filteredData.filter(record => record.classId == classId);
                if (studentId) filteredData = filteredData.filter(record => record.studentId == studentId);
                if (dateFrom) filteredData = filteredData.filter(record => record.date >= dateFrom);
                if (dateTo) filteredData = filteredData.filter(record => record.date <= dateTo);

                filteredData.sort((a, b) => a.fullName.localeCompare(b.fullName)).forEach(record => {
                    const cls = classes.find(c => c.id == record.classId);
                    const formattedClass = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;
                    const statusClass = record.status === 'Present' ? 'status-present' :
                                       record.status === 'Late' ? 'status-late' : 'status-absent';
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${formattedClass}</td>
                        <td>${record.studentId}</td>
                        <td>${record.fullName}</td>
                        <td><span class="status-badge ${statusClass}">${record.status}</span></td>
                        <td>${record.timeChecked}</td>
                    `;
                    reportTbody.appendChild(row);
                });
            }

            reportResults.style.display = 'block';
            reportResults.scrollIntoView({ behavior: 'smooth' });
        }

        function exportReport() {
            const format = document.getElementById('export-format').value;
            const reportType = document.getElementById('report-type').value;
            const month = document.getElementById('month-select').value;
            const classId = document.getElementById('class-filter').value;

            if (!format) {
                alert('Please select an export format');
                return;
            }

            if (reportType === 'sf2' && !month) {
                alert('Please select a month for SF2 report');
                return;
            }

            const table = document.getElementById('report-table');
            const rows = table.querySelectorAll('tr');
            let data = [];
            let headers = [];

            // Define headers based on report type
            const headerMap = {
                'student': ['Class', 'LRN', 'Name', 'Status', 'Time Checked'],
                'class': ['Class', 'Total Students', 'Present', 'Absent', 'Late', 'Average Attendance'],
                'perfect': ['Class', 'LRN', 'Name', 'Status', 'Attendance Issue Summary', 'Adjusted Attendance Record'],
                'sf2': ['SF2 Report']
            };
            headers = headerMap[reportType] || [];

            // Extract row data
            for (let i = 1; i < rows.length; i++) {
                const row = {};
                const cells = rows[i].querySelectorAll('td');
                headers.forEach((header, index) => {
                    let text = cells[index] ? cells[index].textContent.trim() : '';
                    if (cells[index] && cells[index].querySelector('.status-badge')) {
                        text = cells[index].querySelector('.status-badge').textContent.trim();
                    }
                    row[header] = text;
                });
                data.push(row);
            }

            console.log('Headers:', headers);
            console.log('Data:', data);

            const exportBtn = document.getElementById('export-report');
            const originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            exportBtn.disabled = true;

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=exportReport&format=${encodeURIComponent(format)}&data=${encodeURIComponent(JSON.stringify({
                    data: data,
                    headers: headers,
                    reportType: reportType,
                    dateFrom: document.getElementById('date-from').value,
                    dateTo: document.getElementById('date-to').value,
                    title: document.getElementById('report-title').textContent,
                    classId: classId,
                    month: month
                }))}`
            })
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error(`HTTP error! Status: ${res.status}`);
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = `exports/${data.filename}`;
                    downloadLink.download = data.filename;
                    downloadLink.style.display = 'none';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    alert(`Report ${data.filename} exported successfully!`);
                } else {
                    alert(data.message || 'Failed to export report');
                }
            })
            .catch(error => {
                console.error('Export error:', error);
                alert('An error occurred while exporting the report. Please check the console for details.');
            })
            .finally(() => {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            });
        }

        document.getElementById('date-from').value = '<?php echo date('Y-m-d', strtotime('-1 month')); ?>';
        document.getElementById('date-to').value = '<?php echo date('Y-m-d'); ?>';
        populateStudents();
    </script>
</body>
</html>