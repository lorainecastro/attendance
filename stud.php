<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            background-color: var(--background);
            color: var(--blackfont-color);
            padding: var(--spacing-lg);
        }

        h1 {
            font-size: var(--font-size-2xl);
            margin-bottom: var(--spacing-lg);
            color: var(--blackfont-color);
            position: relative;
            padding-bottom: var(--spacing-sm);
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
            border-radius: var(--radius-md);
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
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--blackfont-color);
        }

        /* Student Grid */
        .student-grid {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
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

        .table-controls {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            align-items: center;
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

        .search-container {
            position: relative;
            min-width: 250px;
        }

        .search-input {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md) var(--spacing-sm) 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px var(--primary-blue-light);
        }

        .search-icon {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
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
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .quick-action-btn:hover {
            background: var(--primary-hover);
        }

        .bulk-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }

        .bulk-action-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            background: var(--inputfield-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .bulk-action-btn:hover {
            background: var(--inputfieldhover-color);
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
        }

        tbody tr {
            transition: var(--transition-normal);
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

        .attendance-rate {
            font-weight: 500;
        }

        .attendance-rate.high {
            color: var(--success-green);
        }

        .attendance-rate.medium {
            color: var(--warning-yellow);
        }

        .attendance-rate.low {
            color: var(--danger-red);
        }

        /* Card View */
        .card-view {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
        }

        .student-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition-normal);
        }

        .student-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }

        .student-card-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
        }

        .student-card-header h3 {
            font-size: var(--font-size-lg);
            font-weight: 600;
        }

        .student-card-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
            margin-bottom: var(--spacing-md);
        }

        .student-card-info p {
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .student-card-actions {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .action-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .action-btn.view {
            background: var(--info-cyan);
            color: var(--whitefont-color);
        }

        .action-btn.edit {
            background: var(--warning-yellow);
            color: var(--whitefont-color);
        }

        .action-btn.remove {
            background: var(--danger-red);
            color: var(--whitefont-color);
        }

        .action-btn:hover {
            opacity: 0.9;
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
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--blackfont-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: var(--font-size-lg);
            color: var(--grayfont-color);
            cursor: pointer;
        }

        .close-btn:hover {
            color: var(--blackfont-color);
        }

        .modal-body {
            padding: var(--spacing-md);
            max-height: 60vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }

        .form-group label {
            display: block;
            font-size: var(--font-size-sm);
            color: var(--grayfont-color);
            margin-bottom: var(--spacing-xs);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
        }

        .form-group input[readonly] {
            background: var(--light-gray);
            cursor: not-allowed;
        }

        .photo-upload {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
            align-items: flex-start;
        }

        .photo-upload img {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            object-fit: cover;
        }

        .photo-upload input[type="file"] {
            display: none;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            border-top: 1px solid var(--border-color);
        }

        .cancel-btn {
            background: var(--inputfield-color);
            color: var(--blackfont-color);
        }

        .cancel-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .save-btn {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .save-btn:hover {
            background: var(--primary-hover);
        }

        /* Dropdown Menu */
        .dropdown-menu {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background: var(--card-bg);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            z-index: 1;
            min-width: 120px;
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-content button {
            width: 100%;
            padding: var(--spacing-sm);
            border: none;
            background: none;
            text-align: left;
            font-size: var(--font-size-sm);
            color: var(--blackfont-color);
            cursor: pointer;
        }

        .dropdown-content button:hover {
            background: var(--inputfield-color);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-md);
        }

        .pagination-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--card-bg);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .pagination-btn:hover {
            background: var(--inputfield-color);
        }

        .pagination-btn.active {
            background: var(--primary-color);
            color: var(--whitefont-color);
            border-color: var(--primary-color);
        }

        .pagination-btn:disabled {
            background: var(--light-gray);
            cursor: not-allowed;
        }

        /* Save/Submit Buttons */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
        }

        .action-buttons .save-btn,
        .action-buttons .submit-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .action-buttons .save-btn {
            background: var(--inputfield-color);
            color: var(--blackfont-color);
        }

        .action-buttons .save-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .action-buttons .submit-btn {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .action-buttons .submit-btn:hover {
            background: var(--primary-hover);
        }

        /* View Buttons */
        .view-buttons {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }

        .view-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--card-bg);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .view-btn.active {
            background: var(--primary-color);
            color: var(--whitefont-color);
            border-color: var(--primary-color);
        }

        .view-btn:hover {
            background: var(--inputfield-color);
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            th, td {
                padding: var(--spacing-xs);
            }

            .card-value {
                font-size: var(--font-size-xl);
            }

            .table-controls {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-view {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .table-responsive {
                overflow-x: auto;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-container {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <h1>Student Management</h1>

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
                    <div class="card-title">Grade Levels</div>
                    <div class="card-value" id="grade-levels">0</div>
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
                    <div class="card-title">Subjects</div>
                    <div class="card-value" id="subjects">0</div>
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
                    <div class="card-title">Sections</div>
                    <div class="card-value" id="sections">0</div>
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

    <!-- View Toggle -->
    <div class="view-buttons">
        <button class="view-btn active" id="tableView" onclick="switchView('table')">
            <i class="fas fa-table"></i> Table View
        </button>
        <button class="view-btn" id="cardView" onclick="switchView('card')">
            <i class="fas fa-th-large"></i> Card View
        </button>
    </div>

    <!-- Student Grid -->
    <div class="student-grid">
        <div class="table-header">
            <div class="table-title">Student List</div>
            <div class="table-controls">
                <div class="search-container">
                    <input type="text" class="search-input selector-input" id="searchInput" placeholder="Search by Name or ID">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <select class="selector-select" id="genderFilter">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select class="selector-select" id="gradeLevelFilter">
                    <option value="">All Grade Levels</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                </select>
                <select class="selector-select" id="classFilter">
                    <option value="">All Subjects</option>
                    <!-- Populated dynamically -->
                </select>
                <select class="selector-select" id="sectionFilter">
                    <option value="">All Sections</option>
                    <!-- Populated dynamically -->
                </select>
                <select class="selector-select" id="attendanceRateFilter">
                    <option value="">All Attendance Rates</option>
                    <option value="90-100">90% - 100%</option>
                    <option value="80-90">80% - 89%</option>
                    <option value="below-80">Below 80%</option>
                </select>
                <select class="selector-select" id="sortSelect">
                    <option value="name-asc">Name (A-Z)</option>
                    <option value="name-desc">Name (Z-A)</option>
                    <option value="id">Student ID</option>
                    <option value="attendance">Attendance Rate</option>
                </select>
                <button class="quick-action-btn" onclick="openProfileModal('add')">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            </div>
        </div>
        <div class="bulk-actions">
            <span id="selectedCount">0 selected</span>
            <select class="bulk-action-btn bulk-btn" id="bulk-action-select">
                <option value="">Select Bulk Action</option>
                <option value="export">Export Selected</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button class="bulk-action-btn bulk-btn" onclick="bulkExport()">Export</button>
            <button class="bulk-action-btn bulk-btn" onclick="bulkDelete()">Delete</button>
        </div>
        <div class="table-responsive" id="tableView">
            <table id="student-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
                        <th>Photo</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Contact</th>
                        <th>Grade Level</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Attendance Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated dynamically -->
                </tbody>
            </table>
        </div>
        <div class="card-view" id="cardView">
            <!-- Populated dynamically -->
        </div>
        <div class="pagination" id="pagination">
            <!-- Populated dynamically -->
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="profile-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="profile-modal-title">Student Profile</div>
                <button class="close-btn" onclick="closeModal('profile')">×</button>
            </div>
            <div class="modal-body">
                <div class="tab-content active" id="personal-tab">
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" id="student-id" readonly>
                    </div>
                    <div class="form-group photo-upload">
                        <label>Photo</label>
                        <img id="student-photo-preview" src="https://via.placeholder.com/100" alt="Student Photo" style="width: 100px; height: 100px; border-radius: 8px;">
                        <input type="file" id="student-photo" accept="image/*" onchange="previewPhoto(event)">
                        <button class="action-btn" onclick="document.getElementById('student-photo').click()">Change Photo</button>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="first-name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="last-name" required>
                    </div>
                    <div class="form-group">
                        <label>Email (Optional)</label>
                        <input type="email" id="email">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select id="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" id="dob">
                    </div>
                    <div class="form-group">
                        <label>Grade Level</label>
                        <select id="grade-level">
                            <option value="">Select Grade Level</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                            <option value="Grade 11">Grade 11</option>
                            <option value="Grade 12">Grade 12</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select id="class">
                            <option value="">Select Subject</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <select id="section">
                            <option value="">Select Section</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" id="address">
                    </div>
                    <div class="form-group">
                        <label>Parent/Guardian Name</label>
                        <input type="text" id="parent-name">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact</label>
                        <input type="text" id="emergency-contact">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="cancel-btn" onclick="closeModal('profile')">Cancel</button>
                <button class="save-btn" onclick="saveStudent()">Save</button>
            </div>
        </div>
    </div>

    <!-- Save/Submit Buttons -->
    <div class="action-buttons">
        <button class="save-btn">Save Draft</button>
        <button class="submit-btn">Submit Changes</button>
    </div>

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

        // Student data (aligned with Class Management student structure)
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
            photo: student.photo || 'https://via.placeholder.com/100'
        })));

        let currentPage = 1;
        const rowsPerPage = 10;
        let currentView = 'table';

        // DOM Elements
        const studentTableBody = document.querySelector('#student-table tbody');
        const cardView = document.getElementById('cardView');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchInput');
        const genderFilter = document.getElementById('genderFilter');
        const gradeLevelFilter = document.getElementById('gradeLevelFilter');
        const classFilter = document.getElementById('classFilter');
        const sectionFilter = document.getElementById('sectionFilter');
        const attendanceRateFilter = document.getElementById('attendanceRateFilter');
        const sortSelect = document.getElementById('sortSelect');
        const profileModal = document.getElementById('profile-modal');
        const tableView = document.getElementById('tableView');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            populateClassAndSectionFilters();
            applyFilters();
            setupAutocomplete();
            setupEventListeners();
        });

        // Populate class and section filters
        function populateClassAndSectionFilters() {
            const subjects = [...new Set(classes.map(c => c.subject))];
            const sections = [...new Set(classes.map(c => c.sectionName))];

            classFilter.innerHTML = '<option value="">All Subject</option>';
            sectionFilter.innerHTML = '<option value="">All Sections</option>';

            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                classFilter.appendChild(option);
            });

            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionFilter.appendChild(option);
            });

            // Populate modal class and section selects
            const modalClassSelect = document.getElementById('class');
            const modalSectionSelect = document.getElementById('section');

            modalClassSelect.innerHTML = '<option value="">Select Subject</option>';
            modalSectionSelect.innerHTML = '<option value="">Select Section</option>';

            subjects.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                modalClassSelect.appendChild(option);
            });

            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                modalSectionSelect.appendChild(option);
            });
        }

        // Setup event listeners
        function setupEventListeners() {
            searchInput.addEventListener('input', applyFilters);
            genderFilter.addEventListener('change', applyFilters);
            gradeLevelFilter.addEventListener('change', applyFilters);
            classFilter.addEventListener('change', applyFilters);
            sectionFilter.addEventListener('change', applyFilters);
            attendanceRateFilter.addEventListener('change', applyFilters);
            sortSelect.addEventListener('change', applyFilters);
        }

        // Setup autocomplete
        function setupAutocomplete() {
            searchInput.addEventListener('input', () => {
                const term = searchInput.value.toLowerCase();
                if (term.length < 2) return;

                const suggestions = students.filter(s => 
                    s.fullName.toLowerCase().includes(term) || 
                    s.id.toString().includes(term)
                ).map(s => s.fullName).slice(0, 5);

                console.log('Suggestions:', suggestions);
            });
        }

        // Clear filters
        function clearFilters() {
            searchInput.value = '';
            genderFilter.value = '';
            gradeLevelFilter.value = '';
            classFilter.value = '';
            sectionFilter.value = '';
            attendanceRateFilter.value = '';
            sortSelect.value = 'name-asc';
            applyFilters();
        }

        // Render views
        function renderViews(data) {
            if (currentView === 'table') {
                renderTableView(data);
                tableView.classList.add('active');
                cardView.classList.remove('active');
            } else {
                renderCardView(data);
                tableView.classList.remove('active');
                cardView.classList.add('active');
            }
            renderPagination(data.length);
        }

        // Render table view
        function renderTableView(data) {
            studentTableBody.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = data.slice(start, end);

            paginatedData.forEach(student => {
                const attendanceClass = student.attendanceRate >= 90 ? 'high' : 
                                      student.attendanceRate >= 80 ? 'medium' : 'low';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="checkbox" class="row-checkbox" data-id="${student.id}"></td>
                    <td><img src="${student.photo}" alt="${student.fullName}" class="student-photo"></td>
                    <td>${student.id}</td>
                    <td>${student.fullName}</td>
                    <td>${student.emergencyContact}</td>
                    <td>${student.gradeLevel}</td>
                    <td>${student.class}</td>
                    <td>${student.section}</td>
                    <td class="attendance-rate ${attendanceClass}">${student.attendanceRate}%</td>
                    <td>
                        <div class="dropdown-menu">
                            <button class="action-btn">Actions <i class="fas fa-chevron-down"></i></button>
                            <div class="dropdown-content">
                                <button onclick="openProfileModal('view', '${student.id}')">View Profile</button>
                                <button onclick="openProfileModal('edit', '${student.id}')">Edit</button>
                                <button onclick="deleteStudent('${student.id}')">Remove</button>
                            </div>
                        </div>
                    </td>
                `;
                studentTableBody.appendChild(row);
            });

            updateBulkActions();
        }

        // Render card view
        function renderCardView(data) {
            cardView.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedData = data.slice(start, end);

            paginatedData.forEach(student => {
                const attendanceClass = student.attendanceRate >= 90 ? 'high' : 
                                      student.attendanceRate >= 80 ? 'medium' : 'low';
                
                const card = document.createElement('div');
                card.className = 'student-card';
                card.innerHTML = `
                    <div class="student-card-header">
                        <img src="${student.photo}" alt="${student.fullName}" class="student-photo">
                        <div>
                            <h3>${student.fullName}</h3>
                        </div>
                    </div>
                    <div class="student-card-info">
                        <p><i class="fas fa-id-card"></i> ${student.id}</p>
                        <p><i class="fas fa-envelope"></i> ${student.emergencyContact}</p>
                        <p><i class="fas fa-graduation-cap"></i> ${student.gradeLevel}</p>
                        <p><i class="fas fa-book"></i> ${student.class}</p>
                        <p><i class="fas fa-layer-group"></i> ${student.section}</p>
                        <p><i class="fas fa-percentage"></i> <span class="attendance-rate ${attendanceClass}">${student.attendanceRate}%</span></p>
                    </div>
                    <div class="student-card-actions">
                        <button class="action-btn view" onclick="openProfileModal('view', '${student.id}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="action-btn edit" onclick="openProfileModal('edit', '${student.id}')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="action-btn remove" onclick="deleteStudent('${student.id}')">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                `;
                cardView.appendChild(card);
            });
        }

        // Render pagination
        function renderPagination(totalRows) {
            const pageCount = Math.ceil(totalRows / rowsPerPage);
            pagination.innerHTML = `
                <button class="pagination-btn" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Previous</button>
                ${Array.from({ length: pageCount }, (_, i) => `
                    <button class="pagination-btn ${currentPage === i + 1 ? 'active' : ''}" onclick="changePage(${i + 1})">${i + 1}</button>
                `).join('')}
                <button class="pagination-btn" onclick="changePage(${currentPage + 1})" ${currentPage === pageCount ? 'disabled' : ''}>Next</button>
            `;
        }

        // Change page
        function changePage(page) {
            currentPage = page;
            applyFilters();
        }

        // Apply filters and sorting
        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const gender = genderFilter.value;
            const gradeLevel = gradeLevelFilter.value;
            const className = classFilter.value;
            const section = sectionFilter.value;
            const attendanceRange = attendanceRateFilter.value;

            let filteredStudents = students.filter(student => {
                const matchesSearch = student.fullName.toLowerCase().includes(searchTerm) || 
                                    student.id.toString().includes(searchTerm);
                const matchesGender = gender ? student.gender === gender : true;
                const matchesGradeLevel = gradeLevel ? student.gradeLevel === gradeLevel : true;
                const matchesClass = className ? student.class === className : true;
                const matchesSection = section ? student.section === section : true;
                const matchesAttendance = attendanceRange ? (
                    attendanceRange === '90-100' ? student.attendanceRate >= 90 :
                    attendanceRange === '80-90' ? student.attendanceRate >= 80 && student.attendanceRate < 90 :
                    student.attendanceRate < 80
                ) : true;

                return matchesSearch && matchesGender && matchesGradeLevel && matchesClass && matchesSection && matchesAttendance;
            });

            filteredStudents.sort((a, b) => {
                if (sortSelect.value === 'name-asc') return a.fullName.localeCompare(b.fullName);
                if (sortSelect.value === 'name-desc') return b.fullName.localeCompare(b.fullName);
                if (sortSelect.value === 'id') return a.id.toString().localeCompare(b.id.toString());
                if (sortSelect.value === 'attendance') return b.attendanceRate - a.attendanceRate;
                return 0;
            });

            renderViews(filteredStudents);
        }

        // Switch view
        function switchView(view) {
            currentView = view;
            document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');
            applyFilters();
        }

        // Toggle select all
        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
            updateBulkActions();
        }

        // Update bulk actions
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const selectedCount = document.getElementById('selectedCount');
            const bulkButtons = document.querySelectorAll('.bulk-btn');
            
            selectedCount.textContent = `${checkboxes.length} selected`;
            bulkButtons.forEach(btn => btn.disabled = checkboxes.length === 0);
        }

        // Bulk actions
        function bulkExport() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const selectedStudents = Array.from(checkboxes).map(cb => 
                students.find(s => s.id == cb.dataset.id)
            );
            
            const csv = [
                'Student ID,First Name,Last Name,Email,Gender,Grade Level,Subject,Section,Address,Emergency Contact,Attendance Rate',
                ...selectedStudents.map(s => 
                    `${s.id},${s.firstName},${s.lastName},${s.email},${s.gender},${s.gradeLevel},${s.class},${s.section},${s.address},${s.emergencyContact},${s.attendanceRate}`
                )
            ].join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'selected_students.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        function bulkDelete() {
            if (!confirm('Are you sure you want to delete selected students?')) return;
            
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.dataset.id);
            
            classes.forEach(cls => {
                cls.students = cls.students.filter(s => !ids.includes(s.id.toString()));
            });
            
            students = students.filter(s => !ids.includes(s.id.toString()));
            applyFilters();
        }

        // Toggle actions dropdown
        function toggleActions(btn) {
            const dropdown = btn.nextElementSibling;
            dropdown.classList.toggle('show');
        }

        // Open profile modal
        function openProfileModal(mode, id) {
            const form = {
                studentId: document.getElementById('student-id'),
                firstName: document.getElementById('first-name'),
                lastName: document.getElementById('last-name'),
                email: document.getElementById('email'),
                gender: document.getElementById('gender'),
                dob: document.getElementById('dob'),
                gradeLevel: document.getElementById('grade-level'),
                class: document.getElementById('class'),
                section: document.getElementById('section'),
                address: document.getElementById('address'),
                parentName: document.getElementById('parent-name'),
                emergencyContact: document.getElementById('emergency-contact'),
                photoPreview: document.getElementById('student-photo-preview')
            };

            Object.values(form).forEach(input => {
                if (input.tagName === 'IMG') input.src = 'https://via.placeholder.com/100';
                else if (input.tagName === 'SELECT') input.value = '';
                else input.value = '';
            });

            if (mode !== 'add' && id) {
                const student = students.find(s => s.id == id);
                document.getElementById('profile-modal-title').textContent = `${student.fullName}'s Profile`;
                form.studentId.value = student.id;
                form.firstName.value = student.firstName;
                form.lastName.value = student.lastName;
                form.email.value = student.email;
                form.gender.value = student.gender;
                form.dob.value = student.dob;
                form.gradeLevel.value = student.gradeLevel;
                form.class.value = student.class;
                form.section.value = student.section;
                form.address.value = student.address;
                form.parentName.value = student.parentName;
                form.emergencyContact.value = student.emergencyContact;
                form.photoPreview.src = student.photo;
            } else {
                document.getElementById('profile-modal-title').textContent = 'Add New Student';
            }

            profileModal.classList.add('show');
        }

        // Preview photo
        function previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('student-photo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Save student
        function saveStudent() {
            const form = {
                studentId: document.getElementById('student-id').value.trim(),
                firstName: document.getElementById('first-name').value.trim(),
                lastName: document.getElementById('last-name').value.trim(),
                email: document.getElementById('email').value.trim(),
                gender: document.getElementById('gender').value,
                dob: document.getElementById('dob').value,
                gradeLevel: document.getElementById('grade-level').value,
                class: document.getElementById('class').value,
                section: document.getElementById('section').value,
                address: document.getElementById('address').value.trim(),
                parentName: document.getElementById('parent-name').value.trim(),
                emergencyContact: document.getElementById('emergency-contact').value.trim(),
                photo: document.getElementById('student-photo-preview').src
            };

            if (!form.firstName || !form.lastName || !form.gender || !form.dob || !form.gradeLevel || 
                !form.class || !form.section || !form.address || !form.parentName || !form.emergencyContact) {
                alert('Please fill in all required fields.');
                return;
            }

            const newStudent = {
                id: form.studentId || Date.now(),
                firstName: form.firstName,
                lastName: form.lastName,
                email: form.email,
                fullName: `${form.firstName} ${form.lastName}`,
                gender: form.gender,
                dob: form.dob,
                gradeLevel: form.gradeLevel,
                class: form.class,
                section: form.section,
                address: form.address,
                parentName: form.parentName,
                emergencyContact: form.emergencyContact,
                attendanceRate: 90,
                dateAdded: new Date().toISOString().split('T')[0],
                photo: form.photo
            };

            const classItem = classes.find(c => c.subject === form.class && c.sectionName === form.section);
            if (classItem) {
                const studentIndex = classItem.students.findIndex(s => s.id == newStudent.id);
                if (studentIndex >= 0) {
                    classItem.students[studentIndex] = {
                        id: newStudent.id,
                        firstName: newStudent.firstName,
                        lastName: newStudent.lastName,
                        email: newStudent.email
                    };
                } else {
                    classItem.students.push({
                        id: newStudent.id,
                        firstName: newStudent.firstName,
                        lastName: newStudent.lastName,
                        email: newStudent.email
                    });
                }
            }

            if (students.some(s => s.id == newStudent.id)) {
                const index = students.findIndex(s => s.id == newStudent.id);
                students[index] = newStudent;
            } else {
                students.push(newStudent);
            }

            applyFilters();
            closeModal('profile');
        }

        // Delete student
        function deleteStudent(id) {
            if (!confirm('Are you sure you want to delete this student?')) return;
            
            classes.forEach(cls => {
                cls.students = cls.students.filter(s => s.id != id);
            });
            
            students = students.filter(s => s.id != id);
            applyFilters();
        }

        // Close modal
        function closeModal(type) {
            if (type === 'profile') {
                profileModal.classList.remove('show');
            }
        }

        // Close dropdowns
        document.addEventListener('click', e => {
            if (!e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-content').forEach(d => d.classList.remove('show'));
            }
        });
    </script>
</body>
</html>