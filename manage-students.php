<?php
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Disable displaying errors to the output
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1'); // Enable error logging
ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // Specify error log file
ob_start();

require 'config.php';
require 'vendor/autoload.php'; // Add this for Endroid
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php'; // Add this for TCPDF

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

session_start();

// Validate session
$user = validateSession();
if (!$user) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lrn'])) {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Session expired, please log in again']);
        exit();
    }
    destroySession();
    header("Location: index.php");
    exit();
}

// Handle GET for lrn fetch
if (isset($_GET['lrn'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    $pdo = getDBConnection();
    $lrn = $_GET['lrn'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE lrn = ?");
    $stmt->execute([$lrn]);
    $student = $stmt->fetch();
    if ($student) {
        echo json_encode(['success' => true, 'student' => $student]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Inside the single student deletion handler
if (isset($_GET['delete_lrn']) && isset($_GET['class_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    $pdo = getDBConnection();
    $lrn = $_GET['delete_lrn'];
    $class_id = $_GET['class_id'];
    try {
        // Fetch student photo to delete it
        $stmt = $pdo->prepare("SELECT photo FROM students WHERE lrn = ?");
        $stmt->execute([$lrn]);
        $student = $stmt->fetch();
        if ($student && $student['photo'] && $student['photo'] !== 'no-icon.png') {
            $photo_path = 'uploads/' . $student['photo'];
            if (file_exists($photo_path)) {
                if (!unlink($photo_path)) {
                    error_log("Failed to delete photo: $photo_path");
                }
            }
        }
        // Note: QR code in qrcodes/ is intentionally not deleted to preserve it
        // Delete from class_students
        $stmt = $pdo->prepare("DELETE FROM class_students WHERE lrn = ? AND class_id = ?");
        $stmt->execute([$lrn, $class_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Remove from class error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle bulk removal from class
if (isset($_POST['bulk_delete']) && isset($_POST['lrns']) && isset($_POST['class_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    $pdo = getDBConnection();
    $lrns = json_decode($_POST['lrns'], true);
    $class_id = $_POST['class_id'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM class_students WHERE lrn = ? AND class_id = ?");
        foreach ($lrns as $lrn) {
            $stmt->execute([$lrn, $class_id]);
        }
        // Note: QR codes in qrcodes/ are intentionally not deleted to preserve them
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Bulk remove from class error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle generate QR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'generateQR') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $lrn = $_POST['lrn'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';

    if (empty($lrn) || empty($last_name) || empty($first_name)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields for QR generation']);
        exit();
    }

    $content = "$lrn, $last_name, $first_name" . ($middle_name ? " $middle_name" : '');

    try {
        $qrCode = new QrCode($content);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $dir = 'qrcodes';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        chmod($dir, 0777); // Ensure directory permissions
        $filename = $lrn . '.png';
        $savePath = $dir . '/' . $filename;
        $result->saveToFile($savePath);
        chmod($savePath, 0644); // Set file permissions to readable
        echo json_encode(['success' => true, 'filename' => $filename]);
    } catch (Exception $e) {
        error_log("QR Code generation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to generate QR code: ' . $e->getMessage()]);
    }
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'bulkPrintQR') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $students_data = json_decode($_POST['students'], true);

    if (empty($students_data) || !is_array($students_data)) {
        echo json_encode(['success' => false, 'message' => 'No students selected for printing']);
        exit();
    }

    try {
        $pdo = getDBConnection();
        $students = [];
        foreach ($students_data as $data) {
            $lrn = $data['lrn'] ?? '';
            $qr_code = $data['qr_code'] ?? $lrn . '.png';
            $stmt = $pdo->prepare("SELECT lrn, first_name, middle_name, last_name FROM students WHERE lrn = ?");
            $stmt->execute([$lrn]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($student) {
                $qr_path = 'qrcodes/' . $qr_code;
                if (file_exists($qr_path)) {
                    $student['qr_code'] = $qr_code;
                    $students[] = $student;
                }
            }
        }

        if (empty($students)) {
            echo json_encode(['success' => false, 'message' => 'No valid QR codes found for selected students']);
            exit();
        }

        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your App');
        $pdf->SetTitle('Student QR ID Cards');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        $card_width = 53.975; // 2.125 inches in mm (215.9mm / 4 = 53.975mm)
        $card_height = 85.725; // 3.375 inches in mm (257.175mm / 3 ≈ 85.725mm)
        $cards_per_row = 4;
        $cards_per_col = 3;
        $card_count = 0;

        $x_start = 0;
        $y_start = 0;
        $x_spacing = 0; // No gap between cards
        $y_spacing = 0; // No gap between cards

        $pdf->AddPage();
        foreach ($students as $index => $student) {
            if ($index % ($cards_per_row * $cards_per_col) === 0 && $index !== 0) {
                $pdf->AddPage();
            }

            $row = floor(($index % ($cards_per_row * $cards_per_col)) / $cards_per_row);
            $col = $index % $cards_per_row;

            $x = $x_start + $col * ($card_width + $x_spacing);
            $y = $y_start + $row * ($card_height + $y_spacing);

            // Card Background (Gradient)
            $pdf->LinearGradient(
                $x,
                $y,
                $card_width,
                $card_height,
                array(248, 249, 250), // #f8f9fa
                array(233, 236, 239), // #e9ecef
                array(0, 0, $card_width, $card_height)
            );

            // Outer Border
            $pdf->SetLineStyle(array('width' => 0.5, 'color' => array(0, 0, 0)));
            $pdf->Rect($x + 0.5, $y + 0.5, $card_width - 1, $card_height - 1, 'D');

            // Inner Shadow Effect
            $pdf->SetLineStyle(array('width' => 0.2, 'color' => array(189, 195, 199))); // #bdc3c7
            $pdf->Rect($x + 1, $y + 1, $card_width - 2, $card_height - 2, 'D');

            // Header Section
            $header_height = 10;
            $pdf->LinearGradient(
                $x + 0.5,
                $y + 0.5,
                $card_width - 1,
                $header_height,
                array(52, 152, 219), // #3498db
                array(41, 128, 185), // #2980b9
                array(0, 0, 0, $header_height)
            );
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($x, $y + 2.5);
            $pdf->Cell($card_width, 6, 'SAMS', 0, 1, 'C');

            // QR Code
            $qr_padding = 2;
            $qr_size = $card_width - 5 - ($qr_padding * 2); // Adjusted for padding
            $qr_x = $x + 2.5 + $qr_padding;
            $qr_y = $y + $header_height + 2.5;

            // QR Code Shadow
            $pdf->SetFillColor(0, 0, 0, 10); // 10% black for shadow
            $pdf->Rect($qr_x - 1, $qr_y - 1, $qr_size + 2, $qr_size + 2, 'F');

            // QR Code White Background
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($qr_x, $qr_y, $qr_size, $qr_size, 'F');
            $pdf->Image('qrcodes/' . $student['qr_code'], $qr_x, $qr_y, $qr_size, $qr_size, 'PNG');

            // Decorative Line
            $line_y = $qr_y + $qr_size + 2.5;
            $pdf->LinearGradient(
                $x + 2.5,
                $line_y,
                $card_width - 5,
                $line_y,
                array(255, 255, 255), // Transparent
                array(52, 152, 219), // #3498db
                array(0, 0, $card_width - 5, 0)
            );
            $pdf->SetLineStyle(array('width' => 0.3, 'color' => array(52, 152, 219)));
            $pdf->Line($x + 2.5, $line_y, $x + $card_width - 2.5, $line_y);

            // LRN
            $lrn_y = $line_y + 2.5;
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($x, $lrn_y);
            $pdf->Cell($card_width, 6, 'LRN: ' . $student['lrn'], 0, 1, 'C');

            // Full Name
            $full_name = $student['last_name'] . ', ' . $student['first_name'] . ' ' . ($student['middle_name'] ?: '');
            $name_length = strlen($full_name);
            $font_size = $name_length > 30 ? 8 : ($name_length > 25 ? 9 : ($name_length > 20 ? 10 : 11));
            $pdf->SetFont('helvetica', 'B', $font_size);

            // Handle long names by splitting into lines
            $words = explode(' ', $full_name);
            $max_width = $card_width - 5; // Adjusted for padding
            $line = '';
            $lines = [];
            foreach ($words as $word) {
                $test_line = $line . $word . ' ';
                $text_width = $pdf->GetStringWidth($test_line, 'helvetica', 'B', $font_size);
                if ($text_width > $max_width && $line !== '') {
                    $lines[] = trim($line);
                    $line = $word . ' ';
                } else {
                    $line = $test_line;
                }
            }
            $lines[] = trim($line);

            // Draw Full Name
            $name_y = $lrn_y + 5;
            foreach ($lines as $index => $line) {
                $pdf->SetXY($x, $name_y + ($index * ($font_size / 2)));
                $pdf->Cell($card_width, $font_size / 2, $line, 0, 1, 'C');
            }

            // Footer Decoration
            $footer_y = $y + $card_height - 5;
            $pdf->SetLineStyle(array('width' => 0.2, 'color' => array(189, 195, 199))); // #bdc3c7
            $pdf->Line($x + 2.5, $footer_y, $x + $card_width - 2.5, $footer_y);

            // Decorative Dots
            $pdf->SetFillColor(52, 152, 219); // #3498db
            for ($i = 0; $i < 5; $i++) {
                $dot_x = $x + ($card_width / 6) * ($i + 1);
                $pdf->Circle($dot_x, $footer_y + 1.5, 0.5, 0, 360, 'F');
            }

            $card_count++;
        }

        // Ensure export directory exists
        $export_dir = 'exports';
        if (!file_exists($export_dir)) {
            mkdir($export_dir, 0777, true);
            chmod($export_dir, 0777);
        }

        $filename = 'qr_id_cards_' . date('YmdHis') . '.pdf';
        $pdf->Output(__DIR__ . '/exports/' . $filename, 'F');
        chmod(__DIR__ . '/exports/' . $filename, 0644);

        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'cards_count' => $card_count,
            'message' => 'PDF generated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Bulk QR Print error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate QR ID Cards PDF: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle Excel export
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'exportExcel') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $lrns = json_decode($_POST['lrns'], true);

    if (empty($lrns) || !is_array($lrns)) {
        echo json_encode(['success' => false, 'message' => 'No students selected for export']);
        exit();
    }

    try {
        $pdo = getDBConnection();
        $teacher_id = $user['teacher_id'];

        // Prepare the query to fetch selected students
        $placeholders = str_repeat('?,', count($lrns) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.*, c.grade_level, sub.subject_name as class_name, 
                   c.section_name as section, s.date_added
            FROM class_students cs
            JOIN classes c ON cs.class_id = c.class_id
            JOIN subjects sub ON c.subject_id = sub.subject_id
            JOIN students s ON cs.lrn = s.lrn
            WHERE c.teacher_id = ? AND s.lrn IN ($placeholders)
            ORDER BY s.last_name, s.first_name
        ");

        $params = array_merge([$teacher_id], $lrns);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            echo json_encode(['success' => false, 'message' => 'No students found for export']);
            exit();
        }

        // Create new Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students Export');

        // Set headers
        $headers = [
            'A1' => 'LRN',
            'B1' => 'Last Name',
            'C1' => 'First Name',
            'D1' => 'Middle Name',
            'E1' => 'Email',
            'F1' => 'Gender',
            'G1' => 'Date of Birth',
            'H1' => 'Grade Level',
            'I1' => 'Address',
            'J1' => 'Parent Name',
            'K1' => 'Parent Email', // Add this line
            'L1' => 'Emergency Contact',
            'M1' => 'Photo',
            'N1' => 'QR Code',
            'O1' => 'Date Added' // Update this from N1 to O1
        ];

        // Apply headers with styling
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style the header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15); // LRN
        $sheet->getColumnDimension('B')->setWidth(20); // Last Name
        $sheet->getColumnDimension('C')->setWidth(20); // First Name
        $sheet->getColumnDimension('D')->setWidth(20); // Middle Name
        $sheet->getColumnDimension('E')->setWidth(25); // Email
        $sheet->getColumnDimension('F')->setWidth(10); // Gender
        $sheet->getColumnDimension('G')->setWidth(15); // DOB
        $sheet->getColumnDimension('H')->setWidth(12); // Grade Level
        $sheet->getColumnDimension('I')->setWidth(30); // Address
        $sheet->getColumnDimension('J')->setWidth(25); // Parent Name
        $sheet->getColumnDimension('K')->setWidth(25); // Parent Email
        $sheet->getColumnDimension('L')->setWidth(20); // Emergency Contact (moved from K)
        $sheet->getColumnDimension('M')->setWidth(15); // Photo (moved from L)
        $sheet->getColumnDimension('N')->setWidth(15); // QR Code (moved from M)
        $sheet->getColumnDimension('O')->setWidth(15); // Date Added (moved from N)
        $sheet->getRowDimension(1)->setRowHeight(20);
        // Fill data
        $row = 2;
        foreach ($students as $student) {
            // $sheet->setCellValue('A' . $row, $student['lrn']);
            $sheet->setCellValue('A' . $row, (int)$student['lrn']);
            $sheet->getStyle('A' . $row)->getNumberFormat()->setFormatCode('0');
            $sheet->setCellValue('B' . $row, $student['last_name']);
            $sheet->setCellValue('C' . $row, $student['first_name']);
            $sheet->setCellValue('D' . $row, $student['middle_name']);
            $sheet->setCellValue('E' . $row, $student['email'] ?: 'N/A');
            $sheet->setCellValue('F' . $row, $student['gender'] ?: 'N/A');
            $sheet->setCellValue('G' . $row, $student['dob'] ? date('Y-m-d', strtotime($student['dob'])) : 'N/A');
            $sheet->setCellValue('H' . $row, $student['grade_level']);
            $sheet->setCellValue('I' . $row, $student['address'] ?: 'N/A');
            $sheet->setCellValue('J' . $row, $student['parent_name'] ?: 'N/A');
            $sheet->setCellValue('K' . $row, $student['parent_email'] ?: 'N/A'); // Add this line
            $sheet->setCellValue('L' . $row, $student['emergency_contact'] ?: 'N/A'); // Update from K to L
            $sheet->setCellValue('M' . $row, $student['photo'] ?: 'no-icon.png'); // Update from L to M
            $sheet->setCellValue('N' . $row, $student['qr_code'] ?: 'No QR Code'); // Update from M to N
            $sheet->setCellValue('O' . $row, $student['date_added'] ? date('Y-m-d', strtotime($student['date_added'])) : 'N/A'); // Update from N to O
            $row++;
        }

        // Apply borders to all data
        $dataRange = 'A1:O' . ($row - 1); // Update from N to O
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle($dataRange)->applyFromArray($dataStyle);

        // Alternate row colors
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':O' . $i)->applyFromArray([ // Update from N to O
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
            }
        }

        // Create filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "students_export_{$timestamp}.xlsx";
        $exportDir = 'exports';

        // Create exports directory if it doesn't exist
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0777, true);
            chmod($exportDir, 0777);
        }

        $filepath = $exportDir . '/' . $filename;

        // Save the file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);
        chmod($filepath, 0644);

        // Clean up memory
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'message' => 'Excel file generated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Excel export error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate Excel file: ' . $e->getMessage()
        ]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'checkQR') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $lrn = $_POST['lrn'] ?? '';
    if (empty($lrn)) {
        echo json_encode(['exists' => false]);
        exit();
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT qr_code FROM students WHERE lrn = ?");
        $stmt->execute([$lrn]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $qr_code = $student['qr_code'] ?: $lrn . '.png';
        $qr_path = 'qrcodes/' . $qr_code;

        echo json_encode(['exists' => file_exists($qr_path)]);
    } catch (Exception $e) {
        error_log("Check QR error: " . $e->getMessage());
        echo json_encode(['exists' => false]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'printSingleQR') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $lrn = $_POST['lrn'] ?? '';
    $qr_code = $_POST['qr_code'] ?? $lrn . '.png';
    if (empty($lrn)) {
        echo json_encode(['success' => false, 'message' => 'No LRN provided']);
        exit();
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT lrn, first_name, middle_name, last_name FROM students WHERE lrn = ?");
        $stmt->execute([$lrn]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            exit();
        }

        $qr_path = 'qrcodes/' . $qr_code;
        if (!file_exists($qr_path)) {
            echo json_encode(['success' => false, 'message' => 'QR code file not found']);
            exit();
        }

        require_once 'tcpdf/tcpdf.php';
        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your App');
        $pdf->SetTitle('Student QR ID Card');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(false);

        $pdf->AddPage();
        $card_width = 54; // 2.125 inches in mm
        $card_height = 86; // 3.375 inches in mm
        $x = ($pdf->getPageWidth() - $card_width) / 2;
        $y = 20;

        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell($card_width, 6, $student['last_name'] . ', ' . $student['first_name'] . ' ' . ($student['middle_name'] ?: ''), 0, 1, 'C');
        $pdf->SetXY($x, $y + 6);
        $pdf->Cell($card_width, 6, 'LRN: ' . $student['lrn'], 0, 1, 'C');
        $pdf->Image($qr_path, $x + ($card_width - 40) / 2, $y + 12, 40, 40, 'PNG');

        $filename = 'qr_id_card_' . $lrn . '_' . date('YmdHis') . '.pdf';
        $pdf->Output(__DIR__ . '/exports/' . $filename, 'F');

        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'message' => 'Single QR ID Card generated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Single QR Print error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate QR ID Card: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle save POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lrn']) && !isset($_POST['bulk_delete'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    $pdo = getDBConnection();
    $lrn = $_POST['lrn'];
    $last_name = $_POST['last_name'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $email = $_POST['email'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $address = $_POST['address'] ?? null;
    $parent_name = $_POST['parent_name'] ?? null;
    $parent_email = $_POST['parent_email'] ?? null;
    $emergency_contact = $_POST['emergency_contact'] ?? null;
    $grade_level = $_POST['grade_level'] ?? null;
    $class = $_POST['class'] ?? null; // subject_name
    $section = $_POST['section'] ?? null;
    $qr_code = $_POST['qr_code'] ?? null;

    // Validate required fields
    if (empty($lrn) || empty($last_name) || empty($first_name) || empty($middle_name) || empty($grade_level) || empty($class) || empty($section)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    // Validate LRN is numeric
    if (!preg_match('/^\d+$/', $lrn)) {
        echo json_encode(['success' => false, 'message' => 'LRN or Student Number must contain only numbers']);
        exit();
    }

    // Handle photo upload
    $photo = 'no-icon.png';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = $lrn . '_photo.' . $ext;
        $dir = 'uploads';
        // Create Uploads directory if it doesn't exist
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        chmod($dir, 0777); // Ensure directory permissions
        $path = $dir . '/' . $photo;
        // Read and write the file content instead of moving
        $fileContent = file_get_contents($_FILES['photo']['tmp_name']);
        if (file_put_contents($path, $fileContent) === false) {
            error_log("Failed to save photo to $path");
            echo json_encode(['success' => false, 'message' => 'Failed to save photo']);
            exit();
        }
        chmod($path, 0644); // Set file permissions to readable
    }

    try {
        $pdo->beginTransaction();

        // Check if LRN exists in students table
        $stmt = $pdo->prepare("SELECT * FROM students WHERE lrn = ?");
        $stmt->execute([$lrn]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing student
            if ($photo === 'no-icon.png') {
                $photo = $existing['photo'];
            }
            if (empty($qr_code)) {
                $qr_code = $existing['qr_code'];
            }
            $stmt = $pdo->prepare("
                UPDATE students SET 
                    last_name = ?, first_name = ?, middle_name = ?, email = ?, 
                    gender = ?, dob = ?, grade_level = ?, address = ?, 
                    parent_name = ?, parent_email = ?, emergency_contact = ?, photo = ?,
                    qr_code = ?
                WHERE lrn = ?
            ");
            $stmt->execute([
                $last_name, $first_name, $middle_name, $email,
                $gender, $dob, $grade_level, $address,
                $parent_name, $parent_email, $emergency_contact, $photo,
                $qr_code, $lrn
            ]);

            // Get current class_id for the student
            $stmt = $pdo->prepare("SELECT class_id FROM class_students WHERE lrn = ?");
            $stmt->execute([$lrn]);
            $current_class = $stmt->fetch();

            // Get new class_id based on grade_level, subject_name, section, and teacher_id
            $teacher_id = $user['teacher_id'];
            $stmt = $pdo->prepare("
                SELECT c.class_id 
                FROM classes c 
                JOIN subjects sub ON c.subject_id = sub.subject_id 
                WHERE c.grade_level = ? AND sub.subject_name = ? 
                AND c.section_name = ? AND c.teacher_id = ? AND c.isArchived = 0
            ");
            $stmt->execute([$grade_level, $class, $section, $teacher_id]);
            $new_class = $stmt->fetch();

            if ($new_class) {
                $new_class_id = $new_class['class_id'];
                if ($current_class) {
                    // Update class_id in class_students if it has changed
                    if ($current_class['class_id'] != $new_class_id) {
                        $stmt = $pdo->prepare("
                            UPDATE class_students 
                            SET class_id = ?, is_enrolled = 1, created_at = NOW()
                            WHERE lrn = ?
                        ");
                        $stmt->execute([$new_class_id, $lrn]);
                    }
                } else {
                    // Insert new enrollment if no current class
                    $stmt = $pdo->prepare("
                        INSERT INTO class_students (class_id, lrn, is_enrolled, created_at) 
                        VALUES (?, ?, 1, NOW())
                    ");
                    $stmt->execute([$new_class_id, $lrn]);
                }
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Class not found']);
                exit();
            }
        } else {
            // Insert new student
            $stmt = $pdo->prepare("
                INSERT INTO students (
                    lrn, last_name, first_name, middle_name, email, gender, 
                    dob, grade_level, address, parent_name, parent_email, emergency_contact, 
                    photo, qr_code, date_added
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->execute([
                $lrn, $last_name, $first_name, $middle_name, $email, $gender,
                $dob, $grade_level, $address, $parent_name, $parent_email, $emergency_contact,
                $photo, $qr_code
            ]);

            // Get new class_id
            $teacher_id = $user['teacher_id'];
            $stmt = $pdo->prepare("
                SELECT c.class_id 
                FROM classes c 
                JOIN subjects sub ON c.subject_id = sub.subject_id 
                WHERE c.grade_level = ? AND sub.subject_name = ? 
                AND c.section_name = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$grade_level, $class, $section, $teacher_id]);
            $new_class = $stmt->fetch();

            if ($new_class) {
                $class_id = $new_class['class_id'];
                $stmt = $pdo->prepare("
                    INSERT INTO class_students (class_id, lrn, is_enrolled, created_at) 
                    VALUES (?, ?, 1, NOW())
                ");
                $stmt->execute([$class_id, $lrn]);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Class not found']);
                exit();
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Student saved successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch data for JS
$teacher_id = $user['teacher_id'];
$pdo = getDBConnection();

// Fetch students
$stmt = $pdo->prepare("
    SELECT DISTINCT s.*, c.grade_level AS gradeLevel, sub.subject_name AS `class`, 
        c.section_name AS section, c.class_id
    FROM class_students cs
    JOIN classes c ON cs.class_id = c.class_id
    JOIN subjects sub ON c.subject_id = sub.subject_id
    JOIN students s ON cs.lrn = s.lrn
    WHERE c.teacher_id = ? AND c.isArchived = 0
");
$stmt->execute([$teacher_id]);
$students_data = $stmt->fetchAll();
foreach ($students_data as &$row) {
    $row['fullName'] = $row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name'];
}

// Fetch classes data for dynamic dropdowns
$stmt = $pdo->prepare("
    SELECT c.class_id, c.grade_level, sub.subject_name, c.section_name 
    FROM classes c 
    JOIN subjects sub ON c.subject_id = sub.subject_id 
    WHERE c.teacher_id = ? AND c.isArchived = 0
");
$stmt->execute([$teacher_id]);
$classes_data = $stmt->fetchAll();

// Fetch filters data
$stmt = $pdo->prepare("SELECT DISTINCT c.grade_level FROM classes c WHERE c.teacher_id = ? AND c.isArchived = 0");
$stmt->execute([$teacher_id]);
$gradeLevels = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT DISTINCT sub.subject_name 
    FROM subjects sub 
    JOIN classes c ON sub.subject_id = c.subject_id 
    WHERE c.teacher_id = ? AND c.isArchived = 0
");
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT DISTINCT c.section_name FROM classes c WHERE c.teacher_id = ? AND c.isArchived = 0");
$stmt->execute([$teacher_id]);
$sections = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
    <title>Student Management - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- <link rel="stylesheet" href="styles.css"> Your separate CSS file -->
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

        .controls-right {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            align-items: center;
            flex-direction: column;
        }

        .controls-right .btn.btn-primary,
        .controls-right .view-btn {
            height: 36px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .controls-right .view-btn {
            width: 38px;
            padding: 0;
        }

        .controls-right .view-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .controls-right .btn.btn-primary {
            order: 1;
        }

        .controls-right .view-toggle {
            order: 2;
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

        .filter-select {
            min-width: 180px;
            padding: var(--spacing-xs) var(--spacing-sm);
            width: 180px;
            height: 38px;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            padding: var(--spacing-xs) var(--spacing-sm);
        }

        .filter-select:focus {
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

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: var(--whitefont-color);
        }

        .btn-danger:hover {
            background: var(--danger-red);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.75rem;
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
            background: transparent;
            border: none;
            cursor: pointer;
            transition: var(--transition-normal);
            color: var(--grayfont-color);
            font-size: 0.875rem;
        }

        .view-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .view-btn.active {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-lg);
        }

        .student-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .student-header h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .student-info {
            margin-bottom: var(--spacing-md);
        }

        .student-info p {
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .student-info i {
            width: 16px;
            color: var(--primary-blue);
        }

        .student-actions {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
            align-items: center;
        }

        .student-actions .btn {
            white-space: nowrap;
            min-width: auto;
        }

        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            overflow-x: auto;
            border: 1px solid var(--border-color);
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            scrollbar-width: thin; /* Firefox scrollbar */
            scrollbar-color: var(--border-color, #e0e0e0) transparent; /* Firefox scrollbar styling */
        }

        .table-container::-webkit-scrollbar {
            height: 8px; /* Scrollbar height for WebKit browsers (Chrome, Safari) */
        }

        .table-container::-webkit-scrollbar-thumb {
            background: var(--border-color, #e0e0e0);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .table {
            width: 100%;
            min-width: 900px; /* Ensure table is wide enough for all columns */
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto; /* Allow columns to size based on content */
        }

        .table th,
        .table td {
            padding: var(--spacing-sm, 8px) var(--spacing-md, 12px);
            text-align: left;
            border-bottom: 1px solid var(--border-color, #e0e0e0);
            white-space: nowrap; /* Prevent text wrapping */
            vertical-align: middle;
        }

        .table th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table tr:hover {
            background: var(--inputfieldhover-color);
        }

        .table td .actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .table td .actions .btn {
            white-space: nowrap;
            padding: var(--spacing-xs, 4px) var(--spacing-sm, 8px);
        }

        /* Explicitly ensure all columns are visible */
        .table th,
        .table td {
            display: table-cell !important; /* Override any display: none */
        }

        /* Optional: Minimum column widths */
        .table th:nth-child(1), .table td:nth-child(1) { /* Checkbox */
            min-width: 50px;
        }
        .table th:nth-child(2), .table td:nth-child(2) { /* Photo */
            min-width: 80px;
        }
        .table th:nth-child(3), .table td:nth-child(3) { /* QR Code */
            min-width: 80px;
        }
        .table th:nth-child(4), .table td:nth-child(4) { /* LRN */
            min-width: 120px;
        }
        .table th:nth-child(5), .table td:nth-child(5) { /* Full Name */
            min-width: 200px;
        }
        .table th:nth-child(6), .table td:nth-child(6) { /* Grade Level */
            min-width: 100px;
        }
        .table th:nth-child(7), .table td:nth-child(7) { /* Subject */
            min-width: 120px;
        }
        .table th:nth-child(8), .table td:nth-child(8) { /* Section */
            min-width: 100px;
        }
        .table th:nth-child(9), .table td:nth-child(9) { /* Actions */
            min-width: 150px;
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
            max-width: 1000px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .modal-header {
            padding: var(--spacing-xl) var(--spacing-2xl);
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
            padding: var(--spacing-sm);
            background: none;
            border: none;
            font-size: 1.75rem;
            cursor: pointer;
            color: var(--whitefont-color);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-form {
            padding: var(--spacing-2xl);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl);
            background: linear-gradient(180deg, #f9fafb, #ffffff);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--blackfont-color);
            font-size: var(--font-size-base);
        }

        .form-input,
        .form-select {
            padding: var(--spacing-md) var(--spacing-lg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
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

        .photo-upload input[type="file"] {
            display: none;
        }

        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            object-fit: cover;
            border: 1px solid var(--border-color);
        }

        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: var(--spacing-sm);
        }

        .qr-code {
            width: 100px;
            height: 100px;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--border-color);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination-btn {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--inputfield-color);
            cursor: pointer;
            transition: var(--transition-normal);
            font-size: var(--font-size-sm);
            min-width: 60px;
        }

        .pagination-btn:hover:not(.active) {
            background: var(--inputfieldhover-color);
        }

        .pagination-btn.active {
            background: var(--primary-blue);
            color: var(--whitefont-color);
            border-color: var(--primary-blue);
        }

        .pagination-btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .bulk-actions {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            border: 1px solid var(--border-color);
        }

        .selected-count {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
        }

        .hidden {
            display: none;
        }

        #selectAll,
        #tableSelectAll {
            width: 10px;
            height: 10px;
            transform: scale(1.5);
            vertical-align: middle;
        }

        .row-checkbox {
            width: 10px;
            height: 10px;
            transform: scale(1.5);
            cursor: pointer;
        }

        .photo-qr-container {
            display: flex;
            flex-direction: row;
            gap: var(--spacing-md);
            align-items: flex-start;
            margin-bottom: var(--spacing-lg);
        }

        .photo-upload,
        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .photo-upload img,
        .qr-container img {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            object-fit: cover;
            border: 1px solid var(--border-color);
        }

        .photo-upload button,
        .qr-container button {
            width: 100px;
            justify-content: center;
        }

        .required-asterisk {
            color: red;
            font-size: 1.2em;
            vertical-align: top;
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
                flex-direction: column;
                align-items: stretch;
            }
            .controls-right {
                justify-content: flex-start;
                margin-top: var(--spacing-sm);
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
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
            .student-grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 98%;
                max-height: 95vh;
            }
            .modal-form {
                grid-template-columns: 1fr;
                padding: var(--spacing-lg);
            }
            .form-actions {
                flex-direction: column;
                gap: var(--spacing-sm);
            }
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
            /* Removed column hiding for table */
            .table-container {
                overflow-x: auto; /* Ensure horizontal scrolling */
            }
            .table th,
            .table td {
                padding: var(--spacing-xs) var(--spacing-sm); /* Smaller padding for mobile */
            }
        }

        @media (max-width: 576px) {
            h1 {
                font-size: var(--font-size-xl);
            }
            .student-card {
                padding: var(--spacing-sm);
            }
            .student-actions {
                flex-direction: column;
            }
            .student-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .view-toggle {
                width: 100%;
            }
            .view-btn {
                flex: 1;
                justify-content: center;
            }
            /* Removed column hiding for table */
            .table-container {
                overflow-x: auto; /* Ensure horizontal scrolling */
            }
            .table th,
            .table td {
                padding: var(--spacing-xs) var(--spacing-xs); /* Even smaller padding for very small screens */
            }
        }

        @media print {
            .controls,
            .bulk-actions,
            .student-actions,
            .modal {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .student-card {
                box-shadow: none;
                border: 1px solid var(--border-color);
                page-break-inside: avoid;
            }
        }
    </style>
    <style>
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
            min-width: 180px;
            padding: var(--spacing-xs) var(--spacing-sm);
            width: 180px;
            height: 38px;
            box-sizing: border-box;
        }

        .btn {
            padding: var(--spacing-xs) var(--spacing-md);
            font-size: var(--font-size-sm);
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Student Management</h1>
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Total Students</div>
                        <div class="card-value" id="total-students">0</div>
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
            <!-- <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Active Students</div>
                        <div class="card-value" id="active-students">0</div>
                    </div>
                    <div class="card-icon bg-green">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
                        </svg>
                    </div>
                </div>
            </div> -->
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
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Classes Enrolled</div>
                        <div class="card-value" id="classes-enrolled">0</div>
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
        </div>
        <!-- Controls -->
        <div class="controls">
            <div class="controls-left">
                <div class="search-container">
                    <input type="text" class="form-input search-input" id="searchInput" placeholder="Search by LRN or Name">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <select class="form-select filter-select" id="genderFilter">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select class="form-select filter-select" id="gradeLevelFilter">
                    <option value="">All Grade Levels</option>
                </select>

                <select class="form-select filter-select" id="sectionFilter">
                    <option value="">All Sections</option>
                </select>
                <select class="form-select filter-select" id="classFilter">
                    <option value="">All Subjects</option>
                </select>
                <select class="form-select filter-select" id="sortSelect">
                    <option value="name-asc">Name (A-Z)</option>
                    <option value="name-desc">Name (Z-A)</option>
                    <option value="id">LRN</option>
                </select>
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            </div>
            <div class="controls-right">
                <button class="btn btn-primary" onclick="openProfileModal('add')">
                    <i class="fas fa-plus"></i> Add Student
                </button>
                <div class="view-toggle">
                    <button class="view-btn active" onclick="switchView('table')">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="view-btn" onclick="switchView('grid')">
                        <i class="fas fa-th-large"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <input type="checkbox" id="selectAll" onchange="toggleGlobalSelect()">
            <label for="selectAll">Select All</label>
            <span class="selected-count" id="selectedCount">0 selected</span>
            <button class="btn btn-primary" id="bulkExportBtn" disabled onclick="bulkExport()">
                <i class="fas fa-file-export"></i> Export Selected
            </button>
            <button class="btn btn-primary" id="bulkPrintQRBtn" disabled onclick="bulkPrintQR()">
                <i class="fas fa-print"></i> Print QR Codes
            </button>
            <button class="btn btn-danger" id="bulkDeleteBtn" disabled onclick="bulkDelete()">
                <i class="fas fa-trash"></i> Remove Selected from Class
            </button>
        </div>
        <!-- Student List -->
        <div id="gridView" class="student-grid hidden"></div>
        <div id="tableView" class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="tableSelectAll" onchange="togglePageSelect()"></th>
                        <th>Photo</th>
                        <th>QR Code</th>
                        <th>LRN</th>
                        <th>Full Name</th>
                        <th>Grade Level</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
        <!-- Student Profile Modal -->
        <div class="modal" id="profile-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="profile-modal-title">Student Profile</h2>
                    <button class="close-btn" onclick="closeModal('profile')">×</button>
                </div>
                <form id="studentForm" class="modal-form" enctype="multipart/form-data">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">LRN<span class="required-asterisk"> *</span></label>
                            <input type="text" class="form-input" id="student-id" name="lrn" onkeypress="return (event.charCode != 8 && event.charCode != 0 && (event.charCode >= 48 && event.charCode <= 57))" required>
                        </div>
                        <div class="form-group photo-qr-container">
                            <div class="photo-upload">
                                <label class="form-label">Photo (Optional)</label>
                                <img id="student-photo-preview" src="uploads/no-icon.png" alt="Student Photo">
                                <input type="file" id="student-photo" name="photo" accept="image/*" onchange="previewPhoto(event)">
                                <button type="button" class="btn btn-primary" id="change-photo-btn" onclick="document.getElementById('student-photo').click()">Change</button>
                            </div>
                            <div id="qr-container" class="qr-container" style="display: none;">
                                <label class="form-label">QR Code</label>
                                <div id="qr-code" class="qr-code"></div>
                                <button type="button" class="btn btn-primary" id="print-qr-btn" onclick="printQRCode()" style="display: none;"><i class="fas fa-print"></i> Print</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">First Name<span class="required-asterisk"> *</span></label>
                            <input type="text" class="form-input" id="first-name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name<span class="required-asterisk"> *</span></label>
                            <input type="text" class="form-input" id="middle-name" name="middle_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name<span class="required-asterisk"> *</span></label>
                            <input type="text" class="form-input" id="last-name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email<span class="required-asterisk"> *</span></label>
                            <input type="email" class="form-input" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender<span class="required-asterisk"> *</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth<span class="required-asterisk"> *</span></label>
                            <input type="date" class="form-input" id="dob" name="dob" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">Grade Level<span class="required-asterisk"> *</span></label>
                            <select class="form-select" id="grade-level" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <?php foreach ($gradeLevels as $grade): ?>
                                    <option value="<?php echo htmlspecialchars($grade); ?>"><?php echo htmlspecialchars($grade); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Section<span class="required-asterisk"> *</span></label>
                            <select class="form-select" id="section" name="section" required>
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject<span class="required-asterisk"> *</span></label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address<span class="required-asterisk"> *</span></label>
                            <input type="text" class="form-input" id="address" name="address" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Parent/Guardian Name<span class="required-asterisk"> *</span></label>
                            <input type="text" class="form-input" id="parent-name" name="parent_name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Parent/Guardian Email (Optional)</label>
                            <input type="email" class="form-input" id="parent-email" name="parent_email">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Emergency Contact<span class="required-asterisk"> *</span></label>
                            <input type="text" class="form-input" id="emergency-contact" name="emergency_contact" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('profile')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Student</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal" id="delete-modal">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                    <h2 class="modal-title" id="delete-modal-title">Confirm Removal</h2>
                    <button class="close-btn" onclick="closeModal('delete')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: var(--spacing-2xl);">
                    <!-- Single student deletion content -->
                    <div id="single-delete-content" class="hidden">
                        <p style="font-size: var(--font-size-base); color: var(--grayfont-color); margin-bottom: var(--spacing-lg);">
                            Are you sure you want to remove the following student from the class?
                        </p>
                        <div style="display: flex; align-items: center; gap: var(--spacing-lg); background: var(--inputfield-color); padding: var(--spacing-md); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                            <img id="delete-student-photo" src="uploads/no-icon.png" alt="Student Photo" style="width: 160px; height: 160px; border-radius: var(--radius-md); object-fit: cover; border: 2px solid var(--border-color);">
                            <div style="flex: 1;">
                                <p style="margin-bottom: var(--spacing-sm);"><strong style="color: var(--blackfont-color);">LRN:</strong> <span id="delete-student-lrn"></span></p>
                                <p style="margin-bottom: var(--spacing-sm);"><strong style="color: var(--blackfont-color);">Full Name:</strong> <span id="delete-student-name"></span></p>
                                <p style="margin-bottom: var(--spacing-sm);"><strong style="color: var(--blackfont-color);">Grade Level:</strong> <span id="delete-student-grade"></span></p>
                                <p style="margin-bottom: var(--spacing-sm);"><strong style="color: var(--blackfont-color);">Subject:</strong> <span id="delete-student-subject"></span></p>
                                <p><strong style="color: var(--blackfont-color);">Section:</strong> <span id="delete-student-section"></span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Bulk deletion content -->
                    <div id="bulk-delete-content" class="hidden">
                        <p style="font-size: var(--font-size-base); color: var(--grayfont-color); margin-bottom: var(--spacing-lg);">
                            Are you sure you want to remove the following students from their respective classes?
                        </p>
                        <div class="preview-table-container">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th style="padding: var(--spacing-md); font-size: var(--font-size-sm); text-align: left;">Photo</th>
                                        <th style="padding: var(--spacing-md); font-size: var(--font-size-sm); text-align: left;">LRN</th>
                                        <th style="padding: var(--spacing-md); font-size: var(--font-size-sm); text-align: left;">Full Name</th>
                                        <th style="padding: var(--spacing-md); font-size: var(--font-size-sm); text-align: left;">Grade Level</th>
                                        <th style="padding: var(--spacing-md); font-size: var(--font-size-sm); text-align: left;">Subject</th>
                                        <th style="padding: var(--spacing-md); font-size: var(--font-size-sm); text-align: left;">Section</th>
                                        <th style="padding: var(--spacing-md); font-size: var(--font-size-sm); text-align: left;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="bulk-delete-table"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="form-actions" style="padding: var(--spacing-lg) var(--spacing-2xl); border-top: 1px solid var(--border-color); display: flex; gap: var(--spacing-md); justify-content: flex-end; background: var(--card-bg);">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('delete')" style="padding: var(--spacing-sm) var(--spacing-lg); font-size: var(--font-size-sm);">
                        <i class="fas fa-times" style="margin-right: var(--spacing-xs);"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn" onclick="confirmDelete()" style="padding: var(--spacing-sm) var(--spacing-lg); font-size: var(--font-size-sm);">
                        <i class="fas fa-trash" style="margin-right: var(--spacing-xs);"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Data from PHP
        let students = <?php echo json_encode($students_data); ?>;
        let classes = <?php echo json_encode($classes_data); ?>;
        let gradeLevels = <?php echo json_encode($gradeLevels); ?>;
        let subjects = <?php echo json_encode($subjects); ?>;
        let sections = <?php echo json_encode($sections); ?>;
        let currentPage = 1;
        const rowsPerPage = 5;
        let currentView = 'table';
        let filteredStudents = [];
        const selectedStudents = new Set();
        // Global variables - add these at the top with your existing globals
        let allSelectedStudents = new Set(); // This will track all selected students across pages
        let selectAllMode = false; // Track if "select all" mode is active

        // DOM Elements
        const studentTableBody = document.querySelector('#tableView tbody');
        const gridView = document.getElementById('gridView');
        const tableView = document.getElementById('tableView');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchInput');
        const genderFilter = document.getElementById('genderFilter');
        const gradeLevelFilter = document.getElementById('gradeLevelFilter');
        const classFilter = document.getElementById('classFilter');
        const sectionFilter = document.getElementById('sectionFilter');
        const sortSelect = document.getElementById('sortSelect');
        const profileModal = document.getElementById('profile-modal');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            updateStats();
            populateFilters();
            applyFilters();
            setupEventListeners();
            document.querySelector('#studentForm').addEventListener('submit', saveStudent);
            document.getElementById('student-id').addEventListener('change', autoFillStudent);
            document.getElementById('grade-level').addEventListener('change', updateSectionOptions);
            document.getElementById('section').addEventListener('change', updateSubjectOptions);
        });

        // Auto fill student on LRN change
        // function autoFillStudent() {
        //     const lrn = this.value;
        //     if (lrn) {
        //         fetch(`?lrn=${lrn}`)
        //             .then(res => {
        //                 if (!res.ok) {
        //                     return res.text().then(text => {
        //                         console.error('Non-JSON response:', text);
        //                         throw new Error(`HTTP error! Status: ${res.status}`);
        //                     });
        //                 }
        //                 return res.json();
        //             })
        //             .then(data => {
        //                 if (data.success) {
        //                     const student = data.student;
        //                     document.getElementById('first-name').value = student.first_name;
        //                     document.getElementById('middle-name').value = student.middle_name;
        //                     document.getElementById('last-name').value = student.last_name;
        //                     document.getElementById('email').value = student.email || '';
        //                     document.getElementById('gender').value = student.gender || 'Male';
        //                     document.getElementById('dob').value = student.dob || '';
        //                     document.getElementById('address').value = student.address || '';
        //                     document.getElementById('parent-name').value = student.parent_name || '';
        //                     document.getElementById('emergency-contact').value = student.emergency_contact || '';
        //                     document.getElementById('grade-level').value = student.grade_level || '';
        //                     document.getElementById('grade-level').dispatchEvent(new Event('change'));
        //                     document.getElementById('student-photo-preview').src = student.photo ?
        //                         'uploads/' + student.photo :
        //                         'uploads/no-icon.png';
        //                 } else {
        //                     document.getElementById('first-name').value = '';
        //                     document.getElementById('middle-name').value = '';
        //                     document.getElementById('last-name').value = '';
        //                     document.getElementById('email').value = '';
        //                     document.getElementById('gender').value = 'Male';
        //                     document.getElementById('dob').value = '';
        //                     document.getElementById('address').value = '';
        //                     document.getElementById('parent-name').value = '';
        //                     document.getElementById('emergency-contact').value = '';
        //                     document.getElementById('student-photo-preview').src = 'uploads/no-icon.png';
        //                 }
        //             })
        //             .catch(error => {
        //                 console.error('Fetch error in autoFillStudent:', error);
        //             });
        //     }
        // }

        // Update section options based on grade
        function updateSectionOptions() {
            const grade = document.getElementById('grade-level').value;
            const availableSections = [...new Set(classes.filter(c => c.grade_level === grade).map(c => c.section_name))];
            const sectionSelect = document.getElementById('section');
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            availableSections.forEach(sec => {
                const option = document.createElement('option');
                option.value = sec;
                option.textContent = sec;
                sectionSelect.appendChild(option);
            });
            document.getElementById('class').innerHTML = '<option value="">Select Subject</option>';
        }

        // Update subject options based on grade and section
        function updateSubjectOptions() {
            const grade = document.getElementById('grade-level').value;
            const section = document.getElementById('section').value;
            const availableSubjects = [...new Set(classes.filter(c => c.grade_level === grade && c.section_name === section).map(c => c.subject_name))];
            const subjectSelect = document.getElementById('class');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            availableSubjects.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub;
                option.textContent = sub;
                subjectSelect.appendChild(option);
            });
        }

        // Update stats for cards
        function updateStats() {
            const totalStudents = students.length;
            const classesEnrolled = [...new Set(students.map(s => `${s.class}-${s.section}`))].length;
            document.getElementById('total-students').textContent = totalStudents;
            document.getElementById('classes-enrolled').textContent = classesEnrolled;
        }

        // Populate filters
        function populateFilters() {
            gradeLevelFilter.innerHTML = '<option value="">All Grade Levels</option>';
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelFilter.appendChild(option);
            });
            classFilter.innerHTML = '<option value="">All Subjects</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                classFilter.appendChild(option);
            });
            sectionFilter.innerHTML = '<option value="">All Sections</option>';
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionFilter.appendChild(option);
            });
        }

        // Setup event listeners
        function setupEventListeners() {
            searchInput.addEventListener('input', applyFilters);
            genderFilter.addEventListener('change', applyFilters);
            gradeLevelFilter.addEventListener('change', applyFilters);
            classFilter.addEventListener('change', applyFilters);
            sectionFilter.addEventListener('change', applyFilters);
            sortSelect.addEventListener('change', applyFilters);
        }


        // Modified apply filters function to maintain selections when filtering
        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const gender = genderFilter.value;
            const gradeLevel = gradeLevelFilter.value;
            const className = classFilter.value;
            const section = sectionFilter.value;

            filteredStudents = students.filter(student => {
                const matchesSearch = student.fullName.toLowerCase().includes(searchTerm) ||
                    student.lrn.toString().includes(searchTerm);
                const matchesGender = gender ? student.gender === gender : true;
                const matchesGradeLevel = gradeLevel ? student.gradeLevel === gradeLevel : true;
                const matchesClass = className ? student.class === className : true;
                const matchesSection = section ? student.section === section : true;
                return matchesSearch && matchesGender && matchesGradeLevel && matchesClass && matchesSection;
            });

            filteredStudents.sort((a, b) => {
                if (sortSelect.value === 'name-asc') return a.fullName.localeCompare(b.fullName);
                if (sortSelect.value === 'name-desc') return b.fullName.localeCompare(a.fullName);
                if (sortSelect.value === 'id') return a.lrn.toString().localeCompare(b.lrn.toString());
                return 0;
            });

            // Clean up selections for students that are no longer in the filtered list
            const filteredKeys = new Set(filteredStudents.map(s => `${s.lrn}-${s.class_id}`));
            allSelectedStudents.forEach(key => {
                if (!filteredKeys.has(key)) {
                    // Only remove if the student is not in the main students array anymore
                    const [lrn, class_id] = key.split('-');
                    const studentExists = students.some(s => s.lrn == lrn && String(s.class_id) === String(class_id));
                    if (!studentExists) {
                        allSelectedStudents.delete(key);
                        selectedStudents.delete(key);
                    }
                }
            });

            renderViews(filteredStudents);
        }

        // Render views
        function renderViews(data) {
            gridView.classList.add('hidden');
            tableView.classList.add('hidden');
            if (currentView === 'grid') {
                renderGridView(data);
                gridView.classList.remove('hidden');
            } else {
                renderTableView(data);
                tableView.classList.remove('hidden');
            }
            renderPagination(data.length);
        }

        // Render grid view
        function renderGridView(data) {
            gridView.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = data.slice(start, end);
            if (paginatedData.length === 0) {
                gridView.innerHTML = '<div class="no-students">No students found.</div>';
                return;
            }
            paginatedData.forEach(student => {
                const card = document.createElement('div');
                card.className = 'student-card';
                card.innerHTML = `
                    <div class="student-header">
                        <h3>${student.fullName}</h3>
                    </div>
                    <div class="student-info">
                        <p><i class="fas fa-id-card"></i> LRN: ${student.lrn}</p>
                        <p><i class="fas fa-envelope"></i> ${student.emergency_contact || 'N/A'}</p>
                        <p><i class="fas fa-graduation-cap"></i> ${student.gradeLevel}</p>
                        <p><i class="fas fa-book"></i> ${student.class}</p>
                        <p><i class="fas fa-layer-group"></i> ${student.section}</p>
                    </div>
                    <div class="student-actions">
                        <button class="btn btn-primary btn-sm" onclick="openProfileModal('view', '${student.lrn}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="openProfileModal('edit', '${student.lrn}')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteStudent('${student.lrn}', '${student.class_id}')">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                `;
                gridView.appendChild(card);
            });
        }

        // Modified render table view function
        function renderTableView(data) {
            studentTableBody.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = data.slice(start, end);

            console.log('Rendering table with paginated data:', paginatedData.map(s => ({
                lrn: s.lrn,
                class_id: s.class_id
            })));

            paginatedData.forEach(student => {
                console.log('Rendering student:', {
                    lrn: student.lrn,
                    class_id: student.class_id
                });
                const row = document.createElement('tr');
                const qrCodeId = `qr-${student.lrn}-${student.class_id}`;
                row.innerHTML = `
                    <td><input type="checkbox" class="row-checkbox" data-id="${student.lrn}" data-class-id="${student.class_id}"></td>
                    <td><img src="${student.photo ? 'uploads/' + student.photo : 'uploads/no-icon.png'}" alt="${student.fullName}" style="width: 45px; height: 45px; border-radius: 50%;"></td>
                    <td><div id="${qrCodeId}" style="width: 45px; height: 45px;"></div></td>
                    <td>${student.lrn}</td>
                    <td>${student.fullName}</td>
                    <td>${student.gradeLevel}</td>
                    <td>${student.class}</td>
                    <td>${student.section}</td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-primary btn-sm" onclick="openProfileModal('view', '${student.lrn}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="openProfileModal('edit', '${student.lrn}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteStudent('${student.lrn}', '${student.class_id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                studentTableBody.appendChild(row);

                const qrFile = student.qr_code || `${student.lrn}.png`;
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=checkQR&lrn=${encodeURIComponent(student.lrn)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById(qrCodeId).innerHTML = data.exists ?
                            `<img src="qrcodes/${qrFile}" width="45" height="45">` :
                            'No QR';
                    })
                    .catch(error => {
                        console.error('Check QR error:', error);
                        document.getElementById(qrCodeId).innerHTML = 'Error';
                    });
            });

            // Set checkbox states based on allSelectedStudents set

            const checkboxes = document.querySelectorAll('.row-checkbox');
            console.log('Found row checkboxes:', checkboxes.length);
            checkboxes.forEach(cb => {
                const key = `${cb.dataset.id}-${cb.dataset.classId}`;
                cb.checked = allSelectedStudents.has(key);
                console.log('Checkbox initialized:', key, 'Checked:', cb.checked);

                cb.addEventListener('change', (e) => {
                    const changeKey = `${e.target.dataset.id}-${e.target.dataset.classId}`;
                    console.log('Checkbox changed:', changeKey, 'Checked:', e.target.checked);
                    selectAllMode = false; // Disable select all mode when manually selecting

                    if (e.target.checked) {
                        allSelectedStudents.add(changeKey);
                        selectedStudents.add(changeKey);
                    } else {
                        allSelectedStudents.delete(changeKey);
                        selectedStudents.delete(changeKey);
                    }
                    console.log('allSelectedStudents:', Array.from(allSelectedStudents));
                    updateBulkActions();
                    updateHeaderCheckboxes();
                });
            });

            updateBulkActions();
            updateHeaderCheckboxes();
        }

        function bulkPrintQR() {
            console.log('allSelectedStudents:', Array.from(allSelectedStudents));
            if (allSelectedStudents.size === 0) {
                console.log('No students selected for QR printing.');
                alert('No students selected for QR printing.');
                return;
            }

            // Extract unique LRNs from selected students (remove duplicates)
            const selectedLRNs = Array.from(allSelectedStudents).map(key => String(key.split('-')[0]));
            const uniqueLRNs = [...new Set(selectedLRNs)]; // Remove duplicate LRNs
            
            console.log('selectedLRNs:', selectedLRNs);
            console.log('uniqueLRNs:', uniqueLRNs);
            
            // Get unique students by LRN (take the first occurrence of each LRN)
            const uniqueStudents = [];
            const processedLRNs = new Set();
            
            students.forEach(student => {
                const lrnStr = String(student.lrn);
                if (uniqueLRNs.includes(lrnStr) && !processedLRNs.has(lrnStr)) {
                    processedLRNs.add(lrnStr);
                    const qrFile = student.qr_code || `${student.lrn}.png`;
                    uniqueStudents.push({
                        lrn: student.lrn,
                        qr_code: qrFile
                    });
                }
            });
            
            console.log('uniqueStudents:', uniqueStudents);

            if (uniqueStudents.length === 0) {
                alert('No valid students found for QR printing.');
                return;
            }

            const printBtn = document.getElementById('bulkPrintQRBtn');
            const originalText = printBtn.innerHTML;
            printBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            printBtn.disabled = true;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=bulkPrintQR&students=${encodeURIComponent(JSON.stringify(uniqueStudents))}`
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
                    console.log('Server response:', data);
                    if (data.success) {
                        const downloadLink = document.createElement('a');
                        downloadLink.href = `exports/${data.filename}`;
                        downloadLink.download = data.filename;
                        downloadLink.style.display = 'none';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);

                        alert(`QR ID Cards PDF generated successfully!\n` +
                            `Generated ${data.cards_count} ID cards\n` +
                            `File: ${data.filename}\n\n` +
                            `Layout: 4×3 cards per US Letter page (12 cards/page)\n` +
                            `Card size: 2.125" × 3.375" (standard ID card size)`);
                    } else {
                        alert((data.message || 'Failed to generate QR ID Cards PDF'));
                    }
                })
                .catch(error => {
                    console.error('QR Print error:', error);
                    alert('An error occurred while generating the QR ID Cards PDF. Please check the console for details.');
                })
                .finally(() => {
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = allSelectedStudents.size === 0;
                });
        }

        // Render pagination
        function renderPagination(totalRows) {
            const pageCount = Math.ceil(totalRows / rowsPerPage);
            pagination.innerHTML = `
                <button class="pagination-btn" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Previous</button>
                ${Array.from({ length: pageCount }, (_, i) => `
                    <button class="pagination-btn ${currentPage === i + 1 ? 'active' : ''}" onclick="changePage(${i + 1})">${i + 1}</button>
                `).join('')}
                <button class="pagination-btn" onclick="changePage(${currentPage + 1})" ${currentPage === pageCount ? 'disabled' : ''}>Next</button>
            `;
        }

        // Change page
        function changePage(page) {
            currentPage = page;
            applyFilters();
        }

        // Switch view
        function switchView(view) {
            currentView = view;
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.view-btn[onclick="switchView('${view}')"]`).classList.add('active');
            applyFilters();
        }

        // Modified toggle global select function
        function toggleGlobalSelect() {
            const isChecked = document.getElementById('selectAll').checked;
            console.log('Select All toggled:', isChecked, 'Filtered students count:', filteredStudents.length);
            selectAllMode = isChecked;

            if (isChecked) {
                // Select all filtered students across all pages
                filteredStudents.forEach(student => {
                    const key = `${student.lrn}-${student.class_id}`;
                    console.log('Adding key:', key);
                    allSelectedStudents.add(key);
                });
                selectedStudents.clear();
                filteredStudents.forEach(student => {
                    const key = `${student.lrn}-${student.class_id}`;
                    selectedStudents.add(key);
                });
            } else {
                // Clear all selections
                console.log('Clearing all selections');
                allSelectedStudents.clear();
                selectedStudents.clear();
            }
            console.log('allSelectedStudents:', Array.from(allSelectedStudents));

            // Update visible checkboxes
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                const key = `${cb.dataset.id}-${cb.dataset.classId}`;
                cb.checked = allSelectedStudents.has(key);
                console.log('Updating checkbox:', key, 'Checked:', cb.checked);
            });

            updateBulkActions();
            updateHeaderCheckboxes();
        }

        // Modified toggle page select function
        function togglePageSelect() {
            const isChecked = document.getElementById('tableSelectAll').checked;
            console.log('Table Select All toggled:', isChecked);
            selectAllMode = false; // Disable select all mode when manually selecting page

            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = isChecked;
                const key = `${cb.dataset.id}-${cb.dataset.classId}`;
                console.log('Processing checkbox:', key, 'Checked:', isChecked);
                if (isChecked) {
                    allSelectedStudents.add(key);
                    selectedStudents.add(key);
                } else {
                    allSelectedStudents.delete(key);
                    selectedStudents.delete(key);
                }
            });
            console.log('allSelectedStudents:', Array.from(allSelectedStudents));
            updateBulkActions();
            updateHeaderCheckboxes();
        }

        // Modified update bulk actions function
        function updateBulkActions() {
            const totalSelected = allSelectedStudents.size;
            document.getElementById('selectedCount').textContent = `${totalSelected} selected`;
            document.querySelectorAll('.bulk-actions .btn').forEach(btn => btn.disabled = totalSelected === 0);
        }

        // Modified update header checkboxes function
        function updateHeaderCheckboxes() {
            // Global checkbox - check if ALL filtered students are selected
            const allFilteredSelected = filteredStudents.length > 0 &&
                filteredStudents.every(s => allSelectedStudents.has(`${s.lrn}-${s.class_id}`));
            const someFilteredSelected = filteredStudents.some(s => allSelectedStudents.has(`${s.lrn}-${s.class_id}`));

            document.getElementById('selectAll').checked = allFilteredSelected;
            document.getElementById('selectAll').indeterminate = !allFilteredSelected && someFilteredSelected;

            // Page checkbox - check if all VISIBLE students are selected
            const currentCheckboxes = document.querySelectorAll('.row-checkbox');
            const pageAllSelected = currentCheckboxes.length > 0 &&
                Array.from(currentCheckboxes).every(cb => cb.checked);
            const pageSomeSelected = Array.from(currentCheckboxes).some(cb => cb.checked);

            document.getElementById('tableSelectAll').checked = pageAllSelected;
            document.getElementById('tableSelectAll').indeterminate = !pageAllSelected && pageSomeSelected;
        }

        // Modified bulk export function
        function bulkExport() {
            if (allSelectedStudents.size === 0) {
                alert('No students selected for export.');
                return;
            }

            // Get LRNs from allSelectedStudents instead of visible checkboxes
            const selectedLRNs = Array.from(allSelectedStudents).map(key => key.split('-')[0]);

            console.log('Exporting students:', selectedLRNs); // Debug log

            // Show loading state
            const exportBtn = document.getElementById('bulkExportBtn');
            const originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            exportBtn.disabled = true;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=exportExcel&lrns=${encodeURIComponent(JSON.stringify(selectedLRNs))}`
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
                        // Create download link
                        const downloadLink = document.createElement('a');
                        downloadLink.href = `exports/${data.filename}`;
                        downloadLink.download = data.filename;
                        downloadLink.style.display = 'none';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);

                        // Show success message
                        alert(`Excel file generated successfully! Exported ${selectedLRNs.length} students.`);
                    } else {
                        alert(data.message || 'Failed to generate Excel file');
                    }
                })
                .catch(error => {
                    console.error('Export error:', error);
                    alert('An error occurred while generating the Excel file. Please check the console for details.');
                })
                .finally(() => {
                    // Reset button state
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                });
        }


        // Modified bulk delete function
        function bulkDelete() {
            if (allSelectedStudents.size === 0) {
                alert('No students selected.');
                return;
            }

            const selected = Array.from(allSelectedStudents).map(key => {
                const [lrn, class_id] = key.split('-');
                return {
                    lrn,
                    class_id
                };
            });

            // Populate bulk delete modal
            const tableBody = document.getElementById('bulk-delete-table');
            tableBody.innerHTML = '';
            selected.forEach(({
                lrn,
                class_id
            }) => {
                const student = students.find(s => s.lrn == lrn && String(s.class_id) === String(class_id));
                if (student) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                <td><img src="${student.photo ? 'uploads/' + student.photo : 'uploads/no-icon.png'}" alt="${student.fullName}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;"></td>
                <td>${student.lrn}</td>
                <td>${student.fullName}</td>
                <td>${student.gradeLevel}</td>
                <td>${student.class}</td>
                <td>${student.section}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="removeFromBulkSelection('${student.lrn}', '${student.class_id}')">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </td>
            `;
                    tableBody.appendChild(row);
                }
            });

            document.getElementById('delete-modal-title').textContent = 'Confirm Bulk Removal';
            document.getElementById('single-delete-content').classList.add('hidden');
            document.getElementById('bulk-delete-content').classList.remove('hidden');
            document.getElementById('confirm-delete-btn').dataset.lrns = JSON.stringify(selected);
            document.getElementById('confirm-delete-btn').dataset.mode = 'bulk';
            document.getElementById('delete-modal').classList.add('show');
        }

        // Modified remove from bulk selection function
        function removeFromBulkSelection(lrn, class_id) {
            const key = `${lrn}-${class_id}`;
            allSelectedStudents.delete(key);
            selectedStudents.delete(key);
            selectAllMode = false;
            updateBulkActions();
            updateHeaderCheckboxes();
            bulkDelete(); // Refresh the modal
        }
        // Modified confirm delete function
        function confirmDelete() {
            const confirmBtn = document.getElementById('confirm-delete-btn');
            const mode = confirmBtn.dataset.mode;

            if (mode === 'single') {
                const lrn = confirmBtn.dataset.lrn;
                const class_id = confirmBtn.dataset.classId;
                fetch(`?delete_lrn=${lrn}&class_id=${class_id}`)
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
                            const key = `${lrn}-${class_id}`;
                            allSelectedStudents.delete(key);
                            selectedStudents.delete(key);
                            students = students.filter(s => s.lrn != lrn || String(s.class_id) !== String(class_id));
                            applyFilters();
                            closeModal('delete');
                        } else {
                            alert(data.message || 'Error removing student from class.');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error in confirmDelete (single):', error);
                        alert('An error occurred while removing the student. Please check the console for details.');
                    });
            } else if (mode === 'bulk') {
                const lrns = JSON.parse(confirmBtn.dataset.lrns);
                const groupedByClass = lrns.reduce((acc, {
                    lrn,
                    class_id
                }) => {
                    acc[class_id] = acc[class_id] || [];
                    acc[class_id].push(lrn);
                    return acc;
                }, {});

                const deletePromises = Object.entries(groupedByClass).map(([class_id, lrns]) => {
                    return fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `bulk_delete=true&lrns=${encodeURIComponent(JSON.stringify(lrns))}&class_id=${class_id}`
                        })
                        .then(res => {
                            if (!res.ok) {
                                return res.text().then(text => {
                                    console.error('Non-JSON response:', text);
                                    throw new Error(`HTTP error! Status: ${res.status}`);
                                });
                            }
                            return res.json();
                        });
                });

                Promise.all(deletePromises)
                    .then(results => {
                        const allSuccessful = results.every(data => data.success);
                        if (allSuccessful) {
                            students = students.filter(s => !allSelectedStudents.has(`${s.lrn}-${s.class_id}`));
                            allSelectedStudents.clear();
                            selectedStudents.clear();
                            selectAllMode = false;
                            applyFilters();
                            closeModal('delete');
                        } else {
                            const errorMessage = results.find(data => !data.success)?.message || 'Error removing some students from class.';
                            alert(errorMessage);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error in confirmDelete (bulk):', error);
                        alert('An error occurred while removing students. Please check the console for details.');
                    });
            }
        }

        // Delete student from class
        function deleteStudent(lrn, class_id) {
            const student = students.find(s => s.lrn == lrn && String(s.class_id) === String(class_id));
            if (!student) {
                alert('Student not found.');
                return;
            }

            // Populate single delete modal
            document.getElementById('delete-modal-title').textContent = 'Confirm Student Removal';
            document.getElementById('delete-student-photo').src = student.photo ? 'uploads/' + student.photo : 'uploads/no-icon.png';
            document.getElementById('delete-student-lrn').textContent = student.lrn;
            document.getElementById('delete-student-name').textContent = student.fullName;
            document.getElementById('delete-student-grade').textContent = student.gradeLevel;
            document.getElementById('delete-student-subject').textContent = student.class;
            document.getElementById('delete-student-section').textContent = student.section;
            document.getElementById('single-delete-content').classList.remove('hidden');
            document.getElementById('bulk-delete-content').classList.add('hidden');
            document.getElementById('confirm-delete-btn').dataset.lrn = lrn;
            document.getElementById('confirm-delete-btn').dataset.classId = class_id;
            document.getElementById('confirm-delete-btn').dataset.mode = 'single';
            document.getElementById('delete-modal').classList.add('show');
        }
        // Open profile modal
        function openProfileModal(mode, lrn = null) {
            const form = {
                studentId: document.getElementById('student-id'),
                firstName: document.getElementById('first-name'),
                middleName: document.getElementById('middle-name'),
                lastName: document.getElementById('last-name'),
                email: document.getElementById('email'),
                gender: document.getElementById('gender'),
                dob: document.getElementById('dob'),
                gradeLevel: document.getElementById('grade-level'),
                section: document.getElementById('section'),
                class: document.getElementById('class'),
                address: document.getElementById('address'),
                parentName: document.getElementById('parent-name'),
                parentEmail: document.getElementById('parent-email'), // Add this line
                emergencyContact: document.getElementById('emergency-contact'),
                photoPreview: document.getElementById('student-photo-preview'),
                photoInput: document.getElementById('student-photo')
            };
            Object.values(form).forEach(input => {
                if (input.tagName === 'IMG') input.src = 'uploads/no-icon.png';
                else if (input.tagName === 'SELECT') input.value = '';
                else if (input.type === 'file') input.value = '';
                else input.value = '';
            });
            const qrContainer = document.getElementById('qr-container');
            const qrCodeDiv = document.getElementById('qr-code');
            const printQrBtn = document.getElementById('print-qr-btn');
            qrCodeDiv.innerHTML = '';
            qrContainer.style.display = 'none';
            const changePhotoBtn = document.getElementById('change-photo-btn');
            changePhotoBtn.style.display = mode === 'view' ? 'none' : 'inline-flex';
            document.getElementById('qr_code_input').value = '';

            if (mode !== 'add' && lrn) {
                const student = students.find(s => s.lrn == lrn);
                if (!student) {
                    console.error(`No student found for LRN: ${lrn}`);
                    alert('Student not found.');
                    return;
                }
                console.log('Student:', student);
                document.getElementById('profile-modal-title').textContent = `${student.fullName}'s Profile`;
                form.studentId.value = student.lrn;
                form.firstName.value = student.first_name;
                form.middleName.value = student.middle_name;
                form.lastName.value = student.last_name;
                form.email.value = student.email || '';
                form.gender.value = student.gender || 'Male';
                form.dob.value = student.dob || '';
                form.address.value = student.address || '';
                form.parentName.value = student.parent_name || '';
                form.parentEmail.value = student.parent_email || ''; // Add this line
                form.emergencyContact.value = student.emergency_contact || '';
                form.photoPreview.src = student.photo ?
                    'uploads/' + student.photo :
                    'uploads/no-icon.png';

                // Display QR code in view and edit modes
                if (student.qr_code) {
                    qrContainer.style.display = 'block';
                    qrCodeDiv.innerHTML = `<img src="qrcodes/${student.qr_code}" width="100" height="100">`;
                    printQrBtn.style.display = 'inline-flex'; // Show Print button in view/edit mode
                } else {
                    qrContainer.style.display = 'block';
                    qrCodeDiv.innerHTML = '<p>No QR Code available</p>';
                    printQrBtn.style.display = 'none'; // Hide Print button if no QR code
                }

                // Find the class details based on class_id
                const studentClass = classes.find(c => String(c.class_id) === String(student.class_id));
                console.log('Student Class:', studentClass);
                if (studentClass) {
                    form.gradeLevel.value = studentClass.grade_level;
                    console.log('Setting gradeLevel to:', studentClass.grade_level);
                    updateSectionOptions();
                    form.section.value = studentClass.section_name;
                    updateSubjectOptions();
                    form.class.value = studentClass.subject_name;
                } else {
                    console.warn(`No class found for class_id: ${student.class_id}, using fallback`);
                    form.gradeLevel.value = student.gradeLevel || '';
                    updateSectionOptions();
                    form.section.value = student.section || '';
                    updateSubjectOptions();
                    form.class.value = student.class || '';
                }
            } else {
                document.getElementById('profile-modal-title').textContent = 'Add New Student';
                document.getElementById('section').innerHTML = '<option value="">Select Section</option>';
                document.getElementById('class').innerHTML = '<option value="">Select Subject</option>';
                qrContainer.style.display = 'none';
                printQrBtn.style.display = 'none'; // Hide Print button in add mode
            }

            Object.values(form).forEach(input => {
                if (input.tagName !== 'IMG' && input.type !== 'file') input.disabled = mode === 'view';
            });
            form.photoInput.disabled = mode === 'view';
            document.querySelector('.form-actions .btn-primary').style.display = mode === 'view' ? 'none' : 'inline-flex';
            profileModal.classList.add('show');
        }
        // Preview photo
        function previewPhoto(event) {
            const file = event.target.files[0];
            const photoPreview = document.getElementById('student-photo-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    photoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                photoPreview.src = 'uploads/no-icon.png';
            }
        }

        // Save student
        function saveStudent(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('studentForm'));
            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => {
                    console.log('Response Status:', res.status);
                    console.log('Response Headers:', [...res.headers.entries()]);
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
                        alert(data.message || 'Student saved successfully');
                        document.getElementById('studentForm').reset();
                        closeModal('profile');
                        location.reload();
                    } else {
                        alert(data.message || 'Error saving student');
                    }
                })
                .catch(error => {
                    console.error('Fetch error in saveStudent:', error);
                    alert('An error occurred while saving the student. Please check the console for details.');
                });
        }

        // Add a function to clear all selections
        function clearAllSelections() {
            allSelectedStudents.clear();
            selectedStudents.clear();
            selectAllMode = false;
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            document.getElementById('tableSelectAll').checked = false;
            document.getElementById('tableSelectAll').indeterminate = false;
            updateBulkActions();
        }

        // Modified clear filters to also show selection status
        function clearFilters() {
            searchInput.value = '';
            genderFilter.value = '';
            gradeLevelFilter.value = '';
            classFilter.value = '';
            sectionFilter.value = '';
            sortSelect.value = 'name-asc';
            applyFilters();

            // Show info about maintained selections if any
            if (allSelectedStudents.size > 0) {
                console.log(`Maintained ${allSelectedStudents.size} selections after clearing filters`);
            }
        }

        // Update closeModal to handle delete modal
        function closeModal(type) {
            if (type === 'profile') {
                profileModal.classList.remove('show');
            } else if (type === 'delete') {
                document.getElementById('delete-modal').classList.remove('show');
                document.getElementById('single-delete-content').classList.add('hidden');
                document.getElementById('bulk-delete-content').classList.add('hidden');
                document.getElementById('bulk-delete-table').innerHTML = '';
                document.getElementById('confirm-delete-btn').removeAttribute('data-lrn');
                document.getElementById('confirm-delete-btn').removeAttribute('data-classId');
                document.getElementById('confirm-delete-btn').removeAttribute('data-lrns');
                document.getElementById('confirm-delete-btn').removeAttribute('data-mode');
            }
        }

        function printQRCode() {
            const qrImg = document.querySelector('#qr-code img');
            if (!qrImg) return;

            // Get student data from the form
            const lrn = document.getElementById('student-id').value;
            const firstName = document.getElementById('first-name').value;
            const middleName = document.getElementById('middle-name').value;
            const lastName = document.getElementById('last-name').value;
            const fullName = `${lastName}, ${firstName} ${middleName}`;

            // Create a canvas to generate the ID card image
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // ID card dimensions (portrait - 2.125" x 3.375" at 300 DPI)
            const cardWidth = 638; // 2.125 * 300
            const cardHeight = 1012; // 3.375 * 300
            canvas.width = cardWidth;
            canvas.height = cardHeight;

            // Create image object for QR code
            const qrImage = new Image();
            qrImage.crossOrigin = 'anonymous';

            qrImage.onload = function() {
                // Create gradient background
                const gradient = ctx.createLinearGradient(0, 0, 0, cardHeight);
                gradient.addColorStop(0, '#f8f9fa');
                gradient.addColorStop(0.3, '#ffffff');
                gradient.addColorStop(0.7, '#ffffff');
                gradient.addColorStop(1, '#e9ecef');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, cardWidth, cardHeight);

                // Add elegant border with rounded corners effect
                ctx.strokeStyle = '#000000';
                ctx.lineWidth = 3;
                ctx.strokeRect(15, 15, cardWidth - 30, cardHeight - 30);

                // Add inner shadow effect
                ctx.strokeStyle = '#bdc3c7';
                ctx.lineWidth = 1;
                ctx.strokeRect(18, 18, cardWidth - 36, cardHeight - 36);

                // Add header section with subtle background
                const headerHeight = 80;
                const headerGradient = ctx.createLinearGradient(0, 20, 0, headerHeight + 20);
                headerGradient.addColorStop(0, '#3498db');
                headerGradient.addColorStop(1, '#2980b9');
                ctx.fillStyle = headerGradient;
                ctx.fillRect(25, 25, cardWidth - 50, headerHeight);

                // Add header text
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 28px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('SAMS', cardWidth / 2, 75);

                // Calculate QR code size with 10px padding (QR occupies full width minus padding)
                const cardPadding = 25; // Card border padding
                const qrPadding = 10; // QR code padding
                const availableWidth = cardWidth - (cardPadding * 2) - (qrPadding * 2);
                const qrSize = availableWidth;
                const qrX = cardPadding + qrPadding;
                const qrY = headerHeight + 50;

                // Add QR code background with shadow effect
                ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
                ctx.fillRect(qrX - 5, qrY - 5, qrSize + 10, qrSize + 10);

                // Add white background for QR code
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(qrX, qrY, qrSize, qrSize);

                // Draw QR code
                ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);

                // Add decorative line below QR code
                const lineY = qrY + qrSize + 30;
                const lineGradient = ctx.createLinearGradient(50, lineY, cardWidth - 50, lineY);
                lineGradient.addColorStop(0, 'transparent');
                lineGradient.addColorStop(0.2, '#3498db');
                lineGradient.addColorStop(0.8, '#3498db');
                lineGradient.addColorStop(1, 'transparent');
                ctx.strokeStyle = lineGradient;
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(50, lineY);
                ctx.lineTo(cardWidth - 50, lineY);
                ctx.stroke();

                // Set font for LRN with enhanced styling
                ctx.fillStyle = '#000000';
                ctx.font = 'bold 32px Arial';
                ctx.textAlign = 'center';

                // Add LRN with subtle background
                const lrnText = `LRN: ${lrn}`;
                const lrnY = lineY + 60;

                // Add text shadow effect for LRN
                ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
                ctx.fillText(lrnText, cardWidth / 2 + 2, lrnY + 2);
                ctx.fillStyle = '#000000';
                ctx.fillText(lrnText, cardWidth / 2, lrnY);

                // Set font for Full Name with better styling
                const nameLength = fullName.length;
                let fontSize = nameLength > 30 ? 22 : nameLength > 25 ? 26 : nameLength > 20 ? 28 : 30;
                ctx.font = `bold ${fontSize}px Arial`;
                ctx.fillStyle = '#000000';

                // Handle long names by wrapping text
                const maxWidth = cardWidth - 80;
                const words = fullName.split(' ');
                let line = '';
                let lines = [];

                for (let i = 0; i < words.length; i++) {
                    const testLine = line + words[i] + ' ';
                    const metrics = ctx.measureText(testLine);
                    const testWidth = metrics.width;

                    if (testWidth > maxWidth && i > 0) {
                        lines.push(line.trim());
                        line = words[i] + ' ';
                    } else {
                        line = testLine;
                    }
                }
                lines.push(line.trim());

                // Draw full name with enhanced styling
                const nameStartY = lrnY + 70;
                const lineHeight = fontSize + 10;

                lines.forEach((line, index) => {
                    // Add text shadow for name
                    ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
                    ctx.fillText(line, cardWidth / 2 + 1, nameStartY + (index * lineHeight) + 1);
                    ctx.fillStyle = '#000000';
                    ctx.fillText(line, cardWidth / 2, nameStartY + (index * lineHeight));
                });

                // Add footer decoration
                const footerY = cardHeight - 60;
                ctx.strokeStyle = '#bdc3c7';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(80, footerY);
                ctx.lineTo(cardWidth - 80, footerY);
                ctx.stroke();

                // Add small decorative elements (dots)
                ctx.fillStyle = '#3498db';
                for (let i = 0; i < 5; i++) {
                    const dotX = (cardWidth / 6) * (i + 1);
                    ctx.beginPath();
                    ctx.arc(dotX, footerY + 15, 3, 0, Math.PI * 2);
                    ctx.fill();
                }

                // Convert canvas to blob and automatically download
                canvas.toBlob(function(blob) {
                    // Create download link
                    const downloadLink = document.createElement('a');
                    const url = URL.createObjectURL(blob);

                    // Set download attributes
                    downloadLink.href = url;
                    downloadLink.download = `QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
                    downloadLink.style.display = 'none';

                    // Append to body, click, and remove
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);

                    // Clean up the URL object
                    URL.revokeObjectURL(url);

                    // Show success message with enhanced styling
                    const successMsg = `✅ QR ID card generated successfully!\nFile: QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
                    alert(successMsg);
                }, 'image/png', 1.0);
            };

            qrImage.onerror = function() {
                alert('❌ Error loading QR code image. Please make sure the QR code is generated first.');
            };

            // Load the QR image
            qrImage.src = qrImg.src;
        }
    </script>
    <script>
        // QR Generation Logic
        document.addEventListener('DOMContentLoaded', () => {
            updateStats();
            populateFilters();
            applyFilters();
            setupEventListeners();
            document.querySelector('#studentForm').addEventListener('submit', saveStudent);
            // Remove or comment out the autoFillStudent listener
            // document.getElementById('student-id').addEventListener('change', autoFillStudent);
            document.getElementById('grade-level').addEventListener('change', updateSectionOptions);
            document.getElementById('section').addEventListener('change', updateSubjectOptions);

            // Ensure QR code generation logic
            const lrnInput = document.getElementById('student-id');
            const firstNameInput = document.getElementById('first-name');
            const middleNameInput = document.getElementById('middle-name');
            const lastNameInput = document.getElementById('last-name');
            const qrContainer = document.getElementById('qr-container');
            const qrCodeDiv = document.getElementById('qr-code');
            const qrCodeHidden = document.getElementById('qr_code_input') || (function() {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'qr_code';
                hidden.id = 'qr_code_input';
                document.getElementById('studentForm').appendChild(hidden);
                return hidden;
            })();

            function generateQR() {
                const lrn = lrnInput.value.trim();
                const first_name = firstNameInput.value.trim();
                const middle_name = middleNameInput.value.trim();
                const last_name = lastNameInput.value.trim();

                if (lrn && first_name && last_name) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=generateQR&lrn=${encodeURIComponent(lrn)}&first_name=${encodeURIComponent(first_name)}&middle_name=${encodeURIComponent(middle_name)}&last_name=${encodeURIComponent(last_name)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const filename = data.filename;
                            qrCodeDiv.innerHTML = `<img src="qrcodes/${filename}" width="100" height="100">`;
                            qrContainer.style.display = 'block';
                            qrCodeHidden.value = filename;
                        } else {
                            console.error('QR generation failed:', data.message);
                        }
                    })
                    .catch(err => console.error('Error generating QR:', err));
                }
            }

            lrnInput.addEventListener('change', generateQR);
            firstNameInput.addEventListener('change', generateQR);
            middleNameInput.addEventListener('change', generateQR);
            lastNameInput.addEventListener('change', generateQR);
        });
    </script>
</body>

</html>