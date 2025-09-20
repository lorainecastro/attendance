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

session_start();

$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
    exit();
}

$pdo = getDBConnection();

// Fetch total students
$total_students_stmt = $pdo->prepare("SELECT COUNT(DISTINCT cs.lrn) FROM class_students cs JOIN classes c ON cs.class_id = c.class_id WHERE c.teacher_id = :teacher_id");
$total_students_stmt->execute(['teacher_id' => $user['teacher_id']]);
$total_students = $total_students_stmt->fetchColumn();

// Fetch overall attendance (average)
$overall_att_stmt = $pdo->prepare("SELECT AVG(attendance_percentage) FROM classes WHERE teacher_id = :teacher_id");
$overall_att_stmt->execute(['teacher_id' => $user['teacher_id']]);
$overall_attendance = round($overall_att_stmt->fetchColumn());

// Fetch active classes count
$active_classes_stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = :teacher_id AND status = 'active'");
$active_classes_stmt->execute(['teacher_id' => $user['teacher_id']]);
$active_classes = $active_classes_stmt->fetchColumn();

// Fetch classes
$classes_stmt = $pdo->prepare("SELECT c.*, sub.subject_code, sub.subject_name FROM classes c JOIN subjects sub ON c.subject_id = sub.subject_id WHERE c.teacher_id = :teacher_id");
$classes_stmt->execute(['teacher_id' => $user['teacher_id']]);
$classes_db = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

$classes_php = [];
foreach ($classes_db as $cls) {
    $students_stmt = $pdo->prepare("SELECT s.lrn AS id, s.last_name AS lastName, s.first_name AS firstName, s.middle_name AS middleName, s.email FROM students s JOIN class_students cs ON s.lrn = cs.lrn WHERE cs.class_id = :class_id");
    $students_stmt->execute(['class_id' => $cls['class_id']]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedules_stmt = $pdo->prepare("SELECT * FROM schedules WHERE class_id = :class_id");
    $schedules_stmt->execute(['class_id' => $cls['class_id']]);
    $schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);
    $schedule = [];
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
        'students' => $students
    ];
}

// Fetch attendance data
$attendance_stmt = $pdo->prepare("SELECT at.*, at.lrn AS studentId, at.class_id AS classId, at.time_checked AS timeChecked FROM attendance_tracking at JOIN classes c ON at.class_id = c.class_id WHERE c.teacher_id = :teacher_id");
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
        'timeChecked' => $time_checked
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

    if ($reportType === 'student') {
        $headers = ['Class', 'LRN', 'Name', 'Status', 'Time Checked'];
    } elseif ($reportType === 'class') {
        $headers = ['Class', 'Total Students', 'Present', 'Absent', 'Late', 'Average Attendance'];
    } elseif ($reportType === 'perfect') {
        $headers = ['Class', 'LRN', 'Name', 'Status', 'Reason'];
    }

    try {
        if ($format === 'pdf') {
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
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($title);

            $sheet->setCellValue('A1', $title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->setCellValue('A2', "Date Range: $dateFrom to $dateTo");
            $sheet->getStyle('A2')->getFont()->setSize(10);

            $sheet->fromArray($headers, null, 'A4');
            $row = 5;
            foreach ($reportData as $dataRow) {
                $col = 'A';
                foreach ($headers as $header) {
                    $value = $dataRow[$header] ?? '';
                    if ($header === 'LRN') {
                        $sheet->setCellValue($col . $row, (int)$value);
                        $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('0');
                    } else {
                        $sheet->setCellValue($col . $row, $value);
                    }
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
                    $col++;
                }
                $row++;
            }

            $headerStyle = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '2563EB']],
                'font' => ['color' => ['argb' => 'FFFFFF'], 'bold' => true]
            ];
            $sheet->getStyle('A4:' . chr(65 + count($headers) - 1) . '4')->applyFromArray($headerStyle);

            foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = "{$reportType}-report-" . date('Y-m-d_H-i-s') . '.xlsx';
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

        html, body {
            height: 100%;
            margin: 0;
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
                    <div class="card-title">Total Students</div>
                    <div class="card-value"><?php echo $total_students; ?></div>
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
                    <div class="card-title">Overall Attendance</div>
                    <div class="card-value"><?php echo $overall_attendance . '%'; ?></div>
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
                    <div class="card-title">Active Classes</div>
                    <div class="card-value"><?php echo $active_classes; ?></div>
                </div>
                <div class="card-icon bg-purple">
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
                <option value="perfect">Perfect Attendance Recognition</option> <!-- NEW -->
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
            cls.students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.lastName}, ${student.firstName} ${student.middleName || ''}`.trim();
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
                        reportType === 'class' ? 'Attendance per Class' : 'Perfect Attendance Recognition';
            reportTitle.textContent = title;

            if (reportType === 'class') {
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
                        <th>Reason</th>
                    </tr>
                `;

                let filteredClasses = classId ? classes.filter(cls => cls.id == classId) : classes;
                filteredClasses.forEach(cls => {
                    let students = studentId ? cls.students.filter(s => s.id == studentId) : cls.students;
                    students.forEach(student => {
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

                        const formattedClass = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;
                        const name = `${student.lastName}, ${student.firstName} ${student.middleName || ''}`.trim();
                        const statusClass = status === 'Recognized' ? 'status-present' : 'status-absent';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${formattedClass}</td>
                            <td>${student.id}</td>
                            <td>${name}</td>
                            <td><span class="status-badge ${statusClass}">${status}</span></td>
                            <td>${reason}</td>
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

                filteredData.forEach(record => {
                    const cls = classes.find(c => c.id == record.classId);
                    const student = cls.students.find(s => s.id == record.studentId);
                    if (!student) return;
                    const formattedClass = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;
                    const name = `${student.lastName}, ${student.firstName} ${student.middleName || ''}`.trim();
                    const statusClass = record.status === 'Present' ? 'status-present' :
                                       record.status === 'Late' ? 'status-late' : 'status-absent';
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${formattedClass}</td>
                        <td>${record.studentId}</td>
                        <td>${name}</td>
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

            if (!format) {
                alert('Please select an export format');
                return;
            }

            const table = document.getElementById('report-table');
            const rows = table.querySelectorAll('tr');
            let data = [];
            let headers = [];

            rows[0].querySelectorAll('th').forEach(th => {
                headers.push(th.textContent.trim());
            });

            for (let i = 1; i < rows.length; i++) {
                const row = {};
                rows[i].querySelectorAll('td').forEach((td, index) => {
                    const text = td.textContent.trim();
                    row[headers[index]] = text;
                });
                data.push(row);
            }

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
                    title: document.getElementById('report-title').textContent
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