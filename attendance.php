<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking - Student Attendance System</title>
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
            --status-present-bg: #e6ffed;
            --status-absent-bg: #ffe6e6;
            --status-late-bg: #fff8e6;
            --status-none-bg: #f8fafc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-family); }
        body { background-color: var(--card-bg); color: var(--blackfont-color); padding: 20px; }
        h1 { font-size: 24px; margin-bottom: 20px; color: var(--blackfont-color); position: relative; padding-bottom: 10px; }
        h1:after { content: ''; position: absolute; left: 0; bottom: 0; height: 4px; width: 80px; background: var(--primary-gradient); border-radius: var(--radius-sm); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .card { background: var(--card-bg); border-radius: 12px; padding: 20px; box-shadow: var(--shadow-md); transition: var(--transition-normal); }
        .card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--whitefont-color); }
        .bg-purple { background: var(--primary-gradient); }
        .bg-pink { background: var(--secondary-gradient); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .bg-green { background: linear-gradient(135deg, #10b981, #34d399); }
        .card-title { font-size: 14px; color: var(--grayfont-color); margin-bottom: 5px; }
        .card-value { font-size: 24px; font-weight: 700; color: var(--blackfont-color); }

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

        .action-buttons-container {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-bottom: 15px;
            align-items: center;
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
            flex: 1;
        }

        .bulk-action-btn {
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

        .bulk-action-btn:hover {
            background: var(--inputfieldhover-color);
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--grayfont-color); font-size: 14px; background: var(--inputfield-color); }
        tbody tr { transition: var(--transition-normal); }
        tbody tr:hover { background-color: var(--inputfieldhover-color); }
        .student-photo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .status-select, .notes-select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; transition: var(--transition-normal); width: 100%; }
        .status-select:focus, .notes-select:focus { outline: none; border-color: var(--primary-blue); background: var(--inputfieldhover-color); }
        .status-select option[value="Present"] { background-color: var(--status-present-bg); }
        .status-select option[value="Absent"] { background-color: var(--status-absent-bg); }
        .status-select option[value="Late"] { background-color: var(--status-late-bg); }
        .status-select option[value=""] { background-color: var(--status-none-bg); }
        .status-select.present { background-color: var(--status-present-bg); }
        .status-select.absent { background-color: var(--status-absent-bg); }
        .status-select.late { background-color: var(--status-late-bg); }
        .status-select.none { background-color: var(--status-none-bg); }
        .notes-select:disabled { background: var(--light-gray); cursor: not-allowed; }
        .attendance-rate { color: var(--success-green); font-weight: 600; }
        .action-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .save-btn, .submit-btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; transition: var(--transition-normal); }
        .save-btn { background: var(--inputfield-color); color: var(--blackfont-color); }
        .save-btn:hover { background: var(--inputfieldhover-color); }
        .submit-btn { background: var(--primary-blue); color: var(--whitefont-color); }
        .submit-btn:hover { background: var(--primary-blue-hover); }
        .qr-scanner-container { margin-bottom: 15px; text-align: center; }
        #qr-video { width: 100%; max-width: 300px; border-radius: 8px; }
        #qr-canvas { display: none; }
        .notification { position: fixed; top: 20px; right: 20px; padding: 10px 20px; border-radius: 8px; color: var(--whitefont-color); z-index: 1000; transition: opacity var(--transition-normal); }
        .notification.success { background: var(--success-green); }
        .notification.error { background: var(--danger-red); }

        @media (max-width: 1024px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            .action-buttons-container {
                flex-direction: column;
                align-items: stretch;
            }
            .bulk-actions {
                flex-direction: column;
            }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        }

        @media (max-width: 768px) {
            body { padding: var(--spacing-sm); }
            .controls-left { flex-direction: column; gap: var(--spacing-xs); }
            .search-container { min-width: auto; width: 100%; }
            .selector-input, .selector-select { width: 100%; min-width: auto; }
            .btn { width: 100%; justify-content: center; }
            .bulk-action-btn { width: 100%; }
            .table-responsive { overflow-x: auto; }
        }

        @media (max-width: 576px) {
            .stats-grid { grid-template-columns: 1fr; }
            .card-value { font-size: 20px; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>
    <h1>Attendance Tracking</h1>

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
                <input type="text" class="form-input search-input" id="searchInput" placeholder="Search by LRN or Name">
                <i class="fas fa-search search-icon"></i>
            </div>
            <input type="date" class="selector-input" id="date-selector" value="2025-07-23" min="2025-06-01" max="2025-07-23">
            <select class="selector-select" id="gradeLevelSelector">
                <option value="">All Grade Levels</option>
            </select>
            <select class="selector-select" id="classSelector">
                <option value="">All Subjects</option>
            </select>
            <select class="selector-select" id="sectionSelector">
                <option value="">All Sections</option>
            </select>
            <select class="selector-select" id="statusSelector">
                <option value="">All Statuses</option>
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
        <div class="qr-scanner-container" id="qr-scanner" style="display: none;">
            <video id="qr-video"></video>
            <canvas id="qr-canvas"></canvas>
            <button class="btn btn-secondary" onclick="stopQRScanner()">Stop Scanner</button>
        </div>
        <div class="action-buttons-container">
            <div class="bulk-actions">
                <select class="bulk-action-btn" id="bulk-action-select">
                    <option value="">Select Bulk Action</option>
                    <option value="Present">Mark Selected as Present</option>
                    <option value="Absent">Mark Selected as Absent</option>
                    <option value="Late">Mark Selected as Late</option>
                </select>
                <button class="btn btn-primary" onclick="applyBulkAction()">Apply</button>
            </div>
            <button class="btn btn-primary" onclick="markAllPresent()">
                <i class="fas fa-check-circle"></i> Mark All Present
            </button>
            <button class="btn btn-primary" onclick="startQRScanner()">
                <i class="fas fa-qrcode"></i> Scan QR Code
            </button>
        </div>
        <div class="table-responsive">
            <table id="attendance-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
                        <th>Photo</th>
                        <th>LRN</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Time Checked</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="action-buttons">
        <button class="btn btn-primary submit-btn" onclick="submitAttendance()">Submit Attendance</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
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

        const students = classes.flatMap(cls => cls.students.map(student => ({
            id: student.id,
            name: `${student.firstName} ${student.lastName}`,
            class: cls.subject,
            photo: student.photo || 'Uploads/no-icon.png',
            status: '',
            notes: '',
            gradeLevel: cls.gradeLevel,
            subject: cls.subject,
            section: cls.sectionName,
            attendanceRate: 90
        })));

        let attendanceData = {};
        let today = '2025-07-23';
        let videoStream = null;
        let scannedStudents = new Set();

        if (!attendanceData[today]) {
            attendanceData[today] = {};
            students.forEach(student => {
                attendanceData[today][student.id] = { status: '', notes: '', timeChecked: '', attendanceRate: 90 };
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function populateDropdowns() {
            const gradeLevelSelector = document.getElementById('gradeLevelSelector');
            const classSelector = document.getElementById('classSelector');
            const sectionSelector = document.getElementById('sectionSelector');

            const gradeLevels = [...new Set(classes.map(c => c.gradeLevel))];
            gradeLevelSelector.innerHTML = '<option value="">All Grade Levels</option>';
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelSelector.appendChild(option);
            });

            const subjects = [...new Set(classes.map(c => c.subject))];
            classSelector.innerHTML = '<option value="">All Subjects</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                classSelector.appendChild(option);
            });

            const sections = [...new Set(classes.map(c => c.sectionName))];
            sectionSelector.innerHTML = '<option value="">All Sections</option>';
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelector.appendChild(option);
            });
        }

        function updateStats(filteredStudents) {
            const total = filteredStudents.length;
            const present = filteredStudents.filter(s => attendanceData[today][s.id].status === 'Present').length;
            const absent = filteredStudents.filter(s => attendanceData[today][s.id].status === 'Absent').length;
            const percentage = total ? ((present / total) * 100).toFixed(1) : 0;

            document.getElementById('total-students').textContent = total;
            document.getElementById('present-count').textContent = present;
            document.getElementById('absent-count').textContent = absent;
            document.getElementById('attendance-percentage').textContent = `${percentage}%`;
        }

        function formatDateTime(date) {
            const options = {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            return date.toLocaleString('en-US', options).replace(',', '');
        }

        function renderTable() {
            tableBody.innerHTML = '';
            const gradeLevelFilter = gradeLevelSelector.value;
            const classFilter = classSelector.value;
            const sectionFilter = sectionSelector.value;
            const statusFilter = statusSelector.value;
            const searchQuery = searchInput.value.toLowerCase();
            const filteredStudents = students.filter(s => {
                const matchesGradeLevel = gradeLevelFilter ? s.gradeLevel === gradeLevelFilter : true;
                const matchesClass = classFilter ? s.subject === classFilter : true;
                const matchesSection = sectionFilter ? s.section === sectionFilter : true;
                const matchesStatus = statusFilter ? attendanceData[today][s.id].status === statusFilter : true;
                const matchesSearch = searchQuery ? 
                    s.id.toString().includes(searchQuery) || 
                    s.name.toLowerCase().includes(searchQuery) : true;
                return matchesGradeLevel && matchesClass && matchesSection && matchesStatus && matchesSearch;
            });

            filteredStudents.forEach(student => {
                const isNotesDisabled = attendanceData[today][student.id].status === 'Present';
                const statusClass = attendanceData[today][student.id].status ? attendanceData[today][student.id].status.toLowerCase() : 'none';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="select-student" data-id="${student.id}"></td>
                    <td><img src="${student.photo}" class="student-photo" alt="${student.name}"></td>
                    <td>${student.id}</td>
                    <td>${student.name}</td>
                    <td>
                        <select class="status-select ${statusClass}" data-id="${student.id}">
                            <option value="" ${attendanceData[today][student.id].status === '' ? 'selected' : ''}>Select Status</option>
                            <option value="Present" ${attendanceData[today][student.id].status === 'Present' ? 'selected' : ''}>Present</option>
                            <option value="Absent" ${attendanceData[today][student.id].status === 'Absent' ? 'selected' : ''}>Absent</option>
                            <option value="Late" ${attendanceData[today][student.id].status === 'Late' ? 'selected' : ''}>Late</option>
                        </select>
                    </td>
                    <td>
                        <select class="notes-select" data-id="${student.id}" ${isNotesDisabled ? 'disabled' : ''}>
                            <option value="" ${attendanceData[today][student.id].notes === '' ? 'selected' : ''}>Select Reason</option>
                            <option value="Health Issue" ${attendanceData[today][student.id].notes === 'Health Issue' ? 'selected' : ''}>Health Issue</option>
                            <option value="Household Income" ${attendanceData[today][student.id].notes === 'Household Income' ? 'selected' : ''}>Household Income</option>
                            <option value="Transportation" ${attendanceData[today][student.id].notes === 'Transportation' ? 'selected' : ''}>Transportation</option>
                            <option value="Family Structure" ${attendanceData[today][student.id].notes === 'Family Structure' ? 'selected' : ''}>Family Structure</option>
                            <option value="No Reason" ${attendanceData[today][student.id].notes === 'No Reason' ? 'selected' : ''}>No Reason</option>
                            <option value="Other" ${attendanceData[today][student.id].notes === 'Other' ? 'selected' : ''}>Other</option>
                        </select>
                    </td>
                    <td>${attendanceData[today][student.id].timeChecked || '-'}</td>
                    <td class="attendance-rate">${student.attendanceRate}%</td>
                `;
                tableBody.appendChild(row);
            });

            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', () => {
                    const studentId = select.dataset.id;
                    const newStatus = select.value;
                    attendanceData[today][studentId].status = newStatus;
                    attendanceData[today][studentId].timeChecked = newStatus ? formatDateTime(new Date()) : '';
                    const notesSelect = tableBody.querySelector(`.notes-select[data-id="${studentId}"]`);
                    notesSelect.disabled = newStatus === 'Present';
                    if (newStatus === 'Present') {
                        attendanceData[today][studentId].notes = '';
                        notesSelect.value = '';
                    }
                    select.classList.remove('present', 'absent', 'late', 'none');
                    select.classList.add(newStatus ? newStatus.toLowerCase() : 'none');
                    updateStats(filteredStudents);
                    renderTable();
                });
            });

            document.querySelectorAll('.notes-select').forEach(select => {
                select.addEventListener('change', () => {
                    const studentId = select.dataset.id;
                    attendanceData[today][studentId].notes = select.value;
                    attendanceData[today][studentId].timeChecked = formatDateTime(new Date());
                    renderTable();
                });
            });

            updateStats(filteredStudents);
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.select-student');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        function markAllPresent() {
            const gradeLevelFilter = gradeLevelSelector.value;
            const classFilter = classSelector.value;
            const sectionFilter = sectionSelector.value;
            const statusFilter = statusSelector.value;
            const searchQuery = searchInput.value.toLowerCase();
            const filteredStudents = students.filter(s => {
                const matchesGradeLevel = gradeLevelFilter ? s.gradeLevel === gradeLevelFilter : true;
                const matchesClass = classFilter ? s.subject === classFilter : true;
                const matchesSection = sectionFilter ? s.section === sectionFilter : true;
                const matchesStatus = statusFilter ? attendanceData[today][s.id].status === statusFilter : true;
                const matchesSearch = searchQuery ? 
                    s.id.toString().includes(searchQuery) || 
                    s.name.toLowerCase().includes(searchQuery) : true;
                return matchesGradeLevel && matchesClass && matchesSection && matchesStatus && matchesSearch;
            });

            filteredStudents.forEach(student => {
                attendanceData[today][student.id].status = 'Present';
                attendanceData[today][student.id].notes = '';
                attendanceData[today][student.id].timeChecked = formatDateTime(new Date());
                attendanceData[today][student.id].attendanceRate = 90;
            });
            renderTable();
        }

        function applyBulkAction() {
            const action = document.getElementById('bulk-action-select').value;
            if (!action) {
                showNotification('Please select a bulk action.', 'error');
                return;
            }
            const selected = document.querySelectorAll('.select-student:checked');
            selected.forEach(checkbox => {
                const studentId = checkbox.dataset.id;
                attendanceData[today][studentId].status = action;
                attendanceData[today][studentId].notes = (action === 'Present') ? '' : 'No Reason';
                attendanceData[today][studentId].timeChecked = formatDateTime(new Date());
                attendanceData[today][studentId].attendanceRate = 90;
            });
            renderTable();
        }

        function submitAttendance() {
            console.log('Submitted Attendance:', attendanceData[today]);
            showNotification('Attendance submitted successfully.', 'success');
        }

        function startQRScanner() {
            const qrScanner = document.getElementById('qr-scanner');
            const video = document.getElementById('qr-video');
            const canvasElement = document.getElementById('qr-canvas');
            const canvas = canvasElement.getContext('2d');

            qrScanner.style.display = 'block';
            scannedStudents.clear();

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(stream => {
                    videoStream = stream;
                    video.srcObject = stream;
                    video.play();
                    requestAnimationFrame(tick);
                })
                .catch(err => {
                    showNotification('Error accessing camera: ' + err.message, 'error');
                    qrScanner.style.display = 'none';
                });

            function tick() {
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvasElement.height = video.videoHeight;
                    canvasElement.width = video.videoWidth;
                    canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
                    const imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: 'dontInvert',
                    });

                    if (code) {
                        const lrn = code.data;
                        const student = students.find(s => s.id.toString() === lrn);
                        if (student) {
                            if (scannedStudents.has(lrn)) {
                                showNotification(`Student ${student.name} already scanned today.`, 'error');
                            } else {
                                attendanceData[today][lrn].status = 'Present';
                                attendanceData[today][lrn].notes = '';
                                attendanceData[today][lrn].timeChecked = formatDateTime(new Date());
                                attendanceData[today][lrn].attendanceRate = 90;
                                scannedStudents.add(lrn);
                                showNotification(`Student ${student.name} marked as Present.`, 'success');
                                renderTable();
                            }
                        } else {
                            showNotification('Invalid LRN.', 'error');
                        }
                    }
                }
                if (qrScanner.style.display !== 'none') {
                    requestAnimationFrame(tick);
                }
            }
        }

        function stopQRScanner() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
            document.getElementById('qr-scanner').style.display = 'none';
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('date-selector').value = '2025-07-23';
            document.getElementById('gradeLevelSelector').value = '';
            document.getElementById('classSelector').value = '';
            document.getElementById('sectionSelector').value = '';
            document.getElementById('statusSelector').value = '';
            today = '2025-07-23';
            renderTable();
        }

        const tableBody = document.querySelector('#attendance-table tbody');
        const dateSelector = document.getElementById('date-selector');
        const gradeLevelSelector = document.getElementById('gradeLevelSelector');
        const classSelector = document.getElementById('classSelector');
        const sectionSelector = document.getElementById('sectionSelector');
        const statusSelector = document.getElementById('statusSelector');
        const selectAllCheckbox = document.getElementById('select-all');
        const searchInput = document.getElementById('searchInput');

        dateSelector.addEventListener('change', () => {
            today = dateSelector.value;
            if (!attendanceData[today]) {
                attendanceData[today] = {};
                students.forEach(student => {
                    attendanceData[today][student.id] = { status: '', notes: '', timeChecked: '', attendanceRate: 90 };
                });
            }
            renderTable();
        });
        gradeLevelSelector.addEventListener('change', renderTable);
        classSelector.addEventListener('change', renderTable);
        sectionSelector.addEventListener('change', renderTable);
        statusSelector.addEventListener('change', renderTable);
        searchInput.addEventListener('input', renderTable);

        document.addEventListener('DOMContentLoaded', () => {
            populateDropdowns();
            renderTable();
        });
    </script>
</body>
</html>