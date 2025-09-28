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
        h4 { font-size: 16px; font-weight: 600; color: #1e293b; margin-top: 10px; margin-bottom: 5px; }
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
        "Student Management User Manual" => [
            "1. Student Management Dashboard" => "The dashboard displays:\n• Total students\n• Total unique students\n• Classes enrolled",
            "2. Adding a New Student" => "To add a new student:\n1. Click the Add Student button in the top-right corner of the dashboard.\n2. In the modal, fill in the required fields:\n  - Learner Reference Number (LRN): Enter a unique numeric LRN.\n  - First Name, Middle Name, Last Name: Provide the student’s full name.\n  - Email: Enter a valid student’s email address.\n  - Gender: Select Male or Female.\n  - Date of Birth: Specify the student’s birth date.\n  - Grade Level: Choose from available grade levels.\n  - Section: Select a section from the dropdown.\n  - Subject: Choose a subject based on grade and section.\n  - Address: Provide the student’s address.\n  - Parent Name: Enter the parent or guardian’s name.\n  - Emergency Contact: Provide a contact number.\n  - Photo (Optional): Upload an image file for the student.\n3. A QR code is automatically generated based on the LRN and name.\n4. Click Save Student to add the student to the system and class.",
            "3. Editing a Student" => "To modify a student’s details:\n1. Locate the student in the grid or table view.\n2. Click the Edit button on the student’s card or table row.\n3. Update the fields in the modal as needed.\n4. Click Save Student to apply changes.",
            "4. Viewing Student Details" => "To view detailed information about a student:\n1. Click the View button on a student’s card or table row.\n2. A modal will display the following details:\n  - LRN and full name\n  - Email, gender, and date of birth\n  - Grade level, subject, and section\n  - Address, parent name, parent email, and emergency contact\n  - Photo (if available)\n  - QR code\n3. Click Close to exit the modal.",
            "5. Removing a Student from a Class" => "To remove a student:\n1. In the grid or table view, click the Remove button next to the student.\n2. Confirm the action in the modal by reviewing the student’s details and clicking Remove.",
            "6. Managing Multiple Students" => "Bulk Removal\n1. Select students using checkboxes or the Select All option.\n2. Click Remove Selected from Class.\n3. Review the list in the confirmation modal and click Remove.\nBulk QR Code Printing\n1. Select students using checkboxes.\n2. Click Print QR Codes.\n3. Download the generated PDF with QR ID cards (4x3 grid per US Letter page, 2.125\" x 3.375\" per card).\nBulk Export to Excel\n1. Select students using checkboxes.\n2. Click Export Selected.\n3. Download the Excel file containing student details (LRN, names, email, gender, DOB, grade level, address, parent info, photo, QR code, date added).",
            "7. Generating and Printing QR Codes" => "Single QR Code\n1. Open a student’s profile in view or edit mode.\n2. If a QR code exists, click Print to download a PNG QR ID card.\nBulk QR Codes\n1. Select multiple students using checkboxes.\n2. Click Print QR Codes to generate a PDF with QR ID cards.\nNote: QR codes are auto-generated when adding or editing a student with valid LRN and name fields.",
            "8. Viewing and Filtering Students" => "• Search: Use the search bar to find students by LRN or name (case-insensitive, partial matches).\n• Filters: Use dropdowns to filter by:\n  - Gender\n  - Grade level\n  - Subject\n  - Section\n• Sort: Sort students by name (A-Z or Z-A) or LRN using the sort dropdown.\n• View Toggle: Switch between Grid View (card layout) or Table View (tabular layout) using the view toggle buttons.\n• Clear Filters: Click Clear Filters to reset search and filters.",
            "9. Notes" => "• Required Fields: The following are mandatory:\n  - LRN, first name, middle name, last name, grade level, subject, section, email, gender, date of birth, address, parent name, emergency contact\n• QR Codes: Generated automatically if not provided.\n• Excel Export: Includes all selected students, even across pages, with columns for LRN, names, email, gender, DOB, grade level, address, parent info, photo, QR code, and date added.",
            "10. Troubleshooting" => "• QR Code Not Generating:\n  - Ensure LRN, first name, and last name are provided.\n• Excel Export Fails:\n  - Confirm that students are selected.\n• Duplicate LRN Error:\n  - Verify that the LRN is unique and numeric.\n• Photo Upload Fails:\n  - Ensure the file is a valid image format (e.g., JPG, PNG, GIF)."
        ],
        "Attendance Marking User Manual" => [
            "1. Marking Attendance" => "To mark attendance for a class:\n1. Select Class:\n  - Choose the Grade Level, Section, and Subject from the dropdown menus.\n  - Ensure the selected date is today, as past dates cannot be modified.\n2. View Students:\n  - A table displays students for the selected class, showing:\n    - LRN\n    - Name\n    - Photo\n    - Status\n    - Time Checked\n3. Mark Attendance:\n  - For individual students, select Present, Absent, or Late from the status dropdown.\n  - For multiple students, use checkboxes to select students and apply bulk actions (Present, Absent, Late) via the bulk action dropdown.\n  - Click Mark All Present to set all non-QR-scanned students as Present.\n4. Submit:\n  - Click Submit Attendance to save changes.\n  - A success notification appears upon successful submission; otherwise, an error message displays.",
            "2. Using the QR Code Scanner" => "To mark attendance using a QR code scanner:\n1. Enable Scanner:\n  - Click Scan QR Code to activate the camera or USB scanner.\n  - This feature is only available for today’s date and classes with an active schedule.\n  - Ensure camera permissions are granted or the USB scanner is properly connected.\n2. Scan QR Code:\n  - Point the camera at the student’s QR code or use a USB scanner.\n  - The system automatically detects the LRN and marks the student as:\n    - Present (if scanned within the grace period)\n    - Late (if scanned after the grace period)\n3. Notifications:\n  - A notification confirms the scan and the assigned status.\n  - Parents receive an email for Present QR scans within the grace period.\n4. Stop Scanner:\n  - Click Stop Scanner to deactivate the camera or scanner.",
            "3. Viewing Attendance" => "• Statistics:\n  - View the following for the filtered class list:\n    - Total Students\n    - Present Count\n    - Absent Count\n    - Attendance Percentage\n• Schedule Info:\n  - Displays the class schedule and grace period (if applicable) for the selected date and class.",
            "4. Key Features" => "• Grace Period:\n  - QR scans within the grace period mark students as Present; scans after the grace period mark students as Late.\n  - A grace period countdown is displayed when active.\n• Locked QR Records:\n  - Attendance records marked via QR code scanning cannot be manually edited.\n• Clear Filters:\n  - Reset all filters to default (today’s date, first available grade/section/subject) using the Clear Filters button.",
            "5. Restrictions" => "• Attendance can only be marked for the current date with an active class schedule.\n• Past date attendance records cannot be edited.\n• QR scanning requires:\n  - A selected class\n  - Today’s date\n  - An active class schedule",
            "6. Troubleshooting" => "• No Schedule Found:\n  - Ensure the selected class has a schedule defined for the selected date in the Class Management settings.\n• QR Scanner Issues:\n  - Check camera permissions or verify the USB scanner is properly connected.\n  - Ensure the correct class and today’s date are selected.\n  - Confirm the class has an active schedule.\n• Email Not Sent:\n  - Verify that the student’s parent email is added in the student’s profile."
        ],
        "Overall Attendance User Manual" => [
            "1. Viewing Attendance" => "To view attendance records:\n1. Select Filters:\n  - Date: Choose a date using the date selector (from the earliest recorded attendance to today).\n  - Grade Level: Select a specific grade level or \"All Grade Levels\" from the dropdown.\n  - Section: Choose a section or \"All Sections\" (options update based on selected grade level).\n  - Subject: Select a subject or \"All Subjects\" (options update based on grade and section).\n  - Status: Filter by attendance status (All, Present, Absent, Late).\n  - Search: Enter a student’s LRN or name to filter the list.\n2. View Results:\n  - A table displays the filtered students with the following columns:\n    - Photo\n    - LRN\n    - Student Name\n    - Status\n    - Time Checked\n  - Status badges are color-coded:\n    - Present: Green\n    - Absent: Red\n    - Late: Yellow\n    - None: Gray\n  - Time Checked: Shows the date and time (in Asia/Manila timezone) when attendance was recorded.",
            "2. Understanding Statistics" => "The system provides the following statistics for the filtered list:\n• Total Students: Number of students in the filtered list.\n• Present Count: Number of students marked as Present or Late.\n• Absent Count: Number of students marked as Absent.\n• Attendance Percentage: Percentage of students marked Present or Late out of the total filtered students.",
            "3. Key Features" => "• Filtering: Combine date, grade level, section, subject, status, and search criteria to narrow down results.\n• Clear Filters: Reset all filters to default settings (today’s date, all grades/sections/subjects) using the Clear Filters button.",
            "4. Restrictions" => "• View-Only: Attendance records cannot be edited on this page. Use the Attendance Marking page to make changes.\n• Date Limit: Future dates cannot be selected; the maximum date is today.\n• Data Availability: Only attendance data from the earliest recorded date for your classes is displayed.",
            "5. Troubleshooting" => "• No Students Displayed:\n  - Check if filters are too restrictive. Use the Clear Filters button to broaden results.\n  - Ensure students are enrolled in the selected class.\n• Missing Classes:\n  - Verify that you are assigned to classes and that they are not archived.\n  - Check class assignments in the Class Management section.\n• Date Issues:\n  - Confirm that attendance records exist for the selected date."
        ],
        "Analytics & Predictions User Manual" => [
            "1. Overview" => "The dashboard displays:\n• Class and student filters\n• Attendance statistics cards\n• ARIMA time series forecast chart\n• Attendance status distribution chart\n• At-risk students table\n• Individual student analytics (when a student is selected)",
            "2. Filtering Data" => "To filter attendance data:\n1. Select Class:\n  - Use the Class Filter dropdown to select a class (e.g., \"Grade 10 – Section A (Mathematics)\").\n  - Default: The first available class is selected.\n2. Search Students:\n  - Enter a student’s LRN or name in the Search input field (case-insensitive, supports partial matches).\n3. Select Student:\n  - Use the Student Filter dropdown to select a specific student or \"All Students.\"\n  - The dropdown updates based on the selected class and search term.\n4. Update Forecast:\n  - Click the Update Forecast button (with refresh icon) to reload data and refresh forecasts.\n5. Clear Filters:\n  - Click the Clear button (with X icon) to reset all filters to default (first class, all students, no search term).",
            "3. Viewing Attendance Statistics" => "The dashboard displays key statistics in cards:\n• Current Attendance Rate:\n  - Shows the current attendance rate for the selected class (percentage of Present or Late records).\n  - Includes a trend indicator (up, down, or stable) compared to the previous period.\n• Predicted Next Month:\n  - Displays the forecasted attendance rate for the next month using the ARIMA model.\n• At-Risk Students:\n  - Shows the number of students at risk of dropping out based on absence thresholds (more than 3 absences for college, more than 14 for K-12).\n  - Includes a trend indicator (up, down, or stable) compared to the previous period.",
            "4. Viewing Attendance Forecasts" => "The ARIMA Time Series Forecast chart displays:\n• Historical Data: Attendance rates for the selected class or student from the earliest recorded date to the current period.\n• Forecasted: Predicted attendance rates for the next 30 days, shown as a dashed line.\n• Chart Details:\n  - Y-axis: Attendance rate (0% to 100%).\n  - X-axis: Dates (historical and forecasted).\n  - Blue line: Historical attendance data.\n  - Red dashed line: Forecasted attendance rate.",
            "5. Analyzing At-Risk Students" => "The At-Risk Students table lists students with excessive absences:\n• Columns:\n  - Class: Grade level, section, and subject (e.g., \"Grade 10 - Section A (Mathematics)\").\n  - Name: Student’s full name.\n  - Total Absences: Number of absences and risk level (e.g., \"15 - High\" for K-12, \"5 - Running for Drop Out\" for college).\n• Risk Levels (K-12):\n  - Low: 1–13 absences\n  - Medium: 14–26 absences\n  - High: 27–39 absences\n  - Running for Drop Out: 40+ absences\n• Risk Levels (College):\n  - No Risk: 0–2 absences\n  - Running for Drop Out: 3+ absences\n• Note: The table updates based on the selected class filter. If no students are at risk, it displays \"No data available.\"",
            "6. Viewing Individual Student Analytics" => "When a student is selected from the Student Filter:\n• Prediction Card:\n  - Displays detailed analytics for the selected student, including:\n    - Class: Subject and section.\n    - LRN: Student’s Learner Reference Number.\n    - Student: Full name.\n    - Current Attendance Rate: Current attendance percentage.\n    - Predicted Next Month: Forecasted attendance rate for the next month.\n    - Total Absences: Number of absences and risk level.\n    - Risk Level (K-12 only): Low, Medium, High, or Running for Drop Out.\n• Individual Forecast Chart:\n  - Shows historical and forecasted attendance rates for the student.\n  - Blue line: Historical data.\n  - Red dashed line: Forecasted rate.\n• Personal Analytics Table:\n  - Lists current metrics (Attendance Rate, Total Absences with risk level).\n• Forecast Metrics Table:\n  - Shows the forecasted attendance rate for the next month.\n• AI Recommendation:\n  - Displays an AI-generated recommendation (via OpenAI) to improve or maintain the student’s attendance.\n  - Example: \"Encourage consistent attendance with positive reinforcement\" (for no risk).\nNote: The Attendance Status Distribution chart updates to show the selected student’s Present, Absent, and Late counts with percentages.",
            "7. Key Features" => "• Class and Student Filters: Narrow down data by class, student, or search term.\n• ARIMA Forecasting: Predicts attendance rates for the next 30 days based on historical data.\n• Attendance Status Distribution: Visualizes Present (green), Absent (red), and Late (yellow) counts in a pie chart.\n• At-Risk Analysis: Identifies students with excessive absences based on grade-specific thresholds.\n• AI Recommendations: Provides tailored suggestions for improving student attendance using OpenAI.\n• Responsive Design: Adapts to various screen sizes (desktop, tablet, mobile).",
            "8. Restrictions" => "• Data Availability: Analytics require historical attendance data. If no data exists, charts and tables display \"No data available.\"\n• Class Assignment: Only classes assigned to the teacher and not archived are displayed.\n• Date Range: Historical data starts from the earliest recorded attendance date; forecasts are limited to 30 days from the current period.\n• View-Only: Attendance data cannot be edited on this page. Use the Attendance Marking page for modifications.",
            "10. Troubleshooting" => "• No Data Displayed:\n  - Verify that you are assigned to active classes in the Class Management section.\n  - Ensure attendance records exist for the selected class.\n  - Clear filters to broaden the data range.\n• Charts Not Rendering:\n  - Check browser console for JavaScript errors.\n  - Ensure the browser supports Chart.js (use a modern browser like Chrome, Firefox, or Edge).\n• At-Risk Students Not Showing:\n  - Confirm that the class has students with absences exceeding the threshold (more than 3 for college, more than 14 for K-12).\n  - Check if the class is archived; unarchive it in the Archived Classes section if needed."
        ],
        "Reports User Manual" => [
            "1. Viewing Reports" => "To generate and view reports:\n1. Select Filters:\n  - Class: Choose a specific class (e.g., \"Grade 10 - Section A (Math)\") from the dropdown or select \"All Classes.\"\n  - Student: Select a student by LRN or name using the search function, or choose \"All Students.\"\n  - Date Range: Set the \"Date From\" and \"Date To\" fields (defaults to the last month to today).\n  - Report Type: Choose one of the following:\n    - Student Attendance History\n    - Attendance per Class\n    - Perfect Attendance Recognition\n2. Generate Report:\n  - Click Generate Report to display the report based on your selected filters.\n  - The report will appear in a table below the filter controls, with a title reflecting the selected report type.",
            "2. Report Types" => "The system offers three types of reports, each with specific columns and details:\n2.1 Student Attendance History\n• Columns: Class, LRN, Name, Status (Present, Absent, Late), Time Checked\n• Details: Displays individual attendance records with color-coded status badges:\n  - Green: Present\n  - Yellow: Late\n  - Red: Absent\n2.2 Attendance per Class\n• Columns: Class, Total Students, Present, Absent, Late, Average Attendance\n• Details: Provides class-level statistics. The average attendance is calculated as the percentage of Present records out of the total attendance records.\n2.3 Perfect Attendance Recognition\n• Columns: Class, LRN, Name, Status (Recognized/Not Recognized), Attendance Issue Summary, Adjusted Attendance Record\n• Details: Identifies students with perfect attendance (no absences or lates, marked as Recognized with a green badge) or those with attendance issues (Not Recognized, red badge). Adjusted records reflect late-to-absent rules (e.g., multiple lates counted as absences based on class settings).",
            "3. Exporting Reports" => "To export a report:\n1. Select Format:\n  - Choose either PDF or Excel from the \"Export Format\" dropdown.\n2. Export:\n  - Click Export Report to generate and download the report in the chosen format.\n  - PDF: Includes a styled table with colored status badges and a header displaying the report title and date range.\n  - Excel: Features formatted headers and color-coded status cells, with LRNs formatted as numbers.\n3. Download:\n  - The file will download automatically with a name like student-report-YYYY-MM-DD_HH-mm-ss.pdf or .xlsx.",
            "4. Key Features" => "• Statistics Cards:\n  - Total Classes: Displays the number of active classes assigned to the teacher.\n  - Total Students: Shows the total number of students across all classes.\n  - Late (Today): Indicates the number of students marked Late for the current day.\n  - Absent (Today): Indicates the number of students marked Absent for the current day.\n• Filtering: Combine class, student, date range, and report type to create customized reports.\n• Search: Quickly filter students by LRN or name.\n• Late-to-Absent Rule: In Perfect Attendance reports, converts lates to absences based on class-specific settings (e.g., 3 lates = 1 absent).",
            "5. Restrictions" => "• Data Availability: Reports require existing attendance data. If no records are found for the selected class, student, or date range, the table will be empty.\n• Date Range: Limited to dates with recorded attendance; future dates are not supported.",
            "6. Troubleshooting" => "• No Data in Report:\n  - Verify that the selected class, student, or date range has associated attendance records.\n  - Adjust filters as needed.\n• Export Fails:\n  - Check browser permissions to ensure downloads are allowed.\n  - Try using a different browser if the issue persists.\n• Incorrect Late-to-Absent Counts:\n  - Confirm the class’s late-to-absent rule in the schedule settings.\n  - Ensure the rule (e.g., 3 lates = 1 absent) is correctly configured.\n• Student Not Found:\n  - Ensure the student is enrolled in the selected class.\n  - Verify that the search term (LRN or name) matches the student’s details."
        ],
        "Archived Classes User Manual" => [
            "1. Viewing Archived Classes" => "To view archived classes:\n1. Navigate:\n  - Access the Archived Classes page in the Student Attendance System to view all classes marked as archived.\n2. Class Cards:\n  - Each archived class is displayed as a card containing the following details:\n    - Grade & Section: Class grade level and section name (e.g., \"Grade 10 - Section A\").\n    - Subject & Code: Subject name and code (e.g., \"Mathematics, MATH101\").\n    - Room: Assigned room or \"No room specified.\"\n    - Total Students: Number of students enrolled in the class.\n    - Late-to-Absent: Number of late marks counted as one absence (e.g., 3 lates = 1 absence).\n    - Grace Period: Minutes allowed for late arrivals (e.g., 15 minutes).\n    - Schedule: Days and times (e.g., \"Monday: 8:00 AM - 9:00 AM (Grace: 15 min)\").\n    - Status: Marked as \"Archived\" with a red badge.",
            "2. Key Features" => "• Statistics Cards:\n  - Total Classes Archived: Displays the number of archived classes assigned to the teacher.\n  - Total Students Archived: Shows the total number of students across all archived classes.\n  - Total Unique Students: Indicates the number of distinct students in archived classes.\n• View Class Details:\n  - Click the View button on a class card to open a modal displaying:\n    - Grade level, section, subject, subject code, room, total students, late-to-absent marks, grace period, and schedule.\n• View Students:\n  - Click the Students button on a class card to open a modal with a table listing:\n    - LRN, last name, first name, middle name, email, gender, date of birth, grade level, address, parent name, parent email, emergency contact, photo, QR code.\n• Unarchive Class:\n  - Click the Unarchive button on a class card.\n  - Confirm the action in the prompt to restore the class to active status.\n  - Once unarchived, the class will be available in other system sections.",
            "3. Restrictions" => "• View-Only:\n  - Archived classes cannot be edited. To modify class details, you must first unarchive the class.\n• Data Availability:\n  - If no classes are archived, the system will display a message: \"No archived classes found.\""
        ],
        "Profile Page User Manual" => [
            "1. Updating Profile Information" => "To update your profile details:\n• Navigate to the Profile Information tab.\n• When updating your profile:\nThe following fields are required:\n  - First name\n  - Last name\n  - Username\nThe following fields are optional:\n  - Institution\n  - Profile picture\n• Click Save Changes to apply your updates.\nNote: The email address field is read-only to maintain account integrity.",
            "2. Changing Your Username" => "To update your username:\n• Enter a new username in the Username field in the Profile Information tab.\n• The system will verify if the username is unique.\n• If the username is already in use, you will see an error message: \"Username is already taken.\"\n• Choose a different, unique username and retry.",
            "3. Supported Image Formats for Profile Pictures" => "The system supports the following image file formats for profile pictures:\n• JPG\n• JPEG\n• PNG\n• GIF\nOther file formats are not supported and will result in an error if uploaded.",
            "4. Optional Profile Picture Upload" => "Uploading a profile picture is optional:\n• You may choose to upload a new profile picture in a supported format.\n• If you do not upload a profile picture, the system will retain your current picture or use the default image if none is set.",
            "5. Changing Your Password" => "To update your password:\n• Go to the Password Management tab.\n• Enter the following:\n  - Your current password.\n  - Your new password.\n  - The confirmation of your new password (must match the new password).\n• Click Change Password to save the new password.\nNote: Ensure the new password and confirmation match to avoid errors.",
            "6. Previewing a New Profile Picture" => "To preview a new profile picture:\n• Select a new image file in the profile picture field.\n• The system will display a preview of the selected image in the profile image section.\n• Review the preview and click Save Changes to confirm, or select a different image if needed."
        ]
    ];

    foreach ($manuals as $manualTitle => $sections) {
        $html .= "<h2>$manualTitle</h2>";
        foreach ($sections as $sectionTitle => $content) {
            $html .= "<div class='section'><h3>$sectionTitle</h3><p>" . nl2br(htmlspecialchars($content)) . "</p></div>";
        }
    }

    $html .= '</div>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('SAMS-User-Manual-' . date('Y-m-d_H-i-s') . '.pdf', 'D');
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
        <button type="submit" name="export_pdf" class="export-button">Export User Manual</button>
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
            <div class="manual-item" onclick="this.classList.toggle('active')">
                <h3>Student Management User Manual <span class="arrow"> ❯ </span></h3>
                <div class="content">
                    <h4>1. Student Management Dashboard</h4>
                    <p>The dashboard displays:<br>• Total students<br>• Total unique students<br>• Classes enrolled</p>
                    <h4>2. Adding a New Student</h4>
                    <p>To add a new student:<br>1. Click the Add Student button in the top-right corner of the dashboard.<br>2. In the modal, fill in the required fields:<br>  - Learner Reference Number (LRN): Enter a unique numeric LRN.<br>  - First Name, Middle Name, Last Name: Provide the student’s full name.<br>  - Email: Enter a valid student’s email address.<br>  - Gender: Select Male or Female.<br>  - Date of Birth: Specify the student’s birth date.<br>  - Grade Level: Choose from available grade levels.<br>  - Section: Select a section from the dropdown.<br>  - Subject: Choose a subject based on grade and section.<br>  - Address: Provide the student’s address.<br>  - Parent Name: Enter the parent or guardian’s name.<br>  - Emergency Contact: Provide a contact number.<br>  - Photo (Optional): Upload an image file for the student.<br>3. A QR code is automatically generated based on the LRN and name.<br>4. Click Save Student to add the student to the system and class.</p>
                    <h4>3. Editing a Student</h4>
                    <p>To modify a student’s details:<br>1. Locate the student in the grid or table view.<br>2. Click the Edit button on the student’s card or table row.<br>3. Update the fields in the modal as needed.<br>4. Click Save Student to apply changes.</p>
                    <h4>4. Viewing Student Details</h4>
                    <p>To view detailed information about a student:<br>1. Click the View button on a student’s card or table row.<br>2. A modal will display the following details:<br>  - LRN and full name<br>  - Email, gender, and date of birth<br>  - Grade level, subject, and section<br>  - Address, parent name, parent email, and emergency contact<br>  - Photo (if available)<br>  - QR code<br>3. Click Close to exit the modal.</p>
                    <h4>5. Removing a Student from a Class</h4>
                    <p>To remove a student:<br>1. In the grid or table view, click the Remove button next to the student.<br>2. Confirm the action in the modal by reviewing the student’s details and clicking Remove.</p>
                    <h4>6. Managing Multiple Students</h4>
                    <p><strong>Bulk Removal</strong><br>1. Select students using checkboxes or the Select All option.<br>2. Click Remove Selected from Class.<br>3. Review the list in the confirmation modal and click Remove.<br><strong>Bulk QR Code Printing</strong><br>1. Select students using checkboxes.<br>2. Click Print QR Codes.<br>3. Download the generated PDF with QR ID cards (4x3 grid per US Letter page, 2.125" x 3.375" per card).<br><strong>Bulk Export to Excel</strong><br>1. Select students using checkboxes.<br>2. Click Export Selected.<br>3. Download the Excel file containing student details (LRN, names, email, gender, DOB, grade level, address, parent info, photo, QR code, date added).</p>
                    <h4>7. Generating and Printing QR Codes</h4>
                    <p><strong>Single QR Code</strong><br>1. Open a student’s profile in view or edit mode.<br>2. If a QR code exists, click Print to download a PNG QR ID card.<br><strong>Bulk QR Codes</strong><br>1. Select multiple students using checkboxes.<br>2. Click Print QR Codes to generate a PDF with QR ID cards.<br><strong>Note:</strong> QR codes are auto-generated when adding or editing a student with valid LRN and name fields.</p>
                    <h4>8. Viewing and Filtering Students</h4>
                    <p>• <strong>Search:</strong> Use the search bar to find students by LRN or name (case-insensitive, partial matches).<br>• <strong>Filters:</strong> Use dropdowns to filter by:<br>  - Gender<br>  - Grade level<br>  - Subject<br>  - Section<br>• <strong>Sort:</strong> Sort students by name (A-Z or Z-A) or LRN using the sort dropdown.<br>• <strong>View Toggle:</strong> Switch between Grid View (card layout) or Table View (tabular layout) using the view toggle buttons.<br>• <strong>Clear Filters:</strong> Click Clear Filters to reset search and filters.</p>
                    <h4>9. Notes</h4>
                    <p>• <strong>Required Fields:</strong> The following are mandatory:<br>  - LRN, first name, middle name, last name, grade level, subject, section, email, gender, date of birth, address, parent name, emergency contact<br>• <strong>QR Codes:</strong> Generated automatically if not provided.<br>• <strong>Excel Export:</strong> Includes all selected students, even across pages, with columns for LRN, names, email, gender, DOB, grade level, address, parent info, photo, QR code, and date added.</p>
                    <h4>10. Troubleshooting</h4>
                    <p>• <strong>QR Code Not Generating:</strong><br>  - Ensure LRN, first name, and last name are provided.<br>• <strong>Excel Export Fails:</strong><br>  - Confirm that students are selected.<br>• <strong>Duplicate LRN Error:</strong><br>  - Verify that the LRN is unique and numeric.<br>• <strong>Photo Upload Fails:</strong><br>  - Ensure the file is a valid image format (e.g., JPG, PNG, GIF).</p>
                </div>
            </div>
            <!-- Add other manuals similarly (omitted for brevity, but same structure applies) -->
        </div>
    </section>
</body>
</html>