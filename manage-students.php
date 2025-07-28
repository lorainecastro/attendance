<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #dbeafe;
            --success-green: #16a34a;
            --warning-yellow: #ca8a04;
            --danger-red: #dc2626;
            --info-cyan: #0891b2;
            --dark-gray: #374151;
            --medium-gray: #6b7280;
            --light-gray: #d1d5db;
            --background: #f9fafb;
            --white: #ffffff;
            --border-color: #e5e7eb;
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
            --font-family: 'Inter', sans-serif;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
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

        .controls {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 10px;
            align-items: center;
            border: 1px solid var(--border-color);
        }

        .controls-left {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .controls-right {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-container {
            position: relative;
            min-width: 250px;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px 8px 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .search-icon {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--grayfont-color);
        }

        .filter-select, .date-input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition-normal);
            min-width: 140px;
        }

        .filter-select:focus, .date-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .advanced-search {
            display: flex;
            grid-column: 1 / -1;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .action-btn, .add-btn, .bulk-btn, .clear-btn {
            border: none;
            background: var(--primary-color);
            color: var(--whitefont-color);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn:hover, .add-btn:hover, .bulk-btn:hover, .clear-btn:hover {
            background: var(--primary-hover);
        }

        .bulk-btn:disabled {
            background: var(--light-gray);
            cursor: not-allowed;
        }

        .view-toggle {
            display: flex;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .view-btn {
            padding: 8px;
            background: var(--inputfield-color);
            border: none;
            cursor: pointer;
            transition: var(--transition-normal);
            color: var(--grayfont-color);
        }

        .view-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .view-btn.active {
            background: var(--primary-gradient);
            color: var(--whitefont-color);
        }

        .student-list {
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

        .table-responsive {
            overflow-x: auto;
            display: none;
        }

        .table-responsive.active {
            display: block;
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

        .dropdown-menu {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: var(--card-bg);
            min-width: 120px;
            box-shadow: var(--shadow-lg);
            border-radius: 8px;
            z-index: 1000;
            border: 1px solid var(--border-color);
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-content button {
            width: 100%;
            text-align: left;
            padding: 8px 12px;
            border: none;
            background: none;
            font-size: 14px;
            color: var(--blackfont-color);
            cursor: pointer;
        }

        .dropdown-content button:hover {
            background: var(--inputfield-color);
        }

        .card-view {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .card-view.active {
            display: grid;
        }

        .student-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 15px;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
        }

        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .student-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .student-card-info {
            margin-bottom: 10px;
        }

        .student-card-info p {
            font-size: 14px;
            color: var(--grayfont-color);
            margin-bottom: 5px;
        }

        .student-card-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .bulk-actions {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 15px;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .selected-count {
            font-size: 14px;
            color: var(--grayfont-color);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .pagination-btn {
            padding: 8px 12px;
            border: none;
            background: var(--inputfield-color);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .pagination-btn.active {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .pagination-btn:hover:not(.active) {
            background: var(--inputfieldhover-color);
        }

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
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--grayfont-color);
        }

        .close-btn:hover {
            color: var(--primary-color);
        }

        .modal-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .tab-btn {
            padding: 8px 16px;
            border: none;
            background: var(--inputfield-color);
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .tab-btn.active {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .tab-btn:hover:not(.active) {
            background: var(--inputfieldhover-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: var(--blackfont-color);
        }

        .form-group input, .form-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--inputfield-color);
            transition: var(--transition-normal);
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 4px var(--primary-blue-light);
        }

        .photo-upload {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .photo-upload input[type="file"] {
            display: none;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .save-btn, .cancel-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-normal);
        }

        .save-btn {
            background: var(--primary-color);
            color: var(--whitefont-color);
        }

        .save-btn:hover {
            background: var(--primary-hover);
        }

        .cancel-btn {
            background: var(--inputfield-color);
            color: var(--blackfont-color);
        }

        .cancel-btn:hover {
            background: var(--inputfieldhover-color);
        }

        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .qr-code {
            width: 100px;
            height: 100px;
        }

        .print-btn {
            border: none;
            background: var(--primary-color);
            color: var(--whitefont-color);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .print-btn:hover {
            background: var(--primary-hover);
        }

        @media (max-width: 768px) {
            .controls {
                grid-template-columns: 1fr;
            }

            .controls-right {
                justify-content: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            th, td {
                padding: 10px;
            }

            .student-card {
                padding: 10px;
            }
        }

        @media (max-width: 576px) {
            .controls-left {
                flex-direction: column;
            }

            .controls-right {
                flex-direction: column;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .modal-content {
                width: 95%;
            }

            .student-card-actions {
                flex-direction: column;
            }

            .photo-upload {
                flex-direction: column;
                align-items: flex-start;
            }

            .qr-container {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <h1>Student Management</h1>

    <!-- Stats Grid -->
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
                    <div class="card-title">Active Students</div>
                    <div class="card-value" id="active-students">0</div>
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
                    <div class="card-title">Average Attendance</div>
                    <div class="card-value" id="average-attendance">0%</div>
                </div>
                <div class="card-icon bg-blue">
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
                    <div class="card-title">Classes Enrolled</div>
                    <div class="card-value" id="classes-enrolled">0</div>
                </div>
                <div class="card-icon bg-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                        <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="controls">
        <div class="controls-left">
            <div class="search-container">
                <input type="text" class="search-input" id="searchInput" placeholder="Search by Name or ID">
                <i class="fas fa-search search-icon"></i>
            </div>
            <div class="advanced-search" id="advancedSearch">
                <select class="filter-select" id="genderFilter">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select class="filter-select" id="gradeLevelFilter">
                    <option value="">All Grade Levels</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                </select>
                <select class="filter-select" id="classFilter">
                    <option value="">All Subject</option>
                    <!-- Populated dynamically -->
                </select>
                <select class="filter-select" id="sectionFilter">
                    <option value="">All Sections</option>
                    <!-- Populated dynamically -->
                </select>
                <select class="filter-select" id="attendanceRateFilter">
                    <option value="">All Attendance Rates</option>
                    <option value="90-100">90%+</option>
                    <option value="80-90">80-90%</option>
                    <option value="0-80">Below 80%</option>
                </select>
            </div>
            <select class="filter-select" id="sortSelect">
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="id">LRN</option>
                <option value="attendance">Attendance Rate</option>
            </select>
            <button class="clear-btn" onclick="clearFilters()">
                <i class="fas fa-times"></i> Clear Filters
            </button>
        </div>
        <div class="controls-right">
            <div class="view-toggle">
                <button class="view-btn active" onclick="switchView('table')">
                    <i class="fas fa-table"></i>
                </button>
                <button class="view-btn" onclick="switchView('card')">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
            <button class="add-btn" onclick="openProfileModal('add')">
                <i class="fas fa-plus"></i> Add Student
            </button>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions" id="bulkActions">
        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
        <label for="selectAll">Select All</label>
        <span class="selected-count" id="selectedCount">0 selected</span>
        <button class="bulk-btn" id="bulkExportBtn" disabled onclick="bulkExport()">
            <i class="fas fa-file-export"></i> Export Selected
        </button>
        <button class="bulk-btn" id="bulkDeleteBtn" disabled onclick="bulkDelete()">
            <i class="fas fa-trash"></i> Delete Selected
        </button>
    </div>

    <!-- Student List -->
    <div class="student-list">
        <div class="table-header">
            <div class="table-title">Student List</div>
        </div>
        <div id="tableView" class="table-responsive active">
            <table id="student-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="tableSelectAll" onchange="toggleSelectAll()"></th>
                        <th>Photo</th>
                        <th>LRN</th>
                        <th>Full Name</th>
                        <th>Contact</th>
                        <th>Grade Level</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Attendance Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="cardView" class="card-view"></div>
        <div class="pagination" id="pagination"></div>
    </div>

    <!-- Student Profile Modal -->
    <div class="modal" id="profile-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="profile-modal-title">Student Profile</div>
                <button class="close-btn" onclick="closeModal('profile')">×</button>
            </div>
            <div class="modal-body">
                <div class="tab-content active" id="personal-tab">
                    <div class="form-group">
                        <label>LRN</label>
                        <input type="text" id="student-id" readonly>
                    </div>
                    <div class="form-group photo-upload">
                        <div class="photo-container">
                            <label>Photo</label>
                            <img id="student-photo-preview" src="no-icon.png" alt="Student Photo" style="width: 100px; height: 100px; border-radius: 8px;">
                        </div>
                        <div id="qr-container" class="qr-container" style="display: none;">
                            <label>QR Code</label>
                            <div id="qr-code" class="qr-code"></div>
                            <button class="print-btn" onclick="printQRCode()"><i class="fas fa-print"></i> Print</button>
                        </div>
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
            updateStats();
            populateClassAndSectionFilters();
            applyFilters();
            setupAutocomplete();
            setupEventListeners();
        });

        // Update stats for cards
        function updateStats() {
            const totalStudents = students.length;
            const activeStudents = students.filter(s => classes.find(c => c.subject === s.class && c.sectionName === s.section)?.status === 'active').length;
            const averageAttendance = students.length ? (students.reduce((sum, s) => sum + s.attendanceRate, 0) / students.length).toFixed(1) : 0;
            const classesEnrolled = [...new Set(students.map(s => `${s.class}-${s.section}`))].length;

            document.getElementById('total-students').textContent = totalStudents;
            document.getElementById('active-students').textContent = activeStudents;
            document.getElementById('average-attendance').textContent = `${averageAttendance}%`;
            document.getElementById('classes-enrolled').textContent = classesEnrolled;
        }

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
                            <button class="action-btn" onclick="toggleActions(this)">Actions <i class="fas fa-chevron-down"></i></button>
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
                        <button class="action-btn" onclick="openProfileModal('view', '${student.id}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="action-btn" onclick="openProfileModal('edit', '${student.id}')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="action-btn" onclick="deleteStudent('${student.id}')">
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
                if (sortSelect.value === 'name-desc') return b.fullName.localeCompare(a.fullName);
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
            const selectAll = document.getElementById('selectAll');
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
                'LRN,First Name,Last Name,Email,Gender,Grade Level,Subject,Section,Address,Emergency Contact,Attendance Rate',
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

            // Reset form
            Object.values(form).forEach(input => {
                if (input.tagName === 'IMG') input.src = 'https://via.placeholder.com/100';
                else if (input.tagName === 'SELECT') input.value = '';
                else input.value = '';
            });

            // Clear and hide QR code container
            const qrContainer = document.getElementById('qr-container');
            const qrCodeDiv = document.getElementById('qr-code');
            qrCodeDiv.innerHTML = '';
            qrContainer.style.display = 'none';

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

                // Show QR code only in view mode
                if (mode === 'view') {
                    qrContainer.style.display = 'flex';
                    const qrData = JSON.stringify({
                        id: student.id,
                        fullName: student.fullName,
                        gradeLevel: student.gradeLevel,
                        class: student.class,
                        section: student.section
                    });
                    new QRCode(qrCodeDiv, {
                        text: qrData,
                        width: 100,
                        height: 100
                    });
                }
            } else {
                document.getElementById('profile-modal-title').textContent = 'Add New Student';
            }

            // Disable inputs for view mode
            Object.values(form).forEach(input => {
                if (input.tagName !== 'IMG') input.disabled = mode === 'view';
            });

            document.querySelector('.photo-upload .action-btn').style.display = mode === 'view' ? 'none' : 'inline-flex';
            document.querySelector('.modal-footer .save-btn').style.display = mode === 'view' ? 'none' : 'inline-flex';
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

            // Update class students
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

            // Update students array
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

        // Print QR code
        function printQRCode() {
            const qrCanvas = document.querySelector('#qr-code canvas');
            if (!qrCanvas) return;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print QR Code</title>
                    <style>
                        body { margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
                        img { max-width: 100%; }
                    </style>
                </head>
                <body>
                    <img src="${qrCanvas.toDataURL('image/png')}" alt="QR Code">
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
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