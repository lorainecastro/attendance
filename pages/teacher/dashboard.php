<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
            
            /* Additional Colors */
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
            
            /* Layout */
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
            
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
            height: 3px;
            width: 60px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-2xl);
            flex-wrap: wrap;
        }

        .action-btn {
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
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .action-btn:hover {
            background: var(--primary-blue-hover);
            transform: translateY(-2px);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        .bg-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

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

        .card-footer {
            display: flex;
            align-items: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .card-trend {
            display: flex;
            align-items: center;
            margin-right: 10px;
            font-weight: 600;
        }

        .card-trend.up { color: #10b981; }
        .card-trend.down { color: #ef4444; }

        .card-period { color: var(--grayfont-color); }

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
        }

        .chart-filter {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            border: none;
            background: var(--inputfield-color);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .filter-btn:hover:not(.active) {
            background: var(--inputfieldhover-color);
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .status-badge.present {
            color: var(--success-green);
            background: rgba(22, 163, 74, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .status-badge.absent {
            color: var(--danger-red);
            background: rgba(220, 38, 38, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* Responsive Adjustments */
        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .card-value {
                font-size: 20px;
            }

            .quick-actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <h1>Teacher Dashboard</h1>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <button class="action-btn" onclick="markAttendance()">
            <i class="fas fa-check-circle"></i> Mark Attendance
        </button>
        <button class="action-btn" onclick="viewClassDetails()">
            <i class="fas fa-book"></i> View Class Details
        </button>
        <button class="action-btn" onclick="generateReport()">
            <i class="fas fa-file-alt"></i> Generate Report
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Classes</div>
                    <div class="card-value" id="totalClasses">3</div>
                </div>
                <div class="card-icon bg-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M3.5 2A1.5 1.5 0 0 1 5 0.5h6A1.5 1.5 0 0 1 12.5 2v10a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 12V2zm1.5-.5A.5.5 0 0 0 4.5 2v10a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5V2a.5.5 0 0 0-.5-.5H5z"/>
                        <path d="M7 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                    </svg>
                </div>
            </div>
            <div class="card-footer">
                <div class="card-trend up"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/></svg> 1</div>
                <div class="card-period">Since last week</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Students</div>
                    <div class="card-value" id="totalStudents">9</div>
                </div>
                <div class="card-icon bg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                    </svg>
                </div>
            </div>
            <div class="card-footer">
                <div class="card-trend up"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/></svg> 5</div>
                <div class="card-period">Since last week</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Attendance Rate</div>
                    <div class="card-value" id="attendanceRate">90%</div>
                </div>
                <div class="card-icon bg-green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                    </svg>
                </div>
            </div>
            <div class="card-footer">
                <div class="card-trend up"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/></svg> 2%</div>
                <div class="card-period">Since last week</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">At-Risk Students</div>
                    <div class="card-value" id="atRiskStudents">1</div>
                </div>
                <div class="card-icon bg-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/>
                    </svg>
                </div>
            </div>
            <div class="card-footer">
                <div class="card-trend down"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 .708-.708L7.5 10.293V4.5A.5.5 0 0 1 8 4z"/></svg> 3</div>
                <div class="card-period">Since last week</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Absences Today</div>
                    <div class="card-value" id="absencesToday">2</div>
                </div>
                <div class="card-icon bg-orange">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm0 1h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
            </div>
            <div class="card-footer">
                <div class="card-trend down"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 .708-.708L7.5 10.293V4.5A.5.5 0 0 1 8 4z"/></svg> 2</div>
                <div class="card-period">Since yesterday</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Attendance Trends</div>
                <div class="chart-filter">
                    <button class="filter-btn" data-period="day" data-chart="attendance">Day</button>
                    <button class="filter-btn active" data-period="week" data-chart="attendance">Week</button>
                    <button class="filter-btn" data-period="month" data-chart="attendance">Month</button>
                </div>
            </div>
            <div>
                <canvas id="attendance-chart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Total Students by Grade Level</div>
                <div class="chart-filter">
                    <button class="filter-btn" data-period="week" data-chart="grades">Week</button>
                    <button class="filter-btn active" data-period="month" data-chart="grades">Month</button>
                </div>
            </div>
            <div>
                <canvas id="grades-chart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Predictive Factors Section -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">Top Factors Affecting Attendance</div>
            <div class="chart-filter">
                <button class="filter-btn" data-period="week" data-chart="factors">Week</button>
                <button class="filter-btn active" data-period="month" data-chart="factors">Month</button>
            </div>
        </div>
        <div>
            <canvas id="factors-chart" style="height: 300px; width: 100%;"></canvas>
        </div>
    </div>

    <!-- Attendance Records Table -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">Recent Attendance Records</div>
        </div>
        <div class="table-responsive">
            <table id="attendanceTable" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script>
        const classes = [
            {
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
                attendancePercentage: 92,
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
                attendancePercentage: 90,
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
                attendancePercentage: 88,
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

        const attendanceRecords = [
            { studentId: 1, classId: 1, date: '2025-07-09', status: 'present', remarks: '' },
            { studentId: 2, classId: 1, date: '2025-07-09', status: 'absent', remarks: 'Sick' },
            { studentId: 4, classId: 2, date: '2025-07-09', status: 'present', remarks: '' },
            { studentId: 6, classId: 3, date: '2025-07-09', status: 'absent', remarks: 'Family emergency' },
            { studentId: 7, classId: 3, date: '2025-07-09', status: 'present', remarks: '' }
        ];

        document.addEventListener('DOMContentLoaded', function() {
            updateDashboardStats();
            renderAttendanceRecords();
            initializeCharts();

            window.markAttendance = function() {
                alert('Mark Attendance functionality to be implemented');
            };

            window.viewClassDetails = function() {
                alert('View Class Details functionality to be implemented');
            };

            window.generateReport = function() {
                alert('Generate Report functionality to be implemented');
            };
        });

        function updateDashboardStats() {
            const totalClasses = classes.length;
            const totalStudents = classes.reduce((sum, c) => sum + c.students.length, 0);
            const avgAttendance = classes.reduce((sum, c) => sum + c.attendancePercentage, 0) / classes.length;
            const atRiskStudents = Math.floor(totalStudents * 0.08);
            const absencesToday = attendanceRecords.filter(r => r.date === '2025-07-09' && r.status === 'absent').length;

            document.getElementById('totalClasses').textContent = totalClasses;
            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('attendanceRate').textContent = `${Math.round(avgAttendance)}%`;
            document.getElementById('atRiskStudents').textContent = atRiskStudents;
            document.getElementById('absencesToday').textContent = absencesToday;
        }

        function renderAttendanceRecords() {
            const tbody = document.getElementById('attendanceTable').querySelector('tbody');
            tbody.innerHTML = '';

            attendanceRecords.forEach(record => {
                const student = classes.flatMap(c => c.students).find(s => s.id === record.studentId);
                const classItem = classes.find(c => c.id === record.classId);
                if (student && classItem) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${student.firstName} ${student.lastName}</td>
                        <td>${classItem.code} (${classItem.sectionName})</td>
                        <td>${record.date}</td>
                        <td><span class="status-badge ${record.status}">${record.status}</span></td>
                        <td>${record.remarks || '-'}</td>
                    `;
                    tbody.appendChild(row);
                }
            });
        }

        function initializeCharts() {
            const attendanceData = {
                day: {
                    labels: ['8AM', '9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM'],
                    values: [95, 93, 94, 92, 90, 91, 93, 94]
                },
                week: {
                    labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    values: [92, 90, 93, 91, 94]
                },
                month: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    values: [91, 93, 92, 94]
                }
            };

            const gradesData = {
                week: {
                    labels: ['Grade 7', 'Grade 10', 'Grade 12'],
                    values: [3, 2, 4]
                },
                month: {
                    labels: ['Grade 7', 'Grade 10', 'Grade 12'],
                    values: [12, 8, 16]
                }
            };

            const factorsData = {
                week: {
                    labels: ['Health Issue', 'Transportation', 'Family Issues', 'Weather'],
                    values: [30, 25, 20, 15]
                },
                month: {
                    labels: ['Health Issue', 'Transportation', 'Family Issues', 'Weather'],
                    values: [28, 22, 18, 12]
                }
            };

            const attendanceChartCtx = document.getElementById('attendance-chart').getContext('2d');
            const attendanceChart = new Chart(attendanceChartCtx, {
                type: 'line',
                data: {
                    labels: attendanceData.week.labels,
                    datasets: [{
                        label: 'Attendance Rate (%)',
                        data: attendanceData.week.values,
                        backgroundColor: 'rgba(37, 99, 235, 0.2)',
                        borderColor: var(--primary-blue),
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: var(--primary-blue),
                        pointRadius: 4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 80,
                            max: 100,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: { callback: value => value + '%' }
                        },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: context => context.parsed.y + '%' } }
                    }
                }
            });

            const gradesChartCtx = document.getElementById('grades-chart').getContext('2d');
            const gradesChart = new Chart(gradesChartCtx, {
                type: 'doughnut',
                data: {
                    labels: gradesData.month.labels,
                    datasets: [{
                        data: gradesData.month.values,
                        backgroundColor: ['#2563eb', '#ec4899', '#10b981'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 15 }
                        },
                        tooltip: { callbacks: { label: context => `${context.label}: ${context.parsed} students` } }
                    },
                    cutout: '65%'
                }
            });

            const factorsChartCtx = document.getElementById('factors-chart').getContext('2d');
            const factorsChart = new Chart(factorsChartCtx, {
                type: 'bar',
                data: {
                    labels: factorsData.month.labels,
                    datasets: [{
                        label: 'Impact on Absenteeism (%)',
                        data: factorsData.month.values,
                        backgroundColor: ['#2563eb', '#ec4899', '#10b981', '#f59e0b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 50,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: { callback: value => value + '%' }
                        },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: context => `${context.label}: ${context.parsed.y}%` } }
                    }
                }
            });

            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function() {
                    this.parentNode.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const period = this.getAttribute('data-period');
                    const chartType = this.getAttribute('data-chart');
                    
                    if (chartType === 'attendance') {
                        attendanceChart.data.labels = attendanceData[period].labels;
                        attendanceChart.data.datasets[0].data = attendanceData[period].values;
                        attendanceChart.update();
                    } else if (chartType === 'grades') {
                        gradesChart.data.labels = gradesData[period].labels;
                        gradesChart.data.datasets[0].data = gradesData[period].values;
                        gradesChart.update();
                    } else if (chartType === 'factors') {
                        factorsChart.data.labels = factorsData[period].labels;
                        factorsChart.data.datasets[0].data = factorsData[period].values;
                        factorsChart.update();
                    }
                });
            });
        }
    </script>
</body>
</html>