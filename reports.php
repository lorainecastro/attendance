<?php
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

        .status-excellent {
            background-color: var(--status-present-bg);
            color: var(--success-color);
        }

        .status-good, .status-fair {
            background-color: var(--status-late-bg);
            color: var(--warning-color);
        }

        .status-poor {
            background-color: var(--status-absent-bg);
            color: var(--danger-color);
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

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Students</div>
                    <div class="card-value">9</div>
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
                    <div class="card-value">15%</div>
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
                    <div class="card-value">3</div>
                </div>
                <div class="card-icon bg-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Records</div>
                    <div class="card-value">9</div>
                </div>
                <div class="card-icon bg-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm0 1h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Report Generator -->
    <div class="controls">
        <div class="controls-left">
            <select class="selector-select" id="class-filter">
                <option value="">All Classes</option>
            </select>
            <input type="text" class="selector-input" id="student-search" placeholder="Search student...">
            <select class="selector-select" id="student-filter">
                <option value="">All Students</option>
            </select>
            <select class="selector-select" id="report-type">
                <option value="">Select Report Type</option>
                <option value="student">Attendance History per Student</option>
                <option value="class">Attendance per Class</option>
            </select>
            <input type="date" class="selector-input" id="date-from" value="2024-09-01">
            <input type="date" class="selector-input" id="date-to" value="2024-12-31">
            <select class="selector-select" id="export-format">
                <option value="">Select Export Format</option>
                <option value="json">JSON</option>
                <option value="csv">CSV</option>
                <option value="pdf">PDF</option>
            </select>
            <button class="btn btn-primary" id="generate-report">
                <i class="fas fa-file-alt"></i> Generate Report
            </button>
        </div>
    </div>

    <!-- Report Results -->
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
                        <th>LRN</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Attendance Time</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <!-- Data will be populated here -->
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.3/jspdf.plugin.autotable.min.js"></script>
    <script>
        // Data from Class Management
        const classes = [
            {
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
                attendancePercentage: 10,
                schedule: {
                    monday: { start: '08:00', end: '09:30' },
                    wednesday: { start: '08:00', end: '09:30' },
                    friday: { start: '08:00', end: '09:30' }
                },
                status: 'active',
                students: [
                    { id: 1, firstName: 'John', lastName: 'Doe', email: 'john.doe@email.com' },
                    { id: 2, firstName: 'Jane', lastName: 'Smith', email: 'jane.smith@email.com' },
                    { id: 3, firstName: 'Mike', lastName: 'Johnson', email: 'mike.johnson@email.com' }
                ]
            },
            {
                id: 2,
                code: 'SCI-201-B',
                sectionName: 'Einstein Section',
                subject: 'Science',
                gradeLevel: 'Grade 10',
                room: 'Lab 1',
                attendancePercentage: 15,
                schedule: {
                    tuesday: { start: '10:00', end: '11:30' },
                    thursday: { start: '10:00', end: '11:30' }
                },
                status: 'active',
                students: [
                    { id: 4, firstName: 'Alice', lastName: 'Brown', email: 'alice.brown@email.com' },
                    { id: 5, firstName: 'Bob', lastName: 'Wilson', email: 'bob.wilson@email.com' }
                ]
            },
            {
                id: 3,
                code: 'ENG-301-C',
                sectionName: 'Shakespeare Section',
                subject: 'English Literature',
                gradeLevel: 'Grade 12',
                room: 'Room 305',
                attendancePercentage: 20,
                schedule: {
                    monday: { start: '14:00', end: '15:30' },
                    wednesday: { start: '14:00', end: '15:30' }
                },
                status: 'inactive',
                students: [
                    { id: 6, firstName: 'Carol', lastName: 'Davis', email: 'carol.davis@email.com' },
                    { id: 7, firstName: 'David', lastName: 'Miller', email: 'david.miller@email.com' },
                    { id: 8, firstName: 'Emma', lastName: 'Garcia', email: 'emma.garcia@email.com' },
                    { id: 9, firstName: 'Frank', lastName: 'Rodriguez', email: 'frank.rodriguez@email.com' }
                ]
            }
        ];

        // Sample attendance data
        const attendanceData = [
            { studentId: 1, classId: 1, date: '2024-09-01', status: 'Present', timeIn: '08:00 AM', timeOut: '09:30 AM' },
            { studentId: 2, classId: 1, date: '2024-09-01', status: 'Late', timeIn: '08:15 AM', timeOut: '09:30 AM' },
            { studentId: 3, classId: 1, date: '2024-09-01', status: 'Present', timeIn: '08:00 AM', timeOut: '09:30 AM' },
            { studentId: 4, classId: 2, date: '2024-09-01', status: 'Absent', timeIn: '--', timeOut: '--' },
            { studentId: 5, classId: 2, date: '2024-09-01', status: 'Present', timeIn: '10:00 AM', timeOut: '11:30 AM' },
            { studentId: 6, classId: 3, date: '2024-09-01', status: 'Present', timeIn: '14:00 PM', timeOut: '15:30 PM' },
            { studentId: 7, classId: 3, date: '2024-09-01', status: 'Late', timeIn: '14:15 PM', timeOut: '15:30 PM' },
            { studentId: 8, classId: 3, date: '2024-09-01', status: 'Present', timeIn: '14:00 PM', timeOut: '15:30 PM' },
            { studentId: 9, classId: 3, date: '2024-09-01', status: 'Absent', timeIn: '--', timeOut: '--' }
        ];

        // Populate class filter
        const classFilter = document.getElementById('class-filter');
        classes.forEach(cls => {
            const option = document.createElement('option');
            option.value = cls.id;
            option.textContent = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;
            classFilter.appendChild(option);
        });

        // Event listeners
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
                option.textContent = `${student.lastName}, ${student.firstName}`;
                studentFilter.appendChild(option);
            });
            filterStudents(); // Apply any current search
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

            // Clear previous results
            reportTbody.innerHTML = '';

            // Set report title
            let title = '';
            switch (reportType) {
                case 'student':
                    title = 'Attendance History per Student';
                    break;
                case 'class':
                    title = 'Attendance per Class';
                    break;
            }
            reportTitle.textContent = title;

            // Generate table headers based on report type
            if (reportType === 'class') {
                reportThead.innerHTML = `
                    <tr>
                        <th>Class</th>
                        <th>Total Students</th>
                        <th>Average Attendance</th>
                        <th>Status</th>
                    </tr>
                `;

                // Filter classes
                let filteredClasses = classes;
                if (classId) {
                    filteredClasses = filteredClasses.filter(cls => cls.id == classId);
                }

                // Populate class report data
                filteredClasses.forEach(cls => {
                    const row = document.createElement('tr');
                    const totalStudents = cls.students.length;
                    const attendanceRate = cls.attendancePercentage;
                    const status = attendanceRate >= 90 ? 'Excellent' : 
                                  attendanceRate >= 80 ? 'Good' : 
                                  attendanceRate >= 70 ? 'Fair' : 'Poor';
                    const statusClass = attendanceRate >= 90 ? 'status-excellent' : 
                                       attendanceRate >= 80 ? 'status-good' : 
                                       attendanceRate >= 70 ? 'status-fair' : 'status-poor';
                    const formattedClass = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;
                    
                    row.innerHTML = `
                        <td>${formattedClass}</td>
                        <td>${totalStudents}</td>
                        <td>${attendanceRate}%</td>
                        <td><span class="status-badge ${statusClass}">${status}</span></td>
                    `;
                    reportTbody.appendChild(row);
                });
            } else {
                // Default student attendance table
                reportThead.innerHTML = `
                    <tr>
                        <th>LRN</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Attendance Time</th>
                    </tr>
                `;

                // Filter attendance data
                let filteredData = attendanceData;
                
                if (classId) {
                    filteredData = filteredData.filter(record => record.classId == classId);
                }
                if (studentId) {
                    filteredData = filteredData.filter(record => record.studentId == studentId);
                }
                if (dateFrom) {
                    filteredData = filteredData.filter(record => record.date >= dateFrom);
                }
                if (dateTo) {
                    filteredData = filteredData.filter(record => record.date <= dateTo);
                }

                // Populate student data
                filteredData.forEach(record => {
                    const cls = classes.find(c => c.id === record.classId);
                    const student = cls.students.find(s => s.id === record.studentId);
                    const row = document.createElement('tr');
                    const statusClass = record.status === 'Present' ? 'status-present' : 
                                       record.status === 'Late' ? 'status-late' : 'status-absent';
                    const formattedClass = `${cls.gradeLevel} - ${cls.sectionName} (${cls.subject})`;
                    const name = `${student.lastName}, ${student.firstName}`;
                    
                    row.innerHTML = `
                        <td>${record.studentId}</td>
                        <td>${name}</td>
                        <td>${formattedClass}</td>
                        <td>${record.date}</td>
                        <td><span class="status-badge ${statusClass}">${record.status}</span></td>
                        <td>${record.timeIn}</td>
                    `;
                    reportTbody.appendChild(row);
                });
            }

            // Show report results
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

            // Get current report data
            const table = document.getElementById('report-table');
            const rows = table.querySelectorAll('tr');
            
            let data = [];
            const headers = [];
            
            // Get headers
            rows[0].querySelectorAll('th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            
            // Get data rows
            for (let i = 1; i < rows.length; i++) {
                const row = {};
                rows[i].querySelectorAll('td').forEach((td, index) => {
                    const text = td.textContent.trim().replace(/^.*?(Present|Late|Absent|Excellent|Good|Fair|Poor)$/g, '$1');
                    row[headers[index]] = text;
                });
                data.push(row);
            }

            // Export based on format
            switch (format) {
                case 'json':
                    exportJSON(data, reportType);
                    break;
                case 'csv':
                    exportCSV(data, headers, reportType);
                    break;
                case 'pdf':
                    exportPDF(data, headers, reportType);
                    break;
            }
        }

        function exportJSON(data, reportType) {
            const jsonData = JSON.stringify(data, null, 2);
            const blob = new Blob([jsonData], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${reportType}-report-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function exportCSV(data, headers, reportType) {
            let csv = headers.join(',') + '\n';
            data.forEach(row => {
                csv += headers.map(header => `"${row[header] || ''}"`).join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${reportType}-report-${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function exportPDF(data, headers, reportType) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Set document properties
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(16);
            doc.text(document.getElementById('report-title').textContent, 14, 20);
            
            // Add date range
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            doc.setFontSize(10);
            doc.text(`Date Range: ${dateFrom} to ${dateTo}`, 14, 30);
            
            // Prepare table data
            const tableData = data.map(row => headers.map(header => row[header] || ''));
            
            // Generate table using autoTable
            doc.autoTable({
                startY: 40,
                head: [headers],
                body: tableData,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                    overflow: 'linebreak',
                    minCellHeight: 10
                },
                headStyles: {
                    fillColor: [37, 99, 235],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                columnStyles: {
                    0: { cellWidth: reportType === 'class' ? 50 : 20 },
                    1: { cellWidth: reportType === 'class' ? 30 : 30 },
                    2: { cellWidth: reportType === 'class' ? 30 : 40 },
                    3: { cellWidth: reportType === 'class' ? 30 : 20 },
                    4: { cellWidth: 20 },
                    5: { cellWidth: 20 }
                },
                didParseCell: function(data) {
                    if (data.section === 'body' && data.column.index === headers.indexOf('Status')) {
                        const status = data.cell.text[0];
                        if (status === 'Present' || status === 'Excellent') {
                            data.cell.styles.fillColor = [220, 252, 231];
                            data.cell.styles.textColor = [22, 101, 52];
                        } else if (status === 'Late' || status === 'Good' || status === 'Fair') {
                            data.cell.styles.fillColor = [254, 243, 199];
                            data.cell.styles.textColor = [146, 64, 14];
                        } else if (status === 'Absent' || status === 'Poor') {
                            data.cell.styles.fillColor = [254, 202, 202];
                            data.cell.styles.textColor = [153, 27, 27];
                        }
                    }
                }
            });
            
            // Save the PDF
            doc.save(`${reportType}-report-${new Date().toISOString().split('T')[0]}.pdf`);
        }

        // Initialize with default date range
        document.getElementById('date-from').value = '2024-09-01';
        document.getElementById('date-to').value = '2024-09-30';
    </script>
</body>
</html>