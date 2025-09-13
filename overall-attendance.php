<?php
// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
require 'config.php';
session_start();

// Validate session
$user = validateSession();
if (!$user) {
    destroySession();
    header("Location: index.php");
    exit();
}

$pdo = getDBConnection();

// Fetch the earliest attendance date for the teacher
$stmt = $pdo->prepare("
    SELECT MIN(attendance_date) AS earliest_date 
    FROM attendance_tracking a 
    JOIN classes c ON a.class_id = c.class_id 
    WHERE c.teacher_id = ?
");
$stmt->execute([$user['teacher_id']]);
$earliest_date_result = $stmt->fetch(PDO::FETCH_ASSOC);
$earliest_date = $earliest_date_result['earliest_date'] ?? date('Y-m-d');

// Fetch classes for the teacher
$stmt = $pdo->prepare("
    SELECT c.class_id, c.section_name, s.subject_name, c.grade_level 
    FROM classes c 
    JOIN subjects s ON c.subject_id = s.subject_id 
    WHERE c.teacher_id = ?
");
$stmt->execute([$user['teacher_id']]);
$classes_fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students by class
$students_by_class = [];
foreach ($classes_fetch as $class) {
    $class_id = $class['class_id'];
    $stmt = $pdo->prepare("
        SELECT s.lrn, CONCAT(s.last_name, ', ', s.first_name, ' ', s.middle_name) AS name, s.photo 
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
    <title>Overall Attendance - Student Attendance System</title>
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

        .bg-purple { background: var(--primary-gradient); }
        .bg-pink { background: var(--secondary-gradient); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-green { background: linear-gradient(135deg, #10b981, #34d399); }

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

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .table-title {
            font-size: 18px;
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

        .status-none {
            background-color: var(--status-none-bg);
            color: var(--grayfont-color);
        }

        .attendance-rate {
            color: var(--success-green);
            font-weight: 600;
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
            .search-container {
                min-width: auto;
                width: 100%;
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
    <h1>Overall Attendance</h1>

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
                <input type="text" class="search-input" id="searchInput" placeholder="Search by LRN or Name">
                <i class="fas fa-search search-icon"></i>
            </div>
            <input type="date" class="selector-input" id="date-selector" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
            <select class="selector-select" id="gradeLevelSelector">
                <option value="">All Grade Levels</option>
            </select>
            <select class="selector-select" id="sectionSelector">
                <option value="">All Sections</option>
            </select>
            <select class="selector-select" id="classSelector">
                <option value="">All Subjects</option>
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
        <div class="table-header">
            <div class="table-title">Attendance</div>
        </div>
        <div class="table-responsive">
            <table id="attendance-table">
                <thead>
                    <tr>
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

    <script>
        const classes = <?php echo json_encode($classes_fetch); ?>;
        const students_by_class = <?php echo json_encode($students_by_class); ?>;
        const attendanceData = <?php echo json_encode($attendance_arr); ?> || {};
        const earliestDate = '<?php echo $earliest_date; ?>';
        let today = document.getElementById('date-selector').value;
        let current_class_id = null;
        let currentPage = 1;
        const rowsPerPage = 5;

        function updateStats(filteredStudents) {
            const total = filteredStudents.length;
            const present = filteredStudents.filter(s => 
                attendanceData[today]?.[s.class_id]?.[s.lrn]?.status === 'Present' || 
                attendanceData[today]?.[s.class_id]?.[s.lrn]?.status === 'Late'
            ).length;
            const absent = filteredStudents.filter(s => 
                attendanceData[today]?.[s.class_id]?.[s.lrn]?.status === 'Absent'
            ).length;
            const percentage = total ? ((present / total) * 100).toFixed(1) : 0;

            document.getElementById('total-students').textContent = total;
            document.getElementById('present-count').textContent = present;
            document.getElementById('absent-count').textContent = absent;
            document.getElementById('attendance-percentage').textContent = `${percentage}%`;
        }

        function populateGradeLevels() {
            const gradeLevelSelector = document.getElementById('gradeLevelSelector');
            gradeLevelSelector.innerHTML = '<option value="">All Grade Levels</option>';
            const gradeLevels = [...new Set(classes.map(c => c.grade_level))].sort();
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelSelector.appendChild(option);
            });
        }

        function populateSections(gradeLevel) {
            const sectionSelector = document.getElementById('sectionSelector');
            sectionSelector.innerHTML = '<option value="">All Sections</option>';
            const sections = [...new Set(classes.filter(c => !gradeLevel || c.grade_level === gradeLevel).map(c => c.section_name))].sort();
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelector.appendChild(option);
            });
            if (gradeLevel && sections.length > 0) {
                sectionSelector.value = sections[0];
                populateSubjects(gradeLevel, sections[0]);
            }
        }

        function populateSubjects(gradeLevel, section) {
            const classSelector = document.getElementById('classSelector');
            classSelector.innerHTML = '<option value="">All Subjects</option>';
            const subjects = [...new Set(classes.filter(c => 
                (!gradeLevel || c.grade_level === gradeLevel) && 
                (!section || c.section_name === section)
            ).map(c => c.subject_name))].sort();
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                classSelector.appendChild(option);
            });
        }

        function getAllFilteredStudents() {
            const gradeLevelFilter = document.getElementById('gradeLevelSelector').value;
            const sectionFilter = document.getElementById('sectionSelector').value;
            const subjectFilter = document.getElementById('classSelector').value;
            const statusFilter = document.getElementById('statusSelector').value;
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();

            let filteredClasses = classes;
            if (gradeLevelFilter) {
                filteredClasses = filteredClasses.filter(c => c.grade_level === gradeLevelFilter);
            }
            if (sectionFilter) {
                filteredClasses = filteredClasses.filter(c => c.section_name === sectionFilter);
            }
            if (subjectFilter) {
                filteredClasses = filteredClasses.filter(c => c.subject_name === subjectFilter);
            }

            let allStudents = [];
            filteredClasses.forEach(cls => {
                const students = students_by_class[cls.class_id] || [];
                students.forEach(student => {
                    student.class_id = cls.class_id;
                    allStudents.push(student);
                });
            });

            return allStudents.filter(s => {
                const att = attendanceData[today]?.[s.class_id]?.[s.lrn] || { status: '' };
                const matchesStatus = statusFilter ? att.status === statusFilter : true;
                const matchesSearch = searchQuery ? 
                    s.lrn.toString().includes(searchQuery) || 
                    s.name.toLowerCase().includes(searchQuery) : true;
                return matchesStatus && matchesSearch;
            }).sort((a, b) => a.name.localeCompare(b.name));
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

            // Use the earliest date from the system
            const earliestSystemDate = new Date('<?php echo $earliest_date; ?>');
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

        function renderTable(isPagination = false) {
            if (!isPagination) currentPage = 1;
            const tableBody = document.querySelector('#attendance-table tbody');
            tableBody.innerHTML = '';

            const filteredStudents = getAllFilteredStudents();

            if (filteredStudents.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="no-students-message">No students match the current filters</td></tr>';
                updateStats([]);
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedStudents = filteredStudents.slice(start, end);

            paginatedStudents.forEach(student => {
                const att = attendanceData[today]?.[student.class_id]?.[student.lrn] || { status: '', timeChecked: '' };
                const statusClass = att.status ? att.status.toLowerCase() : 'none';
                const rate = calcAttendanceRate(student.class_id, student.lrn);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><img src="Uploads/${student.photo || 'no-icon.png'}" class="student-photo" alt="${student.name}"></td>
                    <td>${student.lrn}</td>
                    <td>${student.name}</td>
                    <td><span class="status-badge status-${statusClass}">${att.status || 'None'}</span></td>
                    <td>${att.timeChecked || '-'}</td>
                    <td class="attendance-rate">${rate}</td>
                `;
                tableBody.appendChild(row);
            });

            updateStats(filteredStudents);

            // Pagination
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

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('date-selector').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('gradeLevelSelector').value = '';
            document.getElementById('sectionSelector').value = '';
            document.getElementById('classSelector').value = '';
            document.getElementById('statusSelector').value = '';
            today = '<?php echo date('Y-m-d'); ?>';
            populateGradeLevels();
            renderTable();
        }

        const dateSelector = document.getElementById('date-selector');
        const gradeLevelSelector = document.getElementById('gradeLevelSelector');
        const sectionSelector = document.getElementById('sectionSelector');
        const classSelector = document.getElementById('classSelector');
        const statusSelector = document.getElementById('statusSelector');
        const searchInput = document.getElementById('searchInput');

        gradeLevelSelector.addEventListener('change', () => {
            const gradeLevel = gradeLevelSelector.value;
            populateSections(gradeLevel);
            renderTable();
        });

        sectionSelector.addEventListener('change', () => {
            const gradeLevel = gradeLevelSelector.value;
            const section = sectionSelector.value;
            populateSubjects(gradeLevel, section);
            renderTable();
        });

        classSelector.addEventListener('change', () => {
            renderTable();
        });

        statusSelector.addEventListener('change', () => {
            renderTable();
        });

        searchInput.addEventListener('input', () => {
            renderTable();
        });

        dateSelector.addEventListener('change', () => {
            today = dateSelector.value;
            renderTable();
        });

        document.addEventListener('DOMContentLoaded', () => {
            populateGradeLevels();
            renderTable();
        });
    </script>
</body>
</html>