<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Series Analytics & Predictions - Student Attendance System</title>
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
        .bg-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

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

        .card-trend {
            font-size: var(--font-size-sm);
            margin-top: var(--spacing-xs);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .trend-up { color: var(--success-color); }
        .trend-down { color: var(--danger-color); }

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
            max-height: 400px;
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

        .risk-high { color: var(--danger-color); font-weight: 600; }
        .risk-medium { color: var(--warning-color); font-weight: 600; }
        .risk-low { color: var(--success-color); font-weight: 600; }

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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .detail-item {
            font-size: var(--font-size-sm);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            background: var(--inputfield-color);
            border-left: 4px solid var(--primary-blue);
        }

        .detail-item strong {
            color: var(--grayfont-color);
            display: block;
            margin-bottom: var(--spacing-xs);
        }

        .detail-item.risk-high { border-left-color: var(--danger-color); }
        .detail-item.risk-medium { border-left-color: var(--warning-color); }
        .detail-item.risk-low { border-left-color: var(--success-color); }

        /* Alert Styles */
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .alert-warning { background: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--warning-color); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger-color); }
        .alert-info { background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary-blue); }

        /* Forecast Visualization */
        .forecast-container {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-color);
        }

        .forecast-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .forecast-item {
            background: var(--inputfield-color);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            text-align: center;
        }

        .forecast-value {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--primary-blue);
        }

        .forecast-label {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-top: var(--spacing-xs);
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
    <h1>Time Series Analytics & Predictions</h1>

    <!-- Filters -->
    <div class="controls">
        <div class="controls-left">
            <select class="selector-select" id="grade-level-filter">
                <option value="">All Grade Levels</option>
            </select>
            <select class="selector-select" id="subject-filter">
                <option value="">All Subjects</option>
            </select>
            <select class="selector-select" id="section-filter">
                <option value="">All Sections</option>
            </select>
            <select class="selector-select" id="student-filter">
                <option value="">All Students</option>
            </select>
            <select class="selector-select" id="forecast-period">
                <option value="7">Next 7 Days</option>
                <option value="14">Next 2 Weeks</option>
                <option value="30">Next Month</option>
                <option value="60">Next 2 Months</option>
            </select>
            <input type="date" class="date-input" id="start-date" placeholder="Start Date">
            <input type="date" class="date-input" id="end-date" placeholder="End Date">
            <button class="btn btn-primary" id="refresh-data"><i class="fas fa-sync"></i> Update Forecast</button>
            <button class="btn btn-primary" id="export-chart"><i class="fas fa-download"></i> Export</button>
            <button class="btn btn-secondary" id="clear-filters"><i class="fas fa-times"></i> Clear</button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="stats-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Current Attendance Rate</div>
                    <div class="card-value" id="current-attendance-rate">92.3%</div>
                    <div class="card-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+2.1% vs last week</span>
                    </div>
                </div>
                <div class="card-icon bg-green">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Predicted Next Week</div>
                    <div class="card-value" id="predicted-attendance">91.7%</div>
                    <div class="card-trend">
                        <i class="fas fa-crystal-ball"></i>
                        <span>ARIMA Forecast</span>
                    </div>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fas fa-chart-area"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">At-Risk Students</div>
                    <div class="card-value" id="at-risk-count">8</div>
                    <div class="card-trend trend-down">
                        <i class="fas fa-arrow-down"></i>
                        <span>-3 vs last week</span>
                    </div>
                </div>
                <div class="card-icon bg-pink">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Forecast Accuracy (ARIMA)</div>
                    <div class="card-value" id="forecast-accuracy">89.2%</div>
                    <div class="card-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>High confidence</span>
                    </div>
                </div>
                <div class="card-icon bg-purple">
                    <i class="fas fa-brain"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Series Forecast Chart -->
    <div class="forecast-container">
        <div class="chart-header">
            <div class="chart-title">ARIMA Time Series Forecast</div>
            <div class="chart-filter">
                <button class="filter-btn active" data-period="daily">Daily</button>
                <button class="filter-btn" data-period="weekly">Weekly</button>
                <button class="filter-btn" data-period="monthly">Monthly</button>
            </div>
        </div>
        <canvas id="forecast-chart"></canvas>
        <div class="forecast-summary">
            <div class="forecast-item">
                <div class="forecast-value" id="next-day-forecast">93.1%</div>
                <div class="forecast-label">Tomorrow</div>
            </div>
            <div class="forecast-item">
                <div class="forecast-value" id="next-week-forecast">91.7%</div>
                <div class="forecast-label">Next Week Avg</div>
            </div>
            <div class="forecast-item">
                <div class="forecast-value" id="trend-direction">↗ Improving</div>
                <div class="forecast-label">Trend Direction</div>
            </div>
            <div class="forecast-item">
                <div class="forecast-value" id="confidence-interval">±2.3%</div>
                <div class="forecast-label">Confidence Interval</div>
            </div>
        </div>
    </div>

    <!-- Pattern Analysis -->
    <div class="chart-container">
        <div class="chart-header">
            <div class="chart-title">Attendance Patterns & Seasonality</div>
            <div class="chart-filter">
                <button class="filter-btn active" data-pattern="weekday">Day of Week</button>
                <button class="filter-btn" data-pattern="monthly">Monthly</button>
                <button class="filter-btn" data-pattern="seasonal">Seasonal</button>
            </div>
        </div>
        <canvas id="pattern-chart"></canvas>
    </div>

    <!-- Attendance Status Distribution -->
    <div class="chart-container">
        <div class="table-header">
            <div class="table-title">Attendance Status Distribution</div>
        </div>
        <canvas id="attendance-status"></canvas>
    </div>

    <!-- Individual Student Predictions -->
    <div class="prediction-card" id="student-prediction-card" style="display: none;">
        <div class="prediction-header">
            <div class="table-title">Individual Student Time Series Analysis</div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>ARIMA model predictions based on historical attendance patterns</span>
            </div>
        </div>
        
        <div class="prediction-details" id="student-details"></div>
        
        <div class="chart-container">
            <h3>Individual Forecast Chart</h3>
            <canvas id="individual-forecast-chart"></canvas>
        </div>

        <div class="pattern-table">
            <h3>Personal Analytics & AI Recommendations</h3>
            <div id="student-recommendations"></div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Current Value</th>
                            <th>Forecast (Next Week)</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody id="student-metrics"></tbody>
                </table>
            </div>
        </div>
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

    <!-- Early Warning System -->
    <div class="pattern-table">
        <div class="table-header">
            <div class="table-title">AI-Powered Early Warning System</div>
            <div class="alert alert-warning">
                <i class="fas fa-bell"></i>
                <span>Automated alerts based on time series anomaly detection</span>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Risk Level</th>
                        <th>Predicted Attendance (7 days)</th>
                        <th>Key Pattern</th>
                        <th>Recommended Action</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody id="early-warning-table"></tbody>
            </table>
        </div>
    </div>

    <!-- Attendance Trends Analysis -->
    <div class="pattern-table">
        <div class="table-header">
            <div class="table-title">Time Series Trends Analysis</div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Subject/Class</th>
                        <th>Current Rate</th>
                        <th>Risk Level</th>
                        <th>Trend (30 days)</th>
                        <th>Seasonal Pattern</th>
                        <th>Forecast Confidence</th>
                        <th>Enhancement Strategy</th>
                    </tr>
                </thead>
                <tbody id="trends-analysis-table"></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Sample data structure for ARIMA-based time series
        let classes = [
            {
                id: 1,
                code: 'MATH-101-A',
                sectionName: 'Diamond Section',
                subject: 'Mathematics',
                gradeLevel: 'Grade 7',
                room: 'Room 201',
                attendancePercentage: 92.3,
                schedule: {
                    monday: { start: '08:00', end: '09:30' },
                    wednesday: { start: '08:00', end: '09:30' },
                    friday: { start: '08:00', end: '09:30' }
                },
                status: 'active',
                trend: 'improving',
                seasonality: 'weekday_pattern',
                forecastConfidence: 89.2,
                students: [
                    { 
                        id: 1, 
                        firstName: 'John', 
                        lastName: 'Doe', 
                        email: 'john.doe@email.com', 
                        attendanceRate: 92,
                        timeSeriesData: [92, 95, 89, 97, 93, 91, 96, 94, 88, 95, 93, 97, 92, 94],
                        trend: 'stable',
                        riskLevel: 'low',
                        totalAbsences: 9,
                        primaryAbsenceReason: 'Health Issue',
                        chronicAbsenteeism: 5,
                        attendanceStatus: { present: 20, absent: 6, late: 4 },
                        behaviorPatterns: [{ pattern: 'Frequent Friday absences', frequency: 2 }]
                    },
                    { 
                        id: 2, 
                        firstName: 'Jane', 
                        lastName: 'Smith', 
                        email: 'jane.smith@email.com', 
                        attendanceRate: 76.8,
                        timeSeriesData: [85, 82, 75, 70, 68, 72, 74, 76, 78, 80, 77, 75, 78, 76],
                        trend: 'declining',
                        riskLevel: 'high',
                        totalAbsences: 15,
                        primaryAbsenceReason: 'Transportation',
                        chronicAbsenteeism: 8,
                        attendanceStatus: { present: 15, absent: 10, late: 5 },
                        behaviorPatterns: [{ pattern: 'Monday absences', frequency: 3 }]
                    },
                    { 
                        id: 3, 
                        firstName: 'Mike', 
                        lastName: 'Johnson', 
                        email: 'mike.johnson@email.com', 
                        attendanceRate: 87.2,
                        timeSeriesData: [88, 85, 90, 87, 89, 86, 88, 87, 85, 89, 88, 86, 87, 88],
                        trend: 'stable',
                        riskLevel: 'medium',
                        totalAbsences: 12,
                        primaryAbsenceReason: 'Family Structure',
                        chronicAbsenteeism: 6,
                        attendanceStatus: { present: 18, absent: 8, late: 4 },
                        behaviorPatterns: [{ pattern: 'Midweek absences', frequency: 2 }]
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
                attendancePercentage: 89.7,
                schedule: {
                    tuesday: { start: '10:00', end: '11:30' },
                    thursday: { start: '10:00', end: '11:30' }
                },
                status: 'active',
                trend: 'stable',
                seasonality: 'monthly_pattern',
                forecastConfidence: 91.5,
                students: [
                    { 
                        id: 4, 
                        firstName: 'Alice', 
                        lastName: 'Brown', 
                        email: 'alice.brown@email.com', 
                        attendanceRate: 91.3,
                        timeSeriesData: [90, 92, 89, 93, 91, 90, 92, 91, 89, 93, 92, 90, 91, 92],
                        trend: 'improving',
                        riskLevel: 'low',
                        totalAbsences: 8,
                        primaryAbsenceReason: 'No Reason',
                        chronicAbsenteeism: 4,
                        attendanceStatus: { present: 22, absent: 5, late: 3 },
                        behaviorPatterns: [{ pattern: 'Occasional absences', frequency: 1 }]
                    },
                    { 
                        id: 5, 
                        firstName: 'Bob', 
                        lastName: 'Wilson', 
                        email: 'bob.wilson@email.com', 
                        attendanceRate: 83.4,
                        timeSeriesData: [85, 84, 82, 85, 83, 84, 82, 83, 85, 82, 84, 83, 85, 84],
                        trend: 'stable',
                        riskLevel: 'medium',
                        totalAbsences: 14,
                        primaryAbsenceReason: 'Household Income',
                        chronicAbsenteeism: 7,
                        attendanceStatus: { present: 16, absent: 9, late: 5 },
                        behaviorPatterns: [{ pattern: 'Thursday absences', frequency: 2 }]
                    }
                ]
            }
        ];

        // Generate time series data for forecasting
        function generateTimeSeriesData(days = 30) {
            const data = [];
            const labels = [];
            const baseAttendance = 90;
            
            for (let i = days; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toISOString().split('T')[0]);
                
                const dayOfWeek = date.getDay();
                const weekendEffect = (dayOfWeek === 0 || dayOfWeek === 6) ? -5 : 0;
                const mondayEffect = dayOfWeek === 1 ? -3 : 0;
                const fridayEffect = dayOfWeek === 5 ? -2 : 0;
                const randomVariation = (Math.random() - 0.5) * 4;
                
                const attendance = Math.max(70, Math.min(100, 
                    baseAttendance + weekendEffect + mondayEffect + fridayEffect + randomVariation
                ));
                data.push(attendance);
            }
            
            return { labels, data };
        }

        // ARIMA forecasting simulation
        function arimaForecast(historicalData, periods = 7) {
            const forecast = [];
            const lastValue = historicalData[historicalData.length - 1];
            
            for (let i = 1; i <= periods; i++) {
                const trend = (historicalData[historicalData.length - 1] - historicalData[historicalData.length - 7]) / 7;
                const seasonalEffect = Math.sin(i * Math.PI / 3.5) * 2;
                const noise = (Math.random() - 0.5) * 1.5;
                
                const predictedValue = Math.max(70, Math.min(100, 
                    lastValue + (trend * i) + seasonalEffect + noise
                ));
                forecast.push(predictedValue);
            }
            
            return forecast;
        }

        // DOM Elements
        const gradeLevelFilter = document.getElementById('grade-level-filter');
        const subjectFilter = document.getElementById('subject-filter');
        const sectionFilter = document.getElementById('section-filter');
        const studentFilter = document.getElementById('student-filter');
        const forecastPeriod = document.getElementById('forecast-period');
        const startDate = document.getElementById('start-date');
        const endDate = document.getElementById('end-date');
        
        // Chart contexts
        const forecastChartCtx = document.getElementById('forecast-chart').getContext('2d');
        const patternChartCtx = document.getElementById('pattern-chart').getContext('2d');
        const attendanceStatusCtx = document.getElementById('attendance-status').getContext('2d');
        
        // Chart instances
        let forecastChart, patternChart, attendanceStatusChart, individualForecastChart;

        // Initialize filters
        function initializeFilters() {
            const gradeLevels = [...new Set(classes.map(c => c.gradeLevel))];
            const subjects = [...new Set(classes.map(c => c.subject))];
            const sections = [...new Set(classes.map(c => c.sectionName))];
            
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelFilter.appendChild(option);
            });
            
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
            
            updateStudentFilter();
        }

        function updateStudentFilter() {
            const selectedGradeLevel = gradeLevelFilter.value;
            const selectedSubject = subjectFilter.value;
            const selectedSection = sectionFilter.value;

            let filteredStudents = classes.flatMap(c => c.students.map(s => ({
                ...s,
                gradeLevel: c.gradeLevel,
                subject: c.subject,
                section: c.sectionName
            })));

            if (selectedGradeLevel) {
                filteredStudents = filteredStudents.filter(s => s.gradeLevel === selectedGradeLevel);
            }
            if (selectedSubject) {
                filteredStudents = filteredStudents.filter(s => s.subject === selectedSubject);
            }
            if (selectedSection) {
                filteredStudents = filteredStudents.filter(s => s.section === selectedSection);
            }

            studentFilter.innerHTML = '<option value="">All Students</option>';
            filteredStudents.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.firstName} ${student.lastName} (${student.section})`;
                studentFilter.appendChild(option);
            });
        }

        // Initialize charts
        function initializeCharts() {
            const timeSeriesData = generateTimeSeriesData(30);
            const forecastData = arimaForecast(timeSeriesData.data, 7);
            
            // Forecast Chart
            forecastChart = new Chart(forecastChartCtx, {
                type: 'line',
                data: {
                    labels: [...timeSeriesData.labels, ...Array(7).fill(0).map((_, i) => {
                        const date = new Date();
                        date.setDate(date.getDate() + i + 1);
                        return date.toISOString().split('T')[0];
                    })],
                    datasets: [
                        {
                            label: 'Historical Data',
                            data: [...timeSeriesData.data, ...Array(7).fill(null)],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'ARIMA Forecast',
                            data: [...Array(30).fill(null), ...forecastData],
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'Confidence Interval',
                            data: [...Array(30).fill(null), ...forecastData.map(v => v + 2.3)],
                            borderColor: '#6b7280',
                            backgroundColor: 'rgba(107, 114, 128, 0.1)',
                            borderDash: [2, 2],
                            fill: '+1',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 70,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Attendance Rate (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });

            // Pattern Chart (Day of Week Analysis)
            patternChart = new Chart(patternChartCtx, {
                type: 'bar',
                data: {
                    labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    datasets: [{
                        label: 'Average Attendance Rate',
                        data: [87.2, 91.5, 93.1, 92.8, 89.4],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            '#ef4444',
                            '#22c55e',
                            '#22c55e',
                            '#22c55e',
                            '#f59e0b'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.y.toFixed(1)}% attendance`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 80,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Attendance Rate (%)'
                            }
                        }
                    }
                }
            });

            // Attendance Status Chart
            attendanceStatusChart = new Chart(attendanceStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: [20, 6, 4],
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
        }

        // Update early warning table
        function updateEarlyWarningTable() {
            const earlyWarningTable = document.getElementById('early-warning-table');
            earlyWarningTable.innerHTML = '';
            
            const allStudents = classes.flatMap(c => c.students.map(s => ({
                ...s,
                subject: c.subject,
                section: c.sectionName
            })));
            
            const atRiskStudents = allStudents.filter(s => s.riskLevel !== 'low');
            
            atRiskStudents.forEach(student => {
                const forecast = arimaForecast(student.timeSeriesData, 7);
                const avgForecast = forecast.reduce((a, b) => a + b, 0) / forecast.length;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${student.firstName} ${student.lastName}</td>
                    <td><span class="risk-${student.riskLevel}">${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}</span></td>
                    <td>${avgForecast.toFixed(1)}%</td>
                    <td>${student.trend === 'declining' ? 'Decreasing trend detected' : 'Monday absences pattern'}</td>
                    <td>${student.riskLevel === 'high' ? 'Immediate parent conference' : 'Monitor closely + automated reminders'}</td>
                    <td>${student.riskLevel === 'high' ? 'High' : 'Medium'}</td>
                `;
                earlyWarningTable.appendChild(row);
            });
        }

        // Update trends analysis table
        function updateTrendsAnalysisTable() {
            const trendsTable = document.getElementById('trends-analysis-table');
            trendsTable.innerHTML = '';
            
            classes.forEach(cls => {
                const row = document.createElement('tr');
                const trendIcon = cls.trend === 'improving' ? '↗️' : cls.trend === 'declining' ? '↘️' : '➡️';
                const strategy = cls.attendancePercentage < 85 ? 'Enhanced engagement activities' : 
                               cls.attendancePercentage < 90 ? 'Monitor and maintain' : 'Continue current approach';
                const riskLevel = cls.attendancePercentage < 85 ? 'High' : cls.attendancePercentage < 90 ? 'Medium' : 'Low';
                
                row.innerHTML = `
                    <td>${cls.subject} (${cls.sectionName})</td>
                    <td>${cls.attendancePercentage}%</td>
                    <td><span class="risk-${riskLevel.toLowerCase()}">${riskLevel}</span></td>
                    <td>${trendIcon} ${cls.trend}</td>
                    <td>${cls.seasonality.replace('_', ' ')}</td>
                    <td>${cls.forecastConfidence}%</td>
                    <td>${strategy}</td>
                `;
                trendsTable.appendChild(row);
            });
        }

        // Update behavior patterns table
        function updateBehaviorPatternsTable() {
            const behaviorPatterns = document.getElementById('behavior-patterns');
            behaviorPatterns.innerHTML = '';
            
            const allStudents = classes.flatMap(c => c.students);
            
            allStudents.forEach(student => {
                student.behaviorPatterns.forEach(pattern => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${student.firstName} ${student.lastName}</td>
                        <td>${pattern.pattern}</td>
                        <td>${pattern.frequency}</td>
                    `;
                    behaviorPatterns.appendChild(row);
                });
            });
        }

        // Show individual student prediction
        function showStudentPrediction(studentId) {
            const student = classes.flatMap(c => c.students.map(s => ({
                ...s,
                subject: c.subject,
                section: c.sectionName,
                gradeLevel: c.gradeLevel
            }))).find(s => s.id == studentId);
            
            if (!student) {
                document.getElementById('student-prediction-card').style.display = 'none';
                return;
            }
            
            document.getElementById('student-prediction-card').style.display = 'block';
            
            const forecast = arimaForecast(student.timeSeriesData, 30);
            const avgForecast = forecast.reduce((a, b) => a + b, 0) / forecast.length;
            
            // Update student details
            const studentDetails = document.getElementById('student-details');
            studentDetails.innerHTML = `
                <div class="detail-item">
                    <strong>Student:</strong> ${student.firstName} ${student.lastName}
                </div>
                <div class="detail-item">
                    <strong>Current Attendance:</strong> ${student.attendanceRate}%
                </div>
                <div class="detail-item risk-${student.riskLevel}">
                    <strong>Risk Level:</strong> ${student.riskLevel.charAt(0).toUpperCase() + student.riskLevel.slice(1)}
                </div>
                <div class="detail-item">
                    <strong>Predicted Next Month:</strong> ${avgForecast.toFixed(1)}%
                </div>
                <div class="detail-item">
                    <strong>Total Absences:</strong> ${student.totalAbsences}
                </div>
                <div class="detail-item">
                    <strong>Primary Absence Reason:</strong> ${student.primaryAbsenceReason}
                </div>
                <div class="detail-item">
                    <strong>Chronic Absenteeism:</strong> ${student.chronicAbsenteeism}%
                </div>
                <div class="detail-item">
                    <strong>Class:</strong> ${student.subject} (${student.section})
                </div>
            `;
            
            // Generate recommendations
            const recommendations = generateRecommendations(student);
            const recDiv = document.getElementById('student-recommendations');
            recDiv.innerHTML = recommendations.map(rec => 
                `<div class="alert alert-${rec.type}">
                    <i class="fas fa-${rec.icon}"></i>
                    <span>${rec.message}</span>
                </div>`
            ).join('');
            
            // Update metrics table
            const metricsTable = document.getElementById('student-metrics');
            metricsTable.innerHTML = `
                <tr>
                    <td>Attendance Rate</td>
                    <td>${student.attendanceRate}%</td>
                    <td>${avgForecast.toFixed(1)}%</td>
                    <td>${avgForecast < student.attendanceRate ? 'Implement intervention plan' : 'Continue monitoring'}</td>
                </tr>
                <tr>
                    <td>Total Absences</td>
                    <td>${student.totalAbsences}</td>
                    <td>-</td>
                    <td>${student.totalAbsences > 10 ? 'Contact parents' : 'Review absence patterns'}</td>
                </tr>
                <tr>
                    <td>Primary Absence Reason</td>
                    <td>${student.primaryAbsenceReason}</td>
                    <td>-</td>
                    <td>${student.primaryAbsenceReason === 'Health Issue' ? 'Health check-up' : 'Address specific issue'}</td>
                </tr>
                <tr>
                    <td>Chronic Absenteeism</td>
                    <td>${student.chronicAbsenteeism}%</td>
                    <td>-</td>
                    <td>${student.chronicAbsenteeism > 10 ? 'Implement attendance plan' : 'Monitor attendance'}</td>
                </tr>
            `;
            
            // Create individual forecast chart
            createIndividualForecastChart(student);
            
            // Update attendance status chart for selected student
            if (attendanceStatusChart) {
                attendanceStatusChart.data.datasets[0].data = [
                    student.attendanceStatus.present,
                    student.attendanceStatus.absent,
                    student.attendanceStatus.late
                ];
                attendanceStatusChart.update();
            }
        }
        
        function generateRecommendations(student) {
            const recommendations = [];
            
            if (student.riskLevel === 'high') {
                recommendations.push({
                    type: 'danger',
                    icon: 'exclamation-triangle',
                    message: 'Critical: Schedule immediate intervention meeting with parents and counselor'
                });
                recommendations.push({
                    type: 'warning',
                    icon: 'phone',
                    message: 'Enable daily automated SMS reminders and check-ins'
                });
            } else if (student.riskLevel === 'medium') {
                recommendations.push({
                    type: 'warning',
                    icon: 'bell',
                    message: 'Moderate risk: Implement peer support system and weekly progress reviews'
                });
            } else {
                recommendations.push({
                    type: 'info',
                    icon: 'thumbs-up',
                    message: 'Good attendance: Continue current engagement strategies'
                });
            }
            
            if (student.trend === 'declining') {
                recommendations.push({
                    type: 'warning',
                    icon: 'chart-line-down',
                    message: 'Declining trend detected: Investigate underlying causes and adjust approach'
                });
            }
            
            return recommendations;
        }
        
        function createIndividualForecastChart(student) {
            const ctx = document.getElementById('individual-forecast-chart');
            if (!ctx) return;
            
            if (individualForecastChart) {
                individualForecastChart.destroy();
            }
            
            const forecast = arimaForecast(student.timeSeriesData, 7);
            const labels = [...Array(14).fill(0).map((_, i) => `Day ${i-6}`), ...Array(7).fill(0).map((_, i) => `Day +${i+1}`)];
            
            individualForecastChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Historical',
                            data: [...student.timeSeriesData, ...Array(7).fill(null)],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Forecast',
                            data: [...Array(14).fill(null), ...forecast],
                            borderColor: '#ef4444',
                            borderDash: [5, 5],
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 60,
                            max: 100,
                            title: { display: true, text: 'Attendance Rate (%)' }
                        }
                    }
                }
            });
        }

        // Event listeners
        gradeLevelFilter.addEventListener('change', updateStudentFilter);
        subjectFilter.addEventListener('change', updateStudentFilter);
        sectionFilter.addEventListener('change', updateStudentFilter);
        
        studentFilter.addEventListener('change', (e) => {
            if (e.target.value) {
                showStudentPrediction(e.target.value);
            } else {
                document.getElementById('student-prediction-card').style.display = 'none';
                // Reset attendance status chart to default
                if (attendanceStatusChart) {
                    attendanceStatusChart.data.datasets[0].data = [20, 6, 4];
                    attendanceStatusChart.update();
                }
            }
        });

        document.getElementById('refresh-data').addEventListener('click', () => {
            forecastChart.destroy();
            patternChart.destroy();
            attendanceStatusChart.destroy();
            initializeCharts();
            updateEarlyWarningTable();
            updateTrendsAnalysisTable();
            updateBehaviorPatternsTable();
        });

        document.getElementById('clear-filters').addEventListener('click', () => {
            gradeLevelFilter.value = '';
            subjectFilter.value = '';
            sectionFilter.value = '';
            studentFilter.value = '';
            forecastPeriod.value = '7';
            startDate.value = '';
            endDate.value = '';
            updateStudentFilter();
            document.getElementById('student-prediction-card').style.display = 'none';
            // Reset charts to default
            if (attendanceStatusChart) {
                attendanceStatusChart.data.datasets[0].data = [20, 6, 4];
                attendanceStatusChart.update();
            }
        });

        // Chart filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const parent = this.closest('.chart-filter');
                parent.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Initialize everything
        document.addEventListener('DOMContentLoaded', () => {
            initializeFilters();
            initializeCharts();
            updateEarlyWarningTable();
            updateTrendsAnalysisTable();
            updateBehaviorPatternsTable();
        });
    </script>
</body>
</html>