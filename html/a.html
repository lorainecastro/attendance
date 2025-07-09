<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Prediction Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }

        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: #34495e;
            font-size: 0.9em;
        }

        select, input {
            padding: 12px 15px;
            border: 2px solid #bdc3c7;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: white;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #ecf0f1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3em;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .student-dashboard {
            grid-column: 1 / -1;
            display: none;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .metric-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .metric-card.warning {
            background: linear-gradient(135deg, #f39c12, #d68910);
        }

        .metric-card.danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .metric-card.success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .metric-value {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .risk-indicator {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .risk-low {
            background: #d5f4e6;
            color: #27ae60;
        }

        .risk-medium {
            background: #fef5e7;
            color: #f39c12;
        }

        .risk-high {
            background: #fadbd8;
            color: #e74c3c;
        }

        .prediction-section {
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .prediction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .prediction-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .prediction-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .prediction-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #3498db;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        .attendance-pattern {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-top: 20px;
        }

        .day-cell {
            aspect-ratio: 1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
        }

        .day-present {
            background: #27ae60;
        }

        .day-absent {
            background: #e74c3c;
        }

        .day-late {
            background: #f39c12;
        }

        .day-future {
            background: #bdc3c7;
        }

        .recommendations {
            margin-top: 25px;
            padding: 20px;
            background: #e8f5e8;
            border-radius: 10px;
            border-left: 4px solid #27ae60;
        }

        .recommendations h4 {
            color: #27ae60;
            margin-bottom: 15px;
        }

        .recommendations ul {
            list-style: none;
        }

        .recommendations li {
            padding: 5px 0;
            position: relative;
            padding-left: 20px;
        }

        .recommendations li:before {
            content: "✓";
            color: #27ae60;
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .class-overview {
            grid-column: 1 / -1;
        }

        .class-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Analytics & Prediction Dashboard</h1>
            <p>Time Series Forecasting and Regression Analysis for Student Attendance</p>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label for="classSelect">Select Class:</label>
                <select id="classSelect">
                    <option value="">All Classes</option>
                    <option value="grade7-a">Grade 7-A</option>
                    <option value="grade7-b">Grade 7-B</option>
                    <option value="grade8-a">Grade 8-A</option>
                    <option value="grade8-b">Grade 8-B</option>
                    <option value="grade9-a">Grade 9-A</option>
                    <option value="grade10-a">Grade 10-A</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="studentSelect">Select Student:</label>
                <select id="studentSelect">
                    <option value="">Select a student...</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="dateRange">Date Range:</label>
                <input type="date" id="startDate" value="2024-01-01">
                <input type="date" id="endDate" value="2024-12-31">
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Class Overview -->
            <div class="card class-overview" id="classOverview">
                <h3>📚 Class Overview</h3>
                <div class="class-stats">
                    <div class="stat-item">
                        <div class="stat-value" id="totalStudents">35</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="avgAttendance">87%</div>
                        <div class="stat-label">Avg Attendance</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="highRiskStudents">3</div>
                        <div class="stat-label">High Risk</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="perfectAttendance">8</div>
                        <div class="stat-label">Perfect Attendance</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="classAttendanceChart"></canvas>
                </div>
            </div>

            <!-- Attendance Trends -->
            <div class="card">
                <h3>📈 Attendance Trends</h3>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Risk Analysis -->
            <div class="card">
                <h3>⚠️ Risk Analysis</h3>
                <div class="chart-container">
                    <canvas id="riskChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Student Dashboard -->
        <div class="card student-dashboard" id="studentDashboard">
            <h3>👨‍🎓 Student Dashboard - <span id="studentName">Select a student</span></h3>
            
            <div class="metrics-grid">
                <div class="metric-card success">
                    <div class="metric-value" id="studentAttendanceRate">85%</div>
                    <div class="metric-label">Attendance Rate</div>
                </div>
                <div class="metric-card warning">
                    <div class="metric-value" id="studentAbsences">12</div>
                    <div class="metric-label">Total Absences</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="studentLates">5</div>
                    <div class="metric-label">Late Arrivals</div>
                </div>
                <div class="metric-card danger">
                    <div class="metric-value">
                        <span class="risk-indicator risk-medium" id="riskLevel">Medium Risk</span>
                    </div>
                    <div class="metric-label">Risk Assessment</div>
                </div>
            </div>

            <!-- Attendance Pattern -->
            <div>
                <h4>📅 Attendance Pattern (Last 4 Weeks)</h4>
                <div class="attendance-pattern" id="attendancePattern"></div>
            </div>

            <!-- Prediction Section -->
            <div class="prediction-section">
                <h4>🔮 Predictive Analytics (Time Series Forecasting)</h4>
                <div class="prediction-grid">
                    <div class="prediction-card">
                        <div class="prediction-title">Tomorrow's Attendance</div>
                        <div class="prediction-value" id="tomorrowPrediction">78%</div>
                        <small>Probability of being present</small>
                    </div>
                    <div class="prediction-card">
                        <div class="prediction-title">Weekly Forecast</div>
                        <div class="prediction-value" id="weeklyForecast">3-4</div>
                        <small>Expected absences this week</small>
                    </div>
                    <div class="prediction-card">
                        <div class="prediction-title">Monthly Projection</div>
                        <div class="prediction-value" id="monthlyProjection">23%</div>
                        <small>Risk of chronic absenteeism</small>
                    </div>
                    <div class="prediction-card">
                        <div class="prediction-title">Semester Outlook</div>
                        <div class="prediction-value" id="semesterOutlook">67%</div>
                        <small>Meeting minimum requirement</small>
                    </div>
                </div>
            </div>

            <!-- Regression Analysis Section -->
            <div class="prediction-section" style="margin-top: 20px; background: #f0f8ff;">
                <h4>📈 Regression Analysis - Risk Factors</h4>
                <div class="prediction-grid">
                    <div class="prediction-card" style="border-left-color: #9b59b6;">
                        <div class="prediction-title">Gender Impact</div>
                        <div class="prediction-value" id="genderImpact">Female +5%</div>
                        <small>Attendance advantage</small>
                    </div>
                    <div class="prediction-card" style="border-left-color: #e67e22;">
                        <div class="prediction-title">Geographic Factor</div>
                        <div class="prediction-value" id="geoFactor">Urban +8%</div>
                        <small>Location advantage</small>
                    </div>
                    <div class="prediction-card" style="border-left-color: #27ae60;">
                        <div class="prediction-title">Economic Status</div>
                        <div class="prediction-value" id="economicImpact">Middle -3%</div>
                        <small>Income group effect</small>
                    </div>
                    <div class="prediction-card" style="border-left-color: #e74c3c;">
                        <div class="prediction-title">Family Structure</div>
                        <div class="prediction-value" id="familyImpact">Both Parents +12%</div>
                        <small>Family stability effect</small>
                    </div>
                    <div class="prediction-card" style="border-left-color: #f39c12;">
                        <div class="prediction-title">Health Status</div>
                        <div class="prediction-value" id="healthImpact">Chronic Issue -15%</div>
                        <small>Health condition impact</small>
                    </div>
                </div>
            </div>

            <!-- Student Trend Chart -->
            <div class="chart-container">
                <canvas id="studentTrendChart"></canvas>
            </div>

            <!-- Recommendations -->
            <div class="recommendations">
                <h4>💡 AI-Generated Recommendations (Question 3a)</h4>
                <ul id="recommendationsList">
                    <li>Schedule a parent-teacher conference to discuss attendance patterns</li>
                    <li>Implement a morning check-in system with personalized reminders</li>
                    <li>Consider flexible arrival times for students with transportation issues</li>
                    <li>Provide additional support during identified high-risk periods</li>
                </ul>
            </div>

            <!-- System Features (Question 3b-d) -->
            <div class="prediction-section" style="margin-top: 20px; background: #f8f9fa;">
                <h4>🚀 System Features</h4>
                <div class="prediction-grid">
                    <div class="prediction-card">
                        <div class="prediction-title">📊 Real-Time Notifications</div>
                        <div class="prediction-value" id="notifications">3 Active</div>
                        <small>Attendance alerts today</small>
                    </div>
                    <div class="prediction-card">
                        <div class="prediction-title">📈 Class Analytics</div>
                        <div class="prediction-value" id="analyticsUpdated">Live</div>
                        <small>Dashboard updated every 5 min</small>
                    </div>
                    <div class="prediction-card">
                        <div class="prediction-title">📄 Exportable Reports</div>
                        <div class="prediction-value">
                            <button onclick="exportReport()" style="background: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">Export PDF</button>
                        </div>
                        <small>Generate detailed reports</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample data
        const classData = {
            'grade7-a': [
                { id: 1, name: 'Juan Dela Cruz', attendance: 95, absences: 4, lates: 2, risk: 'low' },
                { id: 2, name: 'Maria Santos', attendance: 85, absences: 12, lates: 5, risk: 'medium' },
                { id: 3, name: 'Pedro Garcia', attendance: 72, absences: 22, lates: 8, risk: 'high' },
                { id: 4, name: 'Ana Reyes', attendance: 98, absences: 1, lates: 1, risk: 'low' },
                { id: 5, name: 'Carlos Mendoza', attendance: 88, absences: 9, lates: 3, risk: 'low' }
            ],
            'grade7-b': [
                { id: 6, name: 'Sofia Rodriguez', attendance: 91, absences: 7, lates: 2, risk: 'low' },
                { id: 7, name: 'Miguel Torres', attendance: 78, absences: 17, lates: 6, risk: 'medium' },
                { id: 8, name: 'Isabella Cruz', attendance: 94, absences: 5, lates: 1, risk: 'low' }
            ]
        };

        let classChart, trendsChart, riskChart, studentChart;

        // Initialize charts
        function initializeCharts() {
            // Class Attendance Chart
            const classCtx = document.getElementById('classAttendanceChart').getContext('2d');
            classChart = new Chart(classCtx, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                    datasets: [{
                        label: 'Class Attendance Rate',
                        data: [92, 88, 85, 89, 91, 87],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Trends Chart
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            trendsChart = new Chart(trendsCtx, {
                type: 'bar',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        label: 'Count',
                        data: [782, 98, 45],
                        backgroundColor: ['#27ae60', '#e74c3c', '#f39c12']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Risk Chart
            const riskCtx = document.getElementById('riskChart').getContext('2d');
            riskChart = new Chart(riskCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                    datasets: [{
                        data: [28, 5, 2],
                        backgroundColor: ['#27ae60', '#f39c12', '#e74c3c']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Initialize student trend chart
        function initializeStudentChart() {
            const studentCtx = document.getElementById('studentTrendChart').getContext('2d');
            studentChart = new Chart(studentCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Attendance Rate',
                        data: [95, 92, 88, 85, 82, 85],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Generate attendance pattern
        function generateAttendancePattern() {
            const pattern = document.getElementById('attendancePattern');
            pattern.innerHTML = '';
            
            const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const statuses = ['present', 'absent', 'late', 'present', 'present', 'future', 'future'];
            
            for (let week = 0; week < 4; week++) {
                days.forEach((day, index) => {
                    const cell = document.createElement('div');
                    cell.className = `day-cell day-${statuses[index]}`;
                    cell.textContent = day.charAt(0);
                    cell.title = `${day} - Week ${week + 1}`;
                    pattern.appendChild(cell);
                });
            }
        }

        // Update student dropdown based on selected class
        function updateStudentDropdown() {
            const classSelect = document.getElementById('classSelect');
            const studentSelect = document.getElementById('studentSelect');
            const selectedClass = classSelect.value;

            studentSelect.innerHTML = '<option value="">Select a student...</option>';

            if (selectedClass && classData[selectedClass]) {
                classData[selectedClass].forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = student.name;
                    studentSelect.appendChild(option);
                });
            }
        }

        // Show student dashboard
        function showStudentDashboard() {
            const studentSelect = document.getElementById('studentSelect');
            const studentDashboard = document.getElementById('studentDashboard');
            const classSelect = document.getElementById('classSelect');
            
            if (studentSelect.value && classSelect.value) {
                const selectedClass = classSelect.value;
                const selectedStudent = classData[selectedClass].find(s => s.id == studentSelect.value);
                
                if (selectedStudent) {
                    studentDashboard.style.display = 'block';
                    
                    // Update student info
                    document.getElementById('studentName').textContent = selectedStudent.name;
                    document.getElementById('studentAttendanceRate').textContent = selectedStudent.attendance + '%';
                    document.getElementById('studentAbsences').textContent = selectedStudent.absences;
                    document.getElementById('studentLates').textContent = selectedStudent.lates;
                    
                    // Update risk level
                    const riskElement = document.getElementById('riskLevel');
                    riskElement.textContent = selectedStudent.risk.charAt(0).toUpperCase() + selectedStudent.risk.slice(1) + ' Risk';
                    riskElement.className = `risk-indicator risk-${selectedStudent.risk}`;
                    
                    // Generate attendance pattern
                    generateAttendancePattern();
                    
                    // Update predictions based on risk level
                    updatePredictions(selectedStudent.risk);
                    
                    // Initialize student chart if not already done
                    if (!studentChart) {
                        initializeStudentChart();
                    }
                }
            } else {
                studentDashboard.style.display = 'none';
            }
        }

        // Update predictions based on risk level
        function updatePredictions(riskLevel) {
            const predictions = {
                low: {
                    tomorrow: '92%',
                    weekly: '0-1',
                    monthly: '5%',
                    semester: '95%'
                },
                medium: {
                    tomorrow: '78%',
                    weekly: '2-3',
                    monthly: '23%',
                    semester: '67%'
                },
                high: {
                    tomorrow: '45%',
                    weekly: '4-5',
                    monthly: '65%',
                    semester: '35%'
                }
            };

            const pred = predictions[riskLevel];
            document.getElementById('tomorrowPrediction').textContent = pred.tomorrow;
            document.getElementById('weeklyForecast').textContent = pred.weekly;
            document.getElementById('monthlyProjection').textContent = pred.monthly;
            document.getElementById('semesterOutlook').textContent = pred.semester;
        }

        // Regression Analysis - Update factor impacts
        function updateRegressionFactors(student) {
            // Simulate regression coefficients for different factors
            const factors = {
                gender: student.gender === 'Female' ? '+5%' : '+2%',
                location: student.location === 'Urban' ? '+8%' : '-3%',
                economic: student.economic === 'Middle' ? '-3%' : '+1%',
                family: student.family === 'Both Parents' ? '+12%' : '-8%',
                health: student.health === 'Chronic Issue' ? '-15%' : '+0%'
            };
            
            document.getElementById('genderImpact').textContent = factors.gender;
            document.getElementById('geoFactor').textContent = factors.location;
            document.getElementById('economicImpact').textContent = factors.economic;
            document.getElementById('familyImpact').textContent = factors.family;
            document.getElementById('healthImpact').textContent = factors.health;
        }

        // Time Series Forecasting - Generate trend predictions
        function generateTimeSeriesForecasting(studentData) {
            // Simulate ARIMA/LSTM prediction algorithm
            const historicalData = [95, 92, 88, 85, 82, 85]; // Last 6 months
            const trendSlope = calculateTrend(historicalData);
            const seasonalFactor = calculateSeasonality();
            
            // Future predictions using time series
            const nextMonthPrediction = historicalData[historicalData.length - 1] + trendSlope + seasonalFactor;
            const confidence = 0.85; // 85% confidence interval
            
            return {
                prediction: Math.round(nextMonthPrediction),
                confidence: Math.round(confidence * 100),
                trend: trendSlope > 0 ? 'Improving' : 'Declining'
            };
        }

        function calculateTrend(data) {
            // Simple linear regression for trend
            const n = data.length;
            const sumX = n * (n + 1) / 2;
            const sumY = data.reduce((a, b) => a + b, 0);
            const sumXY = data.reduce((sum, y, i) => sum + (i + 1) * y, 0);
            const sumX2 = n * (n + 1) * (2 * n + 1) / 6;
            
            return (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
        }

        function calculateSeasonality() {
            // Simulate seasonal adjustment
            const currentMonth = new Date().getMonth();
            const seasonalAdjustments = [2, 1, 3, 0, -1, -2, -3, -1, 1, 2, 1, 0];
            return seasonalAdjustments[currentMonth];
        }

        // Export functionality
        function exportReport() {
            alert('Generating detailed attendance report with Time Series Forecasting and Regression Analysis...\n\nReport will include:\n- Individual student predictions\n- Risk factor analysis\n- Intervention recommendations\n- Statistical confidence intervals');
        }

        // Event listeners
        document.getElementById('classSelect').addEventListener('change', () => {
            updateStudentDropdown();
            showStudentDashboard();
        });

        document.getElementById('studentSelect').addEventListener('change', showStudentDashboard);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeCharts();
            updateStudentDropdown();
        });
    </script>
</body>
</html>