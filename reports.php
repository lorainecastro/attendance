<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Recommendations - Student Attendance System</title>
    <style>
        :root {
            /* Primary Colors */
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #dbeafe;
            
            /* Status Colors */
            --success-green: #16a34a;
            --warning-yellow: #ca8a04;
            --danger-red: #dc2626;
            --info-cyan: #0891b2;
            
            /* Neutral Colors */
            --dark-gray: #374151;
            --medium-gray: #6b7280;
            --light-gray: #d1d5db;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e5e7eb;
            
            /* Additional Colors for Missing Variables */
            --card-bg: #ffffff;
            --blackfont-color: #111827;
            --whitefont-color: #ffffff;
            --grayfont-color: #6b7280;
            --primary-gradient: linear-gradient(135deg, #2563eb, #a855f7);
            --secondary-gradient: linear-gradient(135deg, #ec4899, #f472b6);
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --inputfield-color: #f3f4f6;
            --inputfieldhover-color: #e5e7eb;
            
            /* Typography */
            --font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
    
            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            
            /* Border Radius */
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
    
            /* Transitions */
            --transition-fast: 0.15s ease-in-out;
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
            font-size: var(--font-size-2xl);
            margin-bottom: var(--spacing-md);
            color: var(--blackfont-color);
            position: relative;
            padding-bottom: var(--spacing-xs);
        }

        h1:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        /* Quick Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
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
            margin-bottom: var(--spacing-sm);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
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
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
        }

        .card-value {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--blackfont-color);
        }

        /* Attendance Report Generator */
        .attendance-grid {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-md);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-sm);
            flex-wrap: wrap;
            gap: var(--spacing-xs);
        }

        .table-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
        }

        .table-controls {
            display: flex;
            gap: var(--spacing-xs);
            flex-wrap: wrap;
        }

        .selector-input, .selector-select {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .selector-input:focus, .selector-select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--inputfieldhover-color);
        }

        .quick-action-btn {
            border: none;
            background: var(--primary-color);
            color: var(--whitefont-color);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .quick-action-btn:hover {
            background: var(--primary-hover);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: var(--spacing-md) var(--spacing-lg);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            font-weight: 600;
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
        }

        tbody tr {
            transition: var(--transition-normal);
        }

        tbody tr:hover {
            background-color: var(--inputfield-color);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .modal-content h3 {
            margin-bottom: var(--spacing-sm);
        }

        .close-btn {
            float: right;
            font-size: 20px;
            cursor: pointer;
            color: var(--grayfont-color);
        }

        /* Chart */
        .chart-container {
            margin-top: var(--spacing-md);
            position: relative;
            height: 300px;
        }

        /* Status badges */
        .status-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-present {
            background: #dcfce7;
            color: #166534;
        }

        .status-absent {
            background: #fecaca;
            color: #991b1b;
        }

        .status-late {
            background: #fef3c7;
            color: #92400e;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            th, td {
                padding: var(--spacing-sm);
            }

            .card-value {
                font-size: var(--font-size-lg);
            }

            .table-controls {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 576px) {
            .table-responsive {
                overflow-x: auto;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <h1>Reports & Export</h1>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Students</div>
                    <div class="card-value">342</div>
                </div>
                <div class="card-icon bg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Overall Attendance</div>
                    <div class="card-value">91.5%</div>
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
                    <div class="card-value">18</div>
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
                    <div class="card-value">2,856</div>
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
    <div class="attendance-grid">
        <div class="table-header">
            <div class="table-title">Report Generator</div>
            <div class="table-controls">
                <input type="text" class="selector-input" id="search-student" placeholder="Search Student">
                <select class="selector-select" id="report-type">
                    <option value="">Select Report Type</option>
                    <option value="student">Attendance History per Student</option>
                    <option value="class">Attendance per Class</option>
                    <option value="class-report">Class Report</option>
                </select>
                <select class="selector-select" id="report-filter">
                    <option value="">Select Class/Student</option>
                    <option value="all">All Students</option>
                    <option value="math-algebra">Math - Algebra I</option>
                    <option value="math-geometry">Math - Geometry</option>
                    <option value="english-9">English 9</option>
                    <option value="english-10">English 10</option>
                    <option value="biology">Biology</option>
                    <option value="chemistry">Chemistry</option>
                    <option value="world-history">World History</option>
                    <option value="us-history">US History</option>
                    <option value="spanish">Spanish I</option>
                    <option value="pe">Physical Education</option>
                </select>
                <input type="date" class="selector-input" id="date-from" value="2024-01-01">
                <input type="date" class="selector-input" id="date-to" value="2024-12-31">
                <select class="selector-select" id="export-format">
                    <option value="">Select Export Format</option>
                    <option value="json">JSON</option>
                    <option value="csv">CSV</option>
                    <option value="pdf">PDF</option>
                </select>
                <button class="quick-action-btn" id="generate-report">Generate Report</button>
            </div>
        </div>
    </div>

    <!-- Report Results -->
    <div class="report-results" id="report-results">
        <div class="table-header">
            <div class="table-title" id="report-title">Attendance Report</div>
            <button class="quick-action-btn" id="export-report">Export Report</button>
        </div>
        
        <div class="attendance-grid">
            <table id="report-table">
                <thead id="report-thead">
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Grade</th>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <!-- Sample data will be populated here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="attendance-grid">
        <div class="table-header">
            <div class="table-title">Recent Activities</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>2024-07-09</td>
                    <td>Emma Wilson</td>
                    <td>9th</td>
                    <td>Algebra I</td>
                    <td><span class="status-badge status-present">Present</span></td>
                    <td>08:00 AM</td>
                </tr>
                <tr>
                    <td>2024-07-09</td>
                    <td>Liam Johnson</td>
                    <td>10th</td>
                    <td>World History</td>
                    <td><span class="status-badge status-late">Late</span></td>
                    <td>08:15 AM</td>
                </tr>
                <tr>
                    <td>2024-07-09</td>
                    <td>Sophia Rodriguez</td>
                    <td>11th</td>
                    <td>Chemistry</td>
                    <td><span class="status-badge status-absent">Absent</span></td>
                    <td>--</td>
                </tr>
                <tr>
                    <td>2024-07-09</td>
                    <td>Noah Davis</td>
                    <td>12th</td>
                    <td>English 12</td>
                    <td><span class="status-badge status-present">Present</span></td>
                    <td>09:30 AM</td>
                </tr>
                <tr>
                    <td>2024-07-08</td>
                    <td>Olivia Martinez</td>
                    <td>9th</td>
                    <td>Biology</td>
                    <td><span class="status-badge status-present">Present</span></td>
                    <td>10:45 AM</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Sample data for high school
        const sampleData = {
            students: [
                { id: 'HS001', name: 'Emma Wilson', grade: '9th', class: 'Algebra I', date: '2024-07-09', status: 'Present', timeIn: '08:00 AM', timeOut: '08:50 AM' },
                { id: 'HS002', name: 'Liam Johnson', grade: '10th', class: 'World History', date: '2024-07-09', status: 'Late', timeIn: '08:15 AM', timeOut: '09:05 AM' },
                { id: 'HS003', name: 'Sophia Rodriguez', grade: '11th', class: 'Chemistry', date: '2024-07-09', status: 'Absent', timeIn: '--', timeOut: '--' },
                { id: 'HS004', name: 'Noah Davis', grade: '12th', class: 'English 12', date: '2024-07-09', status: 'Present', timeIn: '09:30 AM', timeOut: '10:20 AM' },
                { id: 'HS005', name: 'Olivia Martinez', grade: '9th', class: 'Biology', date: '2024-07-08', status: 'Present', timeIn: '10:45 AM', timeOut: '11:35 AM' },
                { id: 'HS006', name: 'Ethan Brown', grade: '10th', class: 'Geometry', date: '2024-07-08', status: 'Present', timeIn: '11:50 AM', timeOut: '12:40 PM' },
                { id: 'HS007', name: 'Ava Garcia', grade: '11th', class: 'US History', date: '2024-07-08', status: 'Late', timeIn: '01:05 PM', timeOut: '01:55 PM' },
                { id: 'HS008', name: 'Mason Anderson', grade: '12th', class: 'Spanish I', date: '2024-07-08', status: 'Present', timeIn: '02:10 PM', timeOut: '03:00 PM' },
                { id: 'HS009', name: 'Isabella Thompson', grade: '9th', class: 'English 9', date: '2024-07-08', status: 'Present', timeIn: '08:00 AM', timeOut: '08:50 AM' },
                { id: 'HS010', name: 'Lucas White', grade: '10th', class: 'Physical Education', date: '2024-07-08', status: 'Present', timeIn: '09:05 AM', timeOut: '09:55 AM' },
                { id: 'HS011', name: 'Mia Harris', grade: '11th', class: 'Algebra II', date: '2024-07-07', status: 'Present', timeIn: '10:00 AM', timeOut: '10:50 AM' },
                { id: 'HS012', name: 'James Wilson', grade: '12th', class: 'Physics', date: '2024-07-07', status: 'Late', timeIn: '11:05 AM', timeOut: '11:55 AM' }
            ],
            classes: [
                { name: 'Algebra I (9th Grade)', totalStudents: 28, presentToday: 26, avgAttendance: 93 },
                { name: 'Geometry (10th Grade)', totalStudents: 25, presentToday: 22, avgAttendance: 88 },
                { name: 'English 9 (9th Grade)', totalStudents: 30, presentToday: 28, avgAttendance: 93 },
                { name: 'English 10 (10th Grade)', totalStudents: 27, presentToday: 25, avgAttendance: 90 },
                { name: 'Biology (9th Grade)', totalStudents: 32, presentToday: 29, avgAttendance: 91 },
                { name: 'Chemistry (11th Grade)', totalStudents: 24, presentToday: 21, avgAttendance: 87 },
                { name: 'World History (10th Grade)', totalStudents: 29, presentToday: 26, avgAttendance: 89 },
                { name: 'US History (11th Grade)', totalStudents: 26, presentToday: 24, avgAttendance: 92 },
                { name: 'Spanish I (9th-12th Grade)', totalStudents: 22, presentToday: 20, avgAttendance: 91 },
                { name: 'Physical Education (9th-12th Grade)', totalStudents: 35, presentToday: 33, avgAttendance: 94 }
            ]
        };

        // Event listeners
        document.getElementById('generate-report').addEventListener('click', generateReport);
        document.getElementById('export-report').addEventListener('click', exportReport);

        function generateReport() {
            const reportType = document.getElementById('report-type').value;
            const reportFilter = document.getElementById('report-filter').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;

            if (!reportType) {
                alert('Please select a report type');
                return;
            }

            const reportResults = document.getElementById('report-results');
            const reportTitle = document.getElementById('report-title');
            const reportTable = document.getElementById('report-table');
            const reportThead = document.getElementById('report-thead');
            const reportTbody = document.getElementById('report-tbody');

            // Clear previous results
            reportTbody.innerHTML = '';

            // Set report title
            let title = '';
            switch (reportType) {
                case 'student':
                    title = 'Student Attendance History';
                    break;
                case 'class':
                    title = 'Class Attendance Report';
                    break;
                case 'class-report':
                    title = 'Detailed Class Report';
                    break;
            }
            reportTitle.textContent = title;

            // Generate table headers based on report type
            if (reportType === 'class-report') {
                reportThead.innerHTML = `
                    <tr>
                        <th>Class</th>
                        <th>Total Students</th>
                        <th>Present Today</th>
                        <th>Average Attendance</th>
                        <th>Status</th>
                    </tr>
                `;
                
                // Populate class report data
                sampleData.classes.forEach(cls => {
                    const row = document.createElement('tr');
                    const attendanceRate = (cls.presentToday / cls.totalStudents * 100).toFixed(1);
                    const status = attendanceRate >= 90 ? 'Excellent' : attendanceRate >= 80 ? 'Good' : attendanceRate >= 70 ? 'Fair' : 'Poor';
                    const statusClass = attendanceRate >= 90 ? 'status-present' : attendanceRate >= 80 ? 'status-late' : 'status-absent';
                    
                    row.innerHTML = `
                        <td>${cls.name}</td>
                        <td>${cls.totalStudents}</td>
                        <td>${cls.presentToday}</td>
                        <td>${cls.avgAttendance}%</td>
                        <td><span class="status-badge ${statusClass}">${status}</span></td>
                    `;
                    reportTbody.appendChild(row);
                });
            } else {
                // Default student attendance table
                reportThead.innerHTML = `
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Grade</th>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                    </tr>
                `;

                // Filter data based on selection
                let filteredData = sampleData.students;
                
                if (reportFilter && reportFilter !== 'all') {
                    filteredData = sampleData.students.filter(student => 
                        student.class.toLowerCase().includes(reportFilter.replace('-', ' '))
                    );
                }

                // Populate student data
                filteredData.forEach(student => {
                    const row = document.createElement('tr');
                    const statusClass = student.status === 'Present' ? 'status-present' : 
                                       student.status === 'Late' ? 'status-late' : 'status-absent';
                    
                    row.innerHTML = `
                        <td>${student.id}</td>
                        <td>${student.name}</td>
                        <td>${student.grade}</td>
                        <td>${student.class}</td>
                        <td>${student.date}</td>
                        <td><span class="status-badge ${statusClass}">${student.status}</span></td>
                        <td>${student.timeIn}</td>
                        <td>${student.timeOut}</td>
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
                    // Remove badge styling for export
                    const text = td.textContent.trim();
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
                    alert('PDF export functionality would be implemented with a PDF library');
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
        
        // Initialize with default date range
        document.getElementById('date-from').value = '2024-07-01';
        document.getElementById('date-to').value = '2024-07-31';
    </script>
</body>
</html>