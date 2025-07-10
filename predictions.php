<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictions - Student Attendance System</title>
    <style>
        :root {
            --primary-color: #1a1a1a;
            --primary-gradient: linear-gradient(135deg, #2c2c2c, #1a1a1a);
            --primary-hover: #2c2c2c;
            --secondary-color: #f43f5e;
            --secondary-gradient: linear-gradient(135deg, #f43f5e, #ec4899);
            --nav-color: #101010;
            --background-color: #f8fafc;
            --card-bg: #ffffff;
            --division-color: #e5e7eb;
            --boxshadow-color: rgba(0, 0, 0, 0.05);
            --blackfont-color: #1a1a1a;
            --whitefont-color: #ffffff;
            --grayfont-color: #6b7280;
            --border-color: #e5e7eb;
            --inputfield-color: #f3f4f6;
            --inputfieldhover-color: #e5e7eb;
            --buttonhover-color: #2c2c2c;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
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

        h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* KPI Cards */
        .kpi-grid {
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

        /* Charts */
        .chart-container {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
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
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            background: var(--inputfield-color);
            color: var(--grayfont-color);
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn.active {
            background: var(--primary-gradient);
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
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
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
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: var(--inputfield-color);
        }

        /* Student Prediction Card */
        .prediction-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        .prediction-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .selector-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition);
            width: 200px;
        }

        .selector-select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--inputfieldhover-color);
        }

        .prediction-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .detail-item {
            font-size: 14px;
        }

        .detail-item strong {
            color: var(--grayfont-color);
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .button {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            background: var(--primary-gradient);
            color: var(--whitefont-color);
            cursor: pointer;
            transition: var(--transition);
        }

        .button:hover {
            background: var(--buttonhover-color);
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .kpi-grid, .prediction-details {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            th, td {
                padding: 10px;
            }

            .card-value {
                font-size: 20px;
            }

            .prediction-filters {
                flex-direction: column;
            }

            .selector-select {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .table-responsive, .chart-container {
                overflow-x: auto;
            }

            .kpi-grid, .prediction-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <h1>Predictions</h1>

    <div class="filters">
        <select class="selector-select" id="class-filter">
            <option value="">All Classes</option>
            <option value="ClassA-1">Class A - Section 1</option>
            <option value="ClassA-2">Class A - Section 2</option>
            <option value="ClassB-1">Class B - Section 1</option>
        </select>
        <select class="selector-select" id="date-range">
            <option value="">All Time</option>
            <option value="week">Last Week</option>
            <option value="month">Last Month</option>
            <option value="year">Last Year</option>
        </select>
        <button class="button" id="refresh-data">Refresh Data</button>
        <button class="button" id="export-chart">Export Chart</button>
    </div>

    <div class="kpi-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Average Attendance Rate</div>
                    <div class="card-value" id="avg-attendance-rate">92.5%</div>
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
                    <div class="card-value" id="at-risk-students">3</div>
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
                    <div class="card-value" id="top-absenteeism-factor">Holiday</div>
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

    <!-- Predictive Factors Section -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">Top Factors Affecting Attendance</div>
            <div class="chart-filter">
                <button class="filter-btn" data-period="late" data-chart="factors">Late</button>
                <button class="filter-btn active" data-period="absent" data-chart="factors">Absent</button>
            </div>
        </div>
        <div>
            <canvas id="factors-chart" style="height: 300px; width: 100%;"></canvas>
        </div>
    </div>

    <div class="chart-container">
        <h2>Attendance Status Distribution</h2>
        <canvas id="attendance-status"></canvas>
    </div>

    <h2>Historical Analysis</h2>
    <div class="pattern-table">
        <h3>Student Behavior Patterns</h3>
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

    <h2>Enhanced Prediction Models</h2>
    <div class="prediction-card">
        <h3>Individual Student Predictions</h3>
        <div class="prediction-filters">
            <select class="selector-select" id="prediction-class-filter">
                <option value="">All Classes</option>
                <option value="ClassA-1">Class A - Section 1</option>
                <option value="ClassA-2">Class A - Section 2</option>
                <option value="ClassB-1">Class B - Section 1</option>
            </select>
            <select class="selector-select" id="student-selector">
                <option value="">Select Student</option>
            </select>
        </div>
        <div class="prediction-details" id="prediction-details"></div>
    </div>

    <div class="chart-container">
        <h2>Risk Analysis</h2>
        <canvas id="risk-analysis-chart"></canvas>
    </div>

    <h2>Predictive Analytics Dashboard</h2>
    <div class="pattern-table">
        <h3>Early Warning System</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class/Section</th>
                    <th>Risk Level</th>
                    <th>Intervention Success</th>
                    <th>Recommended Action</th>
                    <th>Urgency</th>
                </tr>
            </thead>
            <tbody id="early-warning"></tbody>
        </table>
    </div>
    <div class="pattern-table">
        <h3>Subject-Specific Patterns</h3>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Sample data
        const students = [
            { id: 'STU-001', name: 'Juan Dela Cruz', classSection: 'ClassA-1', attendance: { 2022: 90, 2023: 92, 2024: 93, 2025: 94 }, grades: { Math: 85, Science: 88 }, absences: [{ month: 'Dec', reason: 'Holiday', count: 3 }, { day: 'Monday', count: 5 }], consecutiveAbsences: 5 },
            { id: 'STU-002', name: 'Maria Santos', classSection: 'ClassA-2', attendance: { 2022: 85, 2023: 87, 2024: 86, 2025: 88 }, grades: { Math: 80, Science: 82 }, absences: [{ month: 'Dec', reason: 'Sick', count: 4 }, { day: 'Friday', count: 6 }], consecutiveAbsences: 12 },
            { id: 'STU-003', name: 'Pedro Penduko', classSection: 'ClassB-1', attendance: { 2022: 88, 2023: 90, 2024: 89, 2025: 91 }, grades: { Math: 90, Science: 92 }, absences: [{ month: 'Jan', reason: 'Family', count: 2 }, { day: 'Monday', count: 4 }], consecutiveAbsences: 8 },
            { id: 'STU-004', name: 'Anna Reyes', classSection: 'ClassA-1', attendance: { 2022: 95, 2023: 94, 2024: 96, 2025: 95 }, grades: { Math: 92, Science: 90 }, absences: [{ month: 'Dec', reason: 'Holiday', count: 2 }], consecutiveAbsences: 3 }
        ];

        const absenceData = [
            { month: 'December', reason: 'Holiday', frequency: '12/year' },
            { month: 'June', reason: 'Sick', frequency: '8/year' }
        ];

        const subjects = [
            { name: 'Math', classSection: 'ClassA-1', attendanceRate: 92 },
            { name: 'Science', classSection: 'ClassA-1', attendanceRate: 91 },
            { name: 'Math', classSection: 'ClassA-2', attendanceRate: 87 },
            { name: 'Science', classSection: 'ClassA-2', attendanceRate: 89 },
            { name: 'Math', classSection: 'ClassB-1', attendanceRate: 90 },
            { name: 'Science', classSection: 'ClassB-1', attendanceRate: 88 }
        ];

        const attendanceStatus = [
            { status: 'Present', count: 350 },
            { status: 'Absent', count: 30 },
            { status: 'Late', count: 15 },
            { status: 'Excused', count: 10 }
        ];

        const factorsData = {
            late: {
                labels: ['Health Issue', 'Transportation', 'Family Structure', 'Household Income'],
                values: [25, 20, 15, 10]
            },
            absent: {
                labels: ['Health Issue', 'Household Income', 'Transportation', 'Family Structure'],
                values: [28, 26, 20, 16]
            }
        };

        // DOM Elements
        const classFilter = document.getElementById('class-filter');
        const dateRange = document.getElementById('date-range');
        const studentSelector = document.getElementById('student-selector');
        const predictionClassFilter = document.getElementById('prediction-class-filter');
        const predictionDetails = document.getElementById('prediction-details');
        const behaviorPatterns = document.getElementById('behavior-patterns');
        const earlyWarning = document.getElementById('early-warning');
        const subjectPatterns = document.getElementById('subject-patterns');
        const avgAttendanceRate = document.getElementById('avg-attendance-rate');
        const atRiskStudents = document.getElementById('at-risk-students');
        const topAbsenteeismFactor = document.getElementById('top-absenteeism-factor');

        // Chart Contexts
        const factorsChartCtx = document.getElementById('factors-chart').getContext('2d');
        const attendanceStatusCtx = document.getElementById('attendance-status').getContext('2d');
        const riskAnalysisChartCtx = document.getElementById('risk-analysis-chart').getContext('2d');

        // Initialize Charts
        let factorsChart, attendanceStatusChart, riskAnalysis;

        function initializeCharts() {
            // Destroy existing charts to prevent duplication
            if (factorsChart) factorsChart.destroy();
            if (attendanceStatusChart) attendanceStatusChart.destroy();
            if (riskAnalysis) riskAnalysis.destroy();

            // Initialize Charts with initial data
            factorsChart = new Chart(factorsChartCtx, {
                type: 'bar',
                data: {
                    labels: factorsData.absent.labels,
                    datasets: [{
                        label: 'Percentage Impact',
                        data: factorsData.absent.values,
                        backgroundColor: '#3b82f6',
                        borderColor: '#3b82f6',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: true } },
                    scales: { y: { beginAtZero: true, max: 30, title: { display: true, text: 'Percentage' } } }
                }
            });

            attendanceStatusChart = new Chart(attendanceStatusCtx, {
                type: 'pie',
                data: {
                    labels: attendanceStatus.map(s => s.status),
                    datasets: [{
                        data: attendanceStatus.map(s => s.count),
                        backgroundColor: ['#10b981', '#f43f5e', '#3b82f6', '#6366f1']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { tooltip: { enabled: true }, legend: { position: 'top' } }
                }
            });

            riskAnalysis = new Chart(riskAnalysisChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                    datasets: [{
                        data: [60, 25, 15],
                        backgroundColor: ['#10b981', '#f43f5e', '#3b82f6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' }, tooltip: { enabled: true } }
                }
            });
        }

        // Update student selector based on class filter
        function updateStudentSelector() {
            const selectedClass = predictionClassFilter.value;
            let filteredStudents = students;
            
            if (selectedClass) {
                filteredStudents = students.filter(s => s.classSection === selectedClass);
            }

            // Clear existing options except the first one
            studentSelector.innerHTML = '<option value="">Select Student</option>';
            
            // Add filtered students
            filteredStudents.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.name} (${student.classSection})`;
                studentSelector.appendChild(option);
            });

            // Clear prediction details when filter changes
            predictionDetails.innerHTML = '';
        }

        // Filter and update data
        function updateData() {
            const selectedClass = classFilter.value;
            const selectedDate = dateRange.value;

            let filteredStudents = students;
            if (selectedClass) {
                filteredStudents = students.filter(s => s.classSection === selectedClass);
            }

            // Update KPI cards
            const totalAttendance = filteredStudents.reduce((sum, s) => sum + s.attendance[2025], 0) / filteredStudents.length || 92.5;
            avgAttendanceRate.textContent = `${totalAttendance.toFixed(1)}%`;
            const atRiskCount = filteredStudents.filter(s => s.attendance[2025] < 90 || s.consecutiveAbsences >= 10).length;
            atRiskStudents.textContent = atRiskCount;
            const topFactor = absenceData.sort((a, b) => b.frequency.localeCompare(a.frequency))[0]?.reason || 'Holiday';
            topAbsenteeismFactor.textContent = topFactor;

            // Update Tables
            behaviorPatterns.innerHTML = '';
            const behaviorData = filteredStudents.map(s => ({
                student: s.name,
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
                const avgAttendance = (s.attendance[2022] + s.attendance[2023] + s.attendance[2024]) / 3;
                const riskLevel = avgAttendance < 88 ? 'High' : avgAttendance < 90 ? 'Medium' : 'Low';
                const success = riskLevel === 'High' ? '60%' : '80%';
                const action = riskLevel === 'High' ? 'Parent Meeting' : 'Monitor';
                const urgency = riskLevel === 'High' ? '1 week' : '1 month';
                return { student: s.name, classSection: s.classSection, riskLevel, success, action, urgency };
            });
            earlyWarningData.forEach(data => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${data.student}</td>
                    <td>${data.classSection}</td>
                    <td><span class="risk-${data.riskLevel.toLowerCase()}">${data.riskLevel}</span></td>
                    <td>${data.success}</td>
                    <td>${data.action}</td>
                    <td>${data.urgency}</td>
                `;
                earlyWarning.appendChild(row);
            });

            // Update subject patterns table
            subjectPatterns.innerHTML = '';
            let filteredSubjects = subjects;
            if (selectedClass) {
                filteredSubjects = subjects.filter(s => s.classSection === selectedClass);
            }
            
            filteredSubjects.forEach(subject => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${subject.name}</td>
                    <td>${subject.classSection}</td>
                    <td>${subject.attendanceRate}%</td>
                `;
                subjectPatterns.appendChild(row);
            });
        }

        // Show prediction details for selected student
        function showPredictionDetails() {
            const selectedStudentId = studentSelector.value;
            
            if (!selectedStudentId) {
                predictionDetails.innerHTML = '';
                return;
            }

            const student = students.find(s => s.id === selectedStudentId);
            if (!student) return;

            const avgAttendance = (student.attendance[2022] + student.attendance[2023] + student.attendance[2024]) / 3;
            const trend = student.attendance[2025] > avgAttendance ? 'Improving' : 'Declining';
            const riskLevel = avgAttendance < 88 ? 'High' : avgAttendance < 90 ? 'Medium' : 'Low';
            const predictedAttendance = Math.min(100, student.attendance[2025] + (trend === 'Improving' ? 2 : -1));

            predictionDetails.innerHTML = `
                <div class="detail-item">
                    <strong>Current Attendance:</strong> ${student.attendance[2025]}%
                </div>
                <div class="detail-item">
                    <strong>Predicted Next Month:</strong> ${predictedAttendance}%
                </div>
                <div class="detail-item">
                    <strong>Trend:</strong> ${trend}
                </div>
                <div class="detail-item">
                    <strong>Risk Level:</strong> ${riskLevel}
                </div>
                <div class="detail-item">
                    <strong>Consecutive Absences:</strong> ${student.consecutiveAbsences}
                </div>
                <div class="detail-item">
                    <strong>Primary Absence Reason:</strong> ${student.absences[0]?.reason || 'N/A'}
                </div>
            `;
        }

        // Chart filter functionality
        function updateFactorsChart(period) {
            const data = factorsData[period];
            factorsChart.data.labels = data.labels;
            factorsChart.data.datasets[0].data = data.values;
            factorsChart.update();
        }

        // Event Listeners
        classFilter.addEventListener('change', updateData);
        dateRange.addEventListener('change', updateData);
        predictionClassFilter.addEventListener('change', updateStudentSelector);
        studentSelector.addEventListener('change', showPredictionDetails);

        // Chart filter buttons
        document.querySelectorAll('.filter-btn[data-chart="factors"]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn[data-chart="factors"]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                updateFactorsChart(this.dataset.period);
            });
        });

        // Refresh data button
        document.getElementById('refresh-data').addEventListener('click', function() {
            updateData();
            console.log('Data refreshed');
        });

        // Export chart button
        document.getElementById('export-chart').addEventListener('click', function() {
            const canvas = document.getElementById('factors-chart');
            const link = document.createElement('a');
            link.download = 'attendance-factors-chart.png';
            link.href = canvas.toDataURL();
            link.click();
        });

        // Initialize everything
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            updateStudentSelector();
            updateData();
        });

    </script>
</body>
</html>