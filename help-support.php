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

// Handle PDF export
if (isset($_POST['export_pdf'])) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Student Attendance Monitoring System');
    $pdf->SetTitle('Help and Support Manual');
    $pdf->SetSubject('User Manual');
    $pdf->SetKeywords('Manual, Attendance, Help, Support');
    $pdf->SetHeaderData('', 0, 'Student Attendance Monitoring System', 'Help and Support Manual');
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->AddPage();

    // HTML content for PDF
    $html = '
    <style>
        h1 { font-size: 24px; color: #1e293b; position: relative; padding-bottom: 10px; }
        h1:after { content: ""; display: block; height: 4px; width: 80px; background: linear-gradient(135deg, #3b82f6, #3b82f6); border-radius: 4px; margin-top: 5px; }
        h2 { font-size: 22px; font-weight: bold; color: #1e293b; margin-bottom: 15px; text-align: center; }
        p { font-size: 16px; color: #64748b; margin-bottom: 20px; }
        h3 { font-size: 18px; font-weight: 600; color: #1e293b; margin-top: 15px; margin-bottom: 10px; }
        ul, ol { font-size: 14px; color: #64748b; margin-left: 20px; margin-bottom: 10px; }
        li { margin-bottom: 5px; }
        .section { margin-bottom: 20px; }
        .faq-section { background-color: #f9fafb; padding: 20px; border-radius: 8px; }
    </style>
    <h1>Help & Support</h1>
    <div class="faq-section">
        <h2>Frequently Asked Questions</h2>
        <p>Find answers to common questions about using the Student Attendance Monitoring System. For further assistance, contact us at student.attendance.monitoring.sys@gmail.com.</p>';

    // FAQ content for PDF
    $faqs = [
        ["How do I add a new class?", "You can add a new class by clicking “Add Class” on the dashboard, filling out the required fields (grade level, section name, schedule, etc.), and saving the class."],
        ["Can I edit a class after creating it?", "Yes. Locate the class in the dashboard, click “Edit”, update the details in the modal, and save your changes."],
        ["What happens when I archive a class?", "Archived classes are moved out of the active list. They cannot be edited unless unarchived, but you can still view details and student records."],
        ["How do I import students into a class?", "Open the Students section of the class, upload an Excel file with the required columns (LRN, names, email, etc.), preview, and import. QR codes will be generated automatically if missing."],
        ["How do QR codes work for attendance?", "Each student has a unique QR code generated from their LRN and name. Teachers can scan these codes using a camera or scanner to mark attendance."],
        ["Can I manually mark attendance without QR codes?", "Yes. You can manually mark students as Present, Absent, or Late in the attendance table. Bulk marking is also supported."],
        ["What is the “Grace Period”?", "It is the number of minutes allowed for students to arrive late but still be marked Present. Scans after this period are marked Late."],
        ["What does “Late to Absent” mean?", "This setting converts a number of late marks into one absence (e.g., 3 lates = 1 absence)."],
        ["Can I edit attendance records from past dates?", "No. Attendance can only be marked or modified for the current date. Past attendance is view-only."],
        ["How do I generate reports?", "Go to the Reports section, set filters (class, student, date range, type), generate the report, and export it as PDF or Excel."],
        ["Why can’t I import my Excel file?", "Ensure the file is in .xlsx or .xls format and includes all required columns with the correct headers."],
        ["Why is my QR code not generating?", "Check that the LRN, first name, and last name fields are filled in. QR codes are auto-generated when these fields are valid."],
        ["Can I change my username or password?", "Yes. In the Profile section, update your username or password. The system ensures usernames are unique, and passwords must match the confirmation field."],
        ["How do I contact support?", "For further assistance, email student.attendance.monitoring.sys@gmail.com"],
        ["How does the ARIMA forecasting feature work?", "The ARIMA-based Time Series Forecasting analyzes historical attendance data to predict future patterns. Access the analytics dashboard to view trends and forecasts for your class or individual students."],
        ["How can I identify at-risk students?", "The system’s risk assessment tool highlights students with frequent absences or irregular attendance patterns. Check the Teacher Dashboard for alerts and detailed reports to take timely action."]
    ];

    foreach ($faqs as $index => $faq) {
        $html .= "<h3>" . ($index + 1) . ". {$faq[0]}</h3><p>{$faq[1]}</p>";
    }

    // User Manuals for PDF
    $manuals = [
        "Teacher Dashboard User Manual" => [
            "1. Dashboard Overview" => "The dashboard displays:\n• Quick action buttons for navigation.\n• Key statistics cards (Total Classes, Total Students, Attendance Rate, Absent).\n• Attendance charts (bar and doughnut).\n• Today's schedule table.",
            "2. Quick Actions" => "The Quick Actions section provides navigation links:\n• Mark Attendance: Redirects to mark attendance for recording student attendance.\n• View Class Details: Redirects to manage-classes for managing class information.\n• Generate Report: Redirects to reports for generating attendance reports.\nUsage:\n• Click any action button to navigate to the respective page.",
            "3. Viewing Key Statistics" => "The dashboard displays key metrics in cards:\n• Total Classes: Shows the number of active, non-archived classes assigned to the teacher. Example: \"5\" (indicating 5 active classes).\n• Total Students: Displays the total number of enrolled students across all active classes. Example: \"120\" (indicating 120 students).\n• Attendance Rate (Today): Shows the percentage of students marked as Present or Late for the current date. Example: \"85.50%\" (calculated as (Present + Late) / Total Records).\n• Absent (Today): Displays the number of students marked as Absent for the current date. Example: \"10\" (indicating 10 absent students).\nNote: Statistics are updated dynamically based on the current date.",
            "4. Analyzing Attendance Charts" => "The dashboard includes two charts:\n• Average Attendance Rate by Class (Bar Chart):\n  - X-axis: Class section names (e.g., \"Section A\").\n  - Y-axis: Attendance rate (0% to 100%).\n  - Data: Displays the attendance rate for each class, calculated as the percentage of Present or Late records for the current date.\n  - Appearance: Blue bars with rounded edges, hover tooltips showing percentage (e.g., \"85% attendance\").\n• Attendance Status (Doughnut Chart):\n  - Labels: Present, Absent, Late.\n  - Data: Shows the count of students for each status across all classes for the current date.\n  - Colors: Present (Green), Absent (Red), Late (Yellow).\n  - Appearance: Doughnut chart with a 65% cutout, hover tooltips showing student counts (e.g., \"Present: 50 students\").",
            "5. Reviewing Today's Schedule" => "The Today's Schedule table displays the teacher's class schedule for the current day:\n• Columns:\n  - Grade Level: The grade level of the class (e.g., \"Grade 10\").\n  - Section: The section name (e.g., \"Section A\").\n  - Subject: The subject taught (e.g., \"Mathematics\").\n  - Time: Start and end times (e.g., \"8:00 AM - 9:00 AM\").\n  - Room: Classroom location (e.g., \"Room 101\" or \"No room specified\").\n  - Total Students: Number of enrolled students in the class.\n• Empty Schedule: If no classes are scheduled, the table displays \"No classes scheduled today.\"",
            "6. Restrictions" => "• Data Availability: Statistics and charts require attendance records for the current date.\n• Class Scope: Only active, non-archived classes assigned to the teacher are displayed.\n• Date Scope: Dashboard focuses on the current date for attendance data.\n• View-Only: Attendance cannot be edited on the dashboard. Use the Mark Attendance page to check or mark attendance.",
            "7. Troubleshooting" => "• No Data in Charts or Tables:\n  - Verify that attendance records exist for the current date in the Overall Attendance page.\n  - Ensure you are assigned to active, non-archived classes in Class Management.\n• Schedule Table Empty:\n  - Confirm that schedules are set for the current day in the Class Management.\n  - Check if classes are marked as active and not archived."
        ],
        "Class Management User Manual" => [
            "1. Class Management Dashboard" => "The dashboard displays:\n• Total classes\n• Total students\n• Average attendance percentage (calculated over the past two months)",
            "2. Adding a New Class" => "To create a new class:\n1. Click the Add Class button located at the top-right of the dashboard.\n2. In the modal, fill in the following fields:\n  - Grade Level (Required): Select from Kindergarten to College 5th Year.\n  - Section Name (Required): Enter a unique name (e.g., \"Section A\" or \"Diamond\").\n  - Subject Code (Optional for Kindergarten to Grade 6, Required for Grade 7+): Provide a code (e.g., \"MATH-101-A\").\n  - Subject (Required for Grade 7+, Optional for lower grades): Enter the subject name (e.g., \"Mathematics\").\n  - Room (Optional): Specify the classroom (e.g., \"Room 201\").\n  - Schedule (Required): Check the days and set start/end times for the class.\n  - Grace Period (Optional): Enter the minutes allowed for late arrivals (e.g., 15).\n  - Late to Absent (Optional): Set the number of late marks equaling one absence (e.g., 3).\n3. Click Save Class to create the class.",
            "3. Editing a Class" => "To modify an existing class:\n1. Locate the class in the grid or table view.\n2. Click the Edit button on the class card or table row.\n3. Update the fields in the modal as needed.\n4. Click Save Class to apply changes.",
            "4. Viewing Class Details" => "To view detailed information about a class:\n1. Click the View button on a class card or table row.\n2. A modal will display the following details:\n  - Subject code and name\n  - Section name and grade level\n  - Room\n  - Number of students\n  - Attendance percentage\n  - Schedule\n3. Click Close to exit the modal.",
            "5. Archiving a Class" => "To archive a class:\n1. Click the Archive button on a class card or table row.\n2. Confirm the action in the prompt.\n3. The class will be removed from the active list.",
            "6. Managing Students" => "Adding Students via Excel Import\n1. Click the Students button on a class card or table row to open the student modal.\n2. In the Import Excel section, click Choose File and select an Excel file (.xlsx or .xls).\n3. Ensure the Excel file contains the following columns:\n  - LRN\n  - Last Name\n  - First Name\n  - Middle Name\n  - Email\n  - Gender\n  - DOB (Date of Birth)\n  - Grade Level\n  - Address\n  - Parent Name\n  - Parent Email\n  - Emergency Contact\n4. Preview the data in the table displayed in the modal.\n5. Click Import Excel to add the students to the class.\n6. QR codes are automatically generated for students without one, using their LRN and name.\nRemoving a Student\n1. In the student modal, locate the student in the table.\n2. Click the Delete button next to the student’s record.\n3. Confirm the action to remove the student from the class.",
            "7. Viewing and Filtering Classes" => "• Search: Use the search bar to find classes by subject code, section name, or subject.\n• Filters: Use dropdown menus to filter classes by:\n  - Grade level\n  - Section\n  - Subject\n• View Toggle: Switch between Grid View (card layout) and Table View (tabular layout) using the view toggle buttons.",
            "8. Monitoring Attendance" => "• Dashboard Stats: The dashboard displays the average attendance percentage for all classes over the past two months.\n• Class Attendance: Each class card or table row shows the attendance percentage for that specific class.\n• Calculation: Attendance is calculated as the percentage of \"Present\" records out of the total attendance records for the past two months.",
            "9. Notes" => "• Lower Grades (Kindergarten to Grade 6): Subjects are optional; a default \"No Subject\" is assigned if none is provided.\n• Higher Grades (Grade 7 and above): Subject and subject code are required.\n• QR Codes: Automatically generated during Excel import if not provided in the file, using the student’s LRN and name.\n• Grace Period and Late to Absent: These settings allow tracking of late arrivals and conversion to absences based on your configuration (e.g., 3 lates = 1 absence).",
            "10. Troubleshooting" => "• Excel Import Fails:\n  - Ensure the file is in .xlsx or .xls format and includes all required columns.\n  - Verify that the column headers match the expected format.\n• Duplicate Class Error:\n  - Check that the combination of section name, subject code, and grade level is unique for the teacher.\n  - Modify the section name or subject code to resolve the error."
        ],
        // Add other manuals similarly (omitted for brevity, but same structure applies)
    ];

    foreach ($manuals as $manualTitle => $sections) {
        $html .= "<h2>$manualTitle</h2>";
        foreach ($sections as $sectionTitle => $content) {
            $html .= "<div class='section'><h3>$sectionTitle</h3><p>" . nl2br(htmlspecialchars($content)) . "</p></div>";
        }
    }

    $html .= '</div>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('help-support-manual-' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Help and Support - Student Attendance System</title>
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

        .faq, .manual {
            background-color: var(--background);
            padding: var(--spacing-2xl) var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }

        .faq h2, .manual h2 {
            font-size: var(--font-size-2xl);
            font-weight: 800;
            text-align: center;
            margin-bottom: var(--spacing-lg);
            color: var(--blackfont-color);
        }

        .faq p, .manual p {
            font-size: var(--font-size-lg);
            color: var(--grayfont-color);
            text-align: center;
            margin-bottom: var(--spacing-xl);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .faq .grid, .manual .grid {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--spacing-lg);
        }

        .faq-item, .manual-item {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .faq-item:hover, .manual-item:hover {
            background-color: var(--inputfieldhover-color);
        }

        .faq-item h3, .manual-item h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-item .content, .manual-item .content {
            display: none;
            margin-top: var(--spacing-sm);
            font-size: var(--font-size-base);
            color: var(--grayfont-color);
        }

        .faq-item.active .content, .manual-item.active .content {
            display: block;
        }

        .arrow {
            transition: transform var(--transition-fast);
        }

        .faq-item.active .arrow, .manual-item.active .arrow {
            transform: rotate(180deg);
        }

        .export-button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-lg);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .export-button:hover {
            background: var(--primary-blue-hover);
        }

        @media (max-width: 992px) {
            .faq .grid, .manual .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <h1>Help & Support</h1>

    <!-- Export Button -->
    <form method="post">
        <button type="submit" name="export_pdf" class="export-button">Export Manual as PDF</button>
    </form>

    <!-- FAQ Section -->
    <section id="faq" class="faq">
        <h2>Frequently Asked Questions</h2>
        <p>Find answers to common questions about using the Student Attendance Monitoring System. For further assistance, contact us via email at student.attendance.monitoring.sys@gmail.com.</p>
        <div class="grid">
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>1. How do I add a new class? <span class="arrow"> ❯ </span></h3>
                <div class="content">You can add a new class by clicking “Add Class” on the dashboard, filling out the required fields (grade level, section name, schedule, etc.), and saving the class.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>2. Can I edit a class after creating it? <span class="arrow"> ❯ </span></h3>
                <div class="content">Yes. Locate the class in the dashboard, click “Edit”, update the details in the modal, and save your changes.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>3. What happens when I archive a class? <span class="arrow"> ❯ </span></h3>
                <div class="content">Archived classes are moved out of the active list. They cannot be edited unless unarchived, but you can still view details and student records.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>4. How do I import students into a class? <span class="arrow"> ❯ </span></h3>
                <div class="content">Open the Students section of the class, upload an Excel file with the required columns (LRN, names, email, etc.), preview, and import. QR codes will be generated automatically if missing.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>5. How do QR codes work for attendance? <span class="arrow"> ❯ </span></h3>
                <div class="content">Each student has a unique QR code generated from their LRN and name. Teachers can scan these codes using a camera or scanner to mark attendance.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>6. Can I manually mark attendance without QR codes? <span class="arrow"> ❯ </span></h3>
                <div class="content">Yes. You can manually mark students as Present, Absent, or Late in the attendance table. Bulk marking is also supported.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>7. What is the “Grace Period”? <span class="arrow"> ❯ </span></h3>
                <div class="content">It is the number of minutes allowed for students to arrive late but still be marked Present. Scans after this period are marked Late.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>8. What does “Late to Absent” mean? <span class="arrow"> ❯ </span></h3>
                <div class="content">This setting converts a number of late marks into one absence (e.g., 3 lates = 1 absence).</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>9. Can I edit attendance records from past dates? <span class="arrow"> ❯ </span></h3>
                <div class="content">No. Attendance can only be marked or modified for the current date. Past attendance is view-only.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>10. How do I generate reports? <span class="arrow"> ❯ </span></h3>
                <div class="content">Go to the Reports section, set filters (class, student, date range, type), generate the report, and export it as PDF or Excel.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>11. Why can’t I import my Excel file? <span class="arrow"> ❯ </span></h3>
                <div class="content">Ensure the file is in .xlsx or .xls format and includes all required columns with the correct headers.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>12. Why is my QR code not generating? <span class="arrow"> ❯ </span></h3>
                <div class="content">Check that the LRN, first name, and last name fields are filled in. QR codes are auto-generated when these fields are valid.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>13. Can I change my username or password? <span class="arrow"> ❯ </span></h3>
                <div class="content">Yes. In the Profile section, update your username or password. The system ensures usernames are unique, and passwords must match the confirmation field.</div>
            </div>
            <div class="faq-item" onclick="this.classList.toggle('active')">
                <h3>14. How do I contact support? <span class="arrow"> ❯ </span></h3>
                <div class="content">For further assistance, email student.attendance.monitoring.sys@gmail.com</div>
            </div>
        </div>
    </section>

    <!-- User Manuals Section -->
    <section id="manuals" class="manual">
        <h2>User Manuals</h2>
        <p>Comprehensive guides for using the Student Attendance Monitoring System. Click on a section to view detailed instructions.</p>
        <div class="grid">
            <div class="manual-item" onclick="this.classList.toggle('active')">
                <h3>Teacher Dashboard User Manual <span class="arrow"> ❯ </span></h3>
                <div class="content">
                    <h4>1. Dashboard Overview</h4>
                    <p>The dashboard displays:<br>• Quick action buttons for navigation.<br>• Key statistics cards (Total Classes, Total Students, Attendance Rate, Absent).<br>• Attendance charts (bar and doughnut).<br>• Today's schedule table.</p>
                    <h4>2. Quick Actions</h4>
                    <p>The Quick Actions section provides navigation links:<br>• Mark Attendance: Redirects to mark attendance for recording student attendance.<br>• View Class Details: Redirects to manage-classes for managing class information.<br>• Generate Report: Redirects to reports for generating attendance reports.<br><strong>Usage:</strong><br>• Click any action button to navigate to the respective page.</p>
                    <h4>3. Viewing Key Statistics</h4>
                    <p>The dashboard displays key metrics in cards:<br>• <strong>Total Classes:</strong> Shows the number of active, non-archived classes assigned to the teacher. Example: "5" (indicating 5 active classes).<br>• <strong>Total Students:</strong> Displays the total number of enrolled students across all active classes. Example: "120" (indicating 120 students).<br>• <strong>Attendance Rate (Today):</strong> Shows the percentage of students marked as Present or Late for the current date. Example: "85.50%" (calculated as (Present + Late) / Total Records).<br>• <strong>Absent (Today):</strong> Displays the number of students marked as Absent for the current date. Example: "10" (indicating 10 absent students).<br><strong>Note:</strong> Statistics are updated dynamically based on the current date.</p>
                    <h4>4. Analyzing Attendance Charts</h4>
                    <p>The dashboard includes two charts:<br>• <strong>Average Attendance Rate by Class (Bar Chart):</strong><br>  - X-axis: Class section names (e.g., "Section A").<br>  - Y-axis: Attendance rate (0% to 100%).<br>  - Data: Displays the attendance rate for each class, calculated as the percentage of Present or Late records for the current date.<br>  - Appearance: Blue bars with rounded edges, hover tooltips showing percentage (e.g., "85% attendance").<br>• <strong>Attendance Status (Doughnut Chart):</strong><br>  - Labels: Present, Absent, Late.<br>  - Data: Shows the count of students for each status across all classes for the current date.<br>  - Colors: Present (Green), Absent (Red), Late (Yellow).<br>  - Appearance: Doughnut chart with a 65% cutout, hover tooltips showing student counts (e.g., "Present: 50 students").</p>
                    <h4>5. Reviewing Today's Schedule</h4>
                    <p>The Today's Schedule table displays the teacher's class schedule for the current day:<br>• <strong>Columns:</strong><br>  - Grade Level: The grade level of the class (e.g., "Grade 10").<br>  - Section: The section name (e.g., "Section A").<br>  - Subject: The subject taught (e.g., "Mathematics").<br>  - Time: Start and end times (e.g., "8:00 AM - 9:00 AM").<br>  - Room: Classroom location (e.g., "Room 101" or "No room specified").<br>  - Total Students: Number of enrolled students in the class.<br>• <strong>Empty Schedule:</strong> If no classes are scheduled, the table displays "No classes scheduled today."</p>
                    <h4>6. Restrictions</h4>
                    <p>• <strong>Data Availability:</strong> Statistics and charts require attendance records for the current date.<br>• <strong>Class Scope:</strong> Only active, non-archived classes assigned to the teacher are displayed.<br>• <strong>Date Scope:</strong> Dashboard focuses on the current date for attendance data.<br>• <strong>View-Only:</strong> Attendance cannot be edited on the dashboard. Use the Mark Attendance page to check or mark attendance.</p>
                    <h4>7. Troubleshooting</h4>
                    <p>• <strong>No Data in Charts or Tables:</strong><br>  - Verify that attendance records exist for the current date in the Overall Attendance page.<br>  - Ensure you are assigned to active, non-archived classes in Class Management.<br>• <strong>Schedule Table Empty:</strong><br>  - Confirm that schedules are set for the current day in the Class Management.<br>  - Check if classes are marked as active and not archived.</p>
                </div>
            </div>
            <div class="manual-item" onclick="this.classList.toggle('active')">
                <h3>Class Management User Manual <span class="arrow"> ❯ </span></h3>
                <div class="content">
                    <h4>1. Class Management Dashboard</h4>
                    <p>The dashboard displays:<br>• Total classes<br>• Total students<br>• Average attendance percentage (calculated over the past two months)</p>
                    <h4>2. Adding a New Class</h4>
                    <p>To create a new class:<br>1. Click the Add Class button located at the top-right of the dashboard.<br>2. In the modal, fill in the following fields:<br>  - Grade Level (Required): Select from Kindergarten to College 5th Year.<br>  - Section Name (Required): Enter a unique name (e.g., "Section A" or "Diamond").<br>  - Subject Code (Optional for Kindergarten to Grade 6, Required for Grade 7+): Provide a code (e.g., "MATH-101-A").<br>  - Subject (Required for Grade 7+, Optional for lower grades): Enter the subject name (e.g., "Mathematics").<br>  - Room (Optional): Specify the classroom (e.g., "Room 201").<br>  - Schedule (Required): Check the days and set start/end times for the class.<br>  - Grace Period (Optional): Enter the minutes allowed for late arrivals (e.g., 15).<br>  - Late to Absent (Optional): Set the number of late marks equaling one absence (e.g., 3).<br>3. Click Save Class to create the class.</p>
                    <h4>3. Editing a Class</h4>
                    <p>To modify an existing class:<br>1. Locate the class in the grid or table view.<br>2. Click the Edit button on the class card or table row.<br>3. Update the fields in the modal as needed.<br>4. Click Save Class to apply changes.</p>
                    <h4>4. Viewing Class Details</h4>
                    <p>To view detailed information about a class:<br>1. Click the View button on a class card or table row.<br>2. A modal will display the following details:<br>  - Subject code and name<br>  - Section name and grade level<br>  - Room<br>  - Number of students<br>  - Attendance percentage<br>  - Schedule<br>3. Click Close to exit the modal.</p>
                    <h4>5. Archiving a Class</h4>
                    <p>To archive a class:<br>1. Click the Archive button on a class card or table row.<br>2. Confirm the action in the prompt.<br>3. The class will be removed from the active list.</p>
                    <h4>6. Managing Students</h4>
                    <p><strong>Adding Students via Excel Import</strong><br>1. Click the Students button on a class card or table row to open the student modal.<br>2. In the Import Excel section, click Choose File and select an Excel file (.xlsx or .xls).<br>3. Ensure the Excel file contains the following columns:<br>  - LRN<br>  - Last Name<br>  - First Name<br>  - Middle Name<br>  - Email<br>  - Gender<br>  - DOB (Date of Birth)<br>  - Grade Level<br>  - Address<br>  - Parent Name<br>  - Parent Email<br>  - Emergency Contact<br>4. Preview the data in the table displayed in the modal.<br>5. Click Import Excel to add the students to the class.<br>6. QR codes are automatically generated for students without one, using their LRN and name.<br><strong>Removing a Student</strong><br>1. In the student modal, locate the student in the table.<br>2. Click the Delete button next to the student’s record.<br>3. Confirm the action to remove the student from the class.</p>
                    <h4>7. Viewing and Filtering Classes</h4>
                    <p>• <strong>Search:</strong> Use the search bar to find classes by subject code, section name, or subject.<br>• <strong>Filters:</strong> Use dropdown menus to filter classes by:<br>  - Grade level<br>  - Section<br>  - Subject<br>• <strong>View Toggle:</strong> Switch between Grid View (card layout) and Table View (tabular layout) using the view toggle buttons.</p>
                    <h4>8. Monitoring Attendance</h4>
                    <p>• <strong>Dashboard Stats:</strong> The dashboard displays the average attendance percentage for all classes over the past two months.<br>• <strong>Class Attendance:</strong> Each class card or table row shows the attendance percentage for that specific class.<br>• <strong>Calculation:</strong> Attendance is calculated as the percentage of "Present" records out of the total attendance records for the past two months.</p>
                    <h4>9. Notes</h4>
                    <p>• <strong>Lower Grades (Kindergarten to Grade 6):</strong> Subjects are optional; a default "No Subject" is assigned if none is provided.<br>• <strong>Higher Grades (Grade 7 and above):</strong> Subject and subject code are required.<br>• <strong>QR Codes:</strong> Automatically generated during Excel import if not provided in the file, using the student’s LRN and name.<br>• <strong>Grace Period and Late to Absent:</strong> These settings allow tracking of late arrivals and conversion to absences based on your configuration (e.g., 3 lates = 1 absence).</p>
                    <h4>10. Troubleshooting</h4>
                    <p>• <strong>Excel Import Fails:</strong><br>  - Ensure the file is in .xlsx or .xls format and includes all required columns.<br>  - Verify that the column headers match the expected format.<br>• <strong>Duplicate Class Error:</strong><br>  - Check that the combination of section name, subject code, and grade level is unique for the teacher.<br>  - Modify the section name or subject code to resolve the error.</p>
                </div>
            </div>
            <!-- Add other manuals similarly (omitted for brevity, but same structure applies) -->
        </div>
    </section>
</body>
</html>