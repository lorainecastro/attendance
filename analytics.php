<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Predictions - Student Attendance System</title>
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

        h2 {
            font-size: var(--font-size-xl);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
        }

        h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
        }

        /* Filters */
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

        .selector-select, .date-input {
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

        .selector-select:focus, .date-input:focus {
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

        /* KPI Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
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
            margin-bottom: var(--spacing-md);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xl);
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
            font-size: 24px;
            font-weight: 700;
            color: var(--blackfont-color);
        }

        /* Charts */
        .chart-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .chart-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
        }

        .chart-filter {
            display: flex;
            gap: var(--spacing-sm);
        }

        .filter-btn {
            padding: var(--spacing-xs) var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            background: var(--inputfield-color);
            color: var(--grayfont-color);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .filter-btn.active {
            background: var(--primary-blue);
            color: var(--whitefont-color);
        }

        .filter-btn:hover {
            background: var(--inputfieldhover-color);
        }

        canvas {
            max-height: 300px;
            width: 100%;
        }

        /* Tables */
        .pattern-table {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .table-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: var(--spacing-sm) var(--spacing-md);
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

        .risk-high { color: var(--danger-color); }
        .risk-medium { color: var(--warning-color); }
        .risk-low { color: var(--success-color); }

        /* Prediction Card */
        .prediction-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .prediction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }

        .prediction-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }

        .detail-item {
            font-size: var(--font-size-sm);
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            background: var(--inputfield-color);
        }

        .detail-item strong {
            color: var(--grayfont-color);
            display: block;
            margin-bottom: var(--spacing-xs);
        }

        /* Responsive Adjustments */
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
            .selector-select, .date-input {
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
                padding: var(--spacing-xs);
            }
            .card-value {
                font-size: var(--font-size-xl);
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
    <h1>Analytics & Predictions</h1>

    <!-- Filters -->
    <div class="controls">
        <div class="controls-left">
            <select class="selector-select" id="grade-level-filter">
                <option value="">All Grade Levels</option>
            </select>
            <select class="selector-select" id="section-filter">
                <option value="">All Sections</option>
            </select>
            <select class="selector-select" id="subject-filter">
                <option value="">All Subjects</option>
            </select>
            <select class="selector-select" id="student-filter">
                <option value="">All Students</option>
            </select>
            <select class="selector-select" id="time-filter">
                <option value="">All Time</option>
                <option value="week">Last Week</option>
                <option value="month">Last Month</option>
                <option value="year">Last Year</option>
            </select>
            <input type="date" class="date-input" id="start-date" placeholder="Start Date">
            <input type="date" class="date-input" id="end-date" placeholder="End Date">
            <button class="btn btn-primary" id="refresh-data"><i class="fas fa-sync"></i> Refresh</button>
            <button class="btn btn-primary" id="export-chart"><i class="fas fa-download"></i> Export Chart</button>
            <button class="btn btn-secondary" id="clear-filters"><i class="fas fa-times"></i> Clear Filters</button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Average Attendance Rate</div>
                    <div class="card-value" id="avg-attendance-rate">0%</div>
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
                    <div class="card-title">At-Risk Students</div>
                    <div class="card-value" id="at-risk-students">0</div>
                </div>
                <div class="card-icon bg-pink">
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
                    <div class="card-title">Top Absenteeism Factor</div>
                    <div class="card-value" id="top-absenteeism-factor">-</div>
                </div>
                <div class="card-icon bg-blue">
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
                    <div class="card-title">Prediction Accuracy</div>
                    <div class="card-value" id="prediction-accuracy">85%</div>
                </div>
                <div class="card-icon bg-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Status Distribution -->
    <div class="chart-container">
        <div class="table-header">
            <div class="table-title">Attendance Status Distribution</div>
        </div>
        <canvas id="attendance-status"></canvas>
    </div>

    <!-- Historical Analysis -->
    <div class="pattern-table">
        <div class="table-header">
            <div class="table-title">Historical Analysis</div>
        </div>
        <h3>Student Behavior Patterns</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Pattern</th>
                        <th>Frequency</th>
                    </tr>
                </thead>
                <tbody id="behavior-patterns"></tbody>
            </table>
        </div>
    </div>

    <!-- Individual Student Predictions -->
    <div class="prediction-card" id="prediction-card" style="display: none;">
        <div class="prediction-header">
            <div class="table-title">Individual Student Predictions</div>
        </div>
        <div class="prediction-details" id="prediction-details"></div>
        <div class="prediction-details" id="at-risk-status"></div>
        <div class="pattern-table">
            <h3>Analytics & Recommendations</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody id="student-analytics"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Risk Analysis -->
    <div class="chart-container">
        <div class="table-header">
            <div class="table-title">Risk Analysis</div>
        </div>
        <canvas id="risk-analysis-chart"></canvas>
    </div>

    <!-- Predictive Analytics Dashboard -->
    <div class="pattern-table">
        <div class="table-header">
            <div class="table-title">Predictive Analytics Dashboard</div>
        </div>
        <h3>Early Warning System</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class/Section</th>
                        <th>Risk Level</th>
                        <th>Recommended Action</th>
                        <th>Urgency</th>
                    </tr>
                </thead>
                <tbody id="early-warning"></tbody>
            </table>
        </div>
    </div>

    <div class="pattern-table">
        <div class="table-header">
            <div class="table-title">Subject-Specific Patterns</div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Class/Section</th>
                        <th>Average Attendance Rate</th>
                    </tr>
                </thead>
                <tbody id="subject-patterns"></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Data from Class Management
        let classes = [
            {
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
                attendancePercentage: 90,
                schedule: {
                    monday: { start: '08:00', end: '09:30' },
                    wednesday: { start: '08:00', end: '09:30' },
                    friday: { start: '08:00', end: '09:30' }
                },
                status: 'active',
                students: [
                    { id: 1, firstName: 'John', lastName: 'Doe', email: 'john.doe@email.com', attendanceRate: 92 },
                    { id: 2, firstName: 'Jane', lastName: 'Smith', email: 'jane.smith@email.com', attendanceRate: 88 },
                    { id: 3, firstName: 'Mike', lastName: 'Johnson', email: 'mike.johnson@email.com', attendanceRate: 95 }
                ]
            },
            {
                id: 2,
                code: 'SCI-201-B',
                sectionName: 'Einstein Section',
                subject: 'Science',
                gradeLevel: 'Grade 10',
                room: 'Lab 1',
                attendancePercentage: 85,
                schedule: {
                    tuesday: { start: '10:00', end: '11:30' },
                    thursday: { start: '10:00', end: '11:30' }
                },
                status: 'active',
                students: [
                    { id: 4, firstName: 'Alice', lastName: 'Brown', email: 'alice.brown@email.com', attendanceRate: 80 },
                    { id: 5, firstName: 'Bob', lastName: 'Wilson', email: 'bob.wilson@email.com', attendanceRate: 90 }
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
                    { id: 6, firstName: 'Carol', lastName: 'Davis', email: 'carol.davis@email.com', attendanceRate: 85 },
                    { id: 7, firstName: 'David', lastName: 'Miller', email: 'david.miller@email.com', attendanceRate: 87 },
                    { id: 8, firstName: 'Emma', lastName: 'Garcia', email: 'emma.garcia@email.com', attendanceRate: 90 },
                    { id: 9, firstName: 'Frank', lastName: 'Rodriguez', email: 'frank.rodriguez@email.com', attendanceRate: 82 }
                ]
            }
        ];

        // Student data
        let students = classes.flatMap(cls => cls.students.map(student => ({
            id: student.id,
            firstName: student.firstName,
            lastName: student.lastName,
            email: student.email || '',
            fullName: `${student.firstName} ${student.lastName}`,
            gender: student.gender || 'Male',
            dob: student.dob || '2010-01-01',
            gradeLevel: cls.gradeLevel,
            class: cls.subject,
            section: cls.sectionName,
            address: student.address || '123 Sample St',
            parentName: student.parentName || 'Parent Name',
            emergencyContact: student.emergencyContact || '09234567890',
            attendanceRate: student.attendanceRate || 90,
            dateAdded: student.dateAdded || '2024-09-01',
            photo: student.photo || 'https://via.placeholder.com/100',
            absences: [
                { month: 'Dec', reason: 'Health Issue', count: Math.floor(Math.random() * 5) },
                { day: ['Monday', 'Friday'][Math.floor(Math.random() * 2)], count: Math.floor(Math.random() * 6) }
            ],
            consecutiveAbsences: Math.floor(Math.random() * 15)
        })));

        // Sample absence data
        const absenceData = [
            { month: 'December', reason: 'Health Issue', frequency: '12/year' },
            { month: 'June', reason: 'Sick', frequency: '8/year' }
        ];

        // Sample attendance status data (base)
        const baseAttendanceStatus = [
            { status: 'Present', count: 350 },
            { status: 'Absent', count: 30 },
            { status: 'Late', count: 15 }
        ];

        // DOM Elements
        const gradeLevelFilter = document.getElementById('grade-level-filter');
        const subjectFilter = document.getElementById('subject-filter');
        const sectionFilter = document.getElementById('section-filter');
        const studentFilter = document.getElementById('student-filter');
        const timeFilter = document.getElementById('time-filter');
        const startDate = document.getElementById('start-date');
        const endDate = document.getElementById('end-date');
        const predictionCard = document.getElementById('prediction-card');
        const predictionDetails = document.getElementById('prediction-details');
        const atRiskStatus = document.getElementById('at-risk-status');
        const studentAnalytics = document.getElementById('student-analytics');
        const behaviorPatterns = document.getElementById('behavior-patterns');
        const earlyWarning = document.getElementById('early-warning');
        const subjectPatterns = document.getElementById('subject-patterns');
        const avgAttendanceRate = document.getElementById('avg-attendance-rate');
        const atRiskStudents = document.getElementById('at-risk-students');
        const topAbsenteeismFactor = document.getElementById('top-absenteeism-factor');
        const predictionAccuracy = document.getElementById('prediction-accuracy');

        // Chart Contexts
        const attendanceStatusCtx = document.getElementById('attendance-status').getContext('2d');
        const riskAnalysisChartCtx = document.getElementById('risk-analysis-chart').getContext('2d');

        // Chart Instances
        let attendanceStatusChart, riskAnalysis;

        // Initialize Filters
        function initializeFilters() {
            const gradeLevels = [...new Set(classes.map(c => c.gradeLevel))];
            const subjects = [...new Set(classes.map(c => c.subject))];
            const sections = [...new Set(classes.map(c => c.sectionName))];

            gradeLevelFilter.innerHTML = '<option value="">All Grade Levels</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                subjectFilter.appendChild(option);
            });

            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionFilter.appendChild(option);
            });

            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelFilter.appendChild(option);
            });

            updateStudentFilter();
        }

        // Update Student Filter
        function updateStudentFilter() {
            const selectedGradeLevel = gradeLevelFilter.value;
            const selectedSubject = subjectFilter.value;
            const selectedSection = sectionFilter.value;

            let filteredStudents = students;

            if (selectedGradeLevel) {
                filteredStudents = filteredStudents.filter(s => s.gradeLevel === selectedGradeLevel);
            }
            if (selectedSubject) {
                filteredStudents = filteredStudents.filter(s => s.class === selectedSubject);
            }
            if (selectedSection) {
                filteredStudents = filteredStudents.filter(s => s.section === selectedSection);
            }

            studentFilter.innerHTML = '<option value="">All Students</option>';
            filteredStudents.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.fullName} (${student.section})`;
                studentFilter.appendChild(option);
            });

            // Reset prediction card if no specific student is selected
            if (!studentFilter.value) {
                predictionCard.style.display = 'none';
                predictionDetails.innerHTML = '';
                atRiskStatus.innerHTML = '';
                studentAnalytics.innerHTML = '';
            }
        }

        // Initialize Charts
        function initializeCharts() {
            if (attendanceStatusChart) attendanceStatusChart.destroy();
            if (riskAnalysis) riskAnalysis.destroy();

            attendanceStatusChart = new Chart(attendanceStatusCtx, {
                type: 'pie',
                data: {
                    labels: baseAttendanceStatus.map(s => s.status),
                    datasets: [{
                        data: baseAttendanceStatus.map(s => s.count),
                        backgroundColor: ['#22c55e', '#ef4444', '#f59e0b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: { enabled: true },
                        legend: { position: 'top' }
                    }
                }
            });

            riskAnalysis = new Chart(riskAnalysisChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                    datasets: [{
                        data: [60, 25, 15],
                        backgroundColor: ['#22c55e', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { enabled: true }
                    }
                }
            });
        }

        // Update Attendance Status Chart
        function updateAttendanceStatusChart(selectedTime, startDateValue, endDateValue, selectedStudentId, filteredStudents) {
            let filteredData = [...baseAttendanceStatus];
            if (selectedStudentId) {
                const student = students.find(s => s.id == selectedStudentId);
                if (student) {
                    filteredData = [
                        { status: 'Present', count: Math.floor(student.attendanceRate * 4) },
                        { status: 'Absent', count: Math.floor((100 - student.attendanceRate) * 0.3) },
                        { status: 'Late', count: Math.floor((100 - student.attendanceRate) * 0.2) }
                    ];
                }
            } else {
                filteredData = baseAttendanceStatus.map(item => {
                    const avgCount = filteredStudents.reduce((sum, s) => {
                        const rate = s.attendanceRate;
                        return sum + (item.status === 'Present' ? rate * 4 :
                                     item.status === 'Absent' ? (100 - rate) * 0.3 :
                                     item.status === 'Late' ? (100 - rate) * 0.2 :
                                     (100 - rate) * 0.1);
                    }, 0) / (filteredStudents.length || 1);
                    return { status: item.status, count: Math.floor(avgCount) };
                });
            }

            if (startDateValue && endDateValue) {
                filteredData = filteredData.map(item => ({
                    ...item,
                    count: Math.floor(item.count * (Math.random() * 0.2 + 0.8))
                }));
            } else if (selectedTime) {
                const multiplier = selectedTime === 'week' ? 0.95 : selectedTime === 'month' ? 0.9 : selectedTime === 'year' ? 0.85 : 1;
                filteredData = filteredData.map(item => ({
                    ...item,
                    count: Math.floor(item.count * multiplier)
                }));
            }

            attendanceStatusChart.data.labels = filteredData.map(s => s.status);
            attendanceStatusChart.data.datasets[0].data = filteredData.map(s => s.count);
            attendanceStatusChart.update();
        }

        // Update Risk Analysis Chart
        function updateRiskAnalysisChart(selectedStudentId, filteredStudents) {
            let riskData;
            if (selectedStudentId) {
                const student = students.find(s => s.id == selectedStudentId);
                if (student) {
                    const riskLevel = student.attendanceRate < 85 ? 'High' : student.attendanceRate < 90 ? 'Medium' : 'Low';
                    riskData = {
                        labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                        data: [
                            riskLevel === 'Low' ? 100 : 0,
                            riskLevel === 'Medium' ? 100 : 0,
                            riskLevel === 'High' ? 100 : 0
                        ]
                    };
                } else {
                    riskData = {
                        labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                        data: [60, 25, 15]
                    };
                }
            } else {
                const riskCounts = { low: 0, medium: 0, high: 0 };
                filteredStudents.forEach(s => {
                    if (s.attendanceRate < 85) riskCounts.high++;
                    else if (s.attendanceRate < 90) riskCounts.medium++;
                    else riskCounts.low++;
                });
                const total = filteredStudents.length || 1;
                riskData = {
                    labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                    data: [
                        (riskCounts.low / total) * 100,
                        (riskCounts.medium / total) * 100,
                        (riskCounts.high / total) * 100
                    ]
                };
            }

            riskAnalysis.data.labels = riskData.labels;
            riskAnalysis.data.datasets[0].data = riskData.data;
            riskAnalysis.update();
        }

        // Update KPI Cards
        function updateKPICards(selectedStudentId, filteredStudents, selectedTime, startDateValue, endDateValue) {
            let avgAttendance, atRiskCount, topFactor, predAccuracy;

            if (selectedStudentId) {
                const student = students.find(s => s.id == selectedStudentId);
                if (student) {
                    avgAttendance = student.attendanceRate;
                    atRiskCount = student.attendanceRate < 85 || student.consecutiveAbsences >= 10 ? 1 : 0;
                    topFactor = student.absences[0]?.reason || 'Health Issue';
                    predAccuracy = student.attendanceRate < 85 ? 80 : student.attendanceRate < 90 ? 85 : 90;
                } else {
                    avgAttendance = 90;
                    atRiskCount = 0;
                    topFactor = 'Health Issue';
                    predAccuracy = 85;
                }
            } else {
                avgAttendance = filteredStudents.reduce((sum, s) => sum + s.attendanceRate, 0) / filteredStudents.length || 90;
                atRiskCount = filteredStudents.filter(s => s.attendanceRate < 85 || s.consecutiveAbsences >= 10).length;
                const factorCounts = {};
                filteredStudents.forEach(s => {
                    const reason = s.absences[0]?.reason || 'Health Issue';
                    factorCounts[reason] = (factorCounts[reason] || 0) + 1;
                });
                topFactor = Object.entries(factorCounts).sort((a, b) => b[1] - a[1])[0]?.[0] || 'Health Issue';
                predAccuracy = filteredStudents.length ? (filteredStudents.filter(s => s.attendanceRate >= 85).length / filteredStudents.length * 90).toFixed(1) : 85;
            }

            if (startDateValue && endDateValue) {
                avgAttendance *= (Math.random() * 0.1 + 0.95);
                predAccuracy *= (Math.random() * 0.05 + 0.95);
            } else if (selectedTime) {
                const multiplier = selectedTime === 'week' ? 0.98 : selectedTime === 'month' ? 0.95 : selectedTime === 'year' ? 0.90 : 1;
                avgAttendance *= multiplier;
                predAccuracy *= multiplier;
            }

            avgAttendanceRate.textContent = `${avgAttendance.toFixed(1)}%`;
            atRiskStudents.textContent = atRiskCount;
            topAbsenteeismFactor.textContent = topFactor;
            predictionAccuracy.textContent = `${predAccuracy.toFixed(1)}%`;
        }

        // Update Data
        function updateData() {
            const selectedGradeLevel = gradeLevelFilter.value;
            const selectedSubject = subjectFilter.value;
            const selectedSection = sectionFilter.value;
            const selectedStudentId = studentFilter.value;
            const selectedTime = timeFilter.value;
            const startDateValue = startDate.value;
            const endDateValue = endDate.value;

            let filteredStudents = students;
            let filteredClasses = classes;

            if (selectedGradeLevel) {
                filteredStudents = filteredStudents.filter(s => s.gradeLevel === selectedGradeLevel);
                filteredClasses = filteredClasses.filter(c => c.gradeLevel === selectedGradeLevel);
            }
            if (selectedSubject) {
                filteredStudents = filteredStudents.filter(s => s.class === selectedSubject);
                filteredClasses = filteredClasses.filter(c => c.subject === selectedSubject);
            }
            if (selectedSection) {
                filteredStudents = filteredStudents.filter(s => s.section === selectedSection);
                filteredClasses = filteredClasses.filter(c => c.sectionName === selectedSection);
            }
            if (selectedStudentId) {
                filteredStudents = filteredStudents.filter(s => s.id == selectedStudentId);
                showStudentPrediction(filteredStudents[0]);
            } else {
                predictionCard.style.display = 'none';
                predictionDetails.innerHTML = '';
                atRiskStatus.innerHTML = '';
                studentAnalytics.innerHTML = '';
            }

            if (startDateValue && endDateValue) {
                const dateStart = new Date(startDateValue);
                const dateEnd = new Date(endDateValue);
                if (dateStart > dateEnd) {
                    alert('Start date must be before end date.');
                    return;
                }
                filteredStudents = filteredStudents.map(s => ({
                    ...s,
                    attendanceRate: s.attendanceRate * (Math.random() * 0.1 + 0.95)
                }));
            } else if (selectedTime) {
                const multiplier = selectedTime === 'week' ? 0.98 : selectedTime === 'month' ? 0.95 : selectedTime === 'year' ? 0.90 : 1;
                filteredStudents = filteredStudents.map(s => ({
                    ...s,
                    attendanceRate: s.attendanceRate * multiplier
                }));
            }

            updateKPICards(selectedStudentId, filteredStudents, selectedTime, startDateValue, endDateValue);

            behaviorPatterns.innerHTML = '';
            const behaviorData = filteredStudents.map(s => ({
                student: s.fullName,
                pattern: s.absences.find(a => a.day) ? `Frequent ${s.absences.find(a => a.day).day} absences` : 'None',
                frequency: s.absences.find(a => a.day) ? s.absences.find(a => a.day).count : 0
            }));
            behaviorData.forEach(data => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${data.student}</td><td>${data.pattern}</td><td>${data.frequency}</td>`;
                behaviorPatterns.appendChild(row);
            });

            earlyWarning.innerHTML = '';
            const earlyWarningData = filteredStudents.map(s => {
                const riskLevel = s.attendanceRate < 85 ? 'High' : s.attendanceRate < 90 ? 'Medium' : 'Low';
                const action = riskLevel === 'High' ? 'Parent Meeting' : riskLevel === 'Medium' ? 'Counseling' : 'Monitor';
                const urgency = riskLevel === 'High' ? '1 week' : riskLevel === 'Medium' ? '2 weeks' : '1 month';
                return {
                    student: s.fullName,
                    classSection: `${s.class} (${s.section})`,
                    riskLevel,
                    action,
                    urgency
                };
            });
            earlyWarningData.forEach(data => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${data.student}</td>
                    <td>${data.classSection}</td>
                    <td><span class="risk-${data.riskLevel.toLowerCase()}">${data.riskLevel}</span></td>
                    <td>${data.action}</td>
                    <td>${data.urgency}</td>
                `;
                earlyWarning.appendChild(row);
            });

            subjectPatterns.innerHTML = '';
            const subjectData = filteredClasses.map(c => ({
                name: c.subject,
                classSection: c.sectionName,
                attendanceRate: c.attendancePercentage
            }));
            subjectData.forEach(subject => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${subject.name}</td>
                    <td>${subject.classSection}</td>
                    <td>${subject.attendanceRate}%</td>
                `;
                subjectPatterns.appendChild(row);
            });

            updateAttendanceStatusChart(selectedTime, startDateValue, endDateValue, selectedStudentId, filteredStudents);
            updateRiskAnalysisChart(selectedStudentId, filteredStudents);
        }

        // Show Student Prediction
        function showStudentPrediction(student) {
            if (!student) return;

            predictionCard.style.display = 'block';

            const avgAttendanceRate = student.attendanceRate;
            const riskLevel = student.attendanceRate < 85 ? 'High' : student.attendanceRate < 90 ? 'Medium' : 'Low';
            const predictedAttendance = Math.min(100, student.attendanceRate);
            const probabilityPresentTomorrow = Math.min(100, Math.max(0, student.attendanceRate));
            const chronicAbsenteeism = Math.min(100, ((student.consecutiveAbsences / 180) * 100).toFixed(1));

            predictionDetails.innerHTML = `
                <div class="detail-item">
                    <strong>Student Name:</strong> ${student.fullName}
                </div>
                <div class="detail-item">
                    <strong>Current Attendance Rate:</strong> ${student.attendanceRate}%
                </div>
                <div class="detail-item">
                    <strong>Predicted Next Month:</strong> ${predictedAttendance.toFixed(1)}%
                </div>
                <div class="detail-item">
                    <strong>Risk Level:</strong> <span class="risk-${riskLevel.toLowerCase()}">${riskLevel}</span>
                </div>
                <div class="detail-item">
                    <strong>Total Absences:</strong> ${student.consecutiveAbsences}
                </div>
                <div class="detail-item">
                    <strong>Primary Absence Reason:</strong> ${student.absences[0]?.reason || 'N/A'}
                </div>
                <div class="detail-item">
                    <strong>Chronic Absenteeism:</strong> ${chronicAbsenteeism}%
                </div>
                <div class="detail-item">
                    <strong>Probability of Being Present Tomorrow:</strong> ${probabilityPresentTomorrow.toFixed(1)}%
                </div>
            `;

            atRiskStatus.innerHTML = riskLevel === 'High' ? `
                <div class="detail-item risk-high">
                    <strong>Status:</strong> At Risk
                </div>
            ` : '';

            studentAnalytics.innerHTML = '';
            const analyticsData = [
                {
                    metric: 'Attendance Rate',
                    value: `${student.attendanceRate}%`,
                    recommendation: riskLevel === 'High' ? 'Schedule immediate intervention' : 'Continue monitoring'
                },
                {
                    metric: 'Total Absences',
                    value: student.consecutiveAbsences,
                    recommendation: student.consecutiveAbsences > 10 ? 'Contact parents' : 'Review absence patterns'
                },
                {
                    metric: 'Primary Absence Reason',
                    value: student.absences[0]?.reason || 'N/A',
                    recommendation: student.absences[0]?.reason === 'Sick' ? 'Health check-up' : 'Address specific issue'
                },
                {
                    metric: 'Chronic Absenteeism',
                    value: `${chronicAbsenteeism}%`,
                    recommendation: chronicAbsenteeism > 10 ? 'Implement attendance plan' : 'Monitor attendance'
                }
            ];
            analyticsData.forEach(data => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${data.metric}</td>
                    <td>${data.value}</td>
                    <td>${data.recommendation}</td>
                `;
                studentAnalytics.appendChild(row);
            });
        }

        // Clear Filters
        function clearFilters() {
            gradeLevelFilter.value = '';
            subjectFilter.value = '';
            sectionFilter.value = '';
            studentFilter.value = '';
            timeFilter.value = '';
            startDate.value = '';
            endDate.value = '';
            updateStudentFilter();
            updateData();
        }

        // Event Listeners
        gradeLevelFilter.addEventListener('change', () => {
            updateStudentFilter();
            updateData();
        });
        subjectFilter.addEventListener('change', () => {
            updateStudentFilter();
            updateData();
        });
        sectionFilter.addEventListener('change', () => {
            updateStudentFilter();
            updateData();
        });
        studentFilter.addEventListener('change', updateData);
        timeFilter.addEventListener('change', updateData);
        startDate.addEventListener('change', updateData);
        endDate.addEventListener('change', updateData);
        document.getElementById('clear-filters').addEventListener('change', clearFilters);

        document.getElementById('refresh-data').addEventListener('click', updateData);

        document.getElementById('export-chart').addEventListener('click', () => {
            const canvas = document.getElementById('attendance-status');
            const link = document.createElement('a');
            link.download = 'attendance-status-chart.png';
            link.href = canvas.toDataURL();
            link.click();
        });

        document.getElementById('clear-filters').addEventListener('click', clearFilters);

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            initializeFilters();
            initializeCharts();
            updateData();
        });
    </script>
</body>
</html>