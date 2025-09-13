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
            --font-family: 'Inter', sans-serif;
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
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
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

        /* Dashboard grid layout */
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
            transition: var(--transition);
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

        .card-trend.up {
            color: #10b981;
        }

        .card-trend.down {
            color: #ef4444;
        }

        .card-period {
            color: var(--grayfont-color);
        }

        /* Charts row */
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
            transition: var(--transition);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .filter-btn:hover:not(.active) {
            background: var(--inputfieldhover-color);
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            th,
            td {
                padding: 10px;
            }

            .card-value {
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .table-responsive {
                overflow-x: auto;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
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

        /* Schedule Section */
        .schedule-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-table th,
        .schedule-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .schedule-table th {
            font-size: 14px;
            color: var(--grayfont-color);
            text-transform: uppercase;
        }

        .schedule-table td {
            font-size: 14px;
            color: var(--blackfont-color);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.active {
            background: var(--success-green);
            color: var(--whitefont-color);
        }

        .status-badge.inactive {
            background: var(--danger-red);
            color: var(--whitefont-color);
        }

        .btn-info {
            background: var(--primary-color);
            color: var(--whitefont-color);
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition-normal);
        }

        .btn-info:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .no-schedule {
            color: var(--grayfont-color);
            font-style: italic;
        }

        /* Recent Activity Section */
        .activity-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        .activity-list {
            list-style: none;
            padding: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--whitefont-color);
            margin-right: 12px;
        }

        .activity-details {
            flex: 1;
        }

        .activity-description {
            font-size: 14px;
            color: var(--blackfont-color);
        }

        .activity-time {
            font-size: 12px;
            color: var(--grayfont-color);
        }
    </style>
</head>

<body>
    <h1>Teacher Dashboard</h1>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="attendance.php" class="action-btn">
            <i class="fas fa-check-circle"></i> Mark Attendance
        </a>
        <a href="manage-classes.php" class="action-btn">
            <i class="fas fa-book"></i> View Class Details
        </a>
        <a href="reports.php" class="action-btn">
            <i class="fas fa-file-alt"></i> Generate Report
        </a>
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
                        <path d="M3.5 2A1.5 1.5 0 0 1 5 0.5h6A1.5 1.5 0 0 1 12.5 2v10a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 12V2zm1.5-.5A.5.5 0 0 0 4.5 2v10a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5V2a.5.5 0 0 0-.5-.5H5z" />
                        <path d="M7 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Students</div>
                    <div class="card-value" id="totalStudents">150</div>
                </div>
                <div class="card-icon bg-blue">
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
                    <div class="card-title">Today's Attendance Rate</div>
                    <div class="card-value" id="todayAttendanceRate">94%</div>
                </div>
                <div class="card-icon bg-green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Students at Risk</div>
                    <div class="card-value" id="atRiskStudents">12</div>
                </div>
                <div class="card-icon bg-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">Attendance Trends</div>
                <div class="chart-filter">
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
                <div class="chart-title">Attendance Status Distribution</div>
                <div class="chart-filter">
                    <button class="filter-btn" data-period="week" data-chart="status">Week</button>
                    <button class="filter-btn active" data-period="month" data-chart="status">Month</button>
                </div>
            </div>
            <div>
                <canvas id="status-chart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Today's Schedule Section -->
    <div class="schedule-card">
        <div class="chart-header">
            <div class="chart-title">Today's Schedule</div>
        </div>
        <div class="table-responsive">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Section Name</th>
                        <th>Subject</th>
                        <th>Room</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="scheduleTableBody">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="activity-card">
        <div class="chart-header">
            <div class="chart-title">Recent Activity</div>
        </div>
        <ul class="activity-list" id="activityList">
        </ul>
    </div>

    <script>
        // Sample data for calculations
        const classes = [{
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
                attendancePercentage: 10,
                schedule: {
                    monday: {
                        start: '08:00',
                        end: '09:30'
                    },
                    wednesday: {
                        start: '08:00',
                        end: '09:30'
                    },
                    saturday: {
                        start: '09:00',
                        end: '10:30'
                    }
                },
                status: 'active',
                students: [{
                        id: 1,
                        firstName: 'John',
                        lastName: 'Doe',
                        email: 'john.doe@email.com'
                    },
                    {
                        id: 2,
                        firstName: 'Jane',
                        lastName: 'Smith',
                        email: 'jane.smith@email.com'
                    },
                    {
                        id: 3,
                        firstName: 'Mike',
                        lastName: 'Johnson',
                        email: 'mike.johnson@email.com'
                    }
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
                    tuesday: {
                        start: '10:00',
                        end: '11:30'
                    },
                    thursday: {
                        start: '10:00',
                        end: '11:30'
                    }
                },
                status: 'active',
                students: [{
                        id: 4,
                        firstName: 'Alice',
                        lastName: 'Brown',
                        email: 'alice.brown@email.com'
                    },
                    {
                        id: 5,
                        firstName: 'Bob',
                        lastName: 'Wilson',
                        email: 'bob.wilson@email.com'
                    }
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
                    monday: {
                        start: '14:00',
                        end: '15:30'
                    },
                    wednesday: {
                        start: '14:00',
                        end: '15:30'
                    }
                },
                status: 'inactive',
                students: [{
                        id: 6,
                        firstName: 'Carol',
                        lastName: 'Davis',
                        email: 'carol.davis@email.com'
                    },
                    {
                        id: 7,
                        firstName: 'David',
                        lastName: 'Miller',
                        email: 'david.miller@email.com'
                    },
                    {
                        id: 8,
                        firstName: 'Emma',
                        lastName: 'Garcia',
                        email: 'emma.garcia@email.com'
                    },
                    {
                        id: 9,
                        firstName: 'Frank',
                        lastName: 'Rodriguez',
                        email: 'frank.rodriguez@email.com'
                    }
                ]
            }
        ];

        const attendanceRecords = [{
                date: '2025-07-19',
                studentId: 1,
                status: 'present'
            },
            {
                date: '2025-07-19',
                studentId: 2,
                status: 'absent'
            },
            {
                date: '2025-07-19',
                studentId: 3,
                status: 'late'
            },
            {
                date: '2025-07-18',
                studentId: 1,
                status: 'present'
            },
            {
                date: '2025-07-18',
                studentId: 2,
                status: 'excused'
            },
            {
                date: '2025-07-18',
                studentId: 3,
                status: 'absent'
            },
            {
                date: '2025-07-17',
                studentId: 4,
                status: 'present'
            },
            {
                date: '2025-07-17',
                studentId: 5,
                status: 'late'
            },
            {
                date: '2025-07-16',
                studentId: 6,
                status: 'present'
            },
            {
                date: '2025-07-16',
                studentId: 7,
                status: 'absent'
            },
            {
                date: '2025-07-15',
                studentId: 8,
                status: 'present'
            },
            {
                date: '2025-07-15',
                studentId: 9,
                status: 'excused'
            },
            ...new Array(100).fill().map((_, i) => ({
                date: `2025-07-${(i % 19) + 1 < 10 ? '0' : ''}${(i % 19) + 1}`,
                studentId: (i % 9) + 1,
                status: ['present', 'absent', 'late', 'excused'][Math.floor(Math.random() * 4)]
            }))
        ];

        document.addEventListener('DOMContentLoaded', function() {
            try {
                updateDashboardStats();
                initializeCharts();
                renderTodaySchedule();
                renderRecentActivity();

                window.markAttendance = function() {
                    alert('Mark Attendance functionality to be implemented');
                };

                window.viewClassDetails = function() {
                    alert('View Class Details functionality to be implemented');
                };

                window.generateReport = function() {
                    alert('Generate Report functionality to be implemented');
                };

                window.viewClass = function(classId) {
                    const classItem = classes.find(c => c.id === classId);
                    if (!classItem) return;

                    const scheduleText = formatSchedule(classItem.schedule);

                    alert(`
Class Code: ${classItem.code}
Section Name: ${classItem.sectionName}
Subject: ${classItem.subject}
Grade Level: ${classItem.gradeLevel}
Room: ${classItem.room}
Students: ${classItem.students.length}
Attendance Percentage: ${classItem.attendancePercentage}%
Schedule: ${scheduleText.replace(/<[^>]+>/g, '')}
Status: ${classItem.status}
Students List: ${classItem.students.map(s => `${s.firstName} ${s.lastName}`).join(', ')}
                    `);
                };
            } catch (error) {
                console.error('Error initializing dashboard:', error);
                alert('An error occurred while loading the dashboard. Please try again later.');
            }
        });

        function updateDashboardStats() {
            const totalClasses = classes.length;
            const totalStudents = classes.reduce((sum, c) => sum + c.students.length, 0);
            const todayRecords = attendanceRecords.filter(r => r.date === '2025-07-19');
            const todayPresent = todayRecords.filter(r => r.status === 'present').length;
            const todayAttendanceRate = todayRecords.length > 0 ? (todayPresent / todayRecords.length) * 100 : 0;
            const atRiskStudents = [...new Set(attendanceRecords
                .filter(r => r.date >= '2025-07-01' && r.status === 'absent')
                .map(r => r.studentId))].length || 12;

            document.getElementById('totalClasses').textContent = totalClasses;
            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('todayAttendanceRate').textContent = `${Math.round(todayAttendanceRate)}%`;
            document.getElementById('atRiskStudents').textContent = atRiskStudents;
        }

        function initializeCharts() {
            const attendanceData = {
                week: {
                    labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    values: [92, 90, 93, 91, 94]
                },
                month: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    values: [91, 93, 92, 94]
                }
            };

            const statusData = {
                week: {
                    labels: ['Present', 'Absent', 'Late', 'Excused'],
                    values: [
                        attendanceRecords.filter(r => r.date >= '2025-07-15' && r.date <= '2025-07-19' && r.status === 'present').length,
                        attendanceRecords.filter(r => r.date >= '2025-07-15' && r.date <= '2025-07-19' && r.status === 'absent').length,
                        attendanceRecords.filter(r => r.date >= '2025-07-15' && r.date <= '2025-07-19' && r.status === 'late').length,
                        attendanceRecords.filter(r => r.date >= '2025-07-15' && r.date <= '2025-07-19' && r.status === 'excused').length
                    ]
                },
                month: {
                    labels: ['Present', 'Absent', 'Late', 'Excused'],
                    values: [
                        attendanceRecords.filter(r => r.status === 'present').length,
                        attendanceRecords.filter(r => r.status === 'absent').length,
                        attendanceRecords.filter(r => r.status === 'late').length,
                        attendanceRecords.filter(r => r.status === 'excused').length
                    ]
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
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderColor: '#6366f1',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: '#6366f1',
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
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });

            const statusChartCtx = document.getElementById('status-chart').getContext('2d');
            const statusChart = new Chart(statusChartCtx, {
                type: 'doughnut',
                data: {
                    labels: statusData.month.labels,
                    datasets: [{
                        data: statusData.month.values,
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#6366f1'],
                        borderWidth: 0,
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' students';
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });

            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function() {
                    this.parentNode.querySelectorAll('.filter-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');

                    const period = this.getAttribute('data-period');
                    const chartType = this.getAttribute('data-chart');

                    if (chartType === 'attendance') {
                        attendanceChart.data.labels = attendanceData[period].labels;
                        attendanceChart.data.datasets[0].data = attendanceData[period].values;
                        attendanceChart.update();
                    } else if (chartType === 'status') {
                        statusChart.data.labels = statusData[period].labels;
                        statusChart.data.datasets[0].data = statusData[period].values;
                        statusChart.update();
                    }
                });
            });
        }

        function renderTodaySchedule() {
            const tbody = document.getElementById('scheduleTableBody');
            const today = new Date('2025-07-19');
            const dayName = today.toLocaleString('en-US', {
                weekday: 'long'
            }).toLowerCase();
            const filteredClasses = classes.filter(c => c.schedule[dayName]);

            tbody.innerHTML = '';

            if (filteredClasses.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-schedule">No classes scheduled today</td></tr>';
                return;
            }

            filteredClasses.forEach(classItem => {
                const schedule = classItem.schedule[dayName];
                const time = `${formatTime(schedule.start)} - ${formatTime(schedule.end)}`;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${classItem.code}</td>
                    <td>${classItem.sectionName}</td>
                    <td>${classItem.subject}</td>
                    <td>${classItem.room}</td>
                    <td>${time}</td>
                    <td><span class="status-badge ${classItem.status}">${classItem.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewClass(${classItem.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function renderRecentActivity() {
            const activityList = document.getElementById('activityList');
            const recentRecords = attendanceRecords
                .slice()
                .sort((a, b) => new Date(b.date) - new Date(a.date))
                .slice(0, 5);

            activityList.innerHTML = '';

            if (recentRecords.length === 0) {
                activityList.innerHTML = '<li class="no-schedule">No recent activity</li>';
                return;
            }

            recentRecords.forEach(record => {
                const student = classes
                    .flatMap(c => c.students)
                    .find(s => s.id === record.studentId);
                if (!student) return;

                const classItem = classes.find(c => c.students.some(s => s.id === record.studentId));
                if (!classItem) return;

                const statusColors = {
                    present: 'bg-green',
                    absent: 'bg-pink',
                    late: 'bg-warning-yellow',
                    excused: 'bg-blue'
                };

                const statusIcons = {
                    present: 'fa-check-circle',
                    absent: 'fa-times-circle',
                    late: 'fa-clock',
                    excused: 'fa-info-circle'
                };

                const li = document.createElement('li');
                li.className = 'activity-item';
                li.innerHTML = `
                    <div class="activity-icon ${statusColors[record.status]}">
                        <i class="fas ${statusIcons[record.status]}"></i>
                    </div>
                    <div class="activity-details">
                        <div class="activity-description">
                            ${student.firstName} ${student.lastName} marked as ${record.status} in ${classItem.code} (${classItem.subject})
                        </div>
                        <div class="activity-time">${formatDate(record.date)}</div>
                    </div>
                `;
                activityList.appendChild(li);
            });
        }

        function formatSchedule(schedule) {
            if (!schedule || Object.keys(schedule).length === 0) {
                return '<span class="no-schedule">No schedule set</span>';
            }

            return Object.entries(schedule).map(([day, times]) => {
                const dayName = capitalizeFirst(day);
                return `<div class="schedule-item">${dayName}: ${formatTime(times.start)} - ${formatTime(times.end)}</div>`;
            }).join('');
        }

        function formatTime(time) {
            if (!time) return '';
            const [hours, minutes] = time.split(':');
            const hourNum = parseInt(hours);
            const period = hourNum >= 12 ? 'PM' : 'AM';
            const displayHour = hourNum % 12 || 12;
            return `${displayHour}:${minutes} ${period}`;
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    </script>
</body>

</html>