<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Student Attendance System</title>
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
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
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

        /* Quick Stats */
        .stats-grid {
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

        /* Attendance Grid */
        .attendance-grid {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
        }

        .table-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .selector-input, .selector-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition);
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
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }

        .quick-action-btn:hover {
            background: var(--primary-hover);
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .bulk-action-btn {
            padding: 8px 16px;
            border: none;
            background: var(--inputfield-color);
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }

        .bulk-action-btn:hover {
            background: var(--inputfieldhover-color);
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

        .student-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .status-select, .notes-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition);
            width: 100%;
        }

        .status-select:focus, .notes-select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--inputfieldhover-color);
        }

        .notes-select:disabled {
            background: var(--light-gray);
            cursor: not-allowed;
        }

        /* Save/Submit Buttons */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .save-btn, .submit-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }

        .save-btn {
            background: var(--inputfield-color);
            color: var(--blackfont-color);
        }

        .save-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .submit-btn {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .submit-btn:hover {
            background: var(--primary-hover);
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .stats-grid {
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
    <h1>Attendance Management</h1>

    <!-- Quick Stats -->
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

    <!-- Attendance Grid -->
    <div class="attendance-grid">
        <div class="table-header">
            <div class="table-title">Attendance Grid</div>
            <div class="table-controls">
                <input type="date" class="selector-input" id="date-selector" value="2025-07-11" min="2025-06-01" max="2025-07-11">
                <select class="selector-select" id="gradeLevelSelector">
                    <option value="">All Grade Levels</option>
                </select>
                <select class="selector-select" id="classSelector">
                    <option value="">All Subject</option>
                </select>
                <select class="selector-select" id="sectionSelector">
                    <option value="">All Sections</option>
                </select>
                <button class="quick-action-btn" onclick="markAllPresent()">Mark All Present</button>
            </div>
        </div>
        <div class="bulk-actions">
            <select class="bulk-action-btn" id="bulk-action-select">
                <option value="">Select Bulk Action</option>
                <option value="Present">Mark Selected as Present</option>
                <option value="Absent">Mark Selected as Absent</option>
                <option value="Late">Mark Selected as Late</option>
            </select>
            <button class="bulk-action-btn" onclick="applyBulkAction()">Apply</button>
        </div>
        <div class="table-responsive">
            <table id="attendance-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
                        <th>Photo</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Save/Submit Buttons -->
    <div class="action-buttons">
        <button class="save-btn" onclick="saveDraft()">Save Draft</button>
        <button class="submit-btn" onclick="submitAttendance()">Submit Attendance</button>
    </div>

    <script>
        // Data from Student Management
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

        // Student data aligned with Student Management
        const students = classes.flatMap(cls => cls.students.map(student => ({
            id: student.id,
            name: `${student.firstName} ${student.lastName}`,
            class: cls.subject,
            photo: student.photo || 'https://via.placeholder.com/40',
            status: 'Present', // Default for initialization
            notes: '',
            gradeLevel: cls.gradeLevel,
            subject: cls.subject,
            section: cls.sectionName
        })));

        let attendanceData = {};
        let today = '2025-07-10';

        // Initialize attendance data for today
        if (!attendanceData[today]) {
            attendanceData[today] = {};
            students.forEach(student => {
                attendanceData[today][student.id] = { status: student.status, notes: student.notes };
            });
        }

        // Populate dropdowns with data from classes
        function populateDropdowns() {
            const gradeLevelSelector = document.getElementById('gradeLevelSelector');
            const classSelector = document.getElementById('classSelector');
            const sectionSelector = document.getElementById('sectionSelector');

            // Populate grade levels
            const gradeLevels = [...new Set(classes.map(c => c.gradeLevel))];
            gradeLevelSelector.innerHTML = '<option value="">All Grade Levels</option>';
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeLevelSelector.appendChild(option);
            });

            // Populate subjects
            const subjects = [...new Set(classes.map(c => c.subject))];
            classSelector.innerHTML = '<option value="">All Subject</option>';
            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                classSelector.appendChild(option);
            });

            // Populate sections
            const sections = [...new Set(classes.map(c => c.sectionName))];
            sectionSelector.innerHTML = '<option value="">All Sections</option>';
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelector.appendChild(option);
            });
        }

        // Update stats
        function updateStats() {
            const gradeLevelFilter = gradeLevelSelector.value;
            const classFilter = classSelector.value;
            const sectionFilter = sectionSelector.value;
            const filteredStudents = students.filter(s => {
                const matchesGradeLevel = gradeLevelFilter ? s.gradeLevel === gradeLevelFilter : true;
                const matchesClass = classFilter ? s.subject === classFilter : true;
                const matchesSection = sectionFilter ? s.section === sectionFilter : true;
                return matchesGradeLevel && matchesClass && matchesSection;
            });

            const total = filteredStudents.length;
            const present = filteredStudents.filter(s => attendanceData[today][s.id].status === 'Present').length;
            const absent = filteredStudents.filter(s => attendanceData[today][s.id].status === 'Absent').length;
            const percentage = total ? ((present / total) * 100).toFixed(1) : 0;

            document.getElementById('total-students').textContent = total;
            document.getElementById('present-count').textContent = present;
            document.getElementById('absent-count').textContent = absent;
            document.getElementById('attendance-percentage').textContent = `${percentage}%`;
        }

        // Render table
        function renderTable() {
            tableBody.innerHTML = '';
            const gradeLevelFilter = gradeLevelSelector.value;
            const classFilter = classSelector.value;
            const sectionFilter = sectionSelector.value;
            const filteredStudents = students.filter(s => {
                const matchesGradeLevel = gradeLevelFilter ? s.gradeLevel === gradeLevelFilter : true;
                const matchesClass = classFilter ? s.subject === classFilter : true;
                const matchesSection = sectionFilter ? s.section === sectionFilter : true;
                return matchesGradeLevel && matchesClass && matchesSection;
            });

            filteredStudents.forEach(student => {
                const isNotesDisabled = attendanceData[today][student.id].status === 'Present';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="select-student" data-id="${student.id}"></td>
                    <td><img src="${student.photo}" class="student-photo" alt="${student.name}"></td>
                    <td>${student.name}</td>
                    <td>
                        <select class="status-select" data-id="${student.id}">
                            <option value="Present" ${attendanceData[today][student.id].status === 'Present' ? 'selected' : ''}>Present</option>
                            <option value="Absent" ${attendanceData[today][student.id].status === 'Absent' ? 'selected' : ''}>Absent</option>
                            <option value="Late" ${attendanceData[today][student.id].status === 'Late' ? 'selected' : ''}>Late</option>
                        </select>
                    </td>
                    <td>
                        <select class="notes-select" data-id="${student.id}" ${isNotesDisabled ? 'disabled' : ''}>
                            <option value="" ${attendanceData[today][student.id].notes === '' ? 'selected' : ''}>Select Reason</option>
                            <option value="Sick" ${attendanceData[today][student.id].notes === 'Sick' ? 'selected' : ''}>Sick</option>
                            <option value="Family Emergency" ${attendanceData[today][student.id].notes === 'Family Emergency' ? 'selected' : ''}>Family Emergency</option>
                            <option value="Traffic" ${attendanceData[today][student.id].notes === 'Traffic' ? 'selected' : ''}>Traffic</option>
                            <option value="No Reason" ${attendanceData[today][student.id].notes === 'No Reason' ? 'selected' : ''}>No Reason</option>
                            <option value="Other" ${attendanceData[today][student.id].notes === 'Other' ? 'selected' : ''}>Other</option>
                        </select>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            // Add event listeners for status and notes
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', () => {
                    const studentId = select.dataset.id;
                    const newStatus = select.value;
                    attendanceData[today][studentId].status = newStatus;
                    const notesSelect = tableBody.querySelector(`.notes-select[data-id="${studentId}"]`);
                    notesSelect.disabled = newStatus === 'Present';
                    if (newStatus === 'Present') {
                        attendanceData[today][studentId].notes = '';
                        notesSelect.value = '';
                    }
                    updateStats();
                });
            });

            document.querySelectorAll('.notes-select').forEach(select => {
                select.addEventListener('change', () => {
                    const studentId = select.dataset.id;
                    attendanceData[today][studentId].notes = select.value;
                });
            });

            updateStats();
        }

        // Toggle select all
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.select-student');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        // Mark all present
        function markAllPresent() {
            const gradeLevelFilter = gradeLevelSelector.value;
            const classFilter = classSelector.value;
            const sectionFilter = sectionSelector.value;
            const filteredStudents = students.filter(s => {
                const matchesGradeLevel = gradeLevelFilter ? s.gradeLevel === gradeLevelFilter : true;
                const matchesClass = classFilter ? s.subject === classFilter : true;
                const matchesSection = sectionFilter ? s.section === sectionFilter : true;
                return matchesGradeLevel && matchesClass && matchesSection;
            });

            filteredStudents.forEach(student => {
                attendanceData[today][student.id].status = 'Present';
                attendanceData[today][student.id].notes = '';
            });
            renderTable();
        }

        // Apply bulk action
        function applyBulkAction() {
            const action = document.getElementById('bulk-action-select').value;
            if (!action) {
                alert('Please select a bulk action.');
                return;
            }
            const selected = document.querySelectorAll('.select-student:checked');
            selected.forEach(checkbox => {
                const studentId = checkbox.dataset.id;
                attendanceData[today][studentId].status = action;
                attendanceData[today][studentId].notes = (action === 'Present') ? '' : 'No Reason';
            });
            renderTable();
        }

        // Save draft
        function saveDraft() {
            localStorage.setItem('attendanceDraft', JSON.stringify(attendanceData));
            alert('Attendance draft saved locally.');
        }

        // Submit attendance
        function submitAttendance() {
            console.log('Submitted Attendance:', attendanceData[today]);
            alert('Attendance submitted successfully.');
        }

        // DOM Elements
        const tableBody = document.querySelector('#attendance-table tbody');
        const dateSelector = document.getElementById('date-selector');
        const gradeLevelSelector = document.getElementById('gradeLevelSelector');
        const classSelector = document.getElementById('classSelector');
        const sectionSelector = document.getElementById('sectionSelector');
        const selectAllCheckbox = document.getElementById('select-all');

        // Event listeners
        dateSelector.addEventListener('change', () => {
            today = dateSelector.value;
            if (!attendanceData[today]) {
                attendanceData[today] = {};
                students.forEach(student => {
                    attendanceData[today][student.id] = { status: 'Present', notes: '' };
                });
            }
            renderTable();
        });
        gradeLevelSelector.addEventListener('change', renderTable);
        classSelector.addEventListener('change', renderTable);
        sectionSelector.addEventListener('change', renderTable);

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            populateDropdowns();
            renderTable();
        });
    </script>
</body>
</html>