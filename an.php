<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Predictions - Student Attendance System</title>
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

        .selector-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition);
            width: 200px;
            margin-bottom: 15px;
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
    <h1>Analytics & Predictions</h1>

    <div class="kpi-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total Attendance Rate</div>
                    <div class="card-value" id="total-attendance-rate">92.5%</div>
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
                    <div class="card-title">High-Risk Students</div>
                    <div class="card-value" id="high-risk-students">3</div>
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
                    <div class="card-title">Average Absence Days</div>
                    <div class="card-value" id="avg-absence-days">4.2</div>
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
    <div class="chart-container">
        <h2>Multi-Year Attendance Trend</h2>
        <canvas id="multi-year-trend"></canvas>
    </div>
    <div class="chart-container">
        <h2>Historical vs Current Metrics</h2>
        <canvas id="historical-current"></canvas>
    </div>

    <!-- Historical Analysis Section -->
    <h2>Historical Analysis</h2>
    <div class="chart-container">
        <h3>Multi-Year Attendance Patterns</h3>
        <canvas id="multi-year-patterns"></canvas>
    </div>
    <div class="chart-container">
        <h3>Seasonal Trend Analysis</h3>
        <canvas id="seasonal-trends"></canvas>
    </div>
    <div class="chart-container">
        <h3>Long-Term Performance Indicators</h3>
        <canvas id="performance-indicators"></canvas>
    </div>
    <div class="chart-container">
        <h3>Year-Over-Year Comparison</h3>
        <canvas id="year-over-year"></canvas>
    </div>
    <div class="pattern-table">
        <h3>Recurring Absence Patterns</h3>
        <table>
            <thead>
                <tr>
                    <th>Month/Season</th>
                    <th>Reason</th>
                    <th>Frequency</th>
                </tr>
            </thead>
            <tbody id="absence-patterns"></tbody>
        </table>
    </div>
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
    <div class="chart-container">
        <h3>Attendance vs Grades Correlation</h3>
        <canvas id="correlation-chart"></canvas>
    </div>
    <div class="pattern-table">
        <h3>Long-Term Trends</h3>
        <p id="long-term-trends">Attendance dips in December due to holidays; increases in March due to exam preparations.</p>
    </div>

    <!-- Enhanced Prediction Models -->
    <h2>Enhanced Prediction Models</h2>
    <div class="prediction-card">
        <h3>Individual Student Predictions</h3>
        <select class="selector-select" id="student-selector">
            <option value="">Select Student</option>
        </select>
        <div class="prediction-details" id="prediction-details"></div>
    </div>
    <div class="chart-container">
        <h3>Weekly/Monthly Attendance Forecast</h3>
        <canvas id="forecast-chart"></canvas>
        <p>Seasonal Adjustments: Lower attendance predicted in December (holidays). Academic Calendar: Exams in March boost attendance. Weather: Rainy seasons (June-August) correlate with 5% lower attendance. Historical Accuracy: 85%.</p>
    </div>

    <!-- Predictive Analytics Dashboard -->
    <h2>Predictive Analytics Dashboard</h2>
    <div class="pattern-table">
        <h3>Early Warning System</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Risk Level</th>
                    <th>Intervention Success</th>
                    <th>Recommended Action</th>
                    <th>Urgency</th>
                </tr>
            </thead>
            <tbody id="early-warning"></tbody>
        </table>
    </div>
    <div class="chart-container">
        <h3>Attendance vs Grades Correlation</h3>
        <canvas id="performance-correlation"></canvas>
    </div>
    <div class="pattern-table">
        <h3>Subject-Specific Patterns</h3>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Attendance Rate</th>
                    <th>Average Grade</th>
                </tr>
            </thead>
            <tbody id="subject-patterns"></tbody>
        </table>
    </div>
    <div class="pattern-table">
        <h3>Academic Outcome Predictions</h3>
        <p id="outcome-predictions">Students with >90% attendance are 80% likely to achieve grades above 85. Historical validation: 80% accurate (2022-2024).</p>
    </div>
    <div class="pattern-table">
        <h3>Recommendations</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Recommendation</th>
                    <th>Priority</th>
                    <th>Expected Impact</th>
                    <th>Historical Basis</th>
                </tr>
            </thead>
            <tbody id="recommendations"></tbody>
        </table>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Sample data
        const students = [
            { id: 'STU-001', name: 'Juan Dela Cruz', attendance: { 2022: 90, 2023: 92, 2024: 93, 2025: 94 }, grades: { Math: 85, Science: 88 }, absences: [{ month: 'Dec', reason: 'Holiday', count: 3 }, { day: 'Monday', count: 5 }] },
            { id: 'STU-002', name: 'Maria Santos', attendance: { 2022: 85, 2023: 87, 2024: 86, 2025: 88 }, grades: { Math: 80, Science: 82 }, absences: [{ month: 'Dec', reason: 'Sick', count: 4 }, { day: 'Friday', count: 6 }] },
            { id: 'STU-003', name: 'Pedro Penduko', attendance: { 2022: 88, 2023: 90, 2024: 89, 2025: 91 }, grades: { Math: 90, Science: 92 }, absences: [{ month: 'Jan', reason: 'Family', count: 2 }, { day: 'Monday', count: 4 }] },
            { id: 'STU-004', name: 'Anna Reyes', attendance: { 2022: 95, 2023: 94, 2024: 96, 2025: 95 }, grades: { Math: 92, Science: 90 }, absences: [{ month: 'Dec', reason: 'Holiday', count: 2 }] }
        ];

        const absenceData = [
            { month: 'December', reason: 'Holiday', frequency: '12/year' },
            { month: 'June', reason: 'Rainy Season', frequency: '8/year' }
        ];

        // DOM Elements
        const studentSelector = document.getElementById('student-selector');
        const predictionDetails = document.getElementById('prediction-details');
        const absencePatterns = document.getElementById('absence-patterns');
        const behaviorPatterns = document.getElementById('behavior-patterns');
        const earlyWarning = document.getElementById('early-warning');
        const subjectPatterns = document.getElementById('subject-patterns');
        const recommendations = document.getElementById('recommendations');

        // Populate student selector
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.name;
            studentSelector.appendChild(option);
        });

        // Initialize charts
        const multiYearTrend = new Chart(document.getElementById('multi-year-trend').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['2022', '2023', '2024', '2025'],
                datasets: students.map(student => ({
                    label: student.name,
                    data: [student.attendance[2022], student.attendance[2023], student.attendance[2024], student.attendance[2025]],
                    borderColor: `#${Math.floor(Math.random()*16777215).toString(16)}`,
                    tension: 0.4,
                    fill: false
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: false, min: 80, max: 100 } },
                plugins: { legend: { display: true } }
            }
        });

        const historicalCurrent = new Chart(document.getElementById('historical-current').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Attendance Rate', 'Absence Days'],
                datasets: [
                    { label: 'Historical (2022-2024)', data: [90.3, 4.5], backgroundColor: '#3b82f6' },
                    { label: 'Current (2025)', data: [92.5, 4.2], backgroundColor: '#10b981' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });

        const multiYearPatterns = new Chart(document.getElementById('multi-year-patterns').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['2022', '2023', '2024', '2025'],
                datasets: [{
                    label: 'Average Attendance',
                    data: [90, 91, 92, 92.5],
                    borderColor: '#6366f1',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: false, min: 80, max: 100 } }
            }
        });

        const seasonalTrends = new Chart(document.getElementById('seasonal-trends').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    { label: '2022', data: [92, 93, 94, 91, 90, 89, 88, 89, 90, 91, 92, 88], backgroundColor: '#3b82f6' },
                    { label: '2023', data: [93, 94, 95, 92, 91, 90, 89, 90, 91, 92, 93, 87], backgroundColor: '#10b981' },
                    { label: '2024', data: [94, 95, 96, 93, 92, 91, 90, 91, 92, 93, 94, 86], backgroundColor: '#f59e0b' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: false, min: 80, max: 100 } }
            }
        });

        const performanceIndicators = new Chart(document.getElementById('performance-indicators').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['2022', '2023', '2024', '2025'],
                datasets: [
                    { label: 'Attendance', data: [90, 91, 92, 92.5], borderColor: '#6366f1', tension: 0.4 },
                    { label: 'Grades', data: [85, 86, 87, 88], borderColor: '#10b981', tension: 0.4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: false, min: 80, max: 100 } }
            }
        });

        const yearOverYear = new Chart(document.getElementById('year-over-year').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['2022', '2023', '2024', '2025'],
                datasets: [{
                    label: 'Attendance Rate',
                    data: [90, 91, 92, 92.5],
                    backgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: false, min: 80, max: 100 } }
            }
        });

        const correlationChart = new Chart(document.getElementById('correlation-chart').getContext('2d'), {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Attendance vs Grades',
                    data: students.map(s => ({ x: s.attendance[2025], y: (s.grades.Math + s.grades.Science) / 2 })),
                    backgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Attendance (%)' }, min: 80, max: 100 },
                    y: { title: { display: true, text: 'Average Grade' }, min: 70, max: 100 }
                }
            }
        });

        const performanceCorrelation = new Chart(document.getElementById('performance-correlation').getContext('2d'), {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Attendance vs Grades',
                    data: students.map(s => ({ x: s.attendance[2025], y: (s.grades.Math + s.grades.Science) / 2 })),
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Attendance (%)' }, min: 80, max: 100 },
                    y: { title: { display: true, text: 'Average Grade' }, min: 70, max: 100 }
                }
            }
        });

        const forecastChart = new Chart(document.getElementById('forecast-chart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Month 2'],
                datasets: [{
                    label: 'Predicted Attendance',
                    data: [92, 91, 90, 89, 88],
                    borderColor: '#6366f1',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: false, min: 80, max: 100 } }
            }
        });

        // Populate tables
        absenceData.forEach(data => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${data.month}</td><td>${data.reason}</td><td>${data.frequency}</td>`;
            absencePatterns.appendChild(row);
        });

        const behaviorData = students.map(s => ({
            student: s.name,
            pattern: s.absences.find(a => a.day) ? `Frequent ${s.absences.find(a => a.day).day} absences` : 'None',
            frequency: s.absences.find(a => a.day) ? s.absences.find(a => a.day).count : 0
        }));
        behaviorData.forEach(data => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${data.student}</td><td>${data.pattern}</td><td>${data.frequency}</td>`;
            behaviorPatterns.appendChild(row);
        });

        const earlyWarningData = students.map(s => {
            const avgAttendance = (s.attendance[2022] + s.attendance[2023] + s.attendance[2024]) / 3;
            const riskLevel = avgAttendance < 88 ? 'High' : avgAttendance < 90 ? 'Medium' : 'Low';
            return {
                student: s.name,
                riskLevel,
                success: riskLevel === 'High' ? '60%' : '80%',
                action: riskLevel === 'High' ? 'Parent Meeting' : 'Monitor',
                urgency: riskLevel === 'High' ? '1 week' : '1 month'
            };
        });
        earlyWarningData.forEach(data => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${data.student}</td><td>${data.riskLevel}</td><td>${data.success}</td><td>${data.action}</td><td>${data.urgency}</td>`;
            earlyWarning.appendChild(row);
        });

        const subjectData = [
            { subject: 'Math', attendance: 92, grade: 86 },
            { subject: 'Science', attendance: 91, grade: 88 }
        ];
        subjectData.forEach(data => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${data.subject}</td><td>${data.attendance}%</td><td>${data.grade}</td>`;
            subjectPatterns.appendChild(row);
        });

        // Populate recommendations
        const recommendationData = students.map(s => {
            const avgAttendance = (s.attendance[2022] + s.attendance[2023] + s.attendance[2024]) / 3;
            const riskLevel = avgAttendance < 88 ? 'High' : avgAttendance < 90 ? 'Medium' : 'Low';
            let recommendation = '';
            let priority = riskLevel;
            let expectedImpact = riskLevel === 'High' ? '5% attendance increase' : '2% attendance increase';
            let historicalBasis = riskLevel === 'High' ? '60% success in 2022-2024' : '80% success in 2022-2024';

            if (riskLevel === 'High') {
                recommendation = `Schedule a parent meeting to address frequent absences (${s.absences.map(a => a.reason).join(', ')}).`;
            } else if (riskLevel === 'Medium') {
                recommendation = `Monitor attendance and offer counseling for ${s.absences.map(a => a.reason).join(', ')}.`;
            } else {
                recommendation = `Continue monitoring; no immediate action needed.`;
                expectedImpact = 'Maintain current attendance';
            }

            // Add seasonal recommendations
            const decAbsences = s.absences.find(a => a.month === 'Dec');
            if (decAbsences) {
                recommendation += ` Pre-holiday counseling recommended due to ${decAbsences.count} absences in December.`;
                priority = 'High';
                expectedImpact = '5% attendance increase';
                historicalBasis = '70% success for holiday-related interventions';
            }

            return {
                student: s.name,
                recommendation,
                priority,
                expectedImpact,
                historicalBasis
            };
        });

        recommendationData.forEach(data => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${data.student}</td>
                <td>${data.recommendation}</td>
                <td>${data.priority}</td>
                <td>${data.expectedImpact}</td>
                <td>${data.historicalBasis}</td>
            `;
            recommendations.appendChild(row);
        });

        // Student prediction
        studentSelector.addEventListener('change', () => {
            const studentId = studentSelector.value;
            if (!studentId) {
                predictionDetails.innerHTML = '';
                return;
            }
            const student = students.find(s => s.id === studentId);
            const avgAttendance = (student.attendance[2022] + student.attendance[2023] + student.attendance[2024]) / 3;
            const probability = Math.round(100 - (100 - avgAttendance) * 1.2);
            const riskLevel = avgAttendance < 88 ? 'High' : avgAttendance < 90 ? 'Medium' : 'Low';
            const factors = student.absences.map(a => `${a.reason} (${a.month})`).join(', ');
            const similarStudents = students.filter(s => s.id !== studentId && Math.abs(s.attendance[2025] - student.attendance[2025]) < 5).map(s => s.name).join(', ');
            const studentRecommendation = recommendationData.find(r => r.student === student.name);

            predictionDetails.innerHTML = `
                <div class="detail-item"><strong>Attendance Probability:</strong> ${probability}%</div>
                <div class="detail-item"><strong>Risk Level:</strong> ${riskLevel}</div>
                <div class="detail-item"><strong>Contributing Factors:</strong> ${factors || 'None'}</div>
                <div class="detail-item"><strong>Similar Students:</strong> ${similarStudents || 'None'}</div>
                <div class="detail-item"><strong>Recommendation:</strong> ${studentRecommendation.recommendation}</div>
            `;
        });
    </script>
</body>
</html>